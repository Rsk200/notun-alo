<?php
// ============================================
// index.php - Landing Page (Viewer Friendly Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
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

try {
    $products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 4")->fetchAll();
} catch (PDOException $e) {
    if (!isDatabaseInitialized($pdo)) redirect('init_db.php');
    throw $e;
}

$totalPointsQuery = $pdo->query("SELECT SUM(lifetime_points) as total FROM rewards");
$totalPointsData = $totalPointsQuery->fetch();
$totalPointsEarned = (int)($totalPointsData['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notun Alo — Recycling for a Greener Bangladesh</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --bg-page: #F5F7F2;
            --container-max: 1280px;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-page); color: var(--text-secondary); -webkit-font-smoothing: antialiased; }

        /* NAV */
        nav { height: 72px; background: var(--brand-dark); display: flex; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .nav-inner { width: 100%; max-width: var(--container-max); margin: 0 auto; padding: 0 32px; display: flex; align-items: center; justify-content: space-between; }

        /* HERO */
        .hero { background: white; padding: 140px 32px 100px; border-bottom: 1px solid var(--border); position: relative; overflow: hidden; }
        .hero-inner { max-width: var(--container-max); margin: 0 auto; display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 80px; align-items: center; }
        
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: var(--brand-light); color: #065F46; padding: 8px 18px; border-radius: 99px; font-size: 14px; font-weight: 700; margin-bottom: 32px; }
        .hero-title { font-size: 72px; font-weight: 900; color: var(--text-primary); line-height: 0.95; letter-spacing: -0.04em; margin-bottom: 24px; }
        .hero-title span { color: var(--brand-primary); }
        .hero-sub { font-size: 20px; line-height: 1.6; color: var(--text-secondary); margin-bottom: 40px; max-width: 600px; }
        
        .hero-btns { display: flex; gap: 20px; }
        .btn-xl { padding: 20px 40px; border-radius: 16px; font-size: 18px; font-weight: 800; text-decoration: none; transition: 0.3s; }
        .btn-xl-primary { background: var(--brand-primary); color: white; box-shadow: 0 10px 30px rgba(29,158,117,0.3); }
        .btn-xl-primary:hover { background: #065F46; transform: translateY(-3px); box-shadow: 0 15px 40px rgba(29,158,117,0.4); }
        .btn-xl-outline { border: 2px solid var(--border); color: var(--text-primary); }
        .btn-xl-outline:hover { background: var(--bg-subtle); border-color: var(--text-primary); }

        /* FLOATING CARD */
        .hero-graphic { position: relative; }
        .hero-main-card { background: white; border: 1px solid var(--border); border-radius: 32px; padding: 40px; box-shadow: 0 40px 80px rgba(0,0,0,0.08); position: relative; z-index: 2; transform: rotate(-1deg); }
        .float-tag { position: absolute; background: #FEF3C7; color: #92400E; padding: 12px 24px; border-radius: 20px; font-weight: 800; font-size: 16px; box-shadow: 0 10px 20px rgba(217,119,6,0.15); z-index: 3; }

        /* SECTION */
        .section { padding: 120px 32px; max-width: var(--container-max); margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 80px; }
        .section-tag { color: var(--brand-primary); font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; font-size: 13px; margin-bottom: 12px; display: block; }
        .section-h { font-size: 48px; font-weight: 900; color: var(--text-primary); letter-spacing: -0.02em; }

        .feature-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 32px; }
        .feature-card { background: white; border: 1px solid var(--border); border-radius: 24px; padding: 40px; transition: 0.4s; }
        .feature-card:hover { transform: translateY(-10px); border-color: var(--brand-primary); box-shadow: 0 20px 40px rgba(0,0,0,0.04); }
        .feat-icon { width: 56px; height: 56px; background: var(--brand-light); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: var(--brand-primary); font-size: 24px; margin-bottom: 28px; }

        @media (max-width: 1100px) {
            .hero-inner, .feature-grid { grid-template-columns: 1fr; }
            .hero-title { font-size: 56px; }
            .hero-graphic { order: -1; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="nav-inner">
            <a href="index.php" style="text-decoration: none; display: flex; align-items: center; gap: 12px;">
                <div style="width:40px; height:40px; background:var(--brand-primary); border-radius:12px; display:flex; align-items:center; justify-content:center; color:white; font-size:20px;"><i class="ti ti-recycle"></i></div>
                <span style="color:white; font-weight:900; font-size:22px; letter-spacing:-0.03em;">Notun Alo</span>
            </a>
            <div style="display:flex; gap:32px; align-items:center;">
                <a href="login.php" style="color:white; opacity:0.7; text-decoration:none; font-weight:600; font-size:15px;">Login</a>
                <a href="register.php" style="background:var(--brand-primary); color:white; padding:10px 24px; border-radius:12px; text-decoration:none; font-weight:800; font-size:15px; box-shadow:0 4px 12px rgba(29,158,117,0.2);">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-inner">
            <div class="hero-content">
                <div class="hero-badge"><i class="ti ti-trophy"></i> Bangladesh's Premier Recycling Platform</div>
                <h1 class="hero-title">Recycle. Earn.<br>Build <span>Tomorrow.</span></h1>
                <p class="hero-sub">The smartest way to handle household waste. Schedule pickups at your doorstep, earn valuable reward points, and shop for sustainable goods.</p>
                <div class="hero-btns">
                    <a href="register.php" class="btn-xl btn-xl-primary">Join the Revolution &rarr;</a>
                    <a href="#how" class="btn-xl btn-xl-outline">See How it Works</a>
                </div>
            </div>

            <div class="hero-graphic">
                <div class="float-tag" style="top: -20px; right: 20px;">+500 Points</div>
                <div class="hero-main-card">
                    <div style="display:flex; align-items:center; gap:16px; margin-bottom:32px;">
                        <div style="width:56px; height:56px; background:var(--brand-light); border-radius:16px; display:flex; align-items:center; justify-content:center; color:var(--brand-primary); font-size:24px;"><i class="ti ti-truck-delivery"></i></div>
                        <div>
                            <div style="font-size:18px; font-weight:900; color:var(--text-primary);">Next Pickup</div>
                            <div style="font-size:14px; color:var(--text-muted);">Dhaka Central · Scheduled</div>
                        </div>
                    </div>
                    <div style="padding:24px; background:var(--bg-page); border-radius:20px; border:1px dashed var(--border); text-align:center;">
                        <i class="ti ti-package" style="font-size:40px; color:var(--text-muted); margin-bottom:12px; display:block;"></i>
                        <span style="font-size:14px; font-weight:700; color:var(--text-primary);">12.5 kg Recyclables Ready</span>
                    </div>
                </div>
                <div class="float-tag" style="bottom: -10px; left: -20px; background:#D1FAE5; color:#065F46;">🌱 324kg CO₂ Saved</div>
            </div>
        </div>
    </section>

    <section class="section" id="how">
        <div class="section-header">
            <span class="section-tag">Our Process</span>
            <h2 class="section-h">How We Turn Waste Into Value</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feat-icon"><i class="ti ti-calendar-event"></i></div>
                <h3 style="font-size:20px; font-weight:800; color:var(--text-primary); margin-bottom:16px;">1. Schedule</h3>
                <p style="font-size:15px; line-height:1.6;">Use our intuitive dashboard to book a pickup time that works for you.</p>
            </div>
            <div class="feature-card">
                <div class="feat-icon"><i class="ti ti-home-check"></i></div>
                <h3 style="font-size:20px; font-weight:800; color:var(--text-primary); margin-bottom:16px;">2. Doorstep</h3>
                <p style="font-size:15px; line-height:1.6;">Our verified agents arrive at your door to collect and weigh your items.</p>
            </div>
            <div class="feature-card">
                <div class="feat-icon"><i class="ti ti-bolt"></i></div>
                <h3 style="font-size:20px; font-weight:800; color:var(--text-primary); margin-bottom:16px;">3. Instant</h3>
                <p style="font-size:15px; line-height:1.6;">Points are credited to your digital wallet immediately after collection.</p>
            </div>
            <div class="feature-card">
                <div class="feat-icon"><i class="ti ti-shopping-bag"></i></div>
                <h3 style="font-size:20px; font-weight:800; color:var(--text-primary); margin-bottom:16px;">4. Redeem</h3>
                <p style="font-size:15px; line-height:1.6;">Browse our Upcycle Shop and spend points on handcrafted eco-products.</p>
            </div>
        </div>
    </section>

    <footer style="background:var(--brand-dark); padding:80px 32px 40px; color:white;">
        <div style="max-width:var(--container-max); margin:0 auto; display:grid; grid-template-columns: 2fr 1fr 1fr; gap:60px; padding-bottom:60px; border-bottom:1px solid rgba(255,255,255,0.1);">
            <div>
                <h4 style="font-size:24px; font-weight:900; margin-bottom:20px;">Notun Alo</h4>
                <p style="opacity:0.6; line-height:1.8; max-width:320px;">Bangladesh's leading circular economy platform. Empowering households to build a sustainable future.</p>
            </div>
            <div>
                <h5 style="font-size:16px; font-weight:800; margin-bottom:24px;">Platform</h5>
                <a href="login.php" style="display:block; color:white; opacity:0.6; text-decoration:none; margin-bottom:12px;">Login</a>
                <a href="register.php" style="display:block; color:white; opacity:0.6; text-decoration:none; margin-bottom:12px;">Register</a>
                <a href="#" style="display:block; color:white; opacity:0.6; text-decoration:none;">Impact Stats</a>
            </div>
            <div>
                <h5 style="font-size:16px; font-weight:800; margin-bottom:24px;">Contact</h5>
                <p style="opacity:0.6; font-size:14px; line-height:1.8;">ULAB Research Lab<br>Dhaka, Bangladesh<br>hello@notunalo.com</p>
            </div>
        </div>
        <div style="text-align:center; padding-top:40px; font-size:13px; opacity:0.4; font-weight:500;">
            &copy; <?= date('Y') ?> Notun Alo. All rights reserved. Built with pride in Bangladesh.
        </div>
    </footer>

</body>
</html>
