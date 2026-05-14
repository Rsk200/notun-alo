<?php
function admin_impact_ready(PDO $pdo): bool {
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'emission_factors'");
    return (int)$stmt->fetchColumn() > 0;
}
if (!admin_impact_ready($pdo)): ?>
<section class="card" style="margin-bottom:2rem;border-left:5px solid #ffc107;"><h2><?= $lang['impact_dashboard'] ?? 'Environmental Impact Dashboard' ?></h2><p><?= $lang['impact_dashboard_missing_db'] ?? 'Import <code>ai-service/emission_factors.sql</code> or start the Flask API to initialize impact analytics.' ?></p></section>
<?php return; endif;
$summary = $pdo->query("SELECT COUNT(DISTINCT p.user_id) active_users, COUNT(p.id) total_pickups, ROUND(COALESCE(SUM(p.estimated_weight*COALESCE(ef.co2_sa_adjusted,ca.avg_co2,0)),0),2) co2, ROUND(COALESCE(SUM(p.estimated_weight*COALESCE(ef.water_liters_per_kg,ca.avg_water_liters_per_kg,0)),0),2) water, ROUND(COALESCE(SUM(p.estimated_weight*COALESCE(ef.energy_kwh_per_kg,ca.avg_energy_kwh_per_kg,0)),0),2) energy FROM pickups p LEFT JOIN emission_factors ef ON ef.category=p.category COLLATE utf8mb4_unicode_ci AND p.subcategory IS NOT NULL AND p.subcategory<>'' AND ef.subcategory=p.subcategory COLLATE utf8mb4_unicode_ci LEFT JOIN category_averages ca ON ca.category=p.category COLLATE utf8mb4_unicode_ci WHERE p.status='completed'")->fetch();
$categories = $pdo->query("SELECT p.category, ROUND(SUM(p.estimated_weight),2) kg, ROUND(SUM(p.estimated_weight*COALESCE(ef.co2_sa_adjusted,ca.avg_co2,0)),2) co2 FROM pickups p LEFT JOIN emission_factors ef ON ef.category=p.category COLLATE utf8mb4_unicode_ci AND p.subcategory IS NOT NULL AND p.subcategory<>'' AND ef.subcategory=p.subcategory COLLATE utf8mb4_unicode_ci LEFT JOIN category_averages ca ON ca.category=p.category COLLATE utf8mb4_unicode_ci WHERE p.status='completed' GROUP BY p.category ORDER BY co2 DESC")->fetchAll();
?>
<section class="card" style="margin-bottom:2rem;">
<style>
.admin-impact-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
.admin-impact-box{border:1px solid var(--border);border-radius:8px;padding:1rem;background:var(--white)}
.admin-impact-box span{color:var(--text-muted)}
.admin-impact-box strong{display:block;font-size:1.6rem;color:var(--green-dark)}
.ewaste-row{background:var(--gold-light)}
@media(max-width:800px){.admin-impact-grid{grid-template-columns:1fr}}
/* Dark Mode Overrides */
body.dark-mode .admin-impact-box { background: var(--chat-surface, #1e1e1e); border-color: var(--chat-border, #333); }
body.dark-mode .admin-impact-box span { color: #aaa; }
body.dark-mode .admin-impact-box strong { color: #81c784; }
body.dark-mode .ewaste-row { background: rgba(255, 214, 107, 0.15); }
body.dark-mode .ewaste-row td { color: #ffd66b !important; }
body.dark-mode .admin-impact-header { color: #81c784 !important; }
body.dark-mode .admin-impact-sub { color: #aaa !important; }
</style>
<h2 class="admin-impact-header" style="margin-top:0;color:#1b5e20;"><?= $lang['impact_dashboard'] ?? 'Environmental Impact Dashboard' ?></h2>
<p class="admin-impact-sub" style="color:#667085;"><?= $lang['sustainability_sub'] ?? 'Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling.' ?></p>
<div class="admin-impact-grid">
 <div class="admin-impact-box"><span><?= $lang['total_co2_saved'] ?? 'Total CO2 Saved' ?></span><strong><?= number_format((float)$summary['co2'],2) ?> kg</strong></div>
 <div class="admin-impact-box"><span><?= $lang['total_water_saved'] ?? 'Total Water Saved' ?></span><strong><?= number_format((float)$summary['water'],2) ?> L</strong></div>
 <div class="admin-impact-box"><span><?= $lang['total_energy_saved'] ?? 'Total Energy Saved' ?></span><strong><?= number_format((float)$summary['energy'],2) ?> kWh</strong></div>
</div>
<div class="table-wrap" style="margin-top:1rem;"><table class="data-table"><thead><tr><th><?= $lang['category'] ?? 'Category' ?></th><th><?= $lang['total_kg'] ?? 'Total kg' ?></th><th><?= $lang['co2_saved'] ?? 'CO2 saved' ?></th><th><?= $lang['badge'] ?? 'Badge' ?></th></tr></thead><tbody><?php foreach($categories as $row): ?><tr class="<?= $row['category']==='E-waste'?'ewaste-row':'' ?>"><td><?= e($row['category']) ?></td><td><?= number_format((float)$row['kg'],2) ?></td><td><?= number_format((float)$row['co2'],2) ?> kg</td><td><?= $row['category']==='E-waste'?($lang['high_impact_recycling'] ?? 'High Impact Recycling'):'-' ?></td></tr><?php endforeach; ?></tbody></table></div>
</section>
