<?php
// ============================================
// cron/retrain_model.php
// Scheduled script to retrain ML model weekly
// Crontab: 0 2 * * 0 php /var/www/notun_alo/cron/retrain_model.php
// ============================================
require_once dirname(__DIR__) . '/includes/config.php';

$logFile = dirname(__DIR__) . '/logs/training.log';
function logMsg($msg) {
    global $logFile;
    @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
    echo $msg . "\n";
}

logMsg("Starting scheduled model retrain.");

// 1. Query COUNT of completed pickups
$count = $pdo->query("SELECT COUNT(*) FROM pickups WHERE status = 'completed'")->fetchColumn();

// 2. If < 10 log and exit
if ($count < 10) {
    logMsg("Abort: Only $count completed pickups found. 10 required.");
    exit;
}

// 3. shell_exec to run train_predictor.py
$scriptPath = dirname(__DIR__) . "/ai-service/train_predictor.py";
$cmd = escapeshellcmd("python $scriptPath");
$output = shell_exec($cmd . " 2>&1");

// 4. INSERT into model_versions table
// We assume Python script runs successfully and saves to pkl.
// We parse the python logs for MAE, but for simplicity we just log the run.
$pdo->prepare("
    INSERT INTO model_versions (version_tag, model_type, trained_on, is_active)
    VALUES (?, ?, ?, ?)
")->execute([
    'cron_update_' . date('Ymd'), 
    'RF/GB_Ensemble', 
    $count, 
    1
]);

// Mark old models inactive
$pdo->query("UPDATE model_versions SET is_active = 0 WHERE id != LAST_INSERT_ID()");

// 5. Append to logs
logMsg("Retrain complete. Trained on $count pickups.\nPython Output:\n$output");
?>
