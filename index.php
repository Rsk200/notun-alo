<?php
// ============================================
// index.php - Landing Page
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


// Fetch products for visitors
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();


// Fetch total points for the stats counter
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="landing-body">


<!-- Navbar -->
<nav class="navbar navbar--transparent" id="mainNavbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand">
            <div class="nav-logo-wrap">♻</div>
            <div class="nav-name-wrap">
                <span class="nav-name">Notun Alo</span>
                <span class="nav-tagline">নতুন আলো &middot; Recycling</span>
            </div>
        </a>
        <div class="nav-right" style="gap: 0.75rem;">
            <a href="login.php" class="btn btn-outline btn-sm">Login</a>
            <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
        </div>
    </div>
</nav>


<!-- Preloader (For Landing Page) -->
<div id="preloader">
    <div class="preloader-inner">
        <div class="preloader-icon">♻</div>
        <div class="preloader-name">Notun Alo</div>
        <div class="preloader-bar"><div class="preloader-fill"></div></div>
    </div>
</div>


<!-- Hero V2 -->
<section class="hero-v2">
    <div class="hero-v2-bg"></div>
    <div class="hero-particle" style="left: 10%; animation-duration: 12s;"></div>
    <div class="hero-particle" style="left: 30%; animation-duration: 18s; animation-delay: 2s;"></div>
    <div class="hero-particle" style="left: 50%; animation-duration: 15s; animation-delay: 1s;"></div>
    <div class="hero-particle" style="left: 70%; animation-duration: 10s; animation-delay: 3s;"></div>
    <div class="hero-particle" style="left: 85%; animation-duration: 14s; animation-delay: 0s;"></div>


    <div class="container" style="position: relative; display: flex; width: 100%;">
        <div class="hero-v2-content">
            <span class="hero-v2-badge">🌿 Bangladesh's #1 Recycling Platform — Buildfest 2026</span>
            <h1 class="hero-v2-title">Turn Your Waste<br>Into <em>Notun Alo</em></h1>
            <p class="hero-v2-sub">Schedule doorstep pickups. Earn reward points. Shop eco-friendly goods. Building Bangladesh's circular economy — one household at a time.</p>
            <div class="hero-v2-actions">
                <a href="register.php" class="btn btn-gold btn-lg">Start Recycling Free &rarr;</a>
                <a href="#how" class="btn btn-outline btn-lg">Watch How It Works &#9654;</a>
            </div>
            <div class="hero-v2-trust">
                <span>&#10003; Free Signup</span>
                <span>&#10003; Doorstep Pickup</span>
                <span>&#10003; Instant Points</span>
            </div>
        </div>
       
        <div class="hero-v2-graphic">
            <svg class="wheel-svg" viewBox="0 0 100 100">
                <path d="M50 5 a45 45 0 1 0 0 90 a45 45 0 1 0 0 -90 m0 15 a30 30 0 1 1 0 60 a30 30 0 1 1 0 -60" />
                <path d="M50 0 L60 15 L40 15 Z" />
                <path d="M11 27 L25 35 L11 43 Z" transform="rotate(-60 25 35)" />
                <path d="M89 27 L75 35 L89 43 Z" transform="rotate(60 75 35)" />
            </svg>
           
            <div class="hero-float">
                <div class="float-card" data-reveal>📄 Paper &rarr; 5 pts/kg</div>
                <div class="float-card float-card--2" data-reveal>🧴 Plastic &rarr; 8 pts/kg</div>
                <div class="float-card float-card--3" data-reveal>🔩 Metal &rarr; 12 pts/kg</div>
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
        ♻ 8,000 tons of waste generated daily in Bangladesh &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 🌱 Only 10% currently recycled &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 💡 Join Notun Alo — be the change &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 🏆 Earn while helping the planet &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ♻ 8,000 tons of waste generated daily in Bangladesh &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 🌱 Only 10% currently recycled &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 💡 Join Notun Alo — be the change
    </div>
</div>


