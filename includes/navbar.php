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
        ['url' => 'dashboard.php',            'icon' => 'fa-solid fa-house', 'label' => $lang['dashboard'] ?? 'Dashboard'],
        ['url' => 'user_request_pickup.php',  'icon' => 'fa-solid fa-truck-fast', 'label' => $lang['request_pickup'] ?? 'Request Pickup'],
        ['url' => 'shop.php',                 'icon' => 'fa-solid fa-bag-shopping', 'label' => $lang['shop'] ?? 'Shop'],
        ['url' => 'chatbot.php',              'icon' => 'fa-solid fa-robot', 'label' => $lang['ai_assistant'] ?? 'AI Assistant'],
        ['url' => 'user_impact.php',          'icon' => 'fa-solid fa-earth-asia', 'label' => $lang['environmental_impact'] ?? 'Impact'],
        ['url' => 'user_recent_activity.php', 'icon' => 'fa-solid fa-clock-rotate-left', 'label' => $lang['recent_activity'] ?? 'Recent Activity'],  
    ];
}

elseif ($role === 'admin') {
    $navItems = [
        ['url' => 'admin_pickups.php',       'icon' => 'fa-solid fa-truck', 'label' => $lang['pickups'] ?? 'Pickups'],
        ['url' => 'admin_orders.php',        'icon' => 'fa-solid fa-box-open', 'label' => $lang['orders'] ?? 'Orders'],
        ['url' => 'admin_add_product.php',   'icon' => 'fa-solid fa-circle-plus', 'label' => $lang['add_product'] ?? 'Add Product'],
        ['url' => 'admin_inventory.php',     'icon' => 'fa-solid fa-warehouse', 'label' => $lang['inventory'] ?? 'Inventory'],
        ['url' => 'admin_sustainability.php','icon' => 'fa-solid fa-chart-line', 'label' => $lang['sustainability_report'] ?? 'Sustainability'],
    ];
}

elseif ($role === 'agency') {
    $navItems = [
        ['url' => 'agency.php', 'icon' => 'fa-solid fa-list-check', 'label' => $lang['my_tasks'] ?? 'My Tasks'],
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

.top-navbar {
    width: 100%;
    height: 60px;
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: #0A2E1E;
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: white;
}

.brand-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #1D9E75;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
}

.brand-title {
    font-size: 1.25rem;
    font-weight: 700;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 32px;
    list-style: none;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.nav-link {
    text-decoration: none;
    color: #9CA3AF;
    font-size: 0.9rem;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 99px;
    transition: all 0.3s ease;
}

.nav-link:hover {
    color: white;
}

.nav-link.active {
    background: #1D9E75;
    color: white;
}

.nav-tools {
    display: flex;
    align-items: center;
    gap: 16px;
}

.tool-btn {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.tool-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.profile-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #1D9E75;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
}

.profile-name{
    font-size:0.92rem;
    font-weight:600;
}

/* =========================
   DARK MODE
========================= */

body.dark-mode{
    background:#020617;
    color:white;
}

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

@media(max-width:768px){

    .top-navbar{
        padding:15px;
    }

    .brand-title{
        font-size:1.1rem;
    }

    .nav-link{
        padding:10px 14px;
        font-size:0.85rem;
    }

    .profile-name{
        display:none;
    }
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
                <?= $lang['site_title'] ?? 'Notun Alo' ?>
            </div>

            <span class="brand-subtitle">
                SMART RECYCLING SYSTEM
            </span>
        </div>

    </a>

    <!-- MENU -->
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

    <!-- RIGHT TOOLS -->
    <div class="nav-tools">

        <!-- LANGUAGE -->
        <a href="?lang=<?= $nextLang ?>"
           class="tool-btn"
           title="Language">

            <i class="fa-solid fa-language"></i>

        </a>

        <!-- THEME -->
        <button id="theme-toggle"
                class="tool-btn"
                title="Theme">

            <i class="fa-solid fa-moon"></i>

        </button>

        <!-- LOGOUT -->
        <a href="?logout=1"
           class="tool-btn logout-btn"
           title="Logout"
           onclick="return confirm('Are you sure to logout?');">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

        <!-- PROFILE -->
        <a href="edit_profile.php"
           class="profile-pill">

            <div class="profile-avatar">
                <?= $userInitial ?>
            </div>

            <div class="profile-name">
                <?= htmlspecialchars($userName) ?>
            </div>

        </a>

    </div>

</nav>

<!-- =========================
     JAVASCRIPT
========================= -->

<script>

document.addEventListener('DOMContentLoaded',()=>{

    const themeToggle=document.getElementById('theme-toggle');
    const body=document.body;

    // Load Theme
    const savedTheme=localStorage.getItem('theme');

    if(savedTheme==='dark'){
        body.classList.add('dark-mode');
        themeToggle.innerHTML='<i class="fa-solid fa-sun"></i>';
    }

    // Toggle Theme
    themeToggle.addEventListener('click',()=>{

        body.classList.toggle('dark-mode');

        if(body.classList.contains('dark-mode')){

            localStorage.setItem('theme','dark');

            themeToggle.innerHTML='<i class="fa-solid fa-sun"></i>';

        }else{

            localStorage.setItem('theme','light');

            themeToggle.innerHTML='<i class="fa-solid fa-moon"></i>';
        }

    });

});

</script>