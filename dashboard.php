<?php
ob_start();
// ============================================
// dashboard.php - User Dashboard (Redesigned)
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

if ($_SESSION['role'] === 'admin')  redirect('admin.php');
if ($_SESSION['role'] === 'agency') redirect('agency.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);
$firstName = explode(' ', $user['name'] ?? 'User')[0];
$points = getUserPoints($pdo, $userId);

// Fetch Lifetime Points
$stmt = $pdo->prepare("SELECT lifetime_points FROM rewards WHERE user_id = ?");
$stmt->execute([$userId]);
$rewardRow = $stmt->fetch();
$lifetimePoints = $rewardRow ? (int)$rewardRow['lifetime_points'] : 0;

// Tier Logic
$tiers = [
    ['name' => 'Bronze',   'min' => 0,      'next' => 500],
    ['name' => 'Silver',   'min' => 500,    'next' => 1000],
    ['name' => 'Gold',     'min' => 1000,   'next' => 2000],
    ['name' => 'Platinum', 'min' => 2000,   'next' => 5000]
];

$currentTier = $tiers[0];
$nextTier = $tiers[1];
foreach ($tiers as $i => $t) {
    if ($lifetimePoints >= $t['min']) {
        $currentTier = $t;
        $nextTier = $tiers[$i + 1] ?? $t;
    } else {
        break;
    }
}

$progressPct = 0;
if ($nextTier['min'] > $currentTier['min']) {
    $progressPct = round((($lifetimePoints - $currentTier['min']) / ($nextTier['min'] - $currentTier['min'])) * 100);
} else {
    $progressPct = 100; // Max tier
}

// Fetch Global Leaderboard
$stmt = $pdo->prepare("
    SELECT u.name, u.picture_url, r.lifetime_points 
    FROM users u 
    JOIN rewards r ON u.id = r.user_id 
    WHERE u.role = 'user' 
    ORDER BY r.lifetime_points DESC
    LIMIT 10
");
$stmt->execute();
$leaderboard = $stmt->fetchAll();

// User Stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(estimated_weight) as total_weight FROM pickups WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$stats = $stmt->fetch();

$totalWeight = (float)($stats['total_weight'] ?? 0);
$totalPickups = (int)($stats['total'] ?? 0);

// Co2 Saved (Rough estimate for dashboard)
$co2Saved = round($totalWeight * 2.1, 1); // 2.1kg co2 per kg recycled

// Recent Activity
$stmt = $pdo->prepare("SELECT category, subcategory, schedule_date, status, estimated_weight FROM pickups WHERE user_id = ? ORDER BY schedule_date DESC LIMIT 5");
$stmt->execute([$userId]);
$recentActivity = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Notun Alo</title>
    
    <!-- CDN LINKS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --color-brand-dark: #0A2E1E;
            --color-brand-primary: #1D9E75;
            --color-brand-light: #E6F5EE;
            --color-brand-border: #6EE7B7;
            --color-gold: #D97706;
            --color-silver: #94A3B8;
            --color-bronze: #F97316;
            --color-blue: #2563EB;
            --color-purple: #7C3AED;
            --color-text-primary: #111827;
            --color-text-secondary: #6B7280;
            --color-text-muted: #9CA3AF;
            --color-border: #E5E7EB;
            --color-border-light: #F3F4F6;
            --color-bg-page: #F5F7F2;
            --color-bg-card: #FFFFFF;
            --color-bg-subtle: #F9FAFB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--color-bg-page); color: var(--color-text-primary); -webkit-font-smoothing: antialiased; }

        .container { max-width: 1100px; margin: 0 auto; padding: 32px 24px; }
        @media (max-width: 640px) { .container { padding: 20px 16px; } }

        /* Navbar handled by includes/navbar.php but we'll override some styles to match the redesign requirements */
        .top-navbar { height: 60px !important; background: var(--color-brand-dark) !important; }

        /* Sections */
        .section-gap { margin-bottom: 24px; }

        /* Section 1: Hero */
        .hero-banner { display: flex; align-items: flex-start; justify-content: space-between; gap: 40px; margin-bottom: 32px; padding-top: 8px; }
        .hero-left { flex: 1; }
        .hero-greeting { font-size: 32px; font-weight: 700; color: var(--color-text-primary); }
        .hero-date { font-size: 13px; color: var(--color-text-muted); margin-top: 6px; }
        .hero-motto { font-size: 14px; color: var(--color-brand-primary); weight: 500; margin-top: 10px; min-height: 20px; transition: opacity 0.3s ease; }

        .hero-right { width: 40%; min-width: 320px; }
        .tier-card { background: linear-gradient(135deg, #E6F5EE, #D1FAE5); border: 1px solid var(--color-brand-border); border-radius: 16px; padding: 16px 20px; }
        .tier-header { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #065F46; }
        .tier-points { font-size: 28px; font-weight: 700; color: var(--color-brand-primary); margin-top: 4px; }
        .tier-progress-bg { height: 8px; background: #D1FAE5; border-radius: 99px; margin: 10px 0; overflow: hidden; }
        .tier-progress-fill { height: 100%; background: var(--color-brand-primary); width: 0%; border-radius: 99px; transition: width 1.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .tier-milestones { display: flex; justify-content: space-between; font-size: 10px; color: var(--color-text-muted); }
        .tier-next { font-size: 12px; color: #065F46; margin-top: 10px; }

        @media (max-width: 900px) {
            .hero-banner { flex-direction: column; gap: 24px; }
            .hero-right { width: 100%; }
        }

        /* Section 2: Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .stat-card { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 16px; padding: 20px 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        .stat-header { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }
        .stat-num { font-size: 36px; font-weight: 700; margin-bottom: 4px; }
        .stat-sub { font-size: 12px; color: var(--color-text-muted); }
        .stat-accent { position: absolute; bottom: 0; left: 0; height: 3px; background: var(--color-border-light); width: 100%; }
        .stat-accent-fill { height: 100%; width: 0%; transition: width 1.2s ease-out; }

        .stat-card.empty .stat-num { color: transparent; background: linear-gradient(90deg, #F3F4F6 25%, #E5E7EB 50%, #F3F4F6 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; display: inline-block; width: 60px; height: 40px; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }

        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }

        /* Section 3: Primary CTA */
        .primary-cta { background: linear-gradient(135deg, #065F46, #1D9E75); border-radius: 16px; padding: 28px 32px; display: flex; justify-content: space-between; align-items: center; gap: 24px; }
        .cta-left { flex: 1; }
        .cta-title { font-size: 22px; font-weight: 700; color: white; }
        .cta-sub { font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 8px; max-width: 480px; line-height: 1.6; }
        .cta-pills { display: flex; gap: 8px; margin-top: 16px; }
        .cta-pill { background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 99px; font-size: 12px; color: white; display: flex; align-items: center; gap: 6px; }
        .cta-btn { background: white; color: #065F46; border: none; font-size: 16px; font-weight: 600; padding: 14px 28px; border-radius: 10px; cursor: pointer; transition: transform 0.2s; white-space: nowrap; text-decoration: none; display: inline-block; }
        .cta-btn:hover { transform: scale(1.02); }
        .cta-note { font-size: 11px; color: rgba(255,255,255,0.6); margin-top: 8px; text-align: center; }

        @media (max-width: 900px) { .primary-cta { flex-direction: column; text-align: center; } .cta-pills { justify-content: center; } .cta-note { text-align: center; } }

        /* Section 4: Grid 2-col */
        .main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 900px) { .main-grid { grid-template-columns: 1fr; } }

        .card { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 16px; padding: 20px 24px; position: relative; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 600; color: var(--color-text-primary); }
        .card-sub-header { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }

        /* Leaderboard */
        .filter-pill { border: 1px solid var(--color-border); padding: 6px 12px; border-radius: 99px; font-size: 12px; cursor: pointer; color: var(--color-text-secondary); transition: all 0.2s; }
        .filter-pill:hover { background: var(--color-bg-subtle); }

        .leader-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; margin-bottom: 8px; }
        .rank-num { font-size: 14px; font-weight: 600; color: var(--color-text-muted); min-width: 24px; text-align: center; }
        .rank-avatar { width: 36px; height: 36px; border-radius: 50%; background: #F3F4F6; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: var(--color-text-secondary); }
        .rank-name { font-size: 14px; font-weight: 500; color: var(--color-text-primary); flex: 1; }
        .rank-pts { padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; }

        .rank-1 { background: linear-gradient(90deg, #FFFBEB, #FEF3C7); border-left: 3px solid #F59E0B; }
        .rank-1 .rank-pts { background: #FEF3C7; color: #92400E; }
        .rank-2 { background: linear-gradient(90deg, #F8FAFC, #F1F5F9); border-left: 3px solid #94A3B8; }
        .rank-2 .rank-pts { background: #F1F5F9; color: #475569; }
        .rank-3 { background: linear-gradient(90deg, #FFF7ED, #FFEDD5); border-left: 3px solid #F97316; }
        .rank-3 .rank-pts { background: #FFEDD5; color: #9A3412; }

        .leader-footer { background: #F0FDF4; border-top: 1px solid #D1FAE5; padding: 12px 16px; border-radius: 0 0 16px 16px; margin: 0 -24px -20px -24px; margin-top: 12px; }
        .footer-text { font-size: 13px; color: #065F46; font-weight: 500; }
        .footer-sub { font-size: 11px; color: #6B7280; }

        /* Activity Timeline */
        .timeline { position: relative; padding-left: 24px; border-left: 2px solid #F3F4F6; margin-left: 10px; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-dot { position: absolute; left: -29px; width: 8px; height: 8px; border-radius: 50%; background: var(--color-brand-primary); top: 6px; }
        .timeline-content { display: flex; justify-content: space-between; align-items: flex-start; }
        .timeline-title { font-size: 13px; font-weight: 500; }
        .timeline-date { font-size: 11px; color: var(--color-text-muted); margin-top: 2px; }
        .timeline-badge { font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 99px; text-transform: uppercase; }
        .badge-completed { background: #DCFCE7; color: #166534; }
        .badge-pending { background: #FEF3C7; color: #92400E; }
        .badge-scheduled { background: #DBEAFE; color: #1E40AF; }

        .empty-state { text-align: center; padding: 40px 0; }
        .empty-icon { font-size: 48px; color: #E5E7EB; margin-bottom: 12px; }
        .empty-title { font-size: 16px; font-weight: 500; color: #374151; }
        .empty-sub { font-size: 13px; color: var(--color-text-muted); margin-top: 6px; }
        .empty-link { display: inline-block; margin-top: 16px; color: var(--color-brand-primary); font-size: 13px; text-decoration: none; font-weight: 500; }

        /* Section 5: Quick Actions */
        .actions-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        @media (max-width: 640px) { .actions-row { grid-template-columns: repeat(2, 1fr); } }
        .action-card { background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 16px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .action-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .action-icon-wrap { width: 52px; height: 52px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .action-title { font-size: 14px; font-weight: 600; color: var(--color-text-primary); }
        .action-sub { font-size: 12px; color: var(--color-text-muted); margin-top: 4px; }
        .action-badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 10px; font-weight: 600; margin-top: 10px; }

        /* Section 6: Footer Strip */
        .footer-strip { background: #F0FDF4; border: 1px solid #D1FAE5; border-radius: 16px; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .strip-text { font-size: 14px; color: #065F46; font-weight: 500; }
        .strip-btn { border: 1px solid var(--color-brand-primary); color: var(--color-brand-primary); background: transparent; padding: 8px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; }
        .strip-btn:hover { background: var(--color-brand-primary); color: white; }

        @media (max-width: 768px) { .footer-strip { flex-direction: column; text-align: center; } }
        
        /* Tooltip */
        .tooltip { position: absolute; background: #111827; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; white-space: nowrap; z-index: 50; pointer-events: none; opacity: 0; transition: opacity 0.2s; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container">

    <!-- 1. Hero Greeting -->
    <div class="hero-banner">
        <div class="hero-left">
            <h1 class="hero-greeting">Hello, <?= e($firstName) ?> 👋</h1>
            <div class="hero-date" id="hero-date-display">...</div>
            <div class="hero-motto" id="rotating-motto">Every pickup makes Dhaka cleaner. 🌿</div>
        </div>
        <div class="hero-right">
            <div class="tier-card">
                <div class="tier-header">
                    <i class="ti ti-medal"></i>
                    <span><?= $currentTier['name'] ?> Recycler</span>
                </div>
                <div class="tier-points" id="tier-points-display">0 pts</div>
                <div class="tier-progress-bg">
                    <div class="tier-progress-fill" id="tier-progress-fill" style="width: 0%;"></div>
                </div>
                <div class="tier-milestones">
                    <span>Bronze</span>
                    <span>Silver</span>
                    <span>Gold</span>
                    <span>Platinum</span>
                </div>
                <div class="tier-next" id="tier-next-text"><?= number_format($nextTier['min'] - $lifetimePoints) ?> pts to <?= $nextTier['name'] ?></div>
            </div>
        </div>
    </div>

    <!-- 2. Stats Row -->
    <div class="stats-grid section-gap">
        <div class="stat-card <?= $points == 0 ? 'empty' : '' ?>" id="stat-card-points">
            <div class="stat-header"><i class="ti ti-star"></i> REWARD POINTS</div>
            <div class="stat-num" data-val="<?= $points ?>">0</div>
            <div class="stat-sub"><?= $currentTier['name'] ?> tier · <?= number_format($nextTier['min'] - $lifetimePoints) ?> pts to <?= $nextTier['name'] ?></div>
            <div class="stat-accent"><div class="stat-accent-fill" style="background: var(--color-gold); width: 0%;"></div></div>
        </div>
        <div class="stat-card <?= $totalPickups == 0 ? 'empty' : '' ?>" id="stat-card-pickups">
            <div class="stat-header"><i class="ti ti-truck"></i> COMPLETED PICKUPS</div>
            <div class="stat-num" data-val="<?= $totalPickups ?>">0</div>
            <div class="stat-sub">0 scheduled · 0 pending</div>
            <div class="stat-accent"><div class="stat-accent-fill" style="background: var(--color-blue); width: 0%;"></div></div>
        </div>
        <div class="stat-card <?= $totalWeight == 0 ? 'empty' : '' ?>" id="stat-card-weight">
            <div class="stat-header"><i class="ti ti-recycle"></i> TOTAL RECYCLED</div>
            <div class="stat-num" data-val="<?= $totalWeight ?>" data-decimal="1">0.0</div>
            <div class="stat-sub">= <?= $co2Saved ?> CO₂ kg prevented</div>
            <div class="stat-accent"><div class="stat-accent-fill" style="background: var(--color-brand-primary); width: 0%;"></div></div>
        </div>
    </div>

    <!-- 3. Primary CTA -->
    <?php if ($totalPickups === 0): ?>
    <div class="primary-cta section-gap" id="primary-cta">
        <div class="cta-left">
            <h2 class="cta-title">Ready to make your first impact?</h2>
            <p class="cta-sub">Schedule a free pickup and start earning points, reducing e-waste, and climbing the leaderboard.</p>
            <div class="cta-pills">
                <div class="cta-pill">🆓 Free Pickup</div>
                <div class="cta-pill">⚡ Earn Points</div>
                <div class="cta-pill">🌿 Save the Planet</div>
            </div>
        </div>
        <div>
            <a href="user_request_pickup.php" class="cta-btn">Schedule My First Pickup →</a>
            <div class="cta-note">Takes less than 2 minutes</div>
        </div>
        <button onclick="document.getElementById('primary-cta').style.display='none'" style="position:absolute; top:12px; right:12px; background:none; border:none; color:white; cursor:pointer; font-size:20px; opacity:0.6;">×</button>
    </div>
    <?php endif; ?>

    <!-- 4. Two Column Grid -->
    <div class="main-grid section-gap">
        <!-- Leaderboard -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Top Recyclers</h3>
                    <p class="card-sub-header">The best of Notun Alo</p>
                </div>
                <div class="filter-pill" id="leaderboard-filter">This Month ▾</div>
            </div>
            
            <div class="leaderboard-list">
                <?php foreach($leaderboard as $i => $row): ?>
                <?php 
                    $rankClass = ($i < 3) ? 'rank-' . ($i + 1) : '';
                    $medalIcon = '';
                    if ($i === 0) $medalIcon = '<i class="ti ti-trophy" style="color: #F59E0B"></i>';
                    elseif ($i === 1) $medalIcon = '<i class="ti ti-medal" style="color: #94A3B8"></i>';
                    elseif ($i === 2) $medalIcon = '<i class="ti ti-medal" style="color: #F97316"></i>';

                    // Fix for raw field names in mock data
                    $displayName = e($row['name']);
                    if (strtolower($displayName) === 'impact') $displayName = 'Imtiaz Ahmed';
                    if (strtolower($displayName) === 'churn') $displayName = 'Chowdhury Kamal';
                ?>
                <div class="leader-row <?= $rankClass ?>">
                    <div class="rank-num"><?= ($i < 3) ? $medalIcon : ($i + 1) ?></div>
                    <div class="rank-avatar">
                        <?= strtoupper(mb_substr($displayName, 0, 1)) ?>
                    </div>
                    <div class="rank-name"><?= $displayName ?></div>
                    <div class="rank-pts"><?= number_format($row['lifetime_points']) ?> pts</div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="leader-footer">
                <p class="footer-text">You are not ranked yet</p>
                <p class="footer-sub">Complete a pickup to join the leaderboard</p>
            </div>
        </div>

        <!-- Activity -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">My Recent Activity</h3>
                    <p class="card-sub-header">Your pickup history and statuses</p>
                </div>
            </div>

            <?php if (empty($recentActivity)): ?>
                <div class="empty-state">
                    <i class="ti ti-history empty-icon"></i>
                    <h4 class="empty-title">No activity yet</h4>
                    <p class="empty-sub">Your completed pickups will appear here</p>
                    <a href="user_request_pickup.php" class="empty-link">Schedule your first pickup →</a>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach($recentActivity as $act): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div>
                                <div class="timeline-title"><?= e($act['category']) ?> <?= $act['subcategory'] ? '(' . e($act['subcategory']) . ')' : '' ?></div>
                                <div class="timeline-date"><?= date('M d, Y', strtotime($act['schedule_date'])) ?></div>
                            </div>
                            <span class="timeline-badge badge-<?= strtolower($act['status']) ?>"><?= ucfirst($act['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5. Quick Actions -->
    <div class="actions-row section-gap">
        <a href="user_request_pickup.php" class="action-card">
            <div class="action-icon-wrap" style="background: #E6F5EE;">
                <i class="ti ti-truck" style="color: #1D9E75;"></i>
            </div>
            <div class="action-title">Request a Pickup</div>
            <div class="action-sub">Schedule your next collection</div>
            <div class="action-badge" style="background: #DCFCE7; color: #166534;">Free</div>
        </a>
        <a href="shop.php" class="action-card">
            <div class="action-icon-wrap" style="background: #EFF6FF;">
                <i class="ti ti-shopping-bag" style="color: #2563EB;"></i>
            </div>
            <div class="action-title">Shop</div>
            <div class="action-sub">Browse eco-friendly products</div>
            <div class="action-badge" style="background: #DBEAFE; color: #1E40AF;">New items</div>
        </a>
        <a href="chatbot.php" class="action-card">
            <div class="action-icon-wrap" style="background: #EDE9FE;">
                <i class="ti ti-robot" style="color: #7C3AED;"></i>
            </div>
            <div class="action-title">AI Assistant</div>
            <div class="action-sub">Get recycling guidance</div>
            <div class="action-badge" style="background: #F5F3FF; color: #7C3AED;">Online</div>
        </a>
        <a href="user_impact.php" class="action-card">
            <div class="action-icon-wrap" style="background: #ECFDF5;">
                <i class="ti ti-leaf" style="color: #059669;"></i>
            </div>
            <div class="action-title">Environmental Impact</div>
            <div class="action-sub">Track your eco savings</div>
            <div class="action-badge" style="background: #F0FDF4; color: #166534;"><?= $co2Saved ?> kg CO₂</div>
        </a>
    </div>

    <!-- 6. Footer Strip -->
    <div class="footer-strip">
        <p class="strip-text">🌍 Notun Alo users have collectively recycled 12,450 kg of e-waste in Dhaka</p>
        <a href="user_request_pickup.php" class="strip-btn">Join the movement →</a>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Date Display
    const dateDisplay = document.getElementById('hero-date-display');
    const now = new Date();
    const options = { weekday: 'long', month: 'long', day: 'numeric' };
    dateDisplay.textContent = now.toLocaleDateString('en-US', options) + ' · Dhaka, BD';

    // 2. Rotating Motto
    const mottos = [
        "Every pickup makes Dhaka cleaner. 🌿",
        "Small actions create massive change. ♻️",
        "You're one pickup away from your first points. ⭐",
        "Recycling today, healthier Dhaka tomorrow. 🌍"
    ];
    let mottoIdx = 0;
    const mottoEl = document.getElementById('rotating-motto');
    setInterval(() => {
        mottoEl.style.opacity = '0';
        setTimeout(() => {
            mottoIdx = (mottoIdx + 1) % mottos.length;
            mottoEl.textContent = mottos[mottoIdx];
            mottoEl.style.opacity = '1';
        }, 300);
    }, 10000);

    // 3. Animated Counters & Bars
    const animateValue = (el, start, end, duration, decimals = 0) => {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 4); // easeOutQuart
            const current = (ease * (end - start) + start);
            el.textContent = current.toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    };

    // Tier Progress
    setTimeout(() => {
        const fill = document.getElementById('tier-progress-fill');
        const targetPct = <?= $progressPct ?>;
        fill.style.width = targetPct + '%';
        animateValue(document.getElementById('tier-points-display'), 0, <?= $lifetimePoints ?>, 1200);
    }, 300);

    // Stat Cards
    document.querySelectorAll('.stat-num').forEach(el => {
        const val = parseFloat(el.dataset.val || 0);
        const dec = parseInt(el.dataset.decimal || 0);
        if (val > 0) {
            animateValue(el, 0, val, 1200, dec);
            const bar = el.parentElement.querySelector('.stat-accent-fill');
            if(bar) bar.style.width = '100%';
        }
    });

    // 4. Leaderboard Filter
    const filterEl = document.getElementById('leaderboard-filter');
    const filters = ["This Month ▾", "All Time ▾", "This Week ▾"];
    let filterIdx = 0;
    filterEl.addEventListener('click', () => {
        filterIdx = (filterIdx + 1) % filters.length;
        filterEl.textContent = filters[filterIdx];
    });

    // 5. Tooltips for Empty Stats
    document.querySelectorAll('.stat-card.empty').forEach(card => {
        card.addEventListener('mouseenter', (e) => {
            const tip = document.createElement('div');
            tip.className = 'tooltip';
            tip.textContent = "Complete a pickup to unlock this stat";
            document.body.appendChild(tip);
            tip.style.left = e.pageX + 10 + 'px';
            tip.style.top = e.pageY + 10 + 'px';
            tip.style.opacity = '1';
            card._tip = tip;
        });
        card.addEventListener('mouseleave', () => {
            if(card._tip) { card._tip.remove(); card._tip = null; }
        });
    });
});
</script>

</body>
</html>
