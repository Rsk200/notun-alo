<?php
/**
 * One-time seed script: imports local MariaDB dump into Aiven MySQL.
 * Access: https://notun-alo.onrender.com/seed_db.php?token=YOUR_TOKEN
 * DELETE THIS FILE AND notun_alo_export.sql AFTER SUCCESSFUL IMPORT.
 */
require_once __DIR__ . '/includes/config.php';

const SEED_TOKEN = 'notun_alo_seed_2026';

if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['token']) || $_GET['token'] !== SEED_TOKEN) {
        http_response_code(403);
        die('Forbidden: invalid or missing token.');
    }
}

header('Content-Type: text/plain; charset=utf-8');

$sqlFile = __DIR__ . '/notun_alo_export.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false || strlen($sql) === 0) {
    die("Failed to read SQL file or file is empty.\n");
}

// Normalize line endings
$sql = str_replace("\r\n", "\n", $sql);
$sql = str_replace("\r", "\n", $sql);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$statements = explode(";\n", $sql);

$total = 0;
$success = 0;
$skipped = 0;
$errors = [];

foreach ($statements as $i => $stmtBlock) {
    $stmt = trim($stmtBlock);
    if ($stmt === '') continue;

    $total++;

    // Remove commented lines within the block
    $lines = explode("\n", $stmt);
    $cleanLines = array_filter($lines, function ($line) {
        $t = trim($line);
        return $t !== '' && !str_starts_with($t, '--') && !str_starts_with($t, '/*');
    });
    if (empty($cleanLines)) {
        $skipped++;
        continue;
    }
    $stmt = implode("\n", $cleanLines);
    $stmt = trim($stmt);
    if ($stmt === '') {
        $skipped++;
        continue;
    }

    // Skip SET variable statements
    if (preg_match('/^SET\s+@/i', $stmt)) {
        $skipped++;
        continue;
    }

    // Convert INSERT INTO -> INSERT IGNORE INTO for idempotency
    if (preg_match('/^INSERT\s+INTO/i', $stmt)) {
        $stmt = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $stmt);
    }

    try {
        $pdo->exec($stmt);
        $success++;
        echo "OK [$total] ";
        $firstLine = strtok($stmt, "\n");
        echo substr($firstLine, 0, 100) . "\n";
    } catch (Exception $e) {
        $errors[] = "[$total] " . $e->getMessage() . " — " . substr($stmt, 0, 80);
        echo "FAIL [$total] " . $e->getMessage() . "\n";
    }
}

echo "\n========================================\n";
echo "Total statements : $total\n";
echo "Executed        : $success\n";
echo "Skipped         : $skipped\n";
echo "Errors          : " . count($errors) . "\n";
if (!empty($errors)) {
    echo "\n--- First 10 Errors ---\n";
    foreach (array_slice($errors, 0, 10) as $err) {
        echo "  $err\n";
    }
}
echo "========================================\n";
