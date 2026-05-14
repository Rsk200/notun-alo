<?php
function impact_table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

$impactReady = impact_table_exists($pdo, 'emission_factors');
$hasSubcategory = false;
if ($impactReady) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pickups' AND column_name = 'subcategory'");
    $stmt->execute();
    $hasSubcategory = (int)$stmt->fetchColumn() > 0;
}

if (!$impactReady || !$hasSubcategory): ?>
    <section class="card" data-reveal style="margin-bottom:2rem; border-left:5px solid #ffc107;">
        <h2 class="card-title" style="margin-top:0;">Environmental Impact Dashboard</h2>
        <p class="card-sub">Import <code>emission_factors.sql</code> once, or start <code>impact_api.py</code>, to create the impact tables and pickup subcategory column.</p>
    </section>
<?php return; endif;

$platformImpact = $pdo->query("
    SELECT
      COUNT(DISTINCT p.user_id) AS total_active_users,
      COUNT(p.id) AS total_pickups,
      ROUND(COALESCE(SUM(p.estimated_weight), 0), 2) AS total_kg_collected,
      ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0) / 1000, 4) AS total_co2_saved_tonnes,
      (SELECT p2.category
       FROM pickups p2
       LEFT JOIN emission_factors ef2
         ON p2.category = ef2.category AND COALESCE(p2.subcategory, p2.category) = ef2.subcategory
       LEFT JOIN category_averages ca2 ON p2.category = ca2.category
       WHERE p2.status = 'completed'
       GROUP BY p2.category
       ORDER BY SUM(p2.estimated_weight * COALESCE(ef2.co2_sa_adjusted, ca2.avg_co2)) DESC
       LIMIT 1) AS highest_impact_category,
      ROUND(COALESCE(AVG(p.estimated_weight), 0), 2) AS avg_weight_per_pickup
    FROM pickups p
    LEFT JOIN emission_factors ef
      ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
    LEFT JOIN category_averages ca ON p.category = ca.category
    WHERE p.status = 'completed'
")->fetch();

$categoryRows = $pdo->query("
    SELECT
      p.category,
      ROUND(SUM(p.estimated_weight), 2) AS total_kg,
      ROUND(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 2) AS co2_saved_kg,
      ROUND(
        SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)) /
        NULLIF((SELECT SUM(px.estimated_weight * COALESCE(efx.co2_sa_adjusted, cax.avg_co2))
                FROM pickups px
                LEFT JOIN emission_factors efx
                  ON px.category = efx.category AND COALESCE(px.subcategory, px.category) = efx.subcategory
                LEFT JOIN category_averages cax ON px.category = cax.category
                WHERE px.status = 'completed'), 0) * 100,
        2
      ) AS pct_platform_total,
      ROUND(AVG(p.estimated_weight), 2) AS avg_kg_per_pickup,
      MAX(COALESCE(ef.is_ewaste, 0)) AS has_ewaste
    FROM pickups p
    LEFT JOIN emission_factors ef
      ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
    LEFT JOIN category_averages ca ON p.category = ca.category
    WHERE p.status = 'completed'
    GROUP BY p.category
    ORDER BY co2_saved_kg DESC
")->fetchAll();

$monthRows = $pdo->query("
    SELECT
      SUM(CASE WHEN DATE_FORMAT(schedule_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
          THEN estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2) ELSE 0 END) AS this_month,
      SUM(CASE WHEN DATE_FORMAT(schedule_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')
          THEN estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2) ELSE 0 END) AS last_month
    FROM pickups p
    LEFT JOIN emission_factors ef
      ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
    LEFT JOIN category_averages ca ON p.category = ca.category
    WHERE p.status = 'completed'
")->fetch();

$thisMonth = (float)($monthRows['this_month'] ?? 0);
$lastMonth = (float)($monthRows['last_month'] ?? 0);
$momChange = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : ($thisMonth > 0 ? 100 : 0);
$momColor = $momChange >= 0 ? '#1b5e20' : '#b42318';
$momBg = $momChange >= 0 ? '#e8f5e9' : '#fee4e2';
?>

<section class="card" data-reveal style="margin-bottom:2rem;">
    <div class="card-header" style="margin-bottom:1rem;">
        <h2 class="card-title" style="margin:0;">Environmental Impact Dashboard</h2>
        <p class="card-sub" style="margin:.25rem 0 0;">Platform-wide CO2, water, and energy impact from completed pickups</p>
    </div>

    <div class="stats-grid stats-grid--4" style="margin-bottom:1.5rem;">
        <div class="stat-card stat-card--green">
            <div class="stat-card__icon">🌿</div>
            <div class="stat-card__body">
                <p class="stat-card__label">Total CO2 Saved</p>
                <p class="stat-card__value"><?= number_format((float)$platformImpact['total_co2_saved_tonnes'], 4) ?> t</p>
            </div>
        </div>
        <div class="stat-card stat-card--teal">
            <div class="stat-card__icon">👥</div>
            <div class="stat-card__body">
                <p class="stat-card__label">Total Users</p>
                <p class="stat-card__value"><?= number_format((int)$platformImpact['total_active_users']) ?></p>
            </div>
        </div>
        <div class="stat-card stat-card--gold">
            <div class="stat-card__icon">⚖</div>
            <div class="stat-card__body">
                <p class="stat-card__label">Total kg Collected</p>
                <p class="stat-card__value"><?= number_format((float)$platformImpact['total_kg_collected'], 2) ?></p>
            </div>
        </div>
        <div class="stat-card stat-card--purple">
            <div class="stat-card__icon">🏆</div>
            <div class="stat-card__body">
                <p class="stat-card__label">Top Category</p>
                <p class="stat-card__value"><?= e($platformImpact['highest_impact_category'] ?? 'N/A') ?></p>
            </div>
        </div>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
        <h3 style="margin:0; color:var(--green-dark);">Category Distribution</h3>
        <span style="background:<?= $momBg ?>; color:<?= $momColor ?>; padding:.45rem .7rem; border-radius:999px; font-weight:800;">
            MoM CO2 <?= $momChange >= 0 ? '+' : '' ?><?= number_format($momChange, 1) ?>%
        </span>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total kg</th>
                    <th>CO2 Saved (kg)</th>
                    <th>% of Platform Total</th>
                    <th>Avg kg/pickup</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categoryRows)): ?>
                    <tr><td colspan="5" class="text-center">No completed impact data yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($categoryRows as $row): ?>
                        <tr>
                            <td>
                                <strong><?= e($row['category']) ?></strong>
                                <?php if ((int)$row['has_ewaste'] === 1 || $row['category'] === 'E-waste'): ?>
                                    <span style="background:#fff3cd; color:#7a4b00; border:1px solid #ffd66b; border-radius:999px; padding:.15rem .45rem; font-size:.75rem; font-weight:800; margin-left:.35rem;">High Impact</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format((float)$row['total_kg'], 2) ?></td>
                            <td><?= number_format((float)$row['co2_saved_kg'], 2) ?></td>
                            <td><?= number_format((float)$row['pct_platform_total'], 2) ?>%</td>
                            <td><?= number_format((float)$row['avg_kg_per_pickup'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
