<?php
// ============================================
// admin/retrain_trigger.php
// Triggers the Python ML script via AJAX
// ============================================
require_once '../includes/config.php';
requireRole('admin');

header('Content-Type: application/json');

try {
    // We launch it in background (Windows specific syntax for background proc, 
    // or standard fallback. For this environment we'll use a standard shell_exec 
    // wrapped or just let it run synchronously if fast enough. 
    // Training on small dataset is fast, so synchronous is okay for demo.
    
    $pythonPath = "python"; // Assume python is in PATH
    $scriptPath = realpath(__DIR__ . "/../ai-service/train_predictor.py");
    
    // Check if script exists
    if (!file_exists($scriptPath)) {
        echo json_encode(["success" => false, "message" => "Script not found at $scriptPath"]);
        exit;
    }

    $cmd = escapeshellcmd("$pythonPath $scriptPath");
    $output = shell_exec($cmd . " 2>&1");
    
    echo json_encode([
        "success" => true, 
        "message" => "Retraining completed/started successfully. Check logs for details.",
        "output" => $output
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
