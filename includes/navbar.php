<?php
// ============================================
// includes/navbar.php - PREMIUM FANCY NAVBAR
// ============================================


if (session_status() === PHP_SESSION_NONE) {
    startSession();
}


// Logout
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    session_unset();
    session_destroy();
    header("Location: logout.php");
    exit;
}


// Language Toggle
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] === 'bn' ? 'bn' : 'en';
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}


$role        = $_SESSION['role'] ?? 'user';
$curPage     = basename($_SERVER['PHP_SELF']);
$currentLang = $_SESSION['lang'] ?? 'en';
$nextLang    = ($currentLang === 'en') ? 'bn' : 'en';
$langBtnText = ($currentLang === 'en') ? 'বাং' : 'EN';


$userName    = $_SESSION['name'] ?? 'User';
$userInitial = strtoupper(mb_substr($userName, 0, 1)) ?: '?';


$homeUrl = match($role) {
    'admin'  => 'admin.php',
    'agency' => 'agency.php',
    default  => 'dashboard.php'
};


// =========================
// NAV ITEMS
// =========================
$navItems = [];


if ($role === 'user') {
    $navItems = [
        ['url' => 'user_request_pickup.php',  'icon' => 'fa-solid fa-truck-fast', 'label' => $lang['request_pickup'] ?? 'Request a Pickup'],
        ['url' => 'shop.php',                 'icon' => 'fa-solid fa-bag-shopping', 'label' => $lang['shop'] ?? 'Shop'],
        ['url' => 'chatbot.php',              'icon' => 'fa-solid fa-robot', 'label' => $lang['ai_assistant'] ?? 'AI Assistant'],
        ['url' => 'user_impact.php',          'icon' => 'fa-solid fa-globe', 'label' => $lang['environmental_impact'] ?? 'Environmental Impact'],
        ['url' => 'user_recent_activity.php', 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => $lang['recent_activity'] ?? 'My Recent Activity'],  
    ];
}


elseif ($role === 'admin') {
    $navItems = [
        ['url' => 'admin_pickups.php',       'icon' => 'fa-solid fa-truck', 'label' => $lang['pickups'] ?? 'Pickups'],
        ['url' => 'admin_orders.php',        'icon' => 'fa-solid fa-box-open', 'label' => $lang['orders'] ?? 'Orders'],
        ['url' => 'admin_add_product.php',   'icon' => 'fa-solid fa-circle-plus', 'label' => $lang['add_product'] ?? 'Add Product'],
        ['url' => 'admin_inventory.php',     'icon' => 'fa-solid fa-warehouse', 'label' => $lang['inventory'] ?? 'Inventory'],
        ['url' => 'admin_sustainability.php','icon' => 'fa-solid fa-chart-line', 'label' => $lang['sustainability_report'] ?? 'Sustainability Report'],
    ];
}


elseif ($role === 'agency') {
    $navItems = [
        ['url' => 'agency.php', 'icon' => 'fa-solid fa-list-check', 'label' => $lang['my_tasks'] ?? 'My Tasks'],
        ['url' => 'agency_completed.php', 'icon' => 'fa-solid fa-circle-check', 'label' => $lang['completed_tasks'] ?? 'Completed'],
    ];
}
?>


<!-- FONT AWESOME -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>


<!-- GOOGLE FONTS -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">


<style>


:root{
    --primary:#0f172a;
    --secondary:#1e293b;
    --green:#16a34a;
    --lightgreen:#22c55e;
    --yellow:#facc15;
    --white:#ffffff;
    --glass:rgba(255,255,255,0.08);
    --border:rgba(255,255,255,0.12);
}


*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}


body{
    font-family:'Poppins',sans-serif;
}


/* =========================
   NAVBAR
========================= */


.top-navbar{
    width:100%;
    position:sticky;
    top:0;
    z-index:9999;


    display:flex;
    align-items:center;
    justify-content:space-between;


    padding:14px 35px;


    background:linear-gradient(
        135deg,
        #0f172a,
        #1e293b,
        #064e3b
    );


    backdrop-filter:blur(14px);


    border-bottom:1px solid rgba(255,255,255,0.08);


    box-shadow:
    0 10px 30px rgba(0,0,0,0.25);


    overflow:hidden;
}


