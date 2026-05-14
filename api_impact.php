<?php
// api_impact.php
// A PHP wrapper to retrieve environmental impact via Python CLI
// This avoids the need for a running Flask API on port 5003.

require_once 'includes/config.php';
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
            LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
            LEFT JOIN category_averages ca ON ca.category = p.category
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
