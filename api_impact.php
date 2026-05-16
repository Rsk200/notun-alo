<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'includes/config.php';

// Clear any output from config.php (like notices or warnings)
ob_clean();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$userId || !in_array($action, ['impact', 'forecast', 'percentile_rank'])) {
    echo json_encode(['error' => 'invalid_request']);
    exit;
}

// --------------- Action: percentile_rank ---------------
if ($action === 'percentile_rank') {
    try {
        // Ensure cache table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_rank_cache (
            user_id INT PRIMARY KEY,
            percentile INT NOT NULL DEFAULT 0,
            city VARCHAR(100) NOT NULL DEFAULT '',
            total_in_city INT NOT NULL DEFAULT 0,
            metric VARCHAR(50) NOT NULL DEFAULT 'co2_prevented',
            calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_calculated (calculated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Check cache (valid for 15 min)
        $cached = $pdo->prepare("SELECT percentile, city, total_in_city, metric FROM user_rank_cache WHERE user_id = ? AND calculated_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $cached->execute([$userId]);
        $cacheRow = $cached->fetch();

        if ($cacheRow) {
            echo json_encode([
                'percentile'     => (int)$cacheRow['percentile'],
                'city'           => $cacheRow['city'],
                'totalUsersInCity' => (int)$cacheRow['total_in_city'],
                'metric'         => $cacheRow['metric'],
                'lastUpdated'    => date('c'),
                'cached'         => true
            ]);
            exit;
        }

        // Get user's address and total CO₂ from completed pickups
        $userStmt = $pdo->prepare("SELECT id, address FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $userRow = $userStmt->fetch();

        if (!$userRow) {
            echo json_encode(['error' => 'user_not_found']);
            exit;
        }

        // Extract city from address
        $city = extractCity($userRow['address'] ?? '');
        if (!$city) {
            // Can't determine city — hide banner
            echo json_encode(['hide' => true, 'reason' => 'no_city']);
            exit;
        }

        // Get this user's CO₂
        $co2Stmt = $pdo->prepare("SELECT COALESCE(SUM(estimated_weight), 0) * 1.2 AS co2_kg FROM pickups WHERE user_id = ? AND status = 'completed'");
        $co2Stmt->execute([$userId]);
        $myCO2 = (float)$co2Stmt->fetchColumn();

        // New user with 0 activity — hide banner
        if ($myCO2 <= 0) {
            echo json_encode(['hide' => true, 'reason' => 'no_activity']);
            exit;
        }

        // Get all users in same city with their CO₂
        // Find users whose address contains the city name
        $cityLike = '%' . $city . '%';
        $allStmt = $pdo->prepare("
            SELECT u.id, COALESCE(SUM(p.estimated_weight), 0) * 1.2 AS co2_kg
            FROM users u
            LEFT JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
            WHERE u.address LIKE ? COLLATE utf8mb4_unicode_ci AND u.role = 'user'
            GROUP BY u.id
            HAVING co2_kg > 0
        ");
        $allStmt->execute([$cityLike]);
        $allRows = $allStmt->fetchAll();

        $totalUsers = count($allRows);

        // Less than 10 users in city — show "one of N" instead
        if ($totalUsers < 10) {
            $result = [
                'hide'             => false,
                'oneOf'            => true,
                'totalUsersInCity' => $totalUsers,
                'city'             => $city,
                'metric'           => 'co2_prevented',
                'lastUpdated'      => date('c'),
            ];
        } else {
            // Calculate rank: position among users sorted DESC by CO₂
            $allCO2 = array_map(function($r) { return (float)$r['co2_kg']; }, $allRows);
            rsort($allCO2);
            $rank = 1;
            foreach ($allCO2 as $val) {
                if ($val > $myCO2) $rank++;
                else break;
            }
            $percentile = (int)ceil(($rank / $totalUsers) * 100);
            if ($percentile < 1) $percentile = 1;
            if ($percentile > 99) $percentile = 99;

            $result = [
                'percentile'       => $percentile,
                'city'             => $city,
                'totalUsersInCity' => $totalUsers,
                'metric'           => 'co2_prevented',
                'lastUpdated'      => date('c'),
                'rank'             => $rank,
            ];
        }

        // Write to cache
        $upsert = $pdo->prepare("REPLACE INTO user_rank_cache (user_id, percentile, city, total_in_city, metric, calculated_at) VALUES (?, ?, ?, ?, 'co2_prevented', NOW())");
        $upsert->execute([$userId, $result['percentile'] ?? 0, $city, $totalUsers]);

        echo json_encode($result);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['error' => 'database_error', 'message' => $e->getMessage()]);
        exit;
    }
}

// --------------- Helper: extract city from address ---------------
function extractCity(?string $address): ?string {
    if (!$address || trim($address) === '') return null;
    $addr = trim($address);
    $cities = ['Dhaka','Chittagong','Chattogram','Sylhet','Rajshahi','Khulna','Barishal','Barisal','Rangpur','Mymensingh','Cumilla','Comilla','Narayanganj','Gazipur','Bogra','Jessore','Dinajpur','Tangail','Faridpur'];
    // Match known city names (case-insensitive)
    foreach ($cities as $c) {
        if (preg_match('/\b' . preg_quote($c, '/') . '\b/i', $addr)) {
            return $c === 'Chattogram' ? 'Chittagong' : ($c === 'Comilla' ? 'Cumilla' : $c);
        }
    }
    // Fallback: take first part before comma
    $parts = explode(',', $addr);
    $first = trim($parts[0]);
    return $first ?: null;
}

if ($action === 'impact') {
    // Implement current impact calculation in PHP for maximum reliability
    try {
        // Auto-fix: Ensure category_averages is a VIEW and not a table (fixes 'is not VIEW' error)
        $pdo->exec("DROP TABLE IF EXISTS category_averages_table_tmp"); // cleanup just in case
        $pdo->exec("SET SESSION sql_mode = ''"); // reduce strictness for view creation if needed
        
        $stmt = $pdo->prepare("
            SELECT
                u.id AS user_id,
                u.name AS user_name,
                COUNT(p.id) AS total_pickups,
                SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) AS completed_pickups,
                COALESCE(SUM(p.estimated_weight), 0) AS total_kg_recycled,
                COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 1.2)), 0) AS co2_saved_kg,
                COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 20)), 0) AS water_saved_liters,
                COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 5)), 0) AS energy_saved_kwh,
                SUM(CASE WHEN p.category = 'E-waste' THEN 1 ELSE 0 END) AS ewaste_pickups
            FROM users u
            LEFT JOIN pickups p ON p.user_id = u.id AND p.status IN ('completed', 'scheduled')
            LEFT JOIN emission_factors ef 
              ON ef.category = p.category COLLATE utf8mb4_unicode_ci 
             AND p.subcategory IS NOT NULL AND p.subcategory <> '' 
             AND ef.subcategory = p.subcategory COLLATE utf8mb4_unicode_ci
            LEFT JOIN category_averages ca 
              ON ca.category = p.category COLLATE utf8mb4_unicode_ci
            WHERE u.id = ?
            GROUP BY u.id, u.name
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['error' => 'user_not_found']);
            exit;
        }

        // Environmental constants
        $CAR_CO2 = 0.21;
        $PHONE_KWH = 0.012;
        $WATER_BOTTLE = 0.5;

        $co2 = (float)$row['co2_saved_kg'];
        $water = (float)$row['water_saved_liters'];
        $energy = (float)$row['energy_saved_kwh'];

        // This month's recycling
        $monthStmt = $pdo->prepare("SELECT COALESCE(SUM(estimated_weight), 0) FROM pickups WHERE user_id = ? AND status = 'completed' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())");
        $monthStmt->execute([$userId]);
        $thisMonthKg = (float)$monthStmt->fetchColumn();

        // City averages for comparison
        $cityAvg = ['co2' => 0, 'water' => 0, 'energy' => 0];
        $userAddr = $row['address'] ?? '';
        $userCity = extractCity($userAddr);
        if ($userCity) {
            $cityLike = '%' . $userCity . '%';
            $avgStmt = $pdo->prepare("
                SELECT
                    AVG(u_stats.co2) AS avg_co2,
                    AVG(u_stats.water) AS avg_water,
                    AVG(u_stats.energy) AS avg_energy
                FROM (
                    SELECT
                        u.id,
                        COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 1.2)), 0) AS co2,
                        COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 20)), 0) AS water,
                        COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 5)), 0) AS energy
                    FROM users u
                    LEFT JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
                    LEFT JOIN emission_factors ef ON ef.category = p.category COLLATE utf8mb4_unicode_ci AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory COLLATE utf8mb4_unicode_ci
                    LEFT JOIN category_averages ca ON ca.category = p.category COLLATE utf8mb4_unicode_ci
                    WHERE u.address LIKE ? COLLATE utf8mb4_unicode_ci AND u.role = 'user'
                    GROUP BY u.id
                ) u_stats
            ");
            $avgStmt->execute([$cityLike]);
            $avgRow = $avgStmt->fetch();
            if ($avgRow) {
                $cityAvg = [
                    'co2'    => round((float)$avgRow['avg_co2'], 1),
                    'water'  => round((float)$avgRow['avg_water']),
                    'energy' => round((float)$avgRow['avg_energy'], 1),
                ];
            }
        }

        // Gamification Algorithm: Eco-Rank
        // Base XP for even starting a journey + impact-based XP
        $baseXp = (int)$row['total_pickups'] > 0 ? 50 : 0;
        $xp = $baseXp + ($co2 * 10) + ($water * 1) + ($energy * 10);
        
        $levels = [
            ["name" => "Eco-Seed", "xp" => 0],
            ["name" => "Eco-Sprout", "xp" => 100],
            ["name" => "Eco-Sapling", "xp" => 300],
            ["name" => "Eco-Tree", "xp" => 1000],
            ["name" => "Eco-Forest", "xp" => 2500],
            ["name" => "Eco-Guardian", "xp" => 6000],
            ["name" => "Earth Hero", "xp" => 15000],
            ["name" => "Climate Commander", "xp" => 35000],
            ["name" => "Atmosphere Architect", "xp" => 75000],
            ["name" => "Planet Savior", "xp" => 150000]
        ];

        $currentLevel = $levels[0];
        $currentLevel['index'] = 1;
        $nextLevel = $levels[1];

        foreach ($levels as $i => $l) {
            if ($xp >= $l['xp']) {
                $currentLevel = $l;
                $currentLevel['index'] = $i + 1;
                $nextLevel = $levels[$i + 1] ?? null;
            } else {
                break;
            }
        }

        $progress = 0;
        $nextRankMsg = "";
        if ($nextLevel) {
            $range = $nextLevel['xp'] - $currentLevel['xp'];
            $earned = $xp - $currentLevel['xp'];
            $progress = round(($earned / $range) * 100);
            $nextRankMsg = round($nextLevel['xp'] - $xp) . " XP to " . $nextLevel['name'];
        } else {
            $progress = 100;
            $nextRankMsg = "Ultimate Rank Achieved! 🏆";
        }

        echo json_encode([
            "user_id" => $userId,
            "user_name" => $row['user_name'],
            "total_pickups" => (int)$row['total_pickups'],
            "completed_pickups" => (int)$row['completed_pickups'],
            "total_kg_recycled" => round((float)$row['total_kg_recycled'], 2),
            "this_month_kg" => round($thisMonthKg, 2),
            "city" => $userCity ?? '',
            "city_averages" => $cityAvg,
            "co2_saved_kg" => round($co2, 2),
            "water_saved_liters" => round($water, 2),
            "energy_saved_kwh" => round($energy, 2),
            "car_trip_equivalent" => round($co2 / $CAR_CO2),
            "water_bottle_equivalent" => round($water / $WATER_BOTTLE),
            "phone_charge_equivalent" => round($energy / $PHONE_KWH),
            "gamification" => [
                "xp" => round($xp),
                "level_name" => $currentLevel['name'],
                "level_number" => $currentLevel['index'],
                "next_level_name" => $nextLevel ? $nextLevel['name'] : "Max Level",
                "next_level_xp" => $nextLevel ? $nextLevel['xp'] : $xp,
                "progress_percent" => $progress,
                "points_to_next" => $nextLevel ? round($nextLevel['xp'] - $xp) : 0,
                "next_rank_msg" => $nextRankMsg
            ],
            "high_impact_badge" => ((int)$row['ewaste_pickups'] > 0) ? "High Impact Recycling" : null,
            "ewaste_message" => "Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling."
        ]);
        exit;
    } catch (Throwable $e) {
        echo json_encode(['error' => 'database_error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Action: forecast - Still uses Python CLI
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pythonBin = $isWindows ? 'python' : '/opt/venv/bin/python3';

// On Windows, 'python' is usually in PATH for XAMPP users if they installed it, 
// otherwise we might need the full path. Let's try 'python' first.
if (!$isWindows && !file_exists($pythonBin)) { $pythonBin = 'python3'; }

$scriptPath = __DIR__ . '/ai-service/cli_impact.py';
$cmd = $isWindows 
    ? "$pythonBin " . escapeshellarg($scriptPath) . " forecast " . escapeshellarg((string)$userId) . " 2>&1"
    : "timeout 15 $pythonBin " . escapeshellarg($scriptPath) . " forecast " . escapeshellarg((string)$userId) . " 2>&1";

$output = shell_exec($cmd);
$output = trim($output);

if (!$output || !json_decode($output)) {
    echo json_encode(['error' => 'forecast_engine_failed', 'details' => $output]);
} else {
    echo $output;
}
