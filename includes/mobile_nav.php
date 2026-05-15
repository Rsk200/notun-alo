<?php
if (!isset($pageEmoji)) $pageEmoji = '♻️';
$userName = $_SESSION['name'] ?? 'User';
$profilePic = $user['picture_url'] ?? '';
$initial = strtoupper(mb_substr($userName, 0, 1));
$currentLang = $_SESSION['lang'] ?? 'en';
?>
<!-- ── MOBILE NAV ── -->
<nav class="mob-nav" id="mobNav">
    <div class="mob-top">
        <a href="dashboard.php" class="mob-brand">
            <span class="mob-brand-icon">♻️</span>
            <span class="mob-brand-name">Notun Alo</span>
        </a>
        <span class="mob-page-emoji"><?= $pageEmoji ?></span>
    </div>
    <div class="mob-utils">
        <a href="?lang=bn" title="বাংলা">বাং</a>
        <a href="?lang=en" title="English">EN</a>
        <button class="mob-theme-btn" title="Theme">🌙</button>
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

<style>
.mob-nav {
    display: none;
    position: sticky; top: 0; z-index: 9999;
    background: linear-gradient(135deg, #0f172a, #1e293b, #064e3b);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    transition: transform 0.3s ease;
}
.mob-nav.nav-hide { transform: translateY(-100%); }
.mob-top { display: flex; align-items: center; justify-content: center; padding: 12px 18px 6px; gap: 10px; }
.mob-brand { display: flex; align-items: center; gap: 8px; text-decoration: none; }
.mob-brand-icon { font-size: 1.3rem; }
.mob-brand-name { font-size: 1.05rem; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
.mob-page-emoji { font-size: 1.1rem; opacity: 0.7; }
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

@media (max-width: 767px) {
    .mob-nav { display: block; }
}
</style>

<script>
// ── Mobile: Theme Toggle ──
(function(){
    const btn = document.querySelector('.mob-theme-btn');
    if (btn) {
        const saved = localStorage.getItem('theme');
        if (saved === 'dark') { document.body.classList.add('dark-mode'); btn.textContent = '☀️'; }
        btn.onclick = () => {
            document.body.classList.toggle('dark-mode');
            btn.textContent = document.body.classList.contains('dark-mode') ? '☀️' : '🌙';
            localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        };
    }
    // ── Scroll hide/show ──
    let ls = 0;
    const n = document.getElementById('mobNav');
    if (n) {
        window.addEventListener('scroll', () => {
            const c = window.pageYOffset;
            if (c > 60 && c > ls) n.classList.add('nav-hide');
            else if (c < ls || c <= 60) n.classList.remove('nav-hide');
            ls = c;
        });
    }
})();
</script>
