<?php
/**
 * Dev/QA seed: demo admin, churn users, pickups, rewards.
 * CLI: php seed_churn_test_data.php — do not expose on public hosting (demo passwords).
 */
$sessionPath = __DIR__ . '/.sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);

require_once __DIR__ . '/includes/config.php';

$passwordHash = password_hash('test1234', PASSWORD_DEFAULT);
$adminHash = password_hash('admin1234', PASSWORD_DEFAULT);

$pdo->beginTransaction();

try {
    $adminStmt = $pdo->prepare("
        INSERT INTO users (name, email, password, address, phone, role, created_at)
        VALUES ('AI Demo Admin', 'aiadmin@notunalo.test', ?, 'Dhaka, Bangladesh', '01799000000', 'admin', NOW())
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            password = VALUES(password),
            role = 'admin',
            address = VALUES(address),
            phone = VALUES(phone)
    ");
    $adminStmt->execute([$adminHash]);

    $userStmt = $pdo->prepare("
        INSERT INTO users (name, email, password, address, phone, role, created_at)
        VALUES (?, ?, ?, ?, ?, 'user', DATE_SUB(NOW(), INTERVAL ? DAY))
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            password = VALUES(password),
            address = VALUES(address),
            phone = VALUES(phone),
            role = 'user'
    ");

    $findUserStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $countPickupStmt = $pdo->prepare("SELECT COUNT(*) FROM pickups WHERE user_id = ?");
    $countCompletedStmt = $pdo->prepare("SELECT COUNT(*) FROM pickups WHERE user_id = ? AND status = 'completed'");
    $pickupStmt = $pdo->prepare("
        INSERT INTO pickups (user_id, category, estimated_weight, status, schedule_date, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $rewardStmt = $pdo->prepare("
        INSERT INTO rewards (user_id, total_points, lifetime_points)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            total_points = VALUES(total_points),
            lifetime_points = GREATEST(lifetime_points, VALUES(lifetime_points))
    ");

    $addresses = [
        'Dhaka, Bangladesh',
        'Chattogram, Bangladesh',
        'Sylhet, Bangladesh',
        'Rajshahi, Bangladesh',
        'Khulna, Bangladesh',
        'Narayanganj, Bangladesh',
        'Gazipur, Bangladesh',
    ];
    $categories = ['Paper', 'Plastic', 'Metal'];

    $created = 0;
    $skippedPickups = 0;

    for ($i = 1; $i <= 30; $i++) {
        $email = sprintf('churn%03d@notunalo.test', $i);
        $name = sprintf('Churn Demo User %02d', $i);
        $address = $addresses[($i - 1) % count($addresses)];
        $phone = '01777' . str_pad((string) $i, 6, '0', STR_PAD_LEFT);

        if ($i <= 10) {
            $segment = 'high';
            $ageDays = 8 + $i;
            $points = 120 + ($i % 3) * 10;
            $completedPickups = 1;
            $stalePending = true;
            $lastCompletedDays = $i % 2;
        } elseif ($i <= 20) {
            $segment = 'medium';
            $ageDays = 45 + $i;
            $points = 35 + ($i * 3);
            $completedPickups = 1;
            $stalePending = $i % 2 === 0;
            $lastCompletedDays = 55 + $i;
        } else {
            $segment = 'low';
            $ageDays = 160 + ($i * 4);
            $points = 220 + ($i * 12);
            $completedPickups = 4;
            $stalePending = false;
            $lastCompletedDays = 5 + ($i % 8);
        }

        $userStmt->execute([$name, $email, $passwordHash, $address, $phone, $ageDays]);
        $findUserStmt->execute([$email]);
        $userId = (int) $findUserStmt->fetchColumn();
        $rewardStmt->execute([$userId, $points, $points]);

        $countPickupStmt->execute([$userId]);
        if ((int) $countPickupStmt->fetchColumn() > 0) {
            if ($segment === 'high') {
                $countCompletedStmt->execute([$userId]);
                if ((int) $countCompletedStmt->fetchColumn() === 0) {
                    $pickupStmt->execute([
                        $userId,
                        $categories[$i % count($categories)],
                        3.5 + ($i % 4),
                        'completed',
                        date('Y-m-d', strtotime('-1 day')),
                        date('Y-m-d H:i:s', strtotime('-1 day')),
                    ]);
                }
            }
            $skippedPickups++;
            continue;
        }

        for ($j = 0; $j < $completedPickups; $j++) {
            $daysAgo = ($lastCompletedDays ?? 20) + ($j * 12);
            $pickupStmt->execute([
                $userId,
                $categories[($i + $j) % count($categories)],
                2.5 + (($i + $j) % 6),
                'completed',
                date('Y-m-d', strtotime("-{$daysAgo} days")),
                date('Y-m-d H:i:s', strtotime("-{$daysAgo} days")),
            ]);
        }

        if ($stalePending) {
            $pickupStmt->execute([
                $userId,
                $categories[$i % count($categories)],
                4.0 + ($i % 5),
                $i % 2 === 0 ? 'assigned' : 'pending',
                date('Y-m-d', strtotime('-25 days')),
                date('Y-m-d H:i:s', strtotime('-30 days')),
            ]);
        } elseif ($segment === 'low') {
            $pickupStmt->execute([
                $userId,
                $categories[$i % count($categories)],
                3.0 + ($i % 4),
                'pending',
                date('Y-m-d', strtotime('+5 days')),
                date('Y-m-d H:i:s', strtotime('-2 days')),
            ]);
        }

        $created++;
    }

    $pdo->commit();

    echo "Seed complete.\n";
    echo "Demo admin: aiadmin@notunalo.test / admin1234\n";
    echo "Demo users: churn001@notunalo.test to churn030@notunalo.test / test1234\n";
    echo "Users processed: 30\n";
    echo "Pickup sets inserted for users with no existing pickups: {$created}\n";
    echo "Users skipped for pickup insert because they already had pickups: {$skippedPickups}\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seed failed: " . $e->getMessage() . "\n");
    exit(1);
}
