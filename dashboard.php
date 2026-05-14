<?php
ob_start(); // Prevent "headers already sent" when navbar does logout/lang redirect
// ============================================
// dashboard.php - User Dashboard
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

// Logout and Language toggle is now handled by includes/navbar.php

if ($_SESSION['role'] === 'admin')  redirect('admin.php');
if ($_SESSION['role'] === 'agency') redirect('agency.php');

$userId = (int)$_SESSION['user_id'];

$user   = getCurrentUser($pdo);
$firstName = explode(' ', $user['name'] ?? 'User')[0];
$points = getUserPoints($pdo, $userId);

$stmt = $pdo->prepare("SELECT lifetime_points FROM rewards WHERE user_id = ?");
$stmt->execute([$userId]);
$rewardRow = $stmt->fetch();
$lifetimePoints = $rewardRow ? (int)$rewardRow['lifetime_points'] : 0;

// Calculate Rank based on lifetime points
$rank = 'Bronze';
$nextRankPts = 500;
$progressPct = 0;
if ($lifetimePoints >= 2000) {
    $rank = 'Platinum';
    $nextRankPts = $lifetimePoints; // max
    $progressPct = 100;
} elseif ($lifetimePoints >= 1000) {
    $rank = 'Gold';
    $nextRankPts = 2000;
    $progressPct = (($lifetimePoints - 1000) / 1000) * 100;
} elseif ($lifetimePoints >= 500) {
    $rank = 'Silver';
    $nextRankPts = 1000;
    $progressPct = (($lifetimePoints - 500) / 500) * 100;
} else {
    $rank = 'Bronze';
    $nextRankPts = 500;
    $progressPct = ($lifetimePoints / 500) * 100;
}

