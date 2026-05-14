<?php
// ============================================
// user_impact.php - User Environmental Impact (Viewer Friendly Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);
$currentLang = $_SESSION['lang'] ?? 'en';

$i18n_impact = [
    'en' => [
        'title' => 'Environmental Impact',
        'sub' => 'Quantifying your personal contribution to a greener planet.',
        'back' => '← Dashboard'
    ],
    'bn' => [
        'title' => 'পরিবেশগত প্রভাব',
        'sub' => 'সবুজ পৃথিবী গড়ায় আপনার ব্যক্তিগত অবদান পরিমাপ করা হচ্ছে।',
        'back' => '← ড্যাশবোর্ড'
    ]
];
$ti = $i18n_impact[$currentLang];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ti['title'] ?> — Notun Alo</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { font-family: 'Inter', 'Noto Sans Bengali', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); }

        .container { max-width: 1280px; margin: 0 auto; padding: 60px 32px; }
        @media (max-width: 640px) { .container { padding: 32px 20px; } }

        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--brand-primary); text-decoration: none; font-size: 14px; font-weight: 700; margin-bottom: 24px; transition: 0.2s; }
        .back-link:hover { transform: translateX(-5px); }

        .impact-header { margin-bottom: 48px; border-bottom: 1px solid var(--border); padding-bottom: 32px; }
        h1 { font-size: 44px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; margin-bottom: 8px; }
        .sub-text { font-size: 18px; color: var(--text-muted); }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="container">
        <div class="impact-header">
            <a href="dashboard.php" class="back-link"><?= $ti['back'] ?></a>
            <h1><?= $ti['title'] ?></h1>
            <p class="sub-text"><?= $ti['sub'] ?></p>
        </div>

        <?php $impactUserId = $userId; include __DIR__ . '/includes/impact_card.php'; ?>
    </main>

</body>
</html>