<!-- How It Works V2 -->
<section id="how" class="section">
    <div class="container">
        <h2 class="section-title" data-reveal>How It Works</h2>
        <div class="how-grid-v2">
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">1</div>
                <div class="how-icon">📅</div>
                <h3>Schedule Pickup</h3>
                <p>Choose your waste type, estimate weight, and pick a convenient date for collection.</p>
            </div>
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">2</div>
                <div class="how-icon">🚛</div>
                <h3>We Collect</h3>
                <p>Our partner agencies arrive at your doorstep to collect and weigh your recyclables.</p>
            </div>
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">3</div>
                <div class="how-icon">🏆</div>
                <h3>Earn Points</h3>
                <p>Points are automatically credited to your account based on the weight collected.</p>
            </div>
            <div class="how-card-v2" data-reveal>
                <div class="how-number-bg">4</div>
                <div class="how-icon">🛍</div>
                <h3>Shop Eco Goods</h3>
                <p>Spend your points in our Upcycle Shop for handcrafted eco-friendly products.</p>
            </div>
        </div>
    </div>
</section>


<!-- Stats Strip -->
<section class="stats-strip">
    <div class="container">
        <div class="stats-strip-grid">
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="170">0</span>M+</div>
                <div class="stats-strip__label">Bangladeshis with a recycling gap</div>
            </div>
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="8000">0</span> Tons</div>
                <div class="stats-strip__label">Waste generated daily in Dhaka</div>
            </div>
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="3">0</span></div>
                <div class="stats-strip__label">Materials we collect: Paper, Plastic, Metal</div>
            </div>
            <div class="stat-counter-wrap" data-reveal>
                <div class="stats-strip__value"><span class="stat-counter" data-target="<?= $totalPointsEarned ?>">0</span>+</div>
                <div class="stats-strip__label">Points earned by households</div>
            </div>
        </div>
    </div>
</section>


<!-- Upcycle Shop (Preview) -->
<section class="section shop-preview">
    <div class="container">
        <h2 class="section-title" data-reveal>Explore Our Shop</h2>


        <?php if (!empty($products)): ?>
        <!-- Shop Search Bar -->
        <div class="shop-search-wrap" data-reveal>
            <div class="shop-search-inner">
                <span class="shop-search-icon">🔍</span>
                <input type="text" id="landingShopSearch" placeholder="Search products…" class="shop-search-input" autocomplete="off">
                <button class="shop-search-clear" id="landingSearchClear" aria-label="Clear search">✕</button>
            </div>
        </div>
        <?php endif; ?>


        <?php if (empty($products)): ?>
            <div class="empty-state" data-reveal>
                <div class="empty-icon">🌱</div>
                <p>Shop coming soon.</p>
            </div>
        <?php else: ?>
        <div class="product-grid" id="landingProductGrid">
            <?php foreach ($products as $prod): ?>
            <div class="product-card-v2 <?= (int)($prod['stock'] ?? 0) === 0 ? 'product-card--oos' : '' ?>"
                 data-reveal
                 data-name="<?= strtolower(e($prod['name'])) ?>"
                 data-desc="<?= strtolower(e($prod['description'])) ?>">
                <div class="product-img-wrap">
                    <?php if ($prod['image_url']): ?>
                        <img src="<?= e($prod['image_url']) ?>" alt="<?= e($prod['name']) ?>" class="product-img" loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">🌿</div>
                    <?php endif; ?>
                    <div class="pts-badge-float">🏆 <?= number_format($prod['price_points']) ?> pts</div>
                    <?php if ((int)($prod['stock'] ?? 0) === 0): ?>
                        <div class="oos-overlay">Out of Stock</div>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <h3 class="product-name"><?= e($prod['name']) ?></h3>
                    <p class="product-desc"><?= e($prod['description']) ?></p>
                    <div class="product-actions">
                        <a href="login.php" class="btn btn-outline btn-full" style="color:var(--green-dark); border-color:var(--border);">Login to Buy</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="landingNoResults" style="display:none; text-align:center; padding:3rem 0; color:var(--text-muted);">
            <div style="font-size:2.5rem; margin-bottom:0.75rem;">🔍</div>
            <p style="font-size:1.1rem;">No products found matching your search.</p>
        </div>
        <?php endif; ?>
    </div>
