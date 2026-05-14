<?php
// api_impact.php
// A PHP wrapper to retrieve environmental impact via Python CLI
// This avoids the need for a running Flask API on port 5003.

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$userId || !in_array($action, ['impact', 'forecast'])) {
    echo json_encode(['error' => 'invalid request']);
    exit;
}

$scriptPath = __DIR__ . '/ai-service/cli_impact.py';
$cmd = escapeshellcmd("python " . escapeshellarg($scriptPath) . " " . escapeshellarg($action) . " " . escapeshellarg((string)$userId));

$output = shell_exec($cmd . " 2>&1");
$output = trim($output);

if (!$output || !json_decode($output)) {
    echo json_encode(['error' => 'python script failed', 'details' => $output]);
    exit;
}

echo $output;
