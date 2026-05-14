<?php
// ============================================
// admin_sustainability.php - Platform Sustainability Report
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

// Search user by email for admin lookup (to pass to navbar)
$searchTerm = trim((string)($_GET['search'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sustainability Report — Admin Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sortable-table.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <div>
                <a href="admin.php" class="btn-back"><span class="btn-back__arrow">←</span> Back to Dashboard</a>
                <h1 class="page-title" data-reveal><?= $lang['platform_sustainability'] ?? 'Platform Sustainability' ?></h1>
                <p class="page-sub"><?= $lang['platform_sustainability_sub'] ?? 'Comprehensive overview of the environmental impact across Notun Alo.' ?></p>
            </div>
        </div>

        <?php include __DIR__ . '/includes/admin_impact.php'; ?>
    </div>
</main>
</body>
</html>
