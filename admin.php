<?php
// ============================================
// admin.php - Admin Dashboard
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

$flash = null;

// ---- Stats ----
$totalWaste    = $pdo->query("SELECT COALESCE(SUM(estimated_weight),0) as tw FROM pickups WHERE status='completed'")->fetch()['tw'];
$totalPickups  = $pdo->query("SELECT COUNT(*) as cnt FROM pickups")->fetch()['cnt'];
$totalUsers    = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role='user'")->fetch()['cnt'];
$totalProducts = $pdo->query("SELECT COUNT(*) as cnt FROM products")->fetch()['cnt'];

// ---- Agencies ----
$agencies = $pdo->query("SELECT id, name FROM users WHERE role='agency' ORDER BY name")->fetchAll();

// ---- Top 10 Leaderboard ----
$stmt = $pdo->prepare("
    SELECT u.name, u.email, u.picture_url, r.lifetime_points
    FROM users u
    JOIN rewards r ON u.id = r.user_id
    WHERE u.role = 'user'
    ORDER BY r.lifetime_points DESC
    LIMIT 10
");
$stmt->execute();
$topRecyclers = $stmt->fetchAll();

// ---- Email Search ----
$searchResults = [];
$searchTerm = trim((string)($_GET['search'] ?? ''));
if ($searchTerm !== '') {
    $searchStmt = $pdo->prepare("
        SELECT u.name, u.email, u.picture_url, r.lifetime_points
        FROM users u
        JOIN rewards r ON u.id = r.user_id
        WHERE u.role = 'user'
          AND u.email LIKE ?
        ORDER BY r.lifetime_points DESC
    ");
    $searchStmt->execute(["%{$searchTerm}%"]);
    $searchResults = $searchStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/leaderboard.css">
    <link rel="stylesheet" href="assets/css/sortable-table.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container">

        <div class="page-header">
            <div>
                <h1 class="page-title" data-reveal><?= $lang['hello'] ?? 'Hello' ?>, <?= e(explode(' ', $_SESSION['name'] ?? 'Admin')[0]) ?> 👋</h1>
                <p class="page-sub"><?= $lang['platform_stats'] ?? 'Overview of platform statistics' ?></p>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-grid stats-grid--4">
            <div class="stat-card stat-card--green" data-reveal>
                <div class="stat-card__icon">⚖</div>
                <div class="stat-card__body">
                    <p class="stat-card__label"><?= $lang['total_waste_collected'] ?? 'Total Waste Collected' ?></p>
                    <p class="stat-card__value"><?= en2bn(number_format((float)$totalWaste, 1)) ?> KG</p>
                </div>
            </div>
            <div class="stat-card stat-card--teal" data-reveal>
                <div class="stat-card__icon">📦</div>
                <div class="stat-card__body">
                    <p class="stat-card__label"><?= $lang['total_pickups'] ?? 'Total Pickups' ?></p>
                    <p class="stat-card__value"><?= en2bn(number_format((int)$totalPickups)) ?></p>
                </div>
            </div>
            <div class="stat-card stat-card--gold" data-reveal>
                <div class="stat-card__icon">👤</div>
                <div class="stat-card__body">
                    <p class="stat-card__label"><?= $lang['registered_users'] ?? 'Registered Users' ?></p>
                    <p class="stat-card__value"><?= en2bn(number_format((int)$totalUsers)) ?></p>
                </div>
            </div>
            <div class="stat-card stat-card--purple" data-reveal>
                <div class="stat-card__icon">🛍</div>
                <div class="stat-card__body">
                    <p class="stat-card__label"><?= $lang['products_in_shop'] ?? 'Products in Shop' ?></p>
                    <p class="stat-card__value"><?= en2bn(number_format((int)$totalProducts)) ?></p>
                </div>
            </div>
        </div>

        <!-- Documentation & Pitch Control (New) -->
        <div class="card" data-reveal style="margin-bottom: 2.5rem; padding: 2rem; border-radius: 20px; border-left: 6px solid #38bdf8; background: var(--bg-card);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
                <div>
                    <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;">📄 YC-Style Documentation & Pitch</h2>
                    <p style="color: var(--text-muted); margin-bottom: 1rem;">Manage the live /docs endpoint, scheduling, and technical whitepaper.</p>
                    <a href="admin_docs.php" class="btn btn-primary" style="background: #38bdf8; border: none; padding: 0.8rem 1.5rem; color: #fff; font-weight: 600; text-decoration: none; border-radius: 8px;">Manage Documentation ↗</a>
                </div>
                <div style="background: rgba(56, 189, 248, 0.1); padding: 1.5rem; border-radius: 50%; font-size: 2rem;">🚀</div>
            </div>
        </div>

        <!-- Global Leaderboard (Top 10) -->
        <div class="leaderboard-card" data-reveal>
            <div class="leaderboard-header">
                <h2><?= $lang['top_recyclers'] ?? '🌟 Top Recyclers Leaderboard' ?></h2>
                <p><?= $lang['top_recyclers_hint'] ?? 'The best of Notun Alo' ?></p>
                <form method="GET" class="leaderboard-search">
                    <input
                        type="text"
                        name="search"
                        placeholder="<?= $lang['search_by_email'] ?? 'Search user by email...' ?>"
                        value="<?= e($searchTerm) ?>"
                    >
                    <button type="submit"><?= $lang['search'] ?? 'Search' ?></button>
                    <?php if ($searchTerm !== ''): ?>
                        <a href="admin.php" class="btn btn-outline" style="padding:.85rem 1.2rem;"><?= $lang['clear'] ?? 'Clear' ?></a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="leaderboard-list">
                <?php if (empty($topRecyclers)): ?>
                    <div style="padding: 2rem; text-align: center; color: var(--text-muted);"><?= $lang['no_users_yet'] ?? 'No recyclers yet.' ?></div>
                <?php else: ?>
                    <?php
                    $medals     = ['gold', 'silver', 'bronze'];
                    $medalEmoji = ['🥇', '🥈', '🥉'];
                    foreach ($topRecyclers as $index => $userR):
                        $isTop3    = $index < 3;
                        $itemClass = $isTop3 ? 'leader-item leader-item--' . $medals[$index] : 'leader-item';
                        $avClass   = $isTop3 ? 'leader-avatar leader-avatar--' . $medals[$index] : 'leader-avatar';
                    ?>
                    <div class="<?= $itemClass ?>">
                        <div class="leader-left">
                            <?php if ($isTop3): ?>
                                <div class="leader-rank"><?= $medalEmoji[$index] ?></div>
                            <?php else: ?>
                                <div class="leader-rank leader-rank--num"><?= en2bn($index + 1) ?></div>
                            <?php endif; ?>

                            <div class="<?= $avClass ?>">
                                <?php if (!empty($userR['picture_url'])): ?>
                                    <img src="<?= e($userR['picture_url']) ?>" alt="Profile"
                                         onerror="this.outerHTML='<div class=\'leader-avatar__placeholder\'>👤</div>'">
                                <?php else: ?>
                                    <div class="leader-avatar__placeholder">👤</div>
                                <?php endif; ?>
                            </div>

                            <div class="leader-user">
                                <strong><?= e($userR['name']) ?></strong>
                                <span><?= e($userR['email'] ?? '') ?></span>
                            </div>
                        </div>

                        <div class="leader-points">
                            <?= en2bn(number_format($userR['lifetime_points'])) ?> <?= $lang['pts'] ?? 'pts' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search Results -->
        <?php if ($searchTerm !== ''): ?>
        <div class="leaderboard-card" data-reveal style="margin-bottom: 2rem;">
            <div class="leaderboard-header">
                <h2 style="font-size: 1.2rem;">🔍 <?= $lang['search_results'] ?? 'Search Results' ?></h2>
                <p><?= $lang['users_matching'] ?? 'Users matching email:' ?> <strong><?= e($searchTerm) ?></strong></p>
            </div>
            <div class="leaderboard-list">
                <?php if (empty($searchResults)): ?>
                    <div style="padding: 1.5rem; text-align: center; color: var(--text-muted);"><?= $lang['no_users_found'] ?? 'No users found for this email search.' ?></div>
                <?php else: ?>
                    <?php foreach ($searchResults as $srIdx => $userR): ?>
                    <div class="leader-item">
                        <div class="leader-left">
                            <div class="leader-rank leader-rank--num"><?= en2bn($srIdx + 1) ?></div>
                            <div class="leader-avatar">
                                <?php if (!empty($userR['picture_url'])): ?>
                                    <img src="<?= e($userR['picture_url']) ?>" alt="Profile"
                                         onerror="this.outerHTML='<div class=\'leader-avatar__placeholder\'>👤</div>'">
                                <?php else: ?>
                                    <div class="leader-avatar__placeholder">👤</div>
                                <?php endif; ?>
                            </div>
                            <div class="leader-user">
                                <strong><?= e($userR['name']) ?></strong>
                                <span><?= e($userR['email']) ?></span>
                            </div>
                        </div>
                        <div class="leader-points">
                            <?= en2bn(number_format($userR['lifetime_points'])) ?> <?= $lang['pts'] ?? 'pts' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" style="margin-bottom: 2rem; border-radius: 12px;"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <!-- AI Churn Risk Monitor -->
        <div class="card" data-reveal style="margin-top: 2.5rem; margin-bottom: 2rem;">
            <?php include 'admin_churn_table.php'; ?>
        </div>

    </div>
</main>
</body>
</html>
