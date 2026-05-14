<?php
if (!function_exists('impact_api_get')) {
    function impact_api_get(string $endpoint, int $userId): ?array {
        $url = "http://localhost/notun_alo/api_impact.php?action={$endpoint}&user_id=" . urlencode((string)$userId);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 2.5,
                'header' => "Accept: application/json\r\n",
            ],
        ]);
        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}

$impactUserId = (int)($impactUserId ?? ($_SESSION['user_id'] ?? 0));
$impact = impact_api_get('impact', $impactUserId) ?? [
    'message' => 'impact_api_unavailable',
    'total_co2_saved_kg' => 0,
    'total_water_saved_liters' => 0,
    'total_energy_saved_kwh' => 0,
    'equivalent_car_km_saved' => 0,
];
$forecast = impact_api_get('forecast', $impactUserId) ?? ['forecast' => []];

$co2 = (float)($impact['total_co2_saved_kg'] ?? 0);
$water = (float)($impact['total_water_saved_liters'] ?? 0);
$energy = (float)($impact['total_energy_saved_kwh'] ?? 0);
$carKm = (float)($impact['equivalent_car_km_saved'] ?? ($co2 / 0.21));
$waterBottles = $water / 0.5;
$phoneCharges = $energy / 0.012;
?>

<section style="margin-bottom: 2rem;">
    <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:1rem;">
        <div>
            <h2 style="margin:0; color:#1b5e20; font-size:1.45rem;">Environmental Impact</h2>
            <p style="margin:.25rem 0 0; color:#667085;">Scientific estimate from completed pickups</p>
        </div>
        <?php if (!empty($impact['ewaste_priority_note'])): ?>
            <span style="background:#fff3cd; color:#7a4b00; border:1px solid #ffd66b; padding:.4rem .65rem; border-radius:8px; font-weight:700; font-size:.85rem;">High Impact E-waste</span>
        <?php endif; ?>
    </div>

    <?php if ($co2 <= 0): ?>
        <div style="background:#f1f8e9; border-left:5px solid #2e7d32; border-radius:8px; padding:1rem 1.25rem; color:#1b5e20; font-weight:700;">
            Start recycling to see your impact!
            <?php if (($impact['message'] ?? '') === 'impact_api_unavailable'): ?>
                <span style="display:block; color:#667085; font-weight:500; margin-top:.25rem;">Start the impact API on port 5003 to load live calculations.</span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem;">
            <div style="background:#fff; border-radius:8px; border:1px solid #dfe7dd; border-left:5px solid #2e7d32; padding:1rem; box-shadow:0 8px 18px rgba(27,94,32,.06);">
                <div style="font-size:1.6rem;">🌿</div>
                <div style="font-size:1.8rem; font-weight:800; color:#1b5e20;"><?= number_format($co2, 2) ?> kg</div>
                <div style="color:#344054; font-weight:700;">CO2 Saved</div>
                <div style="color:#667085; font-size:.9rem;">= <?= number_format($carKm, 0) ?> km car trips avoided</div>
            </div>
            <div style="background:#fff; border-radius:8px; border:1px solid #dfe7dd; border-left:5px solid #2e7d32; padding:1rem; box-shadow:0 8px 18px rgba(27,94,32,.06);">
                <div style="font-size:1.6rem;">💧</div>
                <div style="font-size:1.8rem; font-weight:800; color:#1b5e20;"><?= number_format($water, 1) ?> L</div>
                <div style="color:#344054; font-weight:700;">Water Saved</div>
                <div style="color:#667085; font-size:.9rem;">= <?= number_format($waterBottles, 0) ?> bottles of drinking water</div>
            </div>
            <div style="background:#fff; border-radius:8px; border:1px solid #dfe7dd; border-left:5px solid #2e7d32; padding:1rem; box-shadow:0 8px 18px rgba(27,94,32,.06);">
                <div style="font-size:1.6rem;">⚡</div>
                <div style="font-size:1.8rem; font-weight:800; color:#1b5e20;"><?= number_format($energy, 2) ?> kWh</div>
                <div style="color:#344054; font-weight:700;">Energy Saved</div>
                <div style="color:#667085; font-size:.9rem;">= <?= number_format($phoneCharges, 0) ?> phone charges</div>
            </div>
        </div>
    <?php endif; ?>

    <div style="margin-top:1rem; background:#ffffff; border:1px solid #dfe7dd; border-radius:8px; padding:1rem;">
        <h3 style="margin:0 0 .75rem; color:#1b5e20; font-size:1.05rem;">Next 3 Months Forecast</h3>
        <?php if (empty($forecast['forecast'])): ?>
            <p style="margin:0; color:#667085;">Forecast will appear after the impact API is running.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:.75rem;">
                <?php foreach ($forecast['forecast'] as $item): ?>
                    <div style="background:#f8fbf7; border:1px solid #e1eee0; border-radius:8px; padding:.75rem;">
                        <div style="color:#667085; font-size:.85rem;"><?= e($item['month']) ?></div>
                        <div style="color:#1b5e20; font-weight:800; font-size:1.2rem;"><?= number_format((float)$item['predicted_co2_kg'], 2) ?> kg</div>
                        <div style="color:#667085; font-size:.82rem;"><?= e($item['confidence']) ?> confidence</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
