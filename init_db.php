<?php
require_once 'includes/config.php';

// Prevent accidental re-initialization if needed, or just let it run.
// For now, we allow it to run to fix the user's issue.

try {
    $sqlFile = 'clean_merged_notun_alo.sql';
    if (!file_exists($sqlFile)) {
        // Fallback to database/notun_alo.sql if the merged one isn't there
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

    // Execute the SQL (strip DEFINER clauses already handled above)
    $pdo->exec($sql);

    echo "<h1 style='color:green;'>Database initialized successfully!</h1>";
    echo "<p>All tables and views have been created/updated on <strong>" . DB_NAME . "</strong>.</p>";
    echo "<p><a href='index.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>Go to Homepage</a></p>";

} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Initialization Failed</h1>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<hr><p>Stack Trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
