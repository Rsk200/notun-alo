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

$fh = fopen($sqlFile, 'rb');
if (!$fh) {
    die("Failed to open SQL file.\n");
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$currentStmt = '';
$total = 0;
$success = 0;
$skipped = 0;
$errors = [];

// Helper: execute a single statement
function execStmt(PDO $pdo, string $stmt, int &$total, int &$success, int &$skipped, array &$errors): void {
    $stmt = trim($stmt);
    if ($stmt === '') return;

    $total++;
    $upper = strtoupper(substr($stmt, 0, 20));

    // Skip SET variable statements, comments, and delimiter directives
    if (preg_match('/^SET\s+@/i', $stmt) || $stmt === 'DELIMITER ;;') {
        $skipped++;
        return;
    }

    // Convert INSERT INTO -> INSERT IGNORE INTO for idempotency
    if (preg_match('/^INSERT\s+INTO/i', $stmt)) {
        $stmt = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $stmt);
    }

    // Skip DROP TABLE IF EXISTS for safety but keep CREATE TABLE
    if (preg_match('/^DROP\s+(TABLE|VIEW)\s+IF\s+EXISTS/i', $stmt)) {
        echo "DROP: " . substr($stmt, 0, 90) . "\n";
        $skipped++;
        return;
    }

    try {
        $pdo->exec($stmt);
        $success++;
        $firstLine = strtok($stmt, "\n");
        echo "OK $total: " . substr($firstLine, 0, 100) . "\n";
    } catch (Exception $e) {
        $errors[] = "[$total] " . $e->getMessage() . " — " . substr($stmt, 0, 80);
        echo "FAIL $total: " . $e->getMessage() . "\n";
    }
}

while (($line = fgets($fh)) !== false) {
    $trimmed = trim($line);

    // Skip comment lines
    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
        continue;
    }

    $currentStmt .= $line;

    // Check if statement ends with semicolon (possibly followed by whitespace)
    if (str_ends_with(trim($currentStmt), ';')) {
        execStmt($pdo, $currentStmt, $total, $success, $skipped, $errors);
        $currentStmt = '';
    }
}

// Handle any remaining statement
if (trim($currentStmt) !== '') {
    execStmt($pdo, $currentStmt, $total, $success, $skipped, $errors);
}

fclose($fh);

echo "\n========================================\n";
echo "Total statements : $total\n";
echo "Executed        : $success\n";
echo "Skipped         : $skipped\n";
echo "Errors          : " . count($errors) . "\n";
if (!empty($errors)) {
    echo "\n--- Errors ---\n";
    foreach ($errors as $err) {
        echo "  $err\n";
    }
}
echo "========================================\n";