/* animated glow */
.top-navbar::before{
    content:'';
    position:absolute;
    width:300px;
    height:300px;
    background:rgba(34,197,94,0.15);
    border-radius:50%;
    top:-150px;
    left:-100px;
    filter:blur(70px);
}


.top-navbar::after{
    content:'';
    position:absolute;
    width:250px;
    height:250px;
    background:rgba(250,204,21,0.12);
    border-radius:50%;
    right:-100px;
    top:-120px;
    filter:blur(70px);
}


/* =========================
   BRAND
========================= */


.navbar-brand{
    position:relative;
    z-index:2;


    display:flex;
    align-items:center;
    gap:14px;


    text-decoration:none;
}


.brand-icon{
    width:55px;
    height:55px;


    border-radius:18px;


    background:linear-gradient(
        135deg,
        #22c55e,
        #16a34a
    );


    display:flex;
    align-items:center;
    justify-content:center;


    color:white;
    font-size:1.5rem;


    box-shadow:
    0 10px 25px rgba(34,197,94,0.45);


    animation:float 3s ease-in-out infinite;
}


@keyframes float{
    0%{transform:translateY(0);}
    50%{transform:translateY(-5px);}
    100%{transform:translateY(0);}
}


.brand-title{
    color:white;
    font-size:1.45rem;
    font-weight:700;
    line-height:1;
}


.brand-subtitle{
    color:#facc15;
    font-size:0.75rem;
    letter-spacing:2px;
    margin-top:4px;
    display:block;
}


/* =========================
   NAV MENU
========================= */


.nav-menu{
    position:relative;
    z-index:2;


    display:flex;
    align-items:center;
    gap:10px;


    list-style:none;
}


.nav-link{
    position:relative;


    display:flex;
    align-items:center;
    gap:10px;


    padding:12px 18px;


    border-radius:14px;


    text-decoration:none;


    color:#e2e8f0;


    font-size:0.95rem;
    font-weight:500;


    transition:0.35s ease;


    overflow:hidden;
}


.nav-link i{
    font-size:1rem;
    transition:0.3s;
}


.nav-link::before{
    content:'';
    position:absolute;
    inset:0;
    background:rgba(255,255,255,0.08);
    transform:scaleX(0);
    transform-origin:left;
    transition:0.35s;
    border-radius:14px;
}


.nav-link:hover::before{
    transform:scaleX(1);
}


.nav-link:hover{
    transform:translateY(-2px);
    color:white;
}


.nav-link:hover i{
    color:#facc15;
    transform:scale(1.2);
}


/* active */
.nav-link.active{
    background:linear-gradient(
        135deg,
        rgba(34,197,94,0.3),
        rgba(34,197,94,0.15)
    );


    color:white;


    border:1px solid rgba(255,255,255,0.1);


    box-shadow:
    0 10px 25px rgba(34,197,94,0.25);
}


.nav-link.active i{
    color:#facc15;
}


/* =========================
   TOOLS
========================= */


.nav-tools{
    position:relative;
    z-index:2;


    display:flex;
    align-items:center;
    gap:14px;
}


.tool-btn{
    width:45px;
    height:45px;


    border:none;
    outline:none;


    border-radius:14px;


    background:rgba(255,255,255,0.08);


    color:white;


    display:flex;
    align-items:center;
    justify-content:center;


    font-size:1rem;


    cursor:pointer;


    transition:0.35s ease;


    text-decoration:none;


    border:1px solid rgba(255,255,255,0.1);
}


.tool-btn:hover{
    transform:translateY(-3px) scale(1.05);


    background:linear-gradient(
        135deg,
        #22c55e,
        #16a34a
    );


    box-shadow:
    0 10px 25px rgba(34,197,94,0.4);
}


.logout-btn:hover{
    background:linear-gradient(
        135deg,
        #ef4444,
        #dc2626
    );
}


