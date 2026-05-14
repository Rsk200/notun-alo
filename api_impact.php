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

// Use full path to venv Python (Apache may not inherit the venv PATH)
$pythonBin = '/opt/venv/bin/python3';
if (!file_exists($pythonBin)) {
    $pythonBin = 'python3';
}

$scriptPath = __DIR__ . '/ai-service/cli_impact.py';
$cmd = escapeshellcmd("timeout 10 " . $pythonBin . " " . escapeshellarg($scriptPath) . " " . escapeshellarg($action) . " " . escapeshellarg((string)$userId));

$output = shell_exec($cmd . " 2>&1");
$output = trim($output);

if (!$output || !json_decode($output)) {
    error_log('[Notun Alo Impact] Python CLI failed. Action=' . $action . ' User=' . $userId . ' Output=' . substr($output, 0, 500));
    echo json_encode(['error' => 'python script failed', 'details' => $output]);
    exit;
}

echo $output;
