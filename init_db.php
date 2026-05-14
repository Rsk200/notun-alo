<?php
require_once 'includes/config.php';

try {
    $sqlFile = 'database/notun_alo.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found at $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    // We use exec() for the whole block or split it by semicolon
    // Since some SQL files have multiple statements, we use PDO::exec() carefully or split
    $pdo->exec($sql);

    echo "<h1>Database initialized successfully!</h1>";
    echo "<p>All tables have been created on Aiven.</p>";
    echo "<a href='index.php'>Go to Homepage</a>";

} catch (PDOException $e) {
    echo "<h1>Initialization Failed</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
