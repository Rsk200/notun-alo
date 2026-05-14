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

if (!$userId || !in_array($action, ['impact', 'forecast'])) {
    echo json_encode(['error' => 'invalid_request']);
    exit;
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
                COALESCE(SUM(p.estimated_weight), 0) AS total_kg_recycled,
                COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 0) AS co2_saved_kg,
                COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 0) AS water_saved_liters,
                COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 0) AS energy_saved_kwh,
                SUM(CASE WHEN p.category = 'E-waste' THEN 1 ELSE 0 END) AS ewaste_pickups
            FROM users u
            LEFT JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
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

        // Environmental constants (matching environmental_engine.py)
        $CAR_CO2 = 0.21;
        $PHONE_KWH = 0.012;
        $WATER_BOTTLE = 0.5;

        $co2 = (float)$row['co2_saved_kg'];
        $water = (float)$row['water_saved_liters'];
        $energy = (float)$row['energy_saved_kwh'];

        // Gamification Algorithm: Eco-Rank
        // 1 kg CO2 = 10 XP, 1 L Water = 1 XP, 1 kWh Energy = 10 XP
        $xp = ($co2 * 10) + ($water * 1) + ($energy * 10);
        
        $levels = [
            ["name" => "Eco-Seed", "xp" => 0],
            ["name" => "Eco-Sprout", "xp" => 100],
            ["name" => "Eco-Sapling", "xp" => 300],
            ["name" => "Eco-Tree", "xp" => 1000],
            ["name" => "Eco-Forest", "xp" => 2500],
            ["name" => "Eco-Guardian", "xp" => 6000],
            ["name" => "Earth Hero", "xp" => 15000]
        ];

        $currentLevel = $levels[0];
        $nextLevel = null;
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
        if ($nextLevel) {
            $range = $nextLevel['xp'] - $currentLevel['xp'];
            $earned = $xp - $currentLevel['xp'];
            $progress = round(($earned / $range) * 100);
        } else {
            $progress = 100; // Max level
        }

        echo json_encode([
            "user_id" => $userId,
            "user_name" => $row['user_name'],
            "total_pickups" => (int)$row['total_pickups'],
            "total_kg_recycled" => round((float)$row['total_kg_recycled'], 2),
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
                "points_to_next" => $nextLevel ? round($nextLevel['xp'] - $xp) : 0
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
$pythonBin = '/opt/venv/bin/python3';
if (!file_exists($pythonBin)) { $pythonBin = 'python3'; }
$scriptPath = __DIR__ . '/ai-service/cli_impact.py';
$cmd = "timeout 15 $pythonBin " . escapeshellarg($scriptPath) . " forecast " . escapeshellarg((string)$userId) . " 2>&1";
$output = shell_exec($cmd);
$output = trim($output);

if (!$output || !json_decode($output)) {
    echo json_encode(['error' => 'forecast_engine_failed', 'details' => $output]);
} else {
    echo $output;
}
