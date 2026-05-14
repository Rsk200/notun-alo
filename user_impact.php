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

<main class="main-content">
    <div class="container">

        <div class="page-header">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="dashboard.php" class="btn-back"><span class="btn-back__arrow">←</span> Dashboard</a>
                <div>
                    <h1 class="page-title" data-reveal><?= $lang['environmental_impact'] ?? 'Environmental Impact' ?></h1>
                    <p class="page-sub"><?= $lang['impact_track_sub'] ?? 'Track your climate, water, and energy savings through recycling.' ?></p>
                </div>
            </div>
        </div>

        <?php $impactUserId = $userId; include __DIR__ . '/includes/impact_card.php'; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
                <div>
                    <h2 style="margin:0; color:var(--primary-green); font-size:1.4rem;"><?= $lang['monthly_impact'] ?? 'Monthly Environmental Impact' ?></h2>
                    <p class="card-sub" style="margin:5px 0 0 0; color:var(--text-muted);"><?= $lang['monthly_impact_sub'] ?? 'CO2 saved by category over the last 12 months' ?></p>
                </div>
            </div>
            <?php if (empty($monthlyImpact)): ?>
                <div style="padding:2rem; text-align:center; color:var(--text-muted);"><?= $lang['no_data_yet'] ?? 'No data yet' ?></div>
            <?php else: ?>
                <div style="position:relative; height:320px;">
                    <canvas id="monthlyImpactChart"></canvas>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php if (!empty($monthlyImpact)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="ai-service/dashboard_chart.js"></script>
<script>
    const monthlyImpact = <?= json_encode($monthlyImpact, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    if (window.renderNotunAloStackedImpactChart) { renderNotunAloStackedImpactChart('monthlyImpactChart', monthlyImpact); } else {
    const categoryColors = {
        Paper: '#4CAF50',
        Plastic: '#2196F3',
        Metal: '#FF9800',
        'E-waste': '#E91E63',
        Glass: '#9C27B0',
        Organic: '#8BC34A',
        Textile: '#FF5722',
        Others: '#9E9E9E'
    };
    const months = [...new Set(monthlyImpact.map((item) => item.month))];
    const categories = [...new Set(monthlyImpact.map((item) => categoryColors[item.category] ? item.category : 'Others'))];
    const datasets = categories.map((category) => ({
        label: category,
        backgroundColor: categoryColors[category] || categoryColors.Others,
        data: months.map((month) => {
            return monthlyImpact
                .filter((item) => item.month === month && (categoryColors[item.category] ? item.category : 'Others') === category)
                .reduce((sum, item) => sum + Number(item.co2_saved_kg || 0), 0);
        })
    }));
    
    // Check if dark mode is active to adjust chart text colors
    const isDarkMode = document.body.classList.contains('dark-mode');
    const textColor = isDarkMode ? '#e0e0e0' : '#666';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    new Chart(document.getElementById('monthlyImpactChart'), {
        type: 'bar',
        data: { labels: months, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { color: textColor }
                },
                title: { 
                    display: true, 
                    text: '<?= $lang['monthly_impact_chart_title'] ?? 'Your Monthly Environmental Impact (CO₂ kg Saved)' ?>',
                    color: textColor
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' kg CO₂'; }
                    }
                }
            },
            scales: {
                x: { 
                    stacked: true, 
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                },
                y: { 
                    stacked: true, 
                    title: { display: true, text: 'kg CO₂ saved', color: textColor },
                    ticks: { color: textColor },
                    grid: { color: gridColor }
                }
            }
        }
    });
    }
</script>
<?php endif; ?>

</body>
</html>
