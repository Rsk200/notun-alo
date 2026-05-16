<?php
require_once 'includes/config.php';

function runAlter($pdo, string $sql): bool {
    try {
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

try {
    $sqlFile = 'clean_merged_notun_alo.sql';
    if (!file_exists($sqlFile)) {
        $sqlFile = 'database/notun_alo.sql';
    }

    if (!file_exists($sqlFile)) {
        die("<h1 style='color:red;'>SQL file not found!</h1><p>Neither 'clean_merged_notun_alo.sql' nor 'database/notun_alo.sql' exists.</p>");
    }

    echo "<h1>Initializing Database...</h1>";
    echo "<p>Using: <code>$sqlFile</code></p>";

    // Check if tables already exist
    $tablesExist = false;
    try {
        $pdo->query("SELECT 1 FROM users LIMIT 1");
        $tablesExist = true;
    } catch (PDOException $e) {
        $tablesExist = false;
    }

    if (!$tablesExist) {
        // First run — execute full SQL
        $sql = file_get_contents($sqlFile);
        $sql = preg_replace('/\s+DEFINER\s*=\s*[^\s]+/i', '', $sql);
        $sql = preg_replace('/^SET\s+SESSION\s+sql_require_primary_key\s*=\s*[^;]+;/im', '', $sql);
        $pdo->exec($sql);
        echo "<h1 style='color:green;'>Database initialized successfully!</h1>";
    } else {
        // Tables exist — apply AUTO_INCREMENT on ID columns
        echo "<p>Tables already exist. Applying AUTO_INCREMENT fixes...</p>";

        $fixes = [
            "ALTER TABLE users MODIFY id int(11) NOT NULL AUTO_INCREMENT",
            "ALTER TABLE orders MODIFY id int(11) NOT NULL AUTO_INCREMENT",
            "ALTER TABLE pickups MODIFY id int(11) NOT NULL AUTO_INCREMENT",
            "ALTER TABLE products MODIFY id int(11) NOT NULL AUTO_INCREMENT",
            "ALTER TABLE rewards MODIFY id int(11) NOT NULL AUTO_INCREMENT",
            "ALTER TABLE emission_factors MODIFY id int(11) NOT NULL AUTO_INCREMENT",
        ];

        $ok = 0;
        foreach ($fixes as $fix) {
            if (runAlter($pdo, $fix)) {
                $ok++;
            }
        }

        // Also try constraints (may already exist — that's fine)
        $constraints = [
            "ALTER TABLE orders ADD CONSTRAINT fk_orders_agency FOREIGN KEY (agency_id) REFERENCES users(id) ON DELETE SET NULL",
            "ALTER TABLE orders ADD CONSTRAINT orders_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
            "ALTER TABLE orders ADD CONSTRAINT orders_ibfk_2 FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE",
            "ALTER TABLE pickups ADD CONSTRAINT pickups_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
            "ALTER TABLE pickups ADD CONSTRAINT pickups_ibfk_2 FOREIGN KEY (agency_id) REFERENCES users(id) ON DELETE SET NULL",
            "ALTER TABLE rewards ADD CONSTRAINT rewards_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
            "ALTER TABLE user_ml_scores ADD CONSTRAINT fk_user_ml_scores_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        ];

        // Add UNIQUE constraint on email (prevents duplicate accounts)
        $uniqueFixes = [
            "ALTER TABLE users ADD CONSTRAINT uq_users_email UNIQUE (email)",
        ];
        $uk = 0;
        foreach ($uniqueFixes as $uq) {
            if (runAlter($pdo, $uq)) {
                $uk++;
            }
        }

        $ck = 0;
        foreach ($constraints as $con) {
            if (runAlter($pdo, $con)) {
                $ck++;
            }
        }

        // Create rank cache table if not exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_rank_cache (
            user_id INT PRIMARY KEY,
            percentile INT NOT NULL DEFAULT 0,
            city VARCHAR(100) NOT NULL DEFAULT '',
            total_in_city INT NOT NULL DEFAULT 0,
            metric VARCHAR(50) NOT NULL DEFAULT 'co2_prevented',
            calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_calculated (calculated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        echo "<p>AUTO_INCREMENT: $ok/6 tables fixed. Unique: $uk/1 added. Constraints: $ck/7 added.</p>";

        // Fix collation mismatch (MySQL 8.0 default utf8mb4_0900_ai_ci vs MariaDB/older utf8mb4_general_ci)
        echo "<p>Fixing table collations...</p>";
        $collationTables = ['users', 'orders', 'pickups', 'products', 'rewards', 'emission_factors', 'user_ml_scores'];
        $colFixed = 0;
        foreach ($collationTables as $tbl) {
            if (runAlter($pdo, "ALTER TABLE `$tbl` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                $colFixed++;
            }
        }
        echo "<p>$colFixed/" . count($collationTables) . " tables converted to utf8mb4_unicode_ci.</p>";

        echo "<h1 style='color:green;'>Database schema is up to date!</h1>";
    }

    echo "<p><a href='index.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>Go to Homepage</a></p>";

} catch (PDOException $e) {
    echo "<h1 style='color:red;'>Initialization Failed</h1>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<hr><p>Stack Trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
