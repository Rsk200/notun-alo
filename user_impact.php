<?php
// ============================================
// user_impact.php - User Environmental Impact
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);

$monthlyImpact = [];
$impactReadyStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'emission_factors'");
$impactReadyStmt->execute();
$hasEmissionFactors = (int)$impactReadyStmt->fetchColumn() > 0;
$subcatReadyStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pickups' AND column_name = 'subcategory'");
$subcatReadyStmt->execute();
$hasSubcategory = (int)$subcatReadyStmt->fetchColumn() > 0;

if ($hasEmissionFactors && $hasSubcategory) {
    $stmt = $pdo->prepare("
        SELECT
          DATE_FORMAT(p.schedule_date, '%Y-%m') AS month,
          p.category,
          ROUND(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 3) AS co2_saved_kg,
          ROUND(SUM(p.estimated_weight), 2) AS kg_collected
        FROM pickups p
        LEFT JOIN emission_factors ef
          ON p.category = ef.category COLLATE utf8mb4_unicode_ci
         AND p.subcategory IS NOT NULL
         AND p.subcategory <> ''
         AND p.subcategory = ef.subcategory COLLATE utf8mb4_unicode_ci
        LEFT JOIN category_averages ca ON p.category = ca.category COLLATE utf8mb4_unicode_ci
        WHERE p.user_id = ?
          AND p.status = 'completed'
          AND p.schedule_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month, p.category
        ORDER BY month ASC, co2_saved_kg DESC
    ");
    $stmt->execute([$userId]);
    $monthlyImpact = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Environmental Impact — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sortable-table.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>
<main class="main-content" style="background: #F5F7F2; min-height: 100vh;">
    <div class="container" style="max-width: 960px; margin: 0 auto; padding: 40px 24px;">

        <div class="page-header" style="padding: 0 0 8px 0;">
            <a href="dashboard.php" style="display: block; font-size: 14px; color: #6B7280; text-decoration: none; margin-bottom: 12px;">
                <span style="margin-right: 4px;">←</span> Dashboard
            </a>
            <h1 style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;">Environmental Impact</h1>
            <p style="font-size: 14px; color: #6B7280; margin: 6px 0 0 0;">Track your climate, water, and energy savings through recycling.</p>
            <div style="height: 1px; background: #E5E7EB; margin-top: 20px;"></div>
        </div>

        <?php $impactUserId = $userId; include __DIR__ . '/includes/impact_card.php'; ?>

    </div>
</main>
 endif; ?>

</body>
</html>
