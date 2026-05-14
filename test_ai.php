<?php
/**
 * Admin-only smoke test for autoAssignPickupAI (Flask on :5005 + SQL fallback).
 * Usage: test_ai.php?pickup_id=8
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
requireRole('admin');

require_once __DIR__ . '/includes/auto_assign_v2.php';

$pickupId = isset($_GET['pickup_id']) ? (int) $_GET['pickup_id'] : 0;
if ($pickupId < 1) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Usage: test_ai.php?pickup_id=<positive integer>\n";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
$result = autoAssignPickupAI($pickupId, $pdo);
print_r($result);
