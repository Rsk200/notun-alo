<?php
// ============================================
// user_impact.php - Environmental Impact (Stunning Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);
$currentLang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environmental Impact — Notun Alo</title>
    
    <!-- CDNs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --brand-border: #6EE7B7;
            --gold: #D97706;
            --gold-bg: #FEF3C7;
            --gold-text: #92400E;
            --blue: #2563EB;
            --blue-bg: #EFF6FF;
            --blue-text: #1E40AF;
            --purple: #7C3AED;
            --purple-bg: #EDE9FE;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --border-light: #F3F4F6;
            --bg-page: #F5F7F2;
            --bg-card: #FFFFFF;
            --bg-subtle: #F9FAFB;
            --success-bg: #DCFCE7;
            --success-text: #166534;
        }

        /* ===== PREMIUM DARK MODE ===== */
        body.dark-mode {
            --bg-page: #080f09;
            --bg-card: #0f1a10;
            --bg-subtle: #0b130c;
            --text-primary: #E2E8F0;
            --text-secondary: #94A3B8;
            --text-muted: #64748B;
            --border: #1e3222;
            --border-light: #152018;
            --brand-light: #0d2416;
            --brand-primary: #34d399;
            --success-bg: #0a1f12;
            --success-text: #34d399;
            --gold-bg: #1c1200;
            --gold: #FBBF24;
            --blue-bg: #0d1a33;
            --purple-bg: #1a0d33;
        }
        body.dark-mode .white-card {
            background: var(--bg-card) !important;
            border-color: var(--border) !important;
            box-shadow: 0 4px 30px rgba(0,0,0,0.5) !important;
        }
        body.dark-mode .back-link {
            background: #0d2416 !important; border-color: #1e3222 !important; color: #34d399 !important;
        }
        body.dark-mode .page-title { color: #a3e9cb !important; }
        body.dark-mode .page-subtitle { color: var(--text-muted) !important; }
        body.dark-mode .header-divider { background: var(--border) !important; }
        body.dark-mode .tab-btn { background: var(--bg-subtle) !important; border-color: var(--border) !important; color: var(--text-muted) !important; }
        body.dark-mode .tab-btn.active { background: var(--brand-primary) !important; color: white !important; border-color: var(--brand-primary) !important; }
        body.dark-mode canvas { filter: brightness(0.9) hue-rotate(5deg); }
        body.dark-mode .share-btn { background: linear-gradient(135deg, #0d2416, #1a3d25) !important; border-color: #2a5a3a !important; color: #34d399 !important; }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); transition: background-color 0.4s ease, color 0.4s ease; }

        /* LAYOUT */
        .wrapper { max-width: 1000px; margin: 0 auto; padding: 32px 24px; }
        @media (max-width: 640px) { .wrapper { padding: 20px 16px; } }

        /* CARDS */
        .white-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.03);
            padding: 24px;
            margin-bottom: 24px;
        }
        @media (max-width: 640px) { .white-card { padding: 16px; } }

        /* HEADER */
        .page-header-flex { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 24px; padding-top: 20px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #065f46; background: #ecfdf5; border: 1px solid #d1fae5; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; margin-top: 6px; white-space: nowrap; }
        .back-link:hover { background: #d1fae5; transform: translateX(-2px); }
        
        .header-text { display: flex; flex-direction: column; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #064e3b; font-weight: 700; margin-bottom: 4px; line-height: 1.1; }
        .page-subtitle { font-size: 1.05rem; color: #64748b; margin: 0; }
        .header-divider { height: 1px; border: none; background: var(--border); margin-top: 20px; }

        @media (max-width: 768px) {
            .page-title { font-size: 1.8rem; }
            .page-subtitle { font-size: 0.95rem; }
        }
        @media (max-width: 480px) {
            .page-header-flex { flex-direction: column; gap: 12px; }
            .page-title { font-size: 1.5rem; }
            .back-link { white-space: normal; }
        }

        /* UTILS */
        .badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 600; }
    </style>
<style>
@media (max-width:767px) { .mobile-only { display:block; } .desktop-only { display:none; } }
@media (min-width:768px) { .mobile-only { display:none; } .desktop-only { display:block; } }
</style>
</head>
<body>

<?php $pageEmoji = '🌿'; include 'includes/mobile_nav.php'; ?>
<?php include 'includes/navbar.php'; ?>

    <main class="wrapper">
        <header class="page-header-flex">
            <a href="dashboard.php" class="back-link">&larr; <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
            <div class="header-text">
                <h1 class="page-title"><?= $lang['environmental_impact'] ?? 'Environmental Impact' ?></h1>
                <p class="page-subtitle"><?= $lang['impact_track_sub'] ?? 'Track your climate, water, and energy savings through recycling.' ?></p>
            </div>
        </header>
        <hr class="header-divider">

        <!-- Dynamic Impact Content -->
        <?php $impactUserId = $userId; include 'includes/impact_card.php'; ?>
    </main>

</body>
</html>
