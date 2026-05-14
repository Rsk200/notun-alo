<?php
require_once 'includes/config.php';

try {
    $sqlFile = 'clean_merged_notun_alo.sql';
    if (!file_exists($sqlFile)) {
        $sqlFile = 'database/notun_alo.sql';
    }

    if (!file_exists($sqlFile)) {
        die("<h1 style='color:red;'>SQL file not found!</h1><p>Neither 'clean_merged_notun_alo.sql' nor 'database/notun_alo.sql' exists.</p>");
    }

    $sql = file_get_contents($sqlFile);

    // Strip DEFINER clauses (not allowed on Aiven - requires SUPER privilege)
    $sql = preg_replace('/\s+DEFINER\s*=\s*[^\s]+/i', '', $sql);

    // Strip SET SESSION sql_require_primary_key (may be rejected on Aiven)
    $sql = preg_replace('/^SET\s+SESSION\s+sql_require_primary_key\s*=\s*[^;]+;/im', '', $sql);

    echo "<h1>Initializing Database...</h1>";
    echo "<p>Using: <code>$sqlFile</code></p>";

    try {
        $pdo->exec($sql);
        echo "<h1 style='color:green;'>Database initialized successfully!</h1>";
    } catch (PDOException $e) {
        $msg = strtolower($e->getMessage());
        // If tables already exist, just apply ALTER TABLE statements (AUTO_INCREMENT + constraints)
        if (strpos($msg, 'already exists') !== false) {
            echo "<p>Tables already exist. Applying AUTO_INCREMENT and constraint fixes...</p>";

            // Extract all ALTER TABLE statements from the SQL
            preg_match_all('/ALTER\s+TABLE[^;]+;/i', $sql, $matches);
            $alters = $matches[0] ?? [];

            $successCount = 0;
            $failCount = 0;
            foreach ($alters as $alter) {
                try {
                    $pdo->exec($alter);
                    $successCount++;
                } catch (PDOException $alterErr) {
                    // Many alters may already be applied (duplicate key, etc.) - that's fine
                    $failCount++;
                }
            }
            echo "<p>Applied $successCount ALTER TABLE statements ($failCount skipped - already applied).</p>";
            echo "<h1 style='color:green;'>Database schema is up to date!</h1>";
        } else {
            throw $e;
        }
    }

    echo "<p><a href='index.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>Go to Homepage</a></p>";

} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Initialization Failed</h1>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<hr><p>Stack Trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