/* =========================
   PROFILE
========================= */


.profile-pill{
    display:flex;
    align-items:center;
    gap:12px;


    padding:6px 16px 6px 6px;


    border-radius:50px;


    text-decoration:none;


    color:white;


    background:rgba(255,255,255,0.08);


    border:1px solid rgba(255,255,255,0.1);


    transition:0.35s ease;
}


.profile-pill:hover{
    transform:translateY(-2px);


    background:rgba(255,255,255,0.14);
}


.profile-avatar{
    width:40px;
    height:40px;


    border-radius:50%;


    background:linear-gradient(
        135deg,
        #facc15,
        #f59e0b
    );


    color:#111827;


    display:flex;
    align-items:center;
    justify-content:center;


    font-weight:700;
    font-size:1rem;


    box-shadow:
    0 5px 18px rgba(250,204,21,0.35);
}


.profile-name{
    font-size:0.92rem;
    font-weight:600;
}


/* =========================
   DARK MODE
========================= */






/* =========================
   MOBILE
========================= */


@media(max-width:1200px){
    .top-navbar{
        flex-wrap:wrap;
        gap:18px;
    }
    .nav-menu{
        width:100%;
        justify-content:center;
        flex-wrap:wrap;
    }
}


/* === MOBILE HAMBURGER DRAWER === */
.nav-hamburger {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 10px;
    padding: 8px 10px;
    cursor: pointer;
    z-index: 10001;
    transition: background 0.2s;
}
.nav-hamburger:hover { background: rgba(255,255,255,0.16); }
.nav-hamburger span {
    display: block;
    width: 22px;
    height: 2px;
    background: #fff;
    border-radius: 2px;
    transition: all 0.3s ease;
}
.nav-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.nav-hamburger.open span:nth-child(2) { opacity: 0; }
.nav-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }


/* Mobile nav drawer overlay */
.mobile-nav-drawer {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
    backdrop-filter: blur(4px);
}
.mobile-nav-drawer.open { display: block; }


/* Slide-in panel */
.mobile-nav-panel {
    position: fixed;
    top: 0;
    left: -100%;
    width: min(300px, 85vw);
    height: 100%;
    background: linear-gradient(180deg, #0f172a 0%, #064e3b 100%);
    z-index: 9999;
    padding: 0;
    overflow-y: auto;
    transition: left 0.3s cubic-bezier(0.4,0,0.2,1);
    box-shadow: 4px 0 24px rgba(0,0,0,0.5);
    display: flex;
    flex-direction: column;
}
.mobile-nav-panel.open { left: 0; }


.mobile-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    background: rgba(0,0,0,0.28);
}
.mobile-panel-header .brand-icon { width: 40px; height: 40px; font-size: 1.1rem; flex-shrink: 0; }
.mobile-panel-header .brand-title { font-size: 1rem; }
.mobile-panel-header .brand-subtitle { font-size: 0.65rem; }


/* Clickable user section */
a[href="edit_profile.php"] .mobile-panel-user,
.mobile-panel-user {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.04);
    transition: background 0.2s;
}
a[href="edit_profile.php"]:hover .mobile-panel-user { background: rgba(255,255,255,0.09); }
.mobile-panel-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #facc15, #f59e0b);
    color: #111;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1rem;
    flex-shrink: 0;
}
.mobile-panel-uname { color: #fff; font-weight: 600; font-size: 0.9rem; }
.mobile-panel-role { color: rgba(255,255,255,0.5); font-size: 0.72rem; margin-top: 2px; }


.mobile-nav-links {
    display: flex;
    flex-direction: column;
    padding: 12px 12px;
    gap: 4px;
    flex: 1;
    list-style: none;
}
.mobile-nav-links a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border-radius: 12px;
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
}
.mobile-nav-links a i { width: 18px; text-align: center; font-size: 0.95rem; color: rgba(255,255,255,0.5); transition: color 0.2s; }
.mobile-nav-links a:hover,
.mobile-nav-links a.active {
    background: rgba(34,197,94,0.18);
    color: #fff;
}
.mobile-nav-links a:hover i,
.mobile-nav-links a.active i { color: #facc15; }


.mobile-panel-footer {
    display: flex;
    gap: 8px;
    padding: 14px 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
    background: rgba(0,0,0,0.15);
}
.mobile-panel-footer a,
.mobile-panel-footer button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 10px;
    border-radius: 10px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.85);
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s;
}
.mobile-panel-footer a:hover, .mobile-panel-footer button:hover { background: rgba(255,255,255,0.16); }
.mobile-panel-footer .mob-logout { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.25); color: #fca5a5; }
.mobile-panel-footer .mob-logout:hover { background: rgba(239,68,68,0.28); }


