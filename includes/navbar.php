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