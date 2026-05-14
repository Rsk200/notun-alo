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
$actionArg = escapeshellarg($action);
$userArg   = escapeshellarg((string)$userId);
$pathArg   = escapeshellarg($scriptPath);

// Simplify command construction. timeout is usually available on Linux.
$cmd = "timeout 15 $pythonBin $pathArg $actionArg $userArg 2>&1";

$output = shell_exec($cmd);
$output = trim($output);

if (!$output || !json_decode($output)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'python_cli_failed',
        'details' => $output ?: 'Empty output from script',
        'command' => $cmd
    ]);
    exit;
}

echo $output;