@media(max-width:768px){
    .top-navbar { padding: 10px 14px; }
    .nav-hamburger { display: flex; }
    /* Hide the inline menu and tools on mobile - they're in the drawer */
    .top-navbar .nav-menu { display: none; }
    .top-navbar .nav-tools { display: none; }
    /* Show only brand + hamburger in top bar */
    .brand-title { font-size: 1rem; }
    .navbar-brand .brand-icon { width: 36px; height: 36px; font-size: 1rem; }
    .brand-subtitle { display: none; }
}


</style>


<!-- =========================
     NAVBAR HTML
========================= -->


<nav class="top-navbar">


    <!-- BRAND -->
    <a href="<?= $homeUrl ?>" class="navbar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-recycle"></i>
        </div>
        <div>
            <div class="brand-title">
                <?= $currentLang === 'bn' ? 'নতুন আলো' : 'Notun Alo' ?>
            </div>
            <span class="brand-subtitle">
                <?= $currentLang === 'bn' ? 'স্মার্ট রিসাইক্লিং সিস্টেম' : 'SMART RECYCLING SYSTEM' ?>
            </span>
        </div>
    </a>


    <!-- MENU (desktop) -->
    <ul class="nav-menu">
        <?php foreach($navItems as $item): ?>
            <li>
                <a href="<?= $item['url'] ?>"
                   class="nav-link <?= $curPage === $item['url'] ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <?= $item['label'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>


    <!-- RIGHT TOOLS (desktop) -->
    <div class="nav-tools">
        <!-- LANGUAGE -->
        <a href="?lang=<?= $nextLang ?>" class="tool-btn" title="Language"
           style="font-weight:700; font-size:0.8rem; letter-spacing:0.02em;">
            <?= $langBtnText ?>
        </a>
        <!-- THEME -->
        <button id="theme-toggle" class="tool-btn" title="Theme">
            <i class="fa-solid fa-moon"></i>
        </button>
        <!-- LOGOUT -->
        <a href="?logout=1" class="tool-btn logout-btn" title="Logout"
           onclick="return confirm('Are you sure to logout?');">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
        <!-- PROFILE -->
        <a href="edit_profile.php" class="profile-pill">
            <div class="profile-avatar"><?= $userInitial ?></div>
            <div class="profile-name"><?= htmlspecialchars($userName) ?></div>
        </a>
    </div>


    <!-- HAMBURGER (mobile only) -->
    <button class="nav-hamburger" id="navHamburger" aria-label="Open menu">
        <span></span><span></span><span></span>
    </button>


</nav>


