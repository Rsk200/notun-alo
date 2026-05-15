<?php
// ============================================
// dashboard.php — User Dashboard (Clean/Light Redesign)
// Notun Alo Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();

$user = getCurrentUser($pdo);
$userId = (int)$user['id'];
$userName = e($user['name']);
$userInitial = strtoupper(mb_substr($userName, 0, 1));
$currentLang = $_SESSION['lang'] ?? 'en';

// Fetch User Stats
$userPoints = getUserPoints($pdo, $userId);
$pickupStats = $pdo->prepare("SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled
    FROM pickups WHERE user_id = ?");
$pickupStats->execute([$userId]);
$pData = $pickupStats->fetch();

// Fetch Impact (Dummy for now)
$totalImpactKg = 66.3; 
$co2Saved = 336.5;

// Fetch Leaderboard
$leaderboardQuery = $pdo->query("SELECT u.name, r.lifetime_points 
    FROM users u JOIN rewards r ON u.id = r.user_id 
    ORDER BY r.lifetime_points DESC LIMIT 10");
$leaderboardData = $leaderboardQuery->fetchAll();

// Fetch Recent Activity
$activityQuery = $pdo->prepare("SELECT category, status, created_at 
    FROM pickups WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activityQuery->execute([$userId]);
$activityData = $activityQuery->fetchAll();

// Tier Logic
$tier = 'Bronze'; $maxPoints = 500; $nextTier = 'Silver';
if ($userPoints >= 500) { $tier = 'Silver'; $maxPoints = 1500; $nextTier = 'Gold'; }
if ($userPoints >= 1500) { $tier = 'Gold'; $maxPoints = 5000; $nextTier = 'Platinum'; }
$progressPercent = min(100, ($userPoints / $maxPoints) * 100);

// Translations
$t = [
    'en' => [
        'greet' => 'Greetings, ' . $userName . ' 🌿',
        'points' => 'Reward Points',
        'pickups' => 'Completed Pickups',
        'impact' => 'Total Impact',
        'lb' => 'Leaderboard',
        'activity' => 'Recent Activity',
        'req_pickup' => 'Schedule a Pickup →',
        'learn' => 'Learn More →',
        'points_sub' => 'Level: ' . $tier,
        'pickups_sub' => $pData['pending'] . ' pending · ' . $pData['scheduled'] . ' scheduled',
        'impact_sub' => '= ' . $co2Saved . ' CO₂ kg prevented',
    ],
    'bn' => [
        'greet' => 'শুভেচ্ছা, ' . $userName . ' 🌿',
        'points' => 'রিওয়ার্ড পয়েন্ট',
        'pickups' => 'সম্পন্ন পিকআপ',
        'impact' => 'মোট প্রভাব',
        'lb' => 'লিডারবোর্ড',
        'activity' => 'সাম্প্রতিক কার্যকলাপ',
        'req_pickup' => 'পিকআপ শিডিউল করুন →',
        'learn' => 'আরও জানুন →',
        'points_sub' => 'স্তর: ' . $tier,
        'pickups_sub' => $pData['pending'] . 'টি অপেক্ষমান · ' . $pData['scheduled'] . 'টি নির্ধারিত',
        'impact_sub' => '= ' . $co2Saved . ' কেজি CO₂ রোধ করা হয়েছে',
    ]
];
$text = $t[$currentLang];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Notun Alo</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --bg-page: #F5F7F2;
            --bg-card: #FFFFFF;
        }

        /* ===== DARK MODE ===== */
        body.dark-mode {
            --bg-page: #080f09;
            --bg-card: #0f1a10;
            --text-primary: #E2E8F0;
            --text-secondary: #94A3B8;
            --text-muted: #64748B;
            --border: #1e3222;
            --brand-light: #0d2416;
            --brand-primary: #34d399;
        }
        body.dark-mode .white-card {
            background: var(--bg-card) !important;
            border-color: var(--border) !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.4) !important;
        }
        body.dark-mode .tier-card {
            background: linear-gradient(135deg, #1a1200, #261a00) !important;
            border-color: #3d2c00 !important;
        }
        body.dark-mode .activity-row { border-bottom-color: var(--border) !important; }
        body.dark-mode .activity-row:hover { background: rgba(52,211,153,0.05) !important; }
        body.dark-mode .activity-icon { background: #0d2416 !important; }
        body.dark-mode .leaderboard-item:hover { background: rgba(52,211,153,0.05) !important; }
        body.dark-mode .avatar { background: #0d2416 !important; }
        body.dark-mode .cta-banner { box-shadow: 0 20px 40px rgba(0,0,0,0.5) !important; }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); transition: background-color 0.4s ease, color 0.4s ease; }

        .wrapper { max-width: 1280px; margin: 0 auto; padding: 40px 32px; }
        .white-card { background: var(--bg-card, white); border: 1px solid var(--border); border-radius: 24px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: background-color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; }

        /* HERO ROW */
        .hero-row { display: grid; grid-template-columns: 1fr 340px; gap: 32px; margin-bottom: 32px; align-items: stretch; }
        .greeting-card { display: flex; flex-direction: column; justify-content: center; }
        .page-greeting { font-size: 36px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
        .page-date { font-size: 14px; color: var(--text-muted); margin-top: 8px; }
        #rotating-quote { font-size: 15px; color: var(--brand-primary); font-weight: 600; margin-top: 16px; min-height: 24px; }

        .tier-card { display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; background: linear-gradient(135deg, #FFFBEB, #FEF3C7); border: 1px solid #FDE68A; }
        .tier-label { font-size: 11px; font-weight: 800; color: #92400E; text-transform: uppercase; letter-spacing: 0.1em; }
        .points-val { font-size: 44px; font-weight: 800; color: #D97706; margin: 12px 0; }
        .progress-bar-bg { height: 10px; background: rgba(0,0,0,0.05); border-radius: 99px; overflow: hidden; margin-top: 16px; }
        .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #D97706, #F59E0B); width: 0%; transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1); }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .stat-card { transition: 0.3s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.04); }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 20px; }
        .stat-val { font-size: 32px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
        .stat-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }

        /* CTA BANNER */
        .cta-banner { background: linear-gradient(135deg, #065F46, #1D9E75); border-radius: 24px; padding: 40px; margin-bottom: 32px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 20px 40px rgba(29,158,117,0.15); }
        .cta-title { font-size: 28px; font-weight: 800; }
        .btn-cta { background: white; color: #065F46; padding: 16px 32px; border-radius: 14px; font-weight: 700; text-decoration: none; transition: 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-cta:hover { transform: scale(1.05); }

        /* CONTENT GRID */
        .content-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .section-title { font-size: 20px; font-weight: 800; color: var(--text-primary); }
        
        .activity-row { display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #F9FAFB; }
        .activity-icon { width: 40px; height: 40px; background: var(--bg-page); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--brand-primary); }
        .activity-info { flex: 1; }
        .activity-type { font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .activity-date { font-size: 12px; color: var(--text-muted); }
        .badge { font-size: 10px; font-weight: 800; padding: 6px 12px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.05em; }

        .leaderboard-item { display: flex; align-items: center; gap: 16px; padding: 12px; border-radius: 16px; margin-bottom: 8px; transition: 0.2s; }
        .leaderboard-item:hover { background: var(--bg-page); }
        .rank { font-size: 14px; font-weight: 800; color: var(--text-muted); width: 24px; }
        .avatar { width: 40px; height: 40px; border-radius: 12px; background: var(--brand-light); color: var(--brand-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; }
        .lb-name { flex: 1; font-size: 14px; font-weight: 600; color: var(--text-primary); }
        .lb-points { font-weight: 800; color: var(--brand-primary); font-size: 15px; }

        @media (max-width: 1000px) { .hero-row, .stats-grid, .content-grid { grid-template-columns: 1fr; } .cta-banner { flex-direction: column; text-align: center; gap: 24px; } }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="wrapper">
        <!-- HERO -->
        <div class="hero-row">
            <div class="white-card greeting-card">
                <div class="page-greeting"><?= $text['greet'] ?></div>
                <p class="page-date"><?= date('l, F j, Y') ?> · Dhaka Impact Hub</p>
                <div id="rotating-quote"></div>
            </div>

            <div class="white-card tier-card">
                <div class="tier-label"><?= $tier ?> Tier Recycler</div>
                <div class="points-val" id="hero-pts">0</div>
                <div style="font-size:12px; font-weight:600; color:#92400E;"><?= strtoupper($text['points']) ?></div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" id="tier-progress"></div>
                </div>
                <div style="display:flex; justify-content:space-between; margin-top:10px; font-size:11px; font-weight:600; color:#92400E;">
                    <span><?= number_format($userPoints) ?> pts</span>
                    <span><?= number_format($maxPoints - $userPoints) ?> to <?= $nextTier ?></span>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#FEF3C7; color:#D97706;"><i class="ti ti-gift"></i></div>
                <div class="stat-val"><?= number_format($userPoints) ?></div>
                <div class="stat-label"><?= $text['points'] ?></div>
            </div>
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#DBEAFE; color:#2563EB;"><i class="ti ti-truck"></i></div>
                <div class="stat-val"><?= $pData['completed'] ?></div>
                <div class="stat-label"><?= $text['pickups'] ?></div>
            </div>
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#D1FAE5; color:#059669;"><i class="ti ti-leaf"></i></div>
                <div class="stat-val"><?= $totalImpactKg ?> kg</div>
                <div class="stat-label"><?= $text['impact'] ?></div>
            </div>
        </div>

        <!-- BANNER -->
        <div class="cta-banner">
            <div>
                <h2 class="cta-title">Ready for your next pickup?</h2>
                <p style="opacity:0.9; margin-top:8px;">Schedule now and earn up to 500 bonus points this week.</p>
            </div>
            <a href="user_request_pickup.php" class="btn-cta"><?= $text['req_pickup'] ?></a>
        </div>

        <!-- CONTENT GRID -->
        <div class="content-grid">
            <div class="white-card">
                <div class="section-header">
                    <h2 class="section-title"><?= $text['activity'] ?></h2>
                    <a href="user_recent_activity.php" style="font-size:13px; font-weight:700; color:var(--brand-primary); text-decoration:none;">View All</a>
                </div>
                <?php if(empty($activityData)): ?>
                    <p style="text-align:center; padding:40px; color:var(--text-muted);">No recent activity found.</p>
                <?php else: ?>
                    <?php foreach($activityData as $act): ?>
                        <div class="activity-row">
                            <div class="activity-icon"><i class="ti ti-package"></i></div>
                            <div class="activity-info">
                                <div class="activity-type"><?= e($act['category']) ?></div>
                                <div class="activity-date"><?= date('M j, Y', strtotime($act['created_at'])) ?></div>
                            </div>
                            <?php 
                                $st = strtolower($act['status']);
                                $bg = $st=='completed' ? '#DCFCE7' : ($st=='pending' ? '#FEF3C7' : '#DBEAFE');
                                $cl = $st=='completed' ? '#166534' : ($st=='pending' ? '#92400E' : '#1E40AF');
                            ?>
                            <span class="badge" style="background:<?= $bg ?>; color:<?= $cl ?>;"><?= e($act['status']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="white-card">
                <div class="section-header">
                    <h2 class="section-title"><?= $text['lb'] ?></h2>
                    <i class="ti ti-trophy" style="color:#D97706; font-size:20px;"></i>
                </div>
                <?php foreach($leaderboardData as $idx => $lb): ?>
                    <div class="leaderboard-item">
                        <span class="rank"><?= $idx+1 ?></span>
                        <div class="avatar"><?= strtoupper(mb_substr($lb['name'],0,1)) ?></div>
                        <span class="lb-name"><?= e($lb['name']) ?></span>
                        <span class="lb-points"><?= number_format($lb['lifetime_points']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ABOUT US STRIP -->
        <div style="margin-top: 32px; background: var(--brand-light); border: 1px solid var(--border); border-radius: 16px; padding: 18px 28px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; background:var(--brand-primary); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.15rem;">&#9851;</div>
                <div>
                    <div style="font-size:0.9rem; font-weight:700; color:var(--text-primary);"><?= $lang['notun_alo'] ?? 'Notun Alo' ?></div>
                    <div style="font-size:0.75rem; color:var(--text-muted);"><?= $currentLang === 'bn' ? 'টিম ঘোস্টরাইডার্স · ইউল্যাব' : 'Team GhostRiders &nbsp;·&nbsp; ULAB' ?></div>
                </div>
            </div>
            <a href="about.php" style="font-size:0.85rem; font-weight:600; color:var(--brand-primary); text-decoration:none; border:1px solid var(--brand-primary); padding:8px 18px; border-radius:8px; transition:0.2s;" onmouseover="this.style.background='var(--brand-primary)'; this.style.color='white';" onmouseout="this.style.background='transparent'; this.style.color='var(--brand-primary)';"><?= $currentLang === 'bn' ? 'পুরো গল্প পড়ুন ↗' : 'Read Our Full Story ↗' ?></a>
        </div>
    </main>

    <script>
        const quotes = ["Every pickup makes Dhaka cleaner. 🌿", "Small actions create massive change. ♻️", "You're building a greener future. ⭐"];
        let qIdx = 0; const qEl = document.getElementById('rotating-quote');
        function rotate() {
            qEl.style.opacity = 0;
            setTimeout(() => { qEl.innerText = quotes[qIdx]; qEl.style.opacity = 1; qIdx = (qIdx + 1) % quotes.length; }, 500);
        }
        setInterval(rotate, 8000); rotate();

        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start).toLocaleString();
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        }

        window.onload = () => {
            animateValue(document.getElementById('hero-pts'), 0, <?= $userPoints ?>, 1500);
            setTimeout(() => { document.getElementById('tier-progress').style.width = '<?= $progressPercent ?>%'; }, 300);
        };
    </script>
</body>
</html>
