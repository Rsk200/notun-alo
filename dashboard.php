<?php
require_once 'includes/config.php';
requireLogin();


$user = getCurrentUser($pdo);
$userId = (int)$user['id'];
$userName = e($user['name']);
$userInitial = strtoupper(mb_substr($userName, 0, 1));
$currentLang = $_SESSION['lang'] ?? 'en';


$userPoints = getUserPoints($pdo, $userId);
$pickupStats = $pdo->prepare("SELECT
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled
    FROM pickups WHERE user_id = ?");
$pickupStats->execute([$userId]);
$pData = $pickupStats->fetch();


$impactQuery = $pdo->prepare("SELECT COALESCE(SUM(estimated_weight),0) as total_kg FROM pickups WHERE user_id = ? AND status = 'completed'");
$impactQuery->execute([$userId]);
$totalImpactKg = (float)$impactQuery->fetchColumn();
$co2Saved = round($totalImpactKg * 1.2, 1); // ~1.2 kg CO₂ saved per kg recycled


$leaderboardQuery = $pdo->query("SELECT u.name, r.lifetime_points
    FROM users u JOIN rewards r ON u.id = r.user_id
    ORDER BY r.lifetime_points DESC LIMIT 10");
$leaderboardData = $leaderboardQuery->fetchAll();


$activityQuery = $pdo->prepare("SELECT category, status, created_at
    FROM pickups WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$activityQuery->execute([$userId]);
$activityData = $activityQuery->fetchAll();


$tier = 'Bronze'; $nextTier = 'Silver'; $maxPoints = 500;
if ($userPoints >= 500) { $tier = 'Silver'; $nextTier = 'Gold'; $maxPoints = 1500; }
if ($userPoints >= 1500) { $tier = 'Gold'; $nextTier = 'Platinum'; $maxPoints = 5000; }
$progressPercent = min(100, ($userPoints / $maxPoints) * 100);
$profilePic = $user['picture_url'] ?? '';
$initial = strtoupper(mb_substr($userName, 0, 1));


$text = [
    'greet' => $currentLang === 'bn' ? 'শুভেচ্ছা, ' . $userName . ' 🌿' : 'Greetings, ' . $userName . ' 🌿',
    'points' => $currentLang === 'bn' ? 'রিওয়ার্ড পয়েন্ট' : 'Reward Points',
    'pickups' => $currentLang === 'bn' ? 'সম্পন্ন পিকআপ' : 'Completed Pickups',
    'impact' => $currentLang === 'bn' ? 'মোট প্রভাব' : 'Total Impact',
    'lb' => $currentLang === 'bn' ? 'লিডারবোর্ড' : 'Leaderboard',
    'activity' => $currentLang === 'bn' ? 'সাম্প্রতিক কার্যকলাপ' : 'Recent Activity',
    'req_pickup' => $currentLang === 'bn' ? 'পিকআপ শিডিউল করুন →' : 'Schedule a Pickup →',
    'learn' => $currentLang === 'bn' ? 'আরও জানুন →' : 'Learn More →',
    'points_sub' => $currentLang === 'bn' ? 'স্তর: ' . $tier : 'Level: ' . $tier,
    'pickups_sub' => $currentLang === 'bn' ? $pData['pending'] . 'টি অপেক্ষমান · ' . $pData['scheduled'] . 'টি নির্ধারিত' : $pData['pending'] . ' pending · ' . $pData['scheduled'] . ' scheduled',
    'impact_sub' => $currentLang === 'bn' ? '= ' . $co2Saved . ' কেজি CO₂ রোধ করা হয়েছে' : '= ' . $co2Saved . ' CO₂ kg prevented',
];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
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
        body.dark-mode {
            --bg-page: #061405;
            --bg-card: #0d1a0e;
            --text-primary: #c4d4c0;
            --text-secondary: #94a3b8;
            --text-muted: #6a7a6c;
            --border: #1a2e1c;
            --brand-light: #0d2416;
            --brand-primary: #34d399;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-page); color: var(--text-secondary); transition: background 0.4s ease, color 0.4s ease; }


        /* ============================================================
           MOBILE LAYOUT (< 768px)
           ============================================================ */
        .desktop-only { display: none; }
        .mobile-only { display: block; }


        /* Mobile Nav */
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


        .mob-wrap { max-width: 600px; margin: 0 auto; padding: 16px 16px 40px; }


        .mob-hero {
            background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px;
            padding: 20px; margin-bottom: 16px;
            display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
        }
        .mob-hero-left { display: flex; flex-direction: column; gap: 2px; }
        .mob-greet { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
        .mob-meta { font-size: 0.72rem; color: var(--text-muted); display: flex; gap: 6px; flex-wrap: wrap; }
        .mob-tier {
            display: inline-flex; align-items: center; gap: 4px;
            background: #fef3c7; color: #92400e; border-radius: 99px;
            padding: 4px 12px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            white-space: nowrap;
        }
        body.dark-mode .mob-tier { background: #1a2e1c; color: #a8b0a5; }


        .q-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .q-btn {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 14px 6px; border-radius: 16px; background: var(--bg-card); border: 1px solid var(--border);
            text-decoration: none; transition: 0.2s;
        }
        .q-btn:active { transform: scale(0.96); }
        .q-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .q-label { font-size: 0.72rem; font-weight: 600; color: var(--text-secondary); text-align: center; line-height: 1.2; }


        .mob-s-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .mob-s-card {
            background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px;
            padding: 14px 12px; text-align: center;
        }
        .mob-s-val { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); margin-bottom: 2px; }
        .mob-s-label { font-size: 0.65rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }


        .mob-banner {
            background: linear-gradient(135deg, #065F46, #1D9E75); border-radius: 20px;
            padding: 24px 20px; margin-bottom: 16px; color: #fff; text-align: center;
        }
        .mob-banner h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: 4px; }
        .mob-banner p { font-size: 0.8rem; opacity: 0.85; margin-bottom: 14px; }
        .mob-banner a {
            display: inline-block; background: #fff; color: #065F46; font-weight: 700;
            padding: 10px 24px; border-radius: 999px; font-size: 0.85rem; text-decoration: none;
        }


        .mob-card {
            background: var(--bg-card); border: 1px solid var(--border); border-radius: 20px;
            padding: 20px; margin-bottom: 16px;
        }
        .mob-card h3 { font-size: 0.95rem; font-weight: 700; color: var(--text-primary); margin-bottom: 14px; }


        .mob-act-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .mob-act-row:last-child { border: none; padding-bottom: 0; }
        .mob-act-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .mob-act-type { font-size: 0.85rem; font-weight: 600; color: var(--text-primary); }
        .mob-act-date { font-size: 0.7rem; color: var(--text-muted); }
        .badge-sm { font-size: 0.6rem; font-weight: 700; padding: 3px 8px; border-radius: 99px; text-transform: uppercase; }


        .mob-lb-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); }
        .mob-lb-item:last-child { border: none; padding-bottom: 0; }
        .mob-lb-rank { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); width: 20px; text-align: center; }
        .mob-lb-avatar { width: 32px; height: 32px; border-radius: 8px; background: var(--brand-light); color: var(--brand-primary); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; flex-shrink: 0; }
        .mob-lb-name { flex: 1; font-size: 0.82rem; font-weight: 600; color: var(--text-primary); }
        .mob-lb-pts { font-size: 0.82rem; font-weight: 700; color: var(--brand-primary); }


        .mob-about {
            background: var(--brand-light); border: 1px solid var(--border); border-radius: 16px;
            padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px;
            font-size: 0.8rem;
        }
        .mob-about a {
            font-weight: 600; color: var(--brand-primary); text-decoration: none; border: 1px solid var(--brand-primary);
            padding: 6px 14px; border-radius: 8px; font-size: 0.75rem; white-space: nowrap;
        }


        /* === Extra-compact for small phones (≤480px) === */
        @media (max-width: 480px) {
            .mob-wrap { padding: 12px 12px 32px; }
            .mob-hero { padding: 14px; margin-bottom: 12px; border-radius: 16px; }
            .mob-greet { font-size: 0.88rem; }
            .mob-meta { font-size: 0.68rem; }


            .q-grid { gap: 8px; margin-bottom: 12px; }
            .q-btn { padding: 11px 4px; border-radius: 12px; }
            .q-icon { width: 34px; height: 34px; font-size: 1rem; border-radius: 10px; }
            .q-label { font-size: 0.68rem; }


            .mob-s-grid { gap: 8px; margin-bottom: 12px; }
            .mob-s-card { padding: 12px 8px; border-radius: 12px; }
            .mob-s-val { font-size: 1.1rem; }
            .mob-s-label { font-size: 0.6rem; }


            .mob-banner { padding: 18px 14px; margin-bottom: 12px; border-radius: 16px; }
            .mob-banner h2 { font-size: 1rem; }
            .mob-banner p { font-size: 0.75rem; }
            .mob-banner a { padding: 8px 18px; font-size: 0.8rem; }


            .mob-card { padding: 14px; margin-bottom: 12px; border-radius: 16px; }
            .mob-card h3 { font-size: 0.88rem; margin-bottom: 10px; }
            .mob-act-row { padding: 9px 0; gap: 10px; }
            .mob-act-icon { width: 30px; height: 30px; font-size: 0.9rem; border-radius: 8px; }
            .mob-act-type { font-size: 0.8rem; }
            .mob-act-date { font-size: 0.65rem; }


            .mob-lb-item { padding: 8px 0; gap: 10px; }
            .mob-lb-avatar { width: 28px; height: 28px; font-size: 0.65rem; }
            .mob-lb-name { font-size: 0.78rem; }
            .mob-lb-pts { font-size: 0.78rem; }


            .mob-about { padding: 12px 14px; border-radius: 12px; font-size: 0.75rem; }
            .mob-about a { font-size: 0.7rem; padding: 5px 10px; }
        }


        /* ============================================================
           DESKTOP LAYOUT (>= 768px)
           ============================================================ */
        @media (min-width: 768px) {
            .mobile-only { display: none; }
            .desktop-only { display: block; }


            .wrapper { max-width: 1280px; margin: 0 auto; padding: 40px 32px; }
            .white-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 24px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: background 0.4s ease, border-color 0.4s ease; }


            .hero-row { display: grid; grid-template-columns: 1fr 340px; gap: 32px; margin-bottom: 32px; align-items: stretch; }
            .greeting-card { display: flex; flex-direction: column; justify-content: center; }
            .page-greeting { font-size: 36px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
            .page-date { font-size: 14px; color: var(--text-muted); margin-top: 8px; }
            #rotating-quote { font-size: 15px; color: var(--brand-primary); font-weight: 600; margin-top: 16px; min-height: 24px; }


            .tier-card { display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden; background: linear-gradient(135deg, #FFFBEB, #FEF3C7); border: 1px solid #FDE68A; }
            body.dark-mode .tier-card { background: linear-gradient(135deg, #1a1200, #261a00) !important; border-color: #3d2c00 !important; }
            .tier-label { font-size: 11px; font-weight: 800; color: #92400E; text-transform: uppercase; letter-spacing: 0.1em; }
            body.dark-mode .tier-label { color: #a8b0a5; }
            .points-val { font-size: 44px; font-weight: 800; color: #D97706; margin: 12px 0; }
            body.dark-mode .points-val { color: #fbbf24; }
            .progress-bar-bg { height: 10px; background: rgba(0,0,0,0.05); border-radius: 99px; overflow: hidden; margin-top: 16px; }
            .progress-bar-fill { height: 100%; background: linear-gradient(90deg, #D97706, #F59E0B); width: 0%; transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1); }


            .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
            .stat-card { transition: 0.3s; }
            .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.04); }
            .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 20px; }
            .stat-val { font-size: 32px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
            .stat-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }


            .cta-banner { background: linear-gradient(135deg, #065F46, #1D9E75); border-radius: 24px; padding: 40px; margin-bottom: 32px; color: white; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 20px 40px rgba(29,158,117,0.15); }
            .cta-title { font-size: 28px; font-weight: 800; }
            .btn-cta { background: white; color: #065F46; padding: 16px 32px; border-radius: 14px; font-weight: 700; text-decoration: none; transition: 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .btn-cta:hover { transform: scale(1.05); }


            .content-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; }
            .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
            .section-title { font-size: 20px; font-weight: 800; color: var(--text-primary); }
           
            .activity-row { display: flex; align-items: center; gap: 16px; padding: 16px 0; border-bottom: 1px solid #f3f4f6; }
            body.dark-mode .activity-row { border-color: var(--border); }
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


            .about-strip { background: var(--brand-light); border: 1px solid var(--border); border-radius: 16px; padding: 18px 28px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top: 32px; }
            .about-strip .al { display:flex; align-items:center; gap:12px; }
            .about-strip .al .icon { width:40px; height:40px; background:var(--brand-primary); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:1.15rem; }
            .about-strip .al .name { font-size:0.9rem; font-weight:700; color:var(--text-primary); }
            .about-strip .al .sub { font-size:0.75rem; color:var(--text-muted); }
            .about-strip a { font-size:0.85rem; font-weight:600; color:var(--brand-primary); text-decoration:none; border:1px solid var(--brand-primary); padding:8px 18px; border-radius:8px; transition:0.2s; }
            .about-strip a:hover { background:var(--brand-primary); color:white; }
        }
    </style>
</head>
<body>


<!-- Shared navbar (has hamburger drawer for mobile) -->
<?php include 'includes/navbar.php'; ?>


<!-- ============================================================
     MOBILE CONTENT
     ============================================================ -->
<main class="mobile-only">
    <div class="mob-wrap">


        <div class="mob-hero">
            <div class="mob-hero-left">
                <div class="mob-greet">🌿 <?= $text['greet'] ?></div>
                <div class="mob-meta">
                    <span><?= date('D, M j') ?></span>
                    <span>·</span>
                    <span>Dhaka Impact Hub</span>
                </div>
            </div>
            <div class="mob-tier">🏆 <?= $tier ?> Tier</div>
        </div>


        <div class="q-grid">
            <a href="user_request_pickup.php" class="q-btn">
                <div class="q-icon" style="background:#dcfce7; color:#166534;">🚛</div>
                <div class="q-label"><?= $currentLang === 'bn' ? 'পিকআপ শিডিউল' : 'Schedule Pickup' ?></div>
            </a>
            <a href="shop.php" class="q-btn">
                <div class="q-icon" style="background:#dbeafe; color:#1e40af;">🛍</div>
                <div class="q-label"><?= $currentLang === 'bn' ? 'দোকান দেখুন' : 'Browse Shop' ?></div>
            </a>
            <a href="chatbot.php" class="q-btn">
                <div class="q-icon" style="background:#ede9fe; color:#5b21b6;">🤖</div>
                <div class="q-label"><?= $currentLang === 'bn' ? 'এআই সহায়ক' : 'AI Assistant' ?></div>
            </a>
        </div>


        <div class="mob-s-grid">
            <div class="mob-s-card">
                <div class="mob-s-val"><?= number_format($userPoints) ?></div>
                <div class="mob-s-label"><?= $currentLang === 'bn' ? 'পয়েন্ট' : 'Points' ?></div>
            </div>
            <div class="mob-s-card">
                <div class="mob-s-val"><?= $pData['completed'] ?></div>
                <div class="mob-s-label"><?= $currentLang === 'bn' ? 'পিকআপ' : 'Pickups' ?></div>
            </div>
            <div class="mob-s-card">
                <div class="mob-s-val"><?= $totalImpactKg ?> kg</div>
                <div class="mob-s-label"><?= $currentLang === 'bn' ? 'ইমপ্যাক্ট' : 'Impact' ?></div>
            </div>
        </div>


        <div class="mob-banner">
            <h2><?= $currentLang === 'bn' ? 'পরবর্তী পিকআপের জন্য প্রস্তুত?' : 'Ready for your next pickup?' ?></h2>
            <p><?= $currentLang === 'bn' ? 'এখনই শিডিউল করুন এবং বোনাস পয়েন্ট অর্জন করুন।' : 'Schedule now and earn bonus points.' ?></p>
            <a href="user_request_pickup.php"><?= $currentLang === 'bn' ? 'পিকআপ শিডিউল করুন →' : 'Schedule a Pickup →' ?></a>
        </div>


        <div class="mob-card">
            <h3><?= $currentLang === 'bn' ? 'সাম্প্রতিক কার্যকলাপ' : 'Recent Activity' ?></h3>
            <?php if(empty($activityData)): ?>
                <p style="text-align:center; padding:20px; color:var(--text-muted); font-size:0.85rem;"><?= $currentLang === 'bn' ? 'কোনো কার্যকলাপ নেই।' : 'No recent activity.' ?></p>
            <?php else: ?>
                <?php foreach($activityData as $act): ?>
                <div class="mob-act-row">
                    <div class="mob-act-icon" style="background:#e6f5ee; color:#1D9E75;"><i class="ti ti-package"></i></div>
                    <div style="flex:1;">
                        <div class="mob-act-type"><?= e($act['category']) ?></div>
                        <div class="mob-act-date"><?= date('M j, Y', strtotime($act['created_at'])) ?></div>
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


        <div class="mob-card">
            <h3>🏆 <?= $currentLang === 'bn' ? 'লিডারবোর্ড' : 'Leaderboard' ?></h3>
            <?php foreach($leaderboardData as $idx => $lb): ?>
            <div class="mob-lb-item">
                <span class="mob-lb-rank"><?= $idx+1 ?></span>
                <div class="mob-lb-avatar"><?= strtoupper(mb_substr($lb['name'], 0, 1)) ?></div>
                <span class="mob-lb-name"><?= e($lb['name']) ?></span>
                <span class="mob-lb-pts"><?= number_format($lb['lifetime_points']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>


        <div class="mob-about">
            <div>
                <span style="font-weight:600; color:var(--text-primary);">♻️ Notun Alo</span>
                <span style="color:var(--text-muted); font-size:0.7rem; margin-left:6px;"><?= $currentLang === 'bn' ? 'টিম ঘোস্টরাইডার্স' : 'Team GhostRiders' ?></span>
            </div>
            <a href="about.php"><?= $currentLang === 'bn' ? 'গল্প পড়ুন ↗' : 'Our Story ↗' ?></a>
        </div>


    </div>
</main>


<!-- Desktop content uses shared navbar above -->
<main class="desktop-only">
    <div class="wrapper">


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


        <div class="cta-banner">
            <div>
                <h2 class="cta-title"><?= $currentLang === 'bn' ? 'পরবর্তী পিকআপের জন্য প্রস্তুত?' : 'Ready for your next pickup?' ?></h2>
                <p style="opacity:0.9; margin-top:8px;"><?= $currentLang === 'bn' ? 'এখনই শিডিউল করুন এবং ৫০০ বোনাস পয়েন্ট অর্জন করুন।' : 'Schedule now and earn up to 500 bonus points this week.' ?></p>
            </div>
            <a href="user_request_pickup.php" class="btn-cta"><?= $text['req_pickup'] ?></a>
        </div>


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
                                $bg = $st==='completed' ? '#DCFCE7' : ($st==='pending' ? '#FEF3C7' : '#DBEAFE');
                                $cl = $st==='completed' ? '#166534' : ($st==='pending' ? '#92400E' : '#1E40AF');
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


        <div class="about-strip">
            <div class="al">
                <div class="icon">&#9851;</div>
                <div>
                    <div class="name"><?= $currentLang === 'bn' ? 'নতুন আলো' : 'Notun Alo' ?></div>
                    <div class="sub"><?= $currentLang === 'bn' ? 'টিম ঘোস্টরাইডার্স · ইউল্যাব' : 'Team GhostRiders &nbsp;·&nbsp; ULAB' ?></div>
                </div>
            </div>
            <a href="about.php"><?= $currentLang === 'bn' ? 'পুরো গল্প পড়ুন ↗' : 'Read Our Full Story ↗' ?></a>
        </div>
    </div>
</main>


<script>
// ── Theme and points counter logic ──
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


// ── Desktop: rotating quote ──
const quotes = ["Every pickup makes Dhaka cleaner. 🌿", "Small actions create massive change. ♻️", "You're building a greener future. ⭐"];
let qIdx = 0; const qEl = document.getElementById('rotating-quote');
if (qEl) {
    function rotate() { qEl.style.opacity = 0; setTimeout(() => { qEl.innerText = quotes[qIdx]; qEl.style.opacity = 1; qIdx = (qIdx + 1) % quotes.length; }, 500); }
    setInterval(rotate, 8000); rotate();
}


// ── Desktop: animate points ──
function animateValue(obj, start, end, duration) {
    if (!obj) return;
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
    const tp = document.getElementById('tier-progress');
    if (tp) setTimeout(() => { tp.style.width = '<?= $progressPercent ?>%'; }, 300);
};
</script>


</body>
</html>