<!-- MOBILE SLIDE DRAWER -->
<div class="mobile-nav-drawer" id="mobileNavDrawer"></div>
<div class="mobile-nav-panel" id="mobileNavPanel">


    <!-- Panel Header -->
    <div class="mobile-panel-header">
        <div class="brand-icon"><i class="fa-solid fa-recycle"></i></div>
        <div style="flex:1;">
            <div class="brand-title"><?= $currentLang === 'bn' ? 'নতুন আলো' : 'Notun Alo' ?></div>
            <span class="brand-subtitle"><?= $currentLang === 'bn' ? 'স্মার্ট রিসাইক্লিং' : 'SMART RECYCLING' ?></span>
        </div>
        <!-- Theme Toggle for Mobile Drawer -->
        <button id="mob-drawer-theme-toggle" class="tool-btn" style="background:transparent; border:none; color:#fff; font-size:1.1rem; cursor:pointer; padding:4px;">
            <i class="fa-solid fa-moon"></i>
        </button>
    </div>


    <!-- User info -->
    <a href="edit_profile.php" style="text-decoration:none;">
        <div class="mobile-panel-user">
            <div class="mobile-panel-avatar"><?= $userInitial ?></div>
            <div>
                <div class="mobile-panel-uname"><?= htmlspecialchars($userName) ?></div>
                <div class="mobile-panel-role"><?= ucfirst($role) ?></div>
            </div>
        </div>
    </a>


    <!-- Nav links -->
    <ul class="mobile-nav-links">
        <li><a href="<?= $homeUrl ?>" class="<?= $curPage === $homeUrl ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> <?= $currentLang === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' ?>
        </a></li>
        <?php foreach($navItems as $item): ?>
        <li><a href="<?= $item['url'] ?>" class="<?= $curPage === $item['url'] ? 'active' : '' ?>">
            <i class="<?= $item['icon'] ?>"></i> <?= $item['label'] ?>
        </a></li>
        <?php endforeach; ?>
        <li><a href="about.php" class="<?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-info"></i> <?= $currentLang === 'bn' ? 'আমাদের সম্পর্কে' : 'About Us' ?>
        </a></li>
    </ul>


    <!-- Footer actions -->
    <div class="mobile-panel-footer">
        <a href="?lang=<?= $nextLang ?>" title="Switch language" style="text-transform:uppercase;">
            🌐 <?= $langBtnText ?>
        </a>
        <a href="?logout=1" class="mob-logout" onclick="return confirm('Logout?');">
            <i class="fa-solid fa-right-from-bracket"></i> <?= $currentLang === 'bn' ? 'লগআউট' : 'Logout' ?>
        </a>
    </div>


</div>


<!-- =========================
     JAVASCRIPT
========================= -->


<script>
document.addEventListener('DOMContentLoaded',()=>{


    // Theme toggle (Desktop)
    const themeToggle=document.getElementById('theme-toggle');
    // Theme toggle (Mobile)
    const mobDrawerThemeToggle=document.getElementById('mob-drawer-theme-toggle');


    const body=document.body;
    const savedTheme=localStorage.getItem('theme');
   
    function updateThemeIcons(isDark) {
        if(themeToggle) themeToggle.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        if(mobDrawerThemeToggle) mobDrawerThemeToggle.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
    }


    if(savedTheme==='dark'){
        body.classList.add('dark-mode');
        updateThemeIcons(true);
    }
   
    function toggleTheme() {
        body.classList.toggle('dark-mode');
        const isDark = body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcons(isDark);
    }


    if(themeToggle) themeToggle.addEventListener('click', toggleTheme);
    if(mobDrawerThemeToggle) mobDrawerThemeToggle.addEventListener('click', toggleTheme);


    // Scroll hide/show navbar
    let lastScroll = 0;
    const navbar = document.querySelector('.top-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 80 && currentScroll > lastScroll) {
                navbar.classList.add('nav-hidden');
            } else {
                navbar.classList.remove('nav-hidden');
            }
            lastScroll = currentScroll;
        });
    }


    // Mobile hamburger drawer
    const hamburger = document.getElementById('navHamburger');
    const drawer    = document.getElementById('mobileNavDrawer');
    const panel     = document.getElementById('mobileNavPanel');


    function openDrawer() {
        hamburger.classList.add('open');
        drawer.classList.add('open');
        panel.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        hamburger.classList.remove('open');
        drawer.classList.remove('open');
        panel.classList.remove('open');
        document.body.style.overflow = '';
    }


    if (hamburger) hamburger.addEventListener('click', openDrawer);
    if (drawer)    drawer.addEventListener('click', closeDrawer);


    // Close on nav link click
    if (panel) panel.querySelectorAll('a').forEach(a => a.addEventListener('click', closeDrawer));


});
</script>





