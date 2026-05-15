<?php
require_once 'includes/config.php';
requireLogin();

$user = getCurrentUser($pdo);
$userId = (int)$user['id'];
$userName = e($user['name']);
$currentLang = $_SESSION['lang'] ?? 'en';

$userPoints = getUserPoints($pdo, $userId);
$pickupStats = $pdo->prepare("SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
    FROM pickups WHERE user_id = ?");
$pickupStats->execute([$userId]);
$pData = $pickupStats->fetch();

$totalImpactKg = 66.3; 

$leaderboardQuery = $pdo->query("SELECT u.name, r.lifetime_points 
    FROM users u JOIN rewards r ON u.id = r.user_id 
    ORDER BY r.lifetime_points DESC LIMIT 10");
$leaderboardData = $leaderboardQuery->fetchAll();

$activityQuery = $pdo->prepare("SELECT category, status, created_at 
    FROM pickups WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activityQuery->execute([$userId]);
$activityData = $activityQuery->fetchAll();

$tier = 'Bronze'; $maxPoints = 500;
if ($userPoints >= 500) { $tier = 'Silver'; $maxPoints = 1500; }
if ($userPoints >= 1500) { $tier = 'Gold'; $maxPoints = 5000; }
$progressPercent = min(100, ($userPoints / $maxPoints) * 100);
$profilePic = $user['picture_url'] ?? '';
$initial = strtoupper(mb_substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard — Notun Alo</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7f2; color: #4B5563; }
        body.dark-mode { background: #061405; color: #94A3B8; }

        /* ── MOBILE NAV ── */
        .mob-nav {
            position: sticky; top: 0; z-index: 9999;
            background: linear-gradient(135deg, #0f172a, #1e293b, #064e3b);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            transition: transform 0.3s ease;
        }
        .mob-nav.nav-hide { transform: translateY(-100%); }
        .mob-top { display: flex; align-items: center; justify-content: center; padding: 12px 18px 6px; gap: 8px; }
        .mob-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .mob-brand .icon { font-size: 1.3rem; }
        .mob-brand .name { font-size: 1.05rem; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
        .mob-utils {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 6px 18px 10px;
        }
        .mob-utils a, .mob-utils button {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);
            background: transparent; color: rgba(255,255,255,0.7); text-decoration: none;
            font-size: 0.85rem; cursor: pointer; transition: 0.2s;
        }
        .mob-utils a:hover, .mob-utils button:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .mob-profile {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem; color: #fff; text-decoration: none;
            background: linear-gradient(135deg, #1D9E75, #34d399);
        }
        .mob-profile img { width: 100%; height: 100%; border-radius: 8px; object-fit: cover; }
        body.dark-mode .mob-nav { background: #061405; border-bottom-color: rgba(255,255,255,0.04); }

        /* ── WRAPPER ── */
        .wrap { max-width: 600px; margin: 0 auto; padding: 16px 16px 40px; }

        /* ── HERO MERGED ── */
        .hero-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 20px;
            padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
        }
        body.dark-mode .hero-card { background: #0d1a0e; border-color: #1a2e1c; box-shadow: 0 2px 12px rgba(0,0,0,0.3); }
        .hero-left { display: flex; flex-direction: column; gap: 2px; }
        .hero-greet { font-size: 1rem; font-weight: 700; color: #111827; }
        body.dark-mode .hero-greet { color: #c4d4c0; }
        .hero-meta { font-size: 0.72rem; color: #9ca3af; display: flex; gap: 6px; flex-wrap: wrap; }
        body.dark-mode .hero-meta { color: #6a7a6c; }
        .hero-tier {
            display: inline-flex; align-items: center; gap: 4px;
            background: #fef3c7; color: #92400e; border-radius: 99px;
            padding: 4px 12px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            white-space: nowrap;
        }
        body.dark-mode .hero-tier { background: #1a2e1c; color: #a8b0a5; }

        /* ── QUICK ACTIONS ── */
        .q-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .q-btn {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 14px 6px; border-radius: 16px; background: #fff; border: 1px solid #e5e7eb;
            text-decoration: none; transition: 0.2s; box-shadow: 0 1px 6px rgba(0,0,0,0.03);
        }
        body.dark-mode .q-btn { background: #0d1a0e; border-color: #1a2e1c; }
        .q-btn:active { transform: scale(0.96); }
        .q-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .q-label { font-size: 0.72rem; font-weight: 600; color: #4B5563; text-align: center; line-height: 1.2; }
        body.dark-mode .q-label { color: #a8b0a5; }

        /* ── STATS ROW ── */
        .s-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .s-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
            padding: 14px 12px; text-align: center; box-shadow: 0 1px 6px rgba(0,0,0,0.03);
        }
        body.dark-mode .s-card { background: #0d1a0e; border-color: #1a2e1c; }
        .s-val { font-size: 1.3rem; font-weight: 800; color: #111827; margin-bottom: 2px; }
        body.dark-mode .s-val { color: #c4d4c0; }
        .s-label { font-size: 0.65rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; }
        body.dark-mode .s-label { color: #6a7a6c; }

        /* ── BANNER ── */
        .banner {
            background: linear-gradient(135deg, #065F46, #1D9E75); border-radius: 20px;
            padding: 24px 20px; margin-bottom: 16px; color: #fff; text-align: center;
        }
        .banner h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: 4px; }
        .banner p { font-size: 0.8rem; opacity: 0.85; margin-bottom: 14px; }
        .banner a {
            display: inline-block; background: #fff; color: #065F46; font-weight: 700;
            padding: 10px 24px; border-radius: 999px; font-size: 0.85rem; text-decoration: none;
        }
        .banner a:active { transform: scale(0.96); }

        /* ── SECTIONS ── */
        .section-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 20px;
            padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 6px rgba(0,0,0,0.03);
        }
        body.dark-mode .section-card { background: #0d1a0e; border-color: #1a2e1c; }
        .s-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .s-head h3 { font-size: 0.95rem; font-weight: 700; color: #111827; }
        body.dark-mode .s-head h3 { color: #c4d4c0; }
        .s-head a { font-size: 0.75rem; font-weight: 600; color: #1D9E75; text-decoration: none; }

        .act-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
        body.dark-mode .act-row { border-color: #1a2e1c; }
        .act-row:last-child { border: none; padding-bottom: 0; }
        .act-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .act-info { flex: 1; }
        .act-type { font-size: 0.85rem; font-weight: 600; color: #111827; }
        body.dark-mode .act-type { color: #c4d4c0; }
        .act-date { font-size: 0.7rem; color: #9ca3af; }
        body.dark-mode .act-date { color: #6a7a6c; }
        .badge-sm { font-size: 0.6rem; font-weight: 700; padding: 3px 8px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.03em; }

        .lb-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        body.dark-mode .lb-item { border-color: #1a2e1c; }
        .lb-item:last-child { border: none; padding-bottom: 0; }
        .lb-rank { font-size: 0.8rem; font-weight: 700; color: #9ca3af; width: 20px; text-align: center; }
        .lb-avatar { width: 32px; height: 32px; border-radius: 8px; background: #e6f5ee; color: #1D9E75; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
        body.dark-mode .lb-avatar { background: #0d2416; color: #34d399; }
        .lb-name { flex: 1; font-size: 0.82rem; font-weight: 600; color: #111827; }
        body.dark-mode .lb-name { color: #c4d4c0; }
        .lb-pts { font-size: 0.82rem; font-weight: 700; color: #1D9E75; }
        body.dark-mode .lb-pts { color: #34d399; }

        .about-strip {
            background: #e6f5ee; border: 1px solid #d1e5d8; border-radius: 16px;
            padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px;
            font-size: 0.8rem;
        }
        body.dark-mode .about-strip { background: #0d2416; border-color: #1a2e1c; }
        .about-strip .team { font-weight: 600; color: #111827; }
        body.dark-mode .about-strip .team { color: #c4d4c0; }
        .about-strip a {
            font-weight: 600; color: #1D9E75; text-decoration: none; border: 1px solid #1D9E75;
            padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; white-space: nowrap;
        }
        .about-strip a:active { background: #1D9E75; color: #fff; }

        /* ── DESKTOP UPGRADE ── */
        @media (min-width: 768px) {
            .wrap { max-width: 1000px; padding: 32px 32px 60px; }
            .mob-nav .mob-top { padding: 14px 24px 6px; }
            .mob-nav .mob-utils { padding: 6px 24px 12px; gap: 8px; }
            .hero-card { padding: 28px 32px; }
            .hero-greet { font-size: 1.3rem; }
            .q-grid { gap: 16px; }
            .q-btn { padding: 24px 12px; }
            .q-icon { width: 48px; height: 48px; font-size: 1.5rem; }
            .q-label { font-size: 0.85rem; }
            .s-card { padding: 20px; }
            .s-val { font-size: 1.6rem; }
            .section-card { padding: 28px; }
            .s-head h3 { font-size: 1.1rem; }
            .banner { padding: 32px; border-radius: 24px; }
            .banner h2 { font-size: 1.4rem; }
            .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
            .content-grid .section-card { margin-bottom: 0; }
        }
    </style>
</head>
<body>

<!-- ── MOBILE NAV ── -->
<nav class="mob-nav" id="mobNav">
    <div class="mob-top">
        <a href="dashboard.php" class="mob-brand">
            <span class="icon">♻️</span>
            <span class="name">Notun Alo</span>
        </a>
    </div>
    <div class="mob-utils">
        <a href="?lang=bn" title="বাংলা">বাং</a>
        <a href="?lang=en" title="English">EN</a>
        <button id="mobThemeToggle" title="Theme">🌙</button>
        <a href="edit_profile.php" title="Profile">
            <?php if ($profilePic): ?>
                <img src="<?= e($profilePic) ?>" alt="Avatar" class="mob-profile">
            <?php else: ?>
                <span class="mob-profile"><?= $initial ?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" title="Logout"><i class="ti ti-logout"></i></a>
    </div>
</nav>

<main class="wrap">

    <!-- ── HERO (Merged) ── -->
    <div class="hero-card">
        <div class="hero-left">
            <div class="hero-greet">🌿 <?= $currentLang === 'bn' ? 'শুভেচ্ছা, ' . $userName : 'Greetings, ' . $userName ?></div>
            <div class="hero-meta">
                <span><?= date('D, M j' , time()) ?></span>
                <span>·</span>
                <span>Dhaka Impact Hub</span>
            </div>
        </div>
        <div class="hero-tier">🏆 <?= $tier ?> Tier</div>
    </div>

    <!-- ── QUICK ACTIONS ── -->
    <div class="q-grid">
        <a href="user_request_pickup.php" class="q-btn">
            <div class="q-icon" style="background:#dcfce7; color:#166534;">🚛</div>
            <div class="q-label"><?= $currentLang === 'bn' ? 'পিকআপ\nশিডিউল' : 'Schedule\nPickup' ?></div>
        </a>
        <a href="shop.php" class="q-btn">
            <div class="q-icon" style="background:#dbeafe; color:#1e40af;">🛍</div>
            <div class="q-label"><?= $currentLang === 'bn' ? 'দোকান\nঘুরে দেখুন' : 'Browse\nShop' ?></div>
        </a>
        <a href="chatbot.php" class="q-btn">
            <div class="q-icon" style="background:#ede9fe; color:#5b21b6;">🤖</div>
            <div class="q-label"><?= $currentLang === 'bn' ? 'এআই\nসহায়ক' : 'AI\nAssistant' ?></div>
        </a>
    </div>

    <!-- ── STATS ── -->
    <div class="s-grid">
        <div class="s-card">
            <div class="s-val"><?= number_format($userPoints) ?></div>
            <div class="s-label"><?= $currentLang === 'bn' ? 'পয়েন্ট' : 'Points' ?></div>
        </div>
        <div class="s-card">
            <div class="s-val"><?= $pData['completed'] ?></div>
            <div class="s-label"><?= $currentLang === 'bn' ? 'পিকআপ' : 'Pickups' ?></div>
        </div>
        <div class="s-card">
            <div class="s-val"><?= $totalImpactKg ?> kg</div>
            <div class="s-label"><?= $currentLang === 'bn' ? 'ইমপ্যাক্ট' : 'Impact' ?></div>
        </div>
    </div>

    <!-- ── BANNER ── -->
    <div class="banner">
        <h2><?= $currentLang === 'bn' ? 'পরবর্তী পিকআপের জন্য প্রস্তুত?' : 'Ready for your next pickup?' ?></h2>
        <p><?= $currentLang === 'bn' ? 'এখনই শিডিউল করুন এবং এই সপ্তাহে ৫০০ বোনাস পয়েন্ট অর্জন করুন।' : 'Schedule now and earn up to 500 bonus points this week.' ?></p>
        <a href="user_request_pickup.php"><?= $currentLang === 'bn' ? 'পিকআপ শিডিউল করুন →' : 'Schedule a Pickup →' ?></a>
    </div>

    <!-- ── CONTENT ── -->
    <div class="content-grid">
        <!-- Recent Activity -->
        <div class="section-card">
            <div class="s-head">
                <h3><?= $currentLang === 'bn' ? 'সাম্প্রতিক কার্যকলাপ' : 'Recent Activity' ?></h3>
                <a href="user_recent_activity.php"><?= $currentLang === 'bn' ? 'সব দেখুন' : 'View All' ?></a>
            </div>
            <?php if(empty($activityData)): ?>
                <p style="text-align:center; padding:20px; color:var(--text-muted, #9ca3af); font-size:0.85rem;"><?= $currentLang === 'bn' ? 'কোনো কার্যকলাপ নেই।' : 'No recent activity.' ?></p>
            <?php else: ?>
                <?php foreach($activityData as $act): ?>
                <div class="act-row">
                    <div class="act-icon" style="background:#e6f5ee; color:#1D9E75;"><i class="ti ti-package"></i></div>
                    <div class="act-info">
                        <div class="act-type"><?= e($act['category']) ?></div>
                        <div class="act-date"><?= date('M j, Y', strtotime($act['created_at'])) ?></div>
                    </div>
                    <?php 
                        $st = strtolower($act['status']);
                        $bg = $st==='completed' ? '#dcfce7' : ($st==='pending' ? '#fef3c7' : '#dbeafe');
                        $cl = $st==='completed' ? '#166534' : ($st==='pending' ? '#92400e' : '#1e40af');
                    ?>
                    <span class="badge-sm" style="background:<?= $bg ?>; color:<?= $cl ?>;"><?= e($act['status']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Leaderboard -->
        <div class="section-card">
            <div class="s-head">
                <h3>🏆 <?= $currentLang === 'bn' ? 'লিডারবোর্ড' : 'Leaderboard' ?></h3>
            </div>
            <?php foreach($leaderboardData as $idx => $lb): ?>
            <div class="lb-item">
                <span class="lb-rank"><?= $idx+1 ?></span>
                <div class="lb-avatar"><?= strtoupper(mb_substr($lb['name'], 0, 1)) ?></div>
                <span class="lb-name"><?= e($lb['name']) ?></span>
                <span class="lb-pts"><?= number_format($lb['lifetime_points']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── ABOUT ── -->
    <div class="about-strip">
        <div>
            <span class="team">♻️ Notun Alo</span>
            <span style="color:#6b7280; font-size:0.7rem; margin-left:6px;"><?= $currentLang === 'bn' ? 'টিম ঘোস্টরাইডার্স' : 'Team GhostRiders' ?></span>
        </div>
        <a href="about.php"><?= $currentLang === 'bn' ? 'গল্প পড়ুন ↗' : 'Our Story ↗' ?></a>
    </div>

</main>

<script>
// ── Theme Toggle ──
const tToggle = document.getElementById('mobThemeToggle');
if (tToggle) {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') { document.body.classList.add('dark-mode'); tToggle.textContent = '☀️'; }
    tToggle.onclick = () => {
        document.body.classList.toggle('dark-mode');
        const d = document.body.classList.contains('dark-mode');
        tToggle.textContent = d ? '☀️' : '🌙';
        localStorage.setItem('theme', d ? 'dark' : 'light');
    };
}

// ── Scroll hide/show nav ──
let lastScroll = 0;
const nav = document.getElementById('mobNav');
window.addEventListener('scroll', () => {
    const cur = window.pageYOffset;
    if (cur > 60 && cur > lastScroll) nav.classList.add('nav-hide');
    else if (cur < lastScroll || cur <= 60) nav.classList.remove('nav-hide');
    lastScroll = cur;
});
</script>

</body>
</html>
