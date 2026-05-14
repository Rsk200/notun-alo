<?php
// ============================================
// cron/reassign_pending.php
// Assigns pickups that are pending longer than 10 mins
// Crontab: */10 * * * * php /var/www/notun_alo/cron/reassign_pending.php
// ============================================
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auto_assign_v2.php';

$logFile = dirname(__DIR__) . '/logs/assignment_cron.log';
function logCron($msg) {
    global $logFile;
    @file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
    echo $msg . "\n";
}

logCron("Starting pending reassignment sweep.");

// Find pickups WHERE status='pending' AND created_at < NOW() - INTERVAL 10 MINUTE
$stmt = $pdo->query("
    SELECT id FROM pickups 
    WHERE status = 'pending' 
    AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
");
$pending = $stmt->fetchAll();

if (!$pending) {
    logCron("No stale pending pickups found.");
    exit;
}

foreach ($pending as $p) {
    $pid = $p['id'];
    logCron("Attempting auto-assign for Pickup #$pid");
    
    $res = autoAssignPickupAI($pid, $pdo);
    
    if ($res['success']) {
        logCron("Success: Assigned #$pid to Agency ID {$res['agency_id']} (Score: {$res['score']})");
    } else {
        logCron("Failed: Could not assign #$pid - " . ($res['reason'] ?? 'Unknown Error'));
    }
}

logCron("Sweep complete.");
?>
