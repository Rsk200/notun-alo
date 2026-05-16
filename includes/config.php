<?php
ob_start();
// ============================================
// config.php - Database Connection
// Notun Alo (New Light) Recycling Platform
// ============================================
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
loadEnv(__DIR__ . '/../.env');

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'notun_alo');
define('SITE_NAME', 'Notun Alo');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/notun_alo/');

// Points per KG by category
define('POINTS_PAPER',   5);   // 5 points per KG
define('POINTS_PLASTIC', 8);   // 8 points per KG
define('POINTS_METAL',  12);   // 12 points per KG

// SSL for Aiven / remote MySQL
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
if (strtolower((string)getenv('DB_SSL') ?: '') === 'true') {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    $caPath = getenv('DB_SSL_CA');
    if ($caPath) {
        $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    }
}

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
} catch (PDOException $e) {
    $msg = $e->getMessage();
    // Give a helpful hint about env vars
    if (str_contains($msg, 'getaddrinfo') || str_contains($msg, 'Name or service not known')) {
        $msg .= ' — Check that DB_HOST is set correctly in your Render dashboard env vars.';
    }
    die(json_encode(['error' => 'Database connection failed: ' . $msg]));
}

/**
 * If pickups.category is still a legacy ENUM(Paper,Plastic,Metal), widen it so
 * the request UI (Glass, E-waste, Organic, etc.) does not fail on INSERT.
 */
function ensurePickupCategoryVarchar(PDO $pdo): void {
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;
    try {
        $stmt = $pdo->query(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pickups' AND COLUMN_NAME = 'category'"
        );
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $colType = strtolower((string)($row['COLUMN_TYPE'] ?? ''));
        if ($colType !== '' && str_starts_with($colType, 'enum(')) {
            $pdo->exec('ALTER TABLE pickups MODIFY category VARCHAR(50) NOT NULL');
        }
    } catch (Throwable $e) {
        error_log('[Notun Alo] ensurePickupCategoryVarchar: ' . $e->getMessage());
    }
}

ensurePickupCategoryVarchar($pdo);

/**
 * Check if the database has been initialized
 */
function isDatabaseInitialized(PDO $pdo): bool {
    try {
        // Try to query a core table to see if it exists
        $pdo->query("SELECT 1 FROM products LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

// -----------------------------------------------
// Helper Functions
// -----------------------------------------------

/**
 * Start session safely
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Redirect to a given URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

/**
 * Require login - redirect if not
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * For JSON API endpoints: respond with 401 JSON instead of an HTML redirect.
 */
function requireLoginJson(): void {
    startSession();
    if (isset($_SESSION['user_id'])) {
        return;
    }
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['reply' => 'Please log in again.', 'action' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Require specific role
 */
function requireRole(string $role): void {
    requireLogin();
    startSession();
    if ($_SESSION['role'] !== $role) {
        redirect('dashboard.php');
    }
}

/**
 * Get current logged-in user data
 */
function getCurrentUser(PDO $pdo): ?array {
    startSession();
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user reward points
 */
function getUserPoints(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT total_points FROM rewards WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['total_points'] : 0;
}

/**
 * Sanitize output for HTML
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Flash message helper
 */
function setFlash(string $type, string $message): void {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Load Localization Dictionary
require_once __DIR__ . '/lang.php';