// Fetch Global Leaderboard (Top 10)
$stmt = $pdo->prepare("
    SELECT u.name, u.picture_url, r.lifetime_points 
    FROM users u 
    JOIN rewards r ON u.id = r.user_id 
    WHERE u.role = 'user' 
    ORDER BY r.lifetime_points DESC
    LIMIT 10
");
$stmt->execute();
$topRecyclers = $stmt->fetchAll();

// Flash from redirect
$flash = getFlash();

// Stats for user
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(estimated_weight) as total_weight FROM pickups WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$stats = $stmt->fetch();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/leaderboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* =========================================
           GLOBAL & VARIABLES
        ========================================= */
        :root {
            --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            --card-hover-shadow: 0 15px 30px rgba(45, 122, 71, 0.15);
            --transition-fast: 0.3s ease;
            --primary-green: #1b5e20;
            --accent-yellow: #ffc107;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#f3f4f6; transition: background-color 0.3s ease, color 0.3s ease; }
        
        /* Dashboard Content Styles */
        .container { max-width:1200px; margin:40px auto; padding:0 20px; }
        .dashboard-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:30px; }
        .profile-big { display:flex; align-items:center; gap:20px; }
        .profile-big img { width:85px; height:85px; border-radius:50%; object-fit:cover; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(45, 122, 71, 0.3); }
        .profile-img-fallback { width: 85px; height: 85px; border-radius: 50%; background:#e0e0e0; display:flex; align-items:center; justify-content:center; font-size:2rem; color: #333; border: 3px solid #fff; box-shadow: 0 4px 15px rgba(45, 122, 71, 0.3); }
        .card { background:white; border-radius:20px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.06); text-decoration: none; display: block; color: inherit; transition: 0.3s; }
        a.card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(45, 122, 71, 0.15); border: 1px solid #2d7a47; }
        a.card:hover .quick-icon { transform: scale(1.1) rotate(5deg); }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; margin-top:25px; }
        .stat-card { background:white; padding:25px; border-radius:18px; box-shadow:0 8px 20px rgba(0,0,0,0.06); }
        .progress-bar { width:100%; height:12px; background:#e5e7eb; border-radius:50px; overflow:hidden; margin-top:12px; }
        .progress-fill { height:100%; background:linear-gradient(90deg,#22c55e,#16a34a); transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
        .leaderboard-item { display:flex; align-items:center; justify-content:space-between; padding:15px 0; border-bottom:1px solid #eee; transition: background 0.2s ease, transform 0.2s ease; }
        .leaderboard-item:hover { background: rgba(102, 187, 106, 0.08); transform: translateX(4px); }
        
        .tool-btn { border-radius:12px; border:none; background:rgba(0,0,0,0.08); color:#222; display:flex; align-items:center; justify-content:center; cursor:pointer; text-decoration:none; transition:0.3s; font-weight: 500; }
        .tool-btn:hover { transform:translateY(-3px); background:#22c55e; color:white; }
        
        .chatbot-fab-wrap { position:fixed; right:25px; bottom:25px; z-index:9999; }
        .chatbot-fab { width:65px; height:65px; border-radius:50%; background:linear-gradient(135deg,#22c55e,#15803d); display:flex; align-items:center; justify-content:center; color:white; text-decoration:none; font-size:28px; box-shadow:0 10px 30px rgba(34,197,94,0.4); transition: transform .25s ease, box-shadow .25s ease; position: relative; }
        .chatbot-fab:hover { transform: scale(1.12); box-shadow: 0 6px 32px rgba(45,122,71,.75); }
        .chatbot-fab:hover .fab-tooltip { opacity: 1; transform: translateY(-50%) translateX(0); pointer-events: auto; }
        .fab-tooltip { position: absolute; right: 72px; top: 50%; transform: translateY(-50%) translateX(6px); background: #1a1d24; color: #e8eaf0; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 500; white-space: nowrap; opacity: 0; transition: opacity .2s ease, transform .2s ease; pointer-events: none; border: 1px solid #2a2d35; }

        /* Dark Mode */
        body.dark-mode { background:#111827; color:white; }
        body.dark-mode .card, body.dark-mode .stat-card { background:#1f2937; border: 1px solid #333; }
        body.dark-mode .leaderboard-item { border-bottom: 1px solid #333; }
        body.dark-mode .tool-btn { background: rgba(255,255,255,0.1); color: #fff; }
        body.dark-mode .tool-btn:hover { background: #22c55e; }
        body.dark-mode .progress-bar { background: #374151; }
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3 { color: #f1f1f1 !important; }
        body.dark-mode p { color: #aaa !important; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background-color: #d4edda; color: #155724;"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div class="profile-big">
                <?php if (!empty($user['picture_url'])): ?>
                    <img src="<?= e($user['picture_url']) ?>" alt="Profile" onerror="this.outerHTML='<div class=\'profile-img-fallback\'>👤</div>'">
                <?php else: ?>
                    <div class="profile-img-fallback">👤</div>
                <?php endif; ?>
                <div>
                    <h1 style="margin: 0; font-size: 2rem;"><?= $lang['hello'] ?? 'Hello' ?>, <?= e($firstName) ?> 👋</h1>
                    <p style="margin: 5px 0 0 0; color: #666;"><?= $lang['recycling_overview'] ?? 'Here\'s your recycling overview' ?></p>
                </div>
            </div>
            <a href="shop.php" class="tool-btn" style="width:auto;padding:12px 18px;"><?= $lang['visit_shop'] ?? 'Visit Shop' ?></a>
        </div>

        <!-- LEADERBOARD -->
        <div class="card">
            <h2 style="margin:0 0 10px 0;"><?= $lang['leaderboard_progress'] ?? 'Leaderboard Progress' ?></h2>
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 5px;">
                <span><?= $lang['current_rank'] ?? 'Current Rank:' ?> <strong style="color: var(--primary-green);"><?= $rank ?></strong></span>
                <span><?= en2bn(number_format($lifetimePoints)) ?> <?= $lang['pts'] ?? 'pts' ?></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width:<?= $progressPct ?>%"></div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 1rem;"><?= $lang['reward_points'] ?? 'Reward Points' ?></h3>
                <h1 style="margin: 0; font-size: 2.2rem; color: #22c55e;"><?= en2bn(number_format($points)) ?></h1>
            </div>
            <div class="stat-card">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 1rem;"><?= $lang['completed_pickups'] ?? 'Completed Pickups' ?></h3>
                <h1 style="margin: 0; font-size: 2.2rem; color: #3b82f6;"><?= en2bn((int)($stats['total'] ?? 0)) ?></h1>
            </div>
            <div class="stat-card">
                <h3 style="margin: 0 0 10px 0; color: #666; font-size: 1rem;"><?= $lang['total_recycled'] ?? 'Total Recycled' ?></h3>
                <h1 style="margin: 0; font-size: 2.2rem; color: #f59e0b;"><?= en2bn(number_format((float)($stats['total_weight'] ?? 0), 1)) ?> <span style="font-size:1rem; color:#666;">KG</span></h1>
            </div>
        </div>

        <!-- TOP RECYCLERS - Premium Leaderboard -->
        <div class="leaderboard-card" style="margin-top: 30px;">
            <div class="leaderboard-header">
                <h2><?= $lang['top_recyclers'] ?? '🌟 Top Recyclers Leaderboard' ?></h2>
                <p><?= $lang['top_recyclers_hint'] ?? 'The best of Notun Alo' ?></p>
            </div>

            <div class="leaderboard-list">
                <?php if (empty($topRecyclers)): ?>
                    <div style="padding: 2rem; text-align: center; color: #888;"><?= $lang['no_users_yet'] ?? 'No recyclers yet.' ?></div>
                <?php else: ?>
                    <?php
                    $medals     = ['gold', 'silver', 'bronze'];
                    $medalEmoji = ['🥇', '🥈', '🥉'];
                    foreach($topRecyclers as $index => $userR):
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
                                <strong><?= e(explode(' ', $userR['name'])[0]) ?></strong>
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

        <div class="stats-grid" style="margin-bottom: 2rem;">
            <a href="user_request_pickup.php" class="card" style="text-align: center;">
                <div style="font-size: 2.5rem; color: #22c55e; margin-bottom: 10px;"><i class="fa-solid fa-truck-fast"></i></div>
                <h3 style="margin: 0 0 5px 0; color: inherit;"><?= $lang['request_pickup'] ?? 'Request a Pickup' ?></h3>
                <p style="margin: 0; color: #666; font-size: 0.9rem;"><?= $lang['schedule_hint'] ?? 'Schedule your next recycling collection' ?></p>
            </a>
            <a href="user_recent_activity.php" class="card" style="text-align: center;">
                <div style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 10px;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <h3 style="margin: 0 0 5px 0; color: inherit;"><?= $lang['recent_activity'] ?? 'My Recent Activity' ?></h3>
                <p style="margin: 0; color: #666; font-size: 0.9rem;"><?= $lang['activity_hint'] ?? 'Your pickup history and statuses' ?></p>
            </a>
        </div>

    </div>
</main>

<div class="chatbot-fab-wrap">
    <a href="chatbot.php" class="chatbot-fab">
        <span class="fab-tooltip"><?= $lang['ai_assistant'] ?? 'AI Assistant' ?></span>
        <i class="fa-solid fa-robot"></i>
    </a>
</div>
</body>
</html>
