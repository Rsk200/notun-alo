<?php
require_once 'includes/config.php';
startSession();

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'user';
    redirect(match($role) {
        'admin'  => 'admin.php',
        'agency' => 'agency.php',
        default  => 'dashboard.php',
    });
}

$currentLang = $_SESSION['lang'] ?? 'en';
$t = function(string $en, string $bn) use ($currentLang): string {
    return $currentLang === 'bn' ? $bn : $en;
};

try {
    $products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    if (!isDatabaseInitialized($pdo)) redirect('init_db.php');
    throw $e;
}

$totalPointsQuery = $pdo->query("SELECT SUM(lifetime_points) as total FROM rewards");
$totalPointsData = $totalPointsQuery->fetch();
$totalPointsEarned = (int)($totalPointsData['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t('Notun Alo — Recycling for a Greener Bangladesh', 'নতুন আলো — একটি সবুজ বাংলাদেশের জন্য পুনর্ব্যবহার') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --about-bg: linear-gradient(180deg, #f8fdf5 0%, #ffffff 100%);
            --testimonial-bg: #ffffff;
            --pillar-bg: #ffffff;
            --pillar-border: #e5e7eb;
            --team-bg: linear-gradient(135deg,#f0fdf4,#dcfce7);
            --team-border: #bbf7d0;
            --team-box-bg: #ffffff;
            --hero-badge-bg: #dcfce7;
            --hero-badge-text: #166534;
        }
        body.dark-mode {
            --about-bg: linear-gradient(180deg, #0a1a12 0%, #0f1712 100%);
            --testimonial-bg: #0f1712;
            --pillar-bg: #0f1712;
            --pillar-border: #1f2e24;
            --team-bg: linear-gradient(135deg, #0a1f15, #0d2a18);
            --team-border: #1b4d2a;
            --team-box-bg: #0f1712;
            --team-box-bg: #0f1712;
            --hero-badge-bg: #0f2e1a;
            --hero-badge-text: #6ee7b7;
            --sage: #a8b0a5;
            --sage-deep: #88998a;
            --sage-dim: #6a7a6c;
        }
        body.dark-mode .section-title { color: var(--sage); }
        body.dark-mode .hero-v2-title { color: var(--sage); }
        body.dark-mode .hero-v2-title em { color: #c4d4c0; }
        body.dark-mode .hero-v2-sub { color: var(--sage-deep); }
        body.dark-mode .hero-v2-badge { background: #0f2e1a; color: #6ee7b7; }
        body.dark-mode .hero-v2-trust span { color: var(--sage-deep); }
        body.dark-mode .pillar-card h4 { color: var(--sage); }
        body.dark-mode .pillar-card p { color: var(--sage-deep); }
        body.dark-mode .testimonial-card { background: #0f1712; border-color: #1f2e24; }
        body.dark-mode .testimonial-card .testimonial-quote { color: var(--sage); }
        body.dark-mode .testimonial-card .testimonial-author { color: var(--sage-deep); }
        body.dark-mode .how-card-v2 { background: #0f1712; border-color: #1f2e24; }
        body.dark-mode .how-card-v2 h3 { color: var(--sage); }
        body.dark-mode .how-card-v2 p { color: var(--sage-deep); }
        body.dark-mode .how-number-bg { color: rgba(168,176,165,0.06); }
        body.dark-mode .stats-strip { background: #0a1a12; }
        body.dark-mode .stats-strip__label { color: var(--sage-dim); }
        body.dark-mode .stats-strip__value { color: var(--sage); }
        body.dark-mode .shop-preview { background: #0a1a12; }
        body.dark-mode .product-card-v2 { background: #0f1712; border-color: #1f2e24; }
        body.dark-mode .product-card-v2 .product-name { color: var(--sage); }
        body.dark-mode .product-card-v2 .product-desc { color: var(--sage-deep); }
        body.dark-mode .pts-badge-float { background: #1a2e24; color: #c4d4c0; }
        body.dark-mode .empty-state p { color: var(--sage-deep); }
        body.dark-mode #landingNoResults p { color: var(--sage-deep); }
        body.dark-mode .footer-v2 { background: #0a1a12; border-top-color: #1a2e24; }
        body.dark-mode .footer-v2 .footer-tag { color: var(--sage-deep); }
        body.dark-mode .footer-v2 .footer-heading { color: var(--sage); }
        body.dark-mode .footer-v2 .footer-links a { color: var(--sage-deep); }
        body.dark-mode .footer-bottom { color: var(--sage-dim); border-top-color: #1a2e24; }
        body.dark-mode .ticker-wrap { background: #0a1a12; border-color: #1a2e24; }
        body.dark-mode .ticker-content { color: #6ee7b7; }
        body.dark-mode .hero-float-card { background: #0f1712; border: 1px solid #1f2e24; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        body.dark-mode .hero-float-card .pts-label { color: var(--sage-dim); }
        body.dark-mode .hero-float-card .pts-value { color: var(--sage); }
        body.dark-mode .hero-float-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.4); }

        /* ── Hero Animations ── */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes glow {
            0%, 100% { box-shadow: 0 4px 20px rgba(163,230,53,0.15); }
            50% { box-shadow: 0 6px 28px rgba(163,230,53,0.25); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .hero-v2-content { animation: slideUp 0.8s ease-out; }
        .hero-v2-badge { animation: fadeIn 1s ease-out 0.3s both; }
        .hero-v2-title { animation: slideUp 0.8s ease-out 0.2s both; }
        .hero-v2-sub { animation: slideUp 0.8s ease-out 0.4s both; }
        .hero-v2-actions { animation: slideUp 0.8s ease-out 0.6s both; }
        .hero-v2-trust { animation: slideUp 0.8s ease-out 0.8s both; }
        .hero-float-card {
            animation: float 3s ease-in-out infinite;
            background: white;
            border-radius: 16px;
            padding: 16px 22px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s;
        }
        body.dark-mode .hero-float-card { background: #1a2320; border: 1px solid #374151; }
        .hero-float-card:hover { transform: scale(1.05); box-shadow: 0 12px 40px rgba(0,0,0,0.15); }
        .hero-float-card .pts-circle {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 0.85rem;
            flex-shrink: 0;
        }
        .hero-float-card .pts-label { color: #6b7280; font-weight: 400; font-size: 0.8rem; }
        body.dark-mode .hero-float-card .pts-label { color: #9ca3af; }
        .hero-float-card .pts-value { font-weight: 800; font-size: 1.1rem; }
        .float-card-1 { animation-delay: 0s; }
        .float-card-2 { animation-delay: 0.5s; }
        .float-card-3 { animation-delay: 1s; }

        /* Shop Pagination */
        .shop-pagination .pg-btn { border: 1px solid var(--border); background: var(--card-bg, white); color: var(--text-secondary); padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .shop-pagination .pg-btn:hover:not(:disabled) { background: var(--bg-subtle, #f9fafb); color: var(--brand-primary); }
        .shop-pagination .pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .shop-pagination .pg-num { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; cursor: pointer; background: var(--card-bg, white); color: var(--text-secondary); }
        .shop-pagination .pg-num.active { background: var(--brand-primary); color: white; border-color: var(--brand-primary); }
        .shop-pagination .pg-num:hover:not(.active) { background: var(--bg-subtle, #f9fafb); }
        body.dark-mode .shop-pagination .pg-btn { background: #0f1712; border-color: #1f2e24; color: var(--sage-deep); }
        body.dark-mode .shop-pagination .pg-num { background: #0f1712; border-color: #1f2e24; color: var(--sage-deep); }
        body.dark-mode .shop-pagination .pg-num.active { background: var(--brand-primary); color: #fff; border-color: var(--brand-primary); }
        body.dark-mode .shop-pagination .pg-btn:hover:not(:disabled) { background: #1a2e24; color: var(--sage); }
        body.dark-mode .shop-pagination .pg-num:hover:not(.active) { background: #1a2e24; }
    </style>
</head>
<body class="landing-body">

<!-- Navbar -->
<nav class="navbar navbar--transparent" id="mainNavbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <div class="nav-logo-wrap">♻</div>
            <div class="nav-name-wrap">
                <span class="nav-name">Notun Alo</span>
                <span class="nav-tagline"><?= $t('Recycling', 'পুনর্ব্যবহার') ?></span>
            </div>
        </a>
        <div class="nav-right" style="gap: 0.75rem;">
            <a href="?lang=bn" style="color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem; opacity: 0.85; padding: 4px 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; <?= $currentLang === 'bn' ? 'background:rgba(255,255,255,0.15);' : '' ?>">বাং</a>
            <a href="?lang=en" style="color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem; opacity: 0.85; padding: 4px 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; <?= $currentLang === 'en' ? 'background:rgba(255,255,255,0.15);' : '' ?>">EN</a>
            <span id="themeToggleLanding" style="cursor:pointer; color:white; font-size:1.1rem; opacity:0.8;">🌙</span>
            <a href="login.php" style="color: white; text-decoration: none; font-weight: 600; font-size: 0.95rem; opacity: 0.85;"><?= $t('Login', 'লগইন') ?></a>
            <a href="register.php" class="btn btn-primary btn-sm"><?= $t('Get Started', 'নিবন্ধন') ?></a>
        </div>
    </div>
</nav>

<!-- Preloader -->
<div id="preloader">
    <div class="preloader-inner">
        <div class="preloader-icon">♻</div>
        <div class="preloader-name">Notun Alo</div>
        <div class="preloader-bar"><div class="preloader-fill"></div></div>
    </div>
</div>

<!-- Hero -->
<section class="hero-v2">
    <div class="hero-v2-bg"></div>
    <div class="hero-particle" style="left: 10%; animation-duration: 12s;"></div>
    <div class="hero-particle" style="left: 30%; animation-duration: 18s; animation-delay: 2s;"></div>
    <div class="hero-particle" style="left: 50%; animation-duration: 15s; animation-delay: 1s;"></div>
    <div class="hero-particle" style="left: 70%; animation-duration: 10s; animation-delay: 3s;"></div>
    <div class="hero-particle" style="left: 85%; animation-duration: 14s; animation-delay: 0s;"></div>

    <!-- Background rotating emoji behind headline -->
    <div style="position: absolute; top: 16%; left: 50%; transform: translateX(-50%); width: 100%; display: flex; justify-content: center; pointer-events: none; user-select: none; z-index: 0;">
        <div style="font-size: 24rem; opacity: 0.06; animation: spin 25s linear infinite; line-height: 1;">♻️</div>
    </div>

    <div class="container" style="position: relative; width: 100%; z-index: 1;">
        <div class="hero-v2-content" style="text-align: center; max-width: 800px; margin: 0 auto;">
            <span class="hero-v2-badge">🌿 <?= $t("Bangladesh's #1 Recycling Platform — Buildfest 2026", "বাংলাদেশের #১ পুনর্ব্যবহার প্ল্যাটফর্ম — বিল্ডফেস্ট ২০২৬") ?></span>
            <h1 class="hero-v2-title"><?= $t('Recycle. Earn.<br>Build <em>Tomorrow.</em>', 'পুনর্ব্যবহার করুন। উপার্জন করুন।<br>গড়ুন <em>ভবিষ্যত।</em>') ?></h1>
            <p class="hero-v2-sub"><?= $t('The smartest way to handle household waste. Schedule pickups at your doorstep, earn valuable reward points, and shop for sustainable goods.', 'গৃহস্থালির বর্জ্য ব্যবস্থাপনার সবচেয়ে বুদ্ধিমান উপায়। আপনার দরজায় পিকআপ শিডিউল করুন, মূল্যবান রিওয়ার্ড পয়েন্ট অর্জন করুন এবং টেকসই পণ্য কিনুন।') ?></p>
            <div class="hero-v2-actions" style="justify-content: center;">
                <a href="register.php" class="btn btn-lg" style="background: #a3e635; color: #14532d; font-weight: 800; border: none; border-radius: 999px; box-shadow: 0 4px 16px rgba(163,230,53,0.2); transition: all 0.3s ease; padding: 14px 36px;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(163,230,53,0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 16px rgba(163,230,53,0.2)';"><?= $t('Join the Revolution →', 'আন্দোলনে যোগ দিন →') ?></a>
                <a href="#how" class="btn btn-outline btn-lg" style="border-radius: 999px;"><?= $t('See How it Works', 'কিভাবে কাজ করে দেখুন') ?></a>
            </div>
            <div class="hero-v2-trust" style="justify-content: center;">
                <span>&#10003; <?= $t('Free Signup', 'বিনামূল্যে নিবন্ধন') ?></span>
                <span>&#10003; <?= $t('Doorstep Pickup', 'দরজায় পিকআপ') ?></span>
                <span>&#10003; <?= $t('Instant Points', 'তাৎক্ষণিক পয়েন্ট') ?></span>
            </div>
        </div>

        <!-- Rate Cards Row -->
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 48px; flex-wrap: wrap; position: relative; z-index: 2;">
            <div class="hero-float-card float-card-1">
                <div class="pts-circle" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #166534;">📄</div>
                <div>
                    <div class="pts-value" style="color: #166534;"><?= $t('Paper', 'কাগজ') ?></div>
                    <div class="pts-label"><?= $t('5 pts / kg', '৫ পয়েন্ট/কেজি') ?></div>
                </div>
            </div>
            <div class="hero-float-card float-card-2">
                <div class="pts-circle" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af;">🧴</div>
                <div>
                    <div class="pts-value" style="color: #1e40af;"><?= $t('Plastic', 'প্লাস্টিক') ?></div>
                    <div class="pts-label"><?= $t('8 pts / kg', '৮ পয়েন্ট/কেজি') ?></div>
                </div>
            </div>
            <div class="hero-float-card float-card-3">
                <div class="pts-circle" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e;">🔩</div>
                <div>
                    <div class="pts-value" style="color: #92400e;"><?= $t('Metal', 'ধাতু') ?></div>
                    <div class="pts-label"><?= $t('12 pts / kg', '১২ পয়েন্ট/কেজি') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-v2-wave">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none">
            <path d="M0,32L60,42.7C120,53,240,75,360,74.7C480,75,600,53,720,42.7C840,32,960,32,1080,42.7C1200,53,1320,75,1380,85.3L1440,96L1440,120L1380,120C1320,120,1200,120,1080,120C960,120,840,120,720,120C600,120,480,120,360,120C240,120,120,120,60,120L0,120Z"></path>
        </svg>
    </div>
</section>

<!-- Impact Ticker -->
<div class="ticker-wrap">
    <div class="ticker-content">
        ♻ <?= $t('8,000 tons of waste generated daily in Bangladesh', 'বাংলাদেশে প্রতিদিন ৮,০০০ টন বর্জ্য উৎপন্ন হয়') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        🌱 <?= $t('Only 10% currently recycled', 'মাত্র ১০% পুনর্ব্যবহার করা হয়') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        💡 <?= $t('Join Notun Alo — be the change', 'নতুন আলোতে যোগ দিন — পরিবর্তন হোন') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        🏆 <?= $t('Earn while helping the planet', 'গ্রহকে সাহায্য করার সময় উপার্জন করুন') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        ♻ <?= $t('8,000 tons of waste generated daily in Bangladesh', 'বাংলাদেশে প্রতিদিন ৮,০০০ টন বর্জ্য উৎপন্ন হয়') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        🌱 <?= $t('Only 10% currently recycled', 'মাত্র ১০% পুনর্ব্যবহার করা হয়') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        💡 <?= $t('Join Notun Alo — be the change', 'নতুন আলোতে যোগ দিন — পরিবর্তন হোন') ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    </div>
</div>

<!-- How It Works -->
<section id="how" class="section">
    <div class="container">
        <h2 class="section-title" data-reveal><?= $t('How It Works', 'কিভাবে কাজ করে') ?></h2>
        <div class="how-grid-v2">
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">1</div>
                <div class="how-icon">📅</div>
                <h3><?= $t('Schedule Pickup', 'পিকআপ শিডিউল করুন') ?></h3>
                <p><?= $t('Choose your waste type, estimate weight, and pick a convenient date for collection.', 'আপনার বর্জ্যের ধরন বেছে নিন, ওজন অনুমান করুন এবং সংগ্রহের জন্য একটি সুবিধাজনক তারিখ নির্বাচন করুন।') ?></p>
            </div>
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">2</div>
                <div class="how-icon">🚛</div>
                <h3><?= $t('We Collect', 'আমরা সংগ্রহ করি') ?></h3>
                <p><?= $t('Our partner agencies arrive at your doorstep to collect and weigh your recyclables.', 'আমাদের অংশীদার সংস্থাগুলি আপনার পুনর্ব্যবহারযোগ্য জিনিস সংগ্রহ এবং ওজন করতে আপনার দরজায় পৌঁছে।') ?></p>
            </div>
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">3</div>
                <div class="how-icon">🏆</div>
                <h3><?= $t('Earn Points', 'পয়েন্ট অর্জন করুন') ?></h3>
                <p><?= $t('Points are automatically credited to your account based on the weight collected.', 'সংগৃহীত ওজনের ভিত্তিতে পয়েন্টগুলি স্বয়ংক্রিয়ভাবে আপনার অ্যাকাউন্টে জমা হয়।') ?></p>
            </div>
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">4</div>
                <div class="how-icon">🛍</div>
                <h3><?= $t('Shop Eco Goods', 'ইকো পণ্য কিনুন') ?></h3>
                <p><?= $t('Spend your points in our Upcycle Shop for handcrafted eco-friendly products.', 'আমাদের আপসাইকেল শপে হস্তশিল্প ইকো-বন্ধুত্বপূর্ণ পণ্যের জন্য আপনার পয়েন্ট ব্যয় করুন।') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Strip -->
<section class="stats-strip">
    <div class="container">
        <div class="stats-strip-grid">
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="122">0</span>M+</div>
                <div class="stats-strip__label"><?= $t('Bangladeshis with a recycling gap', 'পুনর্ব্যবহারের ব্যবধান রয়েছে এমন বাংলাদেশী') ?></div>
            </div>
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="5745">0</span> <?= $t('Tons', 'টন') ?></div>
                <div class="stats-strip__label"><?= $t('Waste generated daily in Dhaka', 'ঢাকায় প্রতিদিন বর্জ্য উৎপন্ন') ?></div>
            </div>
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="2">0</span></div>
                <div class="stats-strip__label"><?= $t('Materials we collect: Paper, Plastic, More!', 'আমরা সংগ্রহ করি: কাগজ, প্লাস্টিক, আরও অনেক কিছু!') ?></div>
            </div>
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="<?= $totalPointsEarned ?>">0</span>+</div>
                <div class="stats-strip__label"><?= $t('Points earned by households', 'পরিবারগুলি দ্বারা অর্জিত পয়েন্ট') ?></div>
            </div>
        </div>
    </div>
</section>

<!-- Shop Preview -->
<section class="section shop-preview">
    <div class="container">
        <h2 class="section-title" data-reveal><?= $t('Explore Our Shop', 'আমাদের দোকান দেখুন') ?></h2>

        <?php if (!empty($products)): ?>
        <div class="shop-search-wrap" data-reveal style="display:flex; gap:12px; flex-wrap:wrap;">
            <div class="shop-search-inner" style="flex:1; min-width:200px;">
                <span class="shop-search-icon">🔍</span>
                <input type="text" id="landingShopSearch" placeholder="<?= $t('Search products…', 'পণ্য খুঁজুন…') ?>" class="shop-search-input" autocomplete="off">
                <button class="shop-search-clear" id="landingSearchClear" aria-label="Clear search">✕</button>
            </div>
            <select id="landingCatFilter" style="padding: 12px 20px; border-radius: 30px; border: 2px solid var(--border); font-size: 0.95rem; background: var(--card-bg, white); color: var(--text-primary, #111); outline: none; cursor: pointer; min-width: 170px;">
                <option value="ALL"><?= $t('All Categories', 'সব বিভাগ') ?></option>
                <?php
                $cats = [];
                foreach ($products as $p) {
                    $c = $p['category'] ?? 'General';
                    if (!in_array($c, $cats)) $cats[] = $c;
                }
                sort($cats);
                foreach ($cats as $c) {
                    echo '<option value="' . e($c) . '">' . e($c) . '</option>';
                }
                ?>
            </select>
        </div>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <div class="empty-state" data-reveal>
                <div class="empty-icon">🌱</div>
                <p><?= $t('Shop coming soon.', 'শীঘ্রই দোকান আসছে।') ?></p>
            </div>
        <?php else: ?>
        <div class="product-grid" id="landingProductGrid">
            <?php foreach ($products as $prod): ?>
            <div class="product-card-v2 <?= (int)($prod['stock'] ?? 0) === 0 ? 'product-card--oos' : '' ?> product-card-v2-landing"
                 data-name="<?= strtolower(e($prod['name'])) ?>"
                 data-desc="<?= strtolower(e($prod['description'])) ?>"
                 data-category="<?= e($prod['category'] ?? 'General') ?>">
                <div class="product-img-wrap">
                    <?php if ($prod['image_url']): ?>
                        <img src="<?= e($prod['image_url']) ?>" alt="<?= e($prod['name']) ?>" class="product-img" loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">🌿</div>
                    <?php endif; ?>
                    <div class="pts-badge-float">🏆 <?= number_format($prod['price_points']) ?> pts</div>
                    <?php if ((int)($prod['stock'] ?? 0) === 0): ?>
                        <div class="oos-overlay"><?= $t('Out of Stock', 'স্টকে নেই') ?></div>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <h3 class="product-name"><?= e($prod['name']) ?></h3>
                    <p class="product-desc"><?= e($prod['description']) ?></p>
                    <div class="product-actions">
                        <a href="login.php" class="btn btn-outline btn-full" style="color:var(--green-dark); border-color:var(--border);"><?= $t('Login to Buy', 'কিনতে লগইন করুন') ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="landingNoResults" style="display:none; text-align:center; padding:3rem 0; color:var(--text-muted);">
            <div style="font-size:2.5rem; margin-bottom:0.75rem;">🔍</div>
            <p style="font-size:1.1rem;"><?= $t('No products found matching your search.', 'আপনার অনুসন্ধানের সাথে মেলে এমন কোন পণ্য পাওয়া যায়নি।') ?></p>
        </div>
        <div id="landingShopPagination" class="shop-pagination" style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:32px;"></div>
        <?php endif; ?>
    </div>
</section>

<!-- Testimonials -->
<section class="section" style="background: var(--testimonial-bg); transition: background 0.3s;">
    <div class="container">
        <h2 class="section-title" data-reveal><?= $t('Community Impact', 'কমিউনিটি ইমপ্যাক্ট') ?></h2>
        <div class="testimonial-grid">
            <div class="testimonial-card" data-reveal>
                <div class="testimonial-quote"><?= $t('"Every kilogram of plastic I recycle brings me closer to earning eco-products I love."', '"আমি যে প্রতিটি কেজি প্লাস্টিক পুনর্ব্যবহার করি তা আমাকে আমার পছন্দের ইকো-পণ্য অর্জনের কাছাকাছি নিয়ে আসে।"') ?></div>
                <div class="testimonial-author"><?= $t('— Rina Akter, Dhaka', '— রিনা আক্তার, ঢাকা') ?></div>
                <div class="testimonial-icon">🌿</div>
            </div>
            <div class="testimonial-card" data-reveal>
                <div class="testimonial-quote"><?= $t('"Finally a platform that turns my recyclables into real rewards."', '"অবশেষে একটি প্ল্যাটফর্ম যা আমার পুনর্ব্যবহারযোগ্য জিনিসকে বাস্তব পুরস্কারে পরিণত করে।"') ?></div>
                <div class="testimonial-author"><?= $t('— Karim Hossain, Chittagong', '— করিম হোসেন, চট্টগ্রাম') ?></div>
                <div class="testimonial-icon">🌱</div>
            </div>
            <div class="testimonial-card" data-reveal>
                <div class="testimonial-quote"><?= $t('"Our agency now has a structured system to collect and process waste efficiently."', '"আমাদের এজেন্সির এখন দক্ষতার সাথে বর্জ্য সংগ্রহ এবং প্রক্রিয়াকরণের জন্য একটি কাঠামোগত ব্যবস্থা রয়েছে।"') ?></div>
                <div class="testimonial-author"><?= $t('— Fatima Rahman, Agency Partner', '— ফাতিমা রহমান, এজেন্সি পার্টনার') ?></div>
                <div class="testimonial-icon">♻</div>
            </div>
        </div>
    </div>
</section>

<!-- About Us -->
<section id="about" class="section" style="background: var(--about-bg);">
    <div class="container">
        <div style="text-align:center; margin-bottom: 64px;" data-reveal>
            <span style="display:inline-block; background:var(--hero-badge-bg); color:var(--hero-badge-text); font-size:0.8rem; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; padding:6px 18px; border-radius:99px; margin-bottom:20px;"><?= $t('Our Story', 'আমাদের গল্প') ?></span>
            <h2 class="section-title" style="margin-bottom:20px;"><?= $t('Why Notun Alo Exists', 'কেন নতুন আলো বিদ্যমান') ?></h2>
            <p style="max-width:700px; margin:0 auto; font-size:1.1rem; line-height:1.8;"><?= $t('A movement born from a simple question — ', 'একটি সহজ প্রশ্ন থেকে জন্ম নেওয়া একটি আন্দোলন — ') ?><em style="color:#16a34a; font-weight:600;"><?= $t('what if every piece of waste was a beginning, not an end?', 'যদি প্রতিটি বর্জ্য একটি শেষ না হয়ে একটি শুরু হয়?') ?></em></p>
        </div>

        <div style="background: linear-gradient(135deg, #064e3b, #065f46, #0d7556); border-radius:28px; padding: 56px 64px; color:white; margin-bottom:56px; position:relative; overflow:hidden; box-shadow: 0 30px 80px rgba(6,78,59,0.35);" data-reveal>
            <div style="position:absolute; width:350px; height:350px; background:rgba(52,211,153,0.12); border-radius:50%; top:-100px; right:-80px; filter:blur(60px);"></div>
            <div style="position:absolute; width:250px; height:250px; background:rgba(163,230,53,0.1); border-radius:50%; bottom:-80px; left:-60px; filter:blur(50px);"></div>
            <div style="position:relative; z-index:1;">
                <div style="font-size:3rem; margin-bottom:24px;">&#9851;&#65039;</div>
                <h3 style="font-family:'Playfair Display', serif; font-size:clamp(1.4rem, 3vw, 2.2rem); font-weight:700; margin-bottom:28px; line-height:1.4;"><?= $t('"Every day, Dhaka alone generates over 5,700 tons of waste. ', '"প্রতিদিন, শুধু ঢাকাই উৎপন্ন করে ৫,৭০০ টনের বেশি বর্জ্য। ') ?><span style="color:#a3e635;"><?= $t('We decided to turn that crisis into an opportunity."', 'আমরা সেই সংকটকে সুযোগে পরিণত করেছি।"') ?></span></h3>
                <p style="font-size:1.05rem; opacity:0.9; line-height:1.9; max-width:800px; margin-bottom:20px;"><?= $t('Notun Alo — meaning ', 'নতুন আলো — বাংলায় যার অর্থ ') ?><strong style="color:#6ee7b7;"><?= $t('"New Light"', '"নতুন আলো"') ?></strong><?= $t(' in Bengali — was built by a team of passionate students who saw a broken system and chose to fix it. Bangladesh produces millions of tons of recyclable waste each year, yet only a fraction is recovered. The gap exists not because people don\'t care — it\'s because ', ' — তৈরি করেছে একদল আবেগী শিক্ষার্থী যারা একটি ভাঙা ব্যবস্থা দেখেছে এবং এটি ঠিক করতে এগিয়ে এসেছে। বাংলাদেশ প্রতি বছর লক্ষ লক্ষ টন পুনর্ব্যবহারযোগ্য বর্জ্য উৎপাদন করে, অথচ তার সামান্য অংশই পুনরুদ্ধার হয়। এই ব্যবধানটি বিদ্যমান কারণ মানুষ যত্ন নেয় না — বরং ') ?><em><?= $t('there was no easy, rewarding way to act.', 'কাজ করার সহজ ও পুরস্কৃত উপায় ছিল না।') ?></em></p>
                <p style="font-size:1.05rem; opacity:0.9; line-height:1.9; max-width:800px;"><?= $t('We built a bridge. A platform where households become heroes, where waste becomes worth, and where every kilogram recycled lights up a greener future for all of Bangladesh.', 'আমরা একটি সেতু তৈরি করেছি। একটি প্ল্যাটফর্ম যেখানে পরিবারগুলি হিরো হয়ে ওঠে, যেখানে বর্জ্য মূল্যবান হয়, এবং যেখানে প্রতিটি কেজি পুনর্ব্যবহার সমগ্র বাংলাদেশের জন্য একটি সবুজ ভবিষ্যত আলোকিত করে।') ?></p>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:28px; margin-bottom:56px;">
            <div style="background:var(--pillar-bg); border:1px solid var(--pillar-border); border-radius:20px; padding:36px 28px; box-shadow:0 4px 20px rgba(0,0,0,0.05); transition:transform 0.3s, box-shadow 0.3s;" data-reveal onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 16px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.05)';">
                <div style="width:56px; height:56px; background:#dcfce7; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:20px;">&#127758;</div>
                <h4 style="font-size:1.15rem; font-weight:700; margin-bottom:12px;"><?= $t('Our Mission', 'আমাদের লক্ষ্য') ?></h4>
                <p style="line-height:1.75; font-size:0.95rem;"><?= $t('To make responsible waste disposal accessible, rewarding, and community-driven for every household in Bangladesh — starting from Dhaka and scaling to the nation.', 'প্রত্যেক বাংলাদেশী পরিবারের জন্য দায়িত্বশীল বর্জ্য নিষ্কাশনকে সহজলভ্য, পুরস্কৃত এবং কমিউনিটি-চালিত করা — ঢাকা থেকে শুরু করে সারা দেশে সম্প্রসারণ।') ?></p>
            </div>
            <div style="background:var(--pillar-bg); border:1px solid var(--pillar-border); border-radius:20px; padding:36px 28px; box-shadow:0 4px 20px rgba(0,0,0,0.05); transition:transform 0.3s, box-shadow 0.3s;" data-reveal onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 16px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.05)';">
                <div style="width:56px; height:56px; background:#dbeafe; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:20px;">&#128301;</div>
                <h4 style="font-size:1.15rem; font-weight:700; margin-bottom:12px;"><?= $t('Our Vision', 'আমাদের দৃষ্টিভঙ্গি') ?></h4>
                <p style="line-height:1.75; font-size:0.95rem;"><?= $t('A Bangladesh where circular economy principles are woven into daily life — where every citizen is empowered as an environmental steward, and recycling is as natural as breathing.', 'একটি বাংলাদেশ যেখানে সার্কুলার ইকোনমি নীতিগুলি দৈনন্দিন জীবনে বোনা — যেখানে প্রতিটি নাগরিক পরিবেশের রক্ষক হিসাবে ক্ষমতায়িত এবং পুনর্ব্যবহার শ্বাস নেওয়ার মতোই স্বাভাবিক।') ?></p>
            </div>
            <div style="background:var(--pillar-bg); border:1px solid var(--pillar-border); border-radius:20px; padding:36px 28px; box-shadow:0 4px 20px rgba(0,0,0,0.05); transition:transform 0.3s, box-shadow 0.3s;" data-reveal onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 16px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.05)';">
                <div style="width:56px; height:56px; background:#fef3c7; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:20px;">&#128161;</div>
                <h4 style="font-size:1.15rem; font-weight:700; margin-bottom:12px;"><?= $t('Our Motivation', 'আমাদের প্রেরণা') ?></h4>
                <p style="line-height:1.75; font-size:0.95rem;"><?= $t('We are students who refused to accept the status quo. Climate urgency, the SDG goals for 2030, and the raw potential of Bangladesh\'s people ignited us to build something that matters beyond the classroom.', 'আমরা এমন ছাত্র যারা স্থিতাবস্থা মেনে নিতে অস্বীকার করেছি। জলবায়ু জরুরিতা, ২০৩০ সালের এসডিজি লক্ষ্যমাত্রা এবং বাংলাদেশের জনগণের কাঁচা সম্ভাবনা আমাদের ক্লাসরুমের বাইরে কিছু তৈরি করতে প্রজ্বলিত করেছে।') ?></p>
            </div>
            <div style="background:var(--pillar-bg); border:1px solid var(--pillar-border); border-radius:20px; padding:36px 28px; box-shadow:0 4px 20px rgba(0,0,0,0.05); transition:transform 0.3s, box-shadow 0.3s;" data-reveal onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 16px 40px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 20px rgba(0,0,0,0.05)';">
                <div style="width:56px; height:56px; background:#ede9fe; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:20px;">&#129309;</div>
                <h4 style="font-size:1.15rem; font-weight:700; margin-bottom:12px;"><?= $t('Our Promise', 'আমাদের অঙ্গীকার') ?></h4>
                <p style="line-height:1.75; font-size:0.95rem;"><?= $t('We promise transparency, fairness, and impact. Every point you earn is real. Every pickup counts. Every eco-product in our shop was chosen because it represents what responsible commerce should look like.', 'আমরা স্বচ্ছতা, ন্যায্যতা এবং প্রভাবের প্রতিশ্রুতি দিই। আপনার অর্জিত প্রতিটি পয়েন্ট বাস্তব। প্রতিটি পিকআপ গণনা করে। আমাদের দোকানের প্রতিটি ইকো-পণ্য বেছে নেওয়া হয়েছে কারণ এটি দায়িত্বশীল বাণিজ্যের প্রতিনিধিত্ব করে।') ?></p>
            </div>
        </div>

        <div style="background:var(--team-bg); border:1px solid var(--team-border); border-radius:20px; padding:40px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:24px;" data-reveal>
            <div>
                <div style="font-size:0.8rem; font-weight:700; color:#166534; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px;"><?= $t('Built With ❤️ By', 'নির্মিত হয়েছে ❤️ দ্বারা') ?></div>
                <div style="font-size:1.5rem; font-weight:800; color:#064e3b;"><?= $t('Team GhostRiders', 'টিম ঘোস্টরাইডার্স') ?></div>
                <div style="color:#4b5563; margin-top:4px; font-size:0.95rem;"><?= $t('University of Liberal Arts Bangladesh', 'ইউনিভার্সিটি অফ লিবারেল আর্টস বাংলাদেশ') ?></div>
                <div style="color:#16a34a; font-weight:600; font-size:0.9rem; margin-top:4px;"><?= $t('THE INFINITY AI BUILDFEST 2026', 'দ্য ইনফিনিটি এআই বিল্ডফেস্ট ২০২৬') ?></div>
            </div>
            <div style="display:flex; gap:16px; flex-wrap:wrap;">
                <div style="text-align:center; background:var(--team-box-bg); border-radius:14px; padding:16px 24px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                    <div style="font-size:1.6rem; font-weight:800; color:#16a34a;">&#9851;</div>
                    <div style="font-size:0.75rem; margin-top:4px;"><?= $t('Circular Economy', 'সার্কুলার ইকোনমি') ?></div>
                </div>
                <div style="text-align:center; background:var(--team-box-bg); border-radius:14px; padding:16px 24px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                    <div style="font-size:1.6rem; font-weight:800; color:#2563eb;">&#129302;</div>
                    <div style="font-size:0.75rem; margin-top:4px;"><?= $t('AI-Powered', 'এআই-চালিত') ?></div>
                </div>
                <div style="text-align:center; background:var(--team-box-bg); border-radius:14px; padding:16px 24px; box-shadow:0 2px 10px rgba(0,0,0,0.06);">
                    <div style="font-size:1.6rem; font-weight:800; color:#d97706;">&#127942;</div>
                    <div style="font-size:0.75rem; margin-top:4px;"><?= $t('Reward-First', 'পুরস্কার-ভিত্তিক') ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-v2">
    <div class="cta-v2-bg"></div>
    <div class="container cta-v2-content">
        <h2 data-reveal><?= $t('Ready to make a difference?', 'পরিবর্তনের অংশ হতে প্রস্তুত?') ?></h2>
        <p data-reveal style="font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto;"><?= $t('Join thousands of Bangladeshis building a greener future, one pickup at a time.', 'হাজার হাজার বাংলাদেশীর সাথে যান একটি সবুজ ভবিষ্যত গড়তে, এক পিকআপ করে।') ?></p>
        <div class="cta-v2-actions" data-reveal>
            <a href="register.php" class="btn btn-gold btn-lg"><?= $t('Start Recycling Free →', 'বিনামূল্যে শুরু করুন →') ?></a>
            <a href="#how" class="btn btn-outline btn-lg"><?= $t('Learn More', 'আরও জানুন') ?></a>
        </div>
        <div class="cta-v2-ticks" data-reveal>
            <span>&#10003; <?= $t('No credit card required', 'ক্রেডিট কার্ডের প্রয়োজন নেই') ?></span>
            <span>&#10003; <?= $t('Free forever for households', 'পরিবারের জন্য চিরকাল বিনামূল্যে') ?></span>
            <span>&#10003; <?= $t('Points never expire', 'পয়েন্ট কখনো মেয়াদ শেষ হয় না') ?></span>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer-v2">
    <div class="container">
        <div class="footer-v2-grid">
            <div class="footer-col" data-reveal>
                <a href="index.php" class="footer-brand">♻ Notun Alo</a>
                <p class="footer-tag"><?= $t("Building Bangladesh's circular economy — turning household waste into lasting eco-rewards.", 'বাংলাদেশের সার্কুলার ইকোনমি গড়ে তোলা — গৃহস্থালির বর্জ্যকে টেকসই ইকো-পুরস্কারে রূপান্তর করা।') ?></p>
            </div>
            <div class="footer-col" data-reveal>
                <h4 class="footer-heading"><?= $t('Quick Links', 'দ্রুত লিংক') ?></h4>
                <ul class="footer-links">
                    <li><a href="index.php"><?= $t('Home', 'হোম') ?></a></li>
                    <li><a href="login.php"><?= $t('Login', 'লগইন') ?></a></li>
                    <li><a href="register.php"><?= $t('Register', 'নিবন্ধন') ?></a></li>
                    <li><a href="shop.php"><?= $t('Upcycle Shop', 'আপসাইকেল শপ') ?></a></li>
                </ul>
            </div>
            <div class="footer-col" data-reveal>
                <h4 class="footer-heading"><?= $t('Hackathon', 'হ্যাকাথন') ?></h4>
                <p style="opacity: 0.8; font-size: 0.9rem; line-height: 1.6;"><?= $t('Built by Team GhostRiders<br>University of Liberal Arts Bangladesh<br>THE INFINITY AI BUILDFEST 2026', 'নির্মিত হয়েছে টিম ঘোস্টরাইডার্স দ্বারা<br>ইউনিভার্সিটি অফ লিবারেল আর্টস বাংলাদেশ<br>দ্য ইনফিনিটি এআই বিল্ডফেস্ট ২০২৬') ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> Notun Alo (নতুন আলো). <?= $t('Built for a greener Bangladesh. 🌱', 'একটি সবুজ বাংলাদেশের জন্য নির্মিত। 🌱') ?>
        </div>
    </div>
</footer>

<script>
(function () {
    const input   = document.getElementById('landingShopSearch');
    const clear   = document.getElementById('landingSearchClear');
    const grid    = document.getElementById('landingProductGrid');
    const noRes   = document.getElementById('landingNoResults');
    const pagWrap = document.getElementById('landingShopPagination');
    const catSel  = document.getElementById('landingCatFilter');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('.product-card-v2-landing'));
    const itemsPerPage = 9;
    const prevText = "<?= $currentLang === 'bn' ? '← পূর্ববর্তী' : '← Previous' ?>";
    const nextText = "<?= $currentLang === 'bn' ? 'পরবর্তী →' : 'Next →' ?>";
    let currentPage = 1;

    function renderShop() {
        const term = input ? input.value.trim().toLowerCase() : '';
        const cat = catSel ? catSel.value : 'ALL';

        let filtered = cards.filter(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const desc = (card.dataset.desc || '').toLowerCase();
            const cardCat = card.dataset.category || 'General';
            const matchSearch = !term || name.includes(term) || desc.includes(term);
            const matchCat = cat === 'ALL' || cardCat === cat;
            return matchSearch && matchCat;
        });

        if (noRes) noRes.style.display = filtered.length === 0 ? 'block' : 'none';

        const totalPages = Math.ceil(filtered.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = totalPages || 1;

        cards.forEach(c => c.style.display = 'none');
        const start = (currentPage - 1) * itemsPerPage;
        const end = Math.min(start + itemsPerPage, filtered.length);
        for (let i = start; i < end; i++) {
            filtered[i].style.display = '';
        }

        // Pagination
        if (!pagWrap) return;
        if (totalPages <= 1) { pagWrap.innerHTML = ''; return; }

        let html = `<button class="pg-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="window.landingShopPage(${currentPage - 1})">${prevText}</button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<div class="pg-num ${i === currentPage ? 'active' : ''}" onclick="window.landingShopPage(${i})">${i}</div>`;
        }
        html += `<button class="pg-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="window.landingShopPage(${currentPage + 1})">${nextText}</button>`;
        pagWrap.innerHTML = html;
    }

    window.landingShopPage = function(p) { currentPage = p; renderShop(); };

    if (input) input.addEventListener('input', () => { currentPage = 1; renderShop(); });
    if (clear) clear.addEventListener('click', () => { input.value = ''; currentPage = 1; renderShop(); input.focus(); });
    if (catSel) catSel.addEventListener('change', () => { currentPage = 1; renderShop(); });

    renderShop();
})();

    // Dark mode toggle for landing page
    const toggle = document.getElementById('themeToggleLanding');
    if (toggle) {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') { document.body.classList.add('dark-mode'); toggle.textContent = '☀️'; }
        toggle.onclick = () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            toggle.textContent = isDark ? '☀️' : '🌙';
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        };
    }
</script>
<script src="assets/js/animations.js?v=<?= time() ?>"></script>
</body>
</html>