</section>


<!-- Testimonials -->
<section class="section" style="background: white;">
    <div class="container">
        <h2 class="section-title" data-reveal>Community Impact</h2>
        <div class="testimonial-grid">
            <div class="testimonial-card" data-reveal>
                <div class="testimonial-quote">"Every kilogram of plastic I recycle brings me closer to earning eco-products I love."</div>
                <div class="testimonial-author">— Rina Akter, Dhaka</div>
                <div class="testimonial-icon">🌿</div>
            </div>
            <div class="testimonial-card" data-reveal>
                <div class="testimonial-quote">"Finally a platform that turns my recyclables into real rewards."</div>
                <div class="testimonial-author">— Karim Hossain, Chittagong</div>
                <div class="testimonial-icon">🌱</div>
            </div>
            <div class="testimonial-card" data-reveal>
                <div class="testimonial-quote">"Our agency now has a structured system to collect and process waste efficiently."</div>
                <div class="testimonial-author">— Fatima Rahman, Agency Partner</div>
                <div class="testimonial-icon">♻</div>
            </div>
        </div>
    </div>
</section>


<!-- CTA V2 -->
<section class="cta-v2">
    <div class="cta-v2-bg"></div>
    <div class="container cta-v2-content">
        <h2 data-reveal>Ready to make a difference?</h2>
        <p data-reveal style="font-size: 1.1rem; opacity: 0.9; max-width: 600px; margin: 0 auto;">Join thousands of Bangladeshis building a greener future, one pickup at a time.</p>
        <div class="cta-v2-actions" data-reveal>
            <a href="register.php" class="btn btn-gold btn-lg">Start Recycling Free &rarr;</a>
            <a href="#how" class="btn btn-outline btn-lg">Learn More</a>
        </div>
        <div class="cta-v2-ticks" data-reveal>
            <span>&#10003; No credit card required</span>
            <span>&#10003; Free forever for households</span>
            <span>&#10003; Points never expire</span>
        </div>
    </div>
</section>


<!-- Footer V2 -->
<footer class="footer-v2">
    <div class="container">
        <div class="footer-v2-grid">
            <div class="footer-col" data-reveal>
                <a href="index.php" class="footer-brand">♻ Notun Alo</a>
                <p class="footer-tag">Building Bangladesh's circular economy — turning household waste into lasting eco-rewards.</p>
            </div>
            <div class="footer-col" data-reveal>
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="shop.php">Upcycle Shop</a></li>
                </ul>
            </div>
            <div class="footer-col" data-reveal>
                <h4 class="footer-heading">Hackathon</h4>
                <p style="opacity: 0.8; font-size: 0.9rem; line-height: 1.6;">Builted by Team GhostRiders<br>University of Liberal Arts Bangladesh<br>THE INFINITY AI BUILDFEST 2026</p>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> Notun Alo (নতুন আলো). Built for a greener Bangladesh. 🌱
        </div>
    </div>
</footer>


<script>
// Landing Page Shop Search
(function () {
    const input   = document.getElementById('landingShopSearch');
    const clear   = document.getElementById('landingSearchClear');
    const grid    = document.getElementById('landingProductGrid');
    const noRes   = document.getElementById('landingNoResults');
    if (!input || !grid) return;


    function filterProducts() {
        const term  = input.value.trim().toLowerCase();
        const cards = grid.querySelectorAll('.product-card-v2');
        let visible = 0;
        cards.forEach(card => {
            const name = card.dataset.name || '';
            const desc = card.dataset.desc || '';
            const match = !term || name.includes(term) || desc.includes(term);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
        if (clear) clear.style.display = term ? 'flex' : 'none';
    }


    input.addEventListener('input', filterProducts);
    if (clear) {
        clear.addEventListener('click', () => { input.value = ''; filterProducts(); input.focus(); });
    }
    filterProducts();
})();
</script>
<script src="assets/js/animations.js?v=<?= time() ?>"></script>
</body>
</html>



