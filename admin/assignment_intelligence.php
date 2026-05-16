<?php
// ============================================
// admin/assignment_intelligence.php
// AI Transparency Dashboard
// ============================================
require_once '../includes/config.php';
requireRole('admin');

// 1. Top Stats
$monthStart = date('Y-m-01 00:00:00');
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_ai,
        ROUND(AVG(score_total), 2) as avg_score,
        MAX(model_version) as curr_version
    FROM assignment_log 
    WHERE method = 'ai' AND assigned_at >= '$monthStart'
")->fetch();

$agencies_avail = $pdo->query("SELECT COUNT(*) FROM agency_stats WHERE is_available = 1")->fetchColumn();

// 2. Recent Assignments (last 20)
$recent = $pdo->query("
    SELECT al.*, p.category, u.name as agency_name, s.score_load, s.score_distance, s.score_rating, s.score_specialty
    FROM assignment_log al
    JOIN pickups p ON al.pickup_id = p.id
    JOIN users u ON al.agency_id = u.id
    LEFT JOIN assignment_scores s ON al.pickup_id = s.pickup_id AND al.agency_id = s.agency_id
    ORDER BY al.assigned_at DESC LIMIT 20
")->fetchAll();

// 3. Agency Leaderboard
$leaderboard = $pdo->query("
    SELECT s.*, z.zone_label 
    FROM agency_stats s
    LEFT JOIN agency_zones z ON s.agency_id = z.agency_id
    ORDER BY s.active_pickups ASC, s.load_ratio ASC
")->fetchAll();

// 4. Model Status
$model = $pdo->query("SELECT * FROM model_versions WHERE is_active = 1 ORDER BY trained_at DESC LIMIT 1")->fetch();

include '../includes/header.php';
include '../includes/navbar.php';

$t = function($en, $bn) use ($currentLang) {
    return $currentLang === 'bn' ? $bn : $en;
};
?>

<style>
body.dark-mode .ai-card {
    background: var(--card-bg, #1e2a1e) !important;
}
body.dark-mode .ai-card h4,
body.dark-mode .ai-card small {
    color: var(--text-muted, #a8b0a5);
}
body.dark-mode .ai-bar-bg {
    background: #2a3a2a !important;
}
body.dark-mode .ai-legend-text {
    color: #a8b0a5 !important;
}
body.dark-mode .ai-table {
    background: var(--card-bg, #1e2a1e) !important;
}
body.dark-mode .ai-table th,
body.dark-mode .ai-table td {
    border-color: #2a3a2a !important;
}
body.dark-mode .ai-table tbody tr {
    border-color: #2a3a2a !important;
}
body.dark-mode .ai-load-bar {
    background: #2a3a2a !important;
}
</style>

<div class="container" style="padding: 2rem;">
    <h2><?= $t('🧠 AI Smart Assignment Intelligence', '🧠 AI স্মার্ট অ্যাসাইনমেন্ট ইন্টেলিজেন্স') ?></h2>
    
    <!-- Top Stats -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <div class="ai-card" style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4><?= $t('Total AI Assignments', 'মোট AI বরাদ্দ') ?></h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= $stats['total_ai'] ?: 0 ?></div>
            <small><?= $t('This month', 'এই মাসে') ?></small>
        </div>
        <div class="ai-card" style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4><?= $t('Avg AI Confidence Score', 'গড় AI কনফিডেন্স স্কোর') ?></h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= $stats['avg_score'] ?: '0.00' ?></div>
            <small><?= $t('Scale 0.0 - 1.0', 'স্কেল ০.০ - ১.০') ?></small>
        </div>
        <div class="ai-card" style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4><?= $t('Active Model Version', 'সক্রিয় মডেল সংস্করণ') ?></h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= e($stats['curr_version'] ?? $t('N/A', 'না')) ?></div>
            <small><?= $t('Current predictor', 'বর্তমান পূর্বাভাসক') ?></small>
        </div>
        <div class="ai-card" style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4><?= $t('Agencies Available', 'উপলব্ধ এজেন্সি') ?></h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= $agencies_avail ?></div>
            <small><?= $t('Ready for routing', 'রাউটিং এর জন্য প্রস্তুত') ?></small>
        </div>
    </div>

    <!-- Model Control Card -->
    <div class="ai-card" style="background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 0.5rem 0;"><?= $t('Model Status', 'মডেলের অবস্থা') ?></h4>
            <?php if($model): ?>
                <p style="margin:0;"><?= $t('Type:', 'ধরন:') ?> <strong><?= e($model['model_type']) ?></strong> | <?= $t('Trained on:', 'প্রশিক্ষিত:') ?> <strong><?= $model['trained_on'] ?> <?= $t('pickups', 'পিকআপ') ?></strong> | <?= $t('Test MAE:', 'টেস্ট MAE:') ?> <strong><?= $model['test_mae'] ?> <?= $t('hrs', 'ঘণ্টা') ?></strong> | <?= $t('Date:', 'তারিখ:') ?> <strong><?= $model['trained_at'] ?></strong></p>
            <?php else: ?>
                <p style="margin:0; color: #dc3545;"><?= $t('No trained ML model found. Running in weighted-only fallback mode.', 'কোনো প্রশিক্ষিত ML মডেল পাওয়া যায়নি। ওয়েটেড-অনলি ফallback মোডে চলছে।') ?></p>
            <?php endif; ?>
        </div>
        <button id="btnRetrain" style="padding: 0.75rem 1.5rem; background: var(--green, #2E7D32); color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            <?= $t('⚙️ Retrain Model Now', '⚙️ মডেল পুনরায় প্রশিক্ষণ দিন') ?>
        </button>
    </div>

    <!-- Score Breakdown Panel -->
    <h3 style="margin-bottom: 1rem;"><?= $t('🔬 Recent Score Breakdowns', '🔬 সাম্প্রতিক স্কোর বিশ্লেষণ') ?></h3>
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; overflow-x: auto;">
        <?php foreach(array_slice($recent, 0, 5) as $r): 
            if(!$r['score_total']) continue;
            $w_load = ($r['score_load'] * 0.35) * 100;
            $w_dist = ($r['score_distance'] * 0.20) * 100;
            $w_rat = ($r['score_rating'] * 0.12) * 100;
            $w_spec = ($r['score_specialty'] * 0.08) * 100;
            $w_comp = 100 - ($w_load + $w_dist + $w_rat + $w_spec);
        ?>
        <div class="ai-card" style="flex: 0 0 300px; background: var(--card-bg, #fff); padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-weight:bold; margin-bottom: 0.5rem;"><?= $t('Pickup', 'পিকআপ') ?> #<?= $r['pickup_id'] ?> &rarr; <?= e($r['agency_name']) ?></div>
            <div style="font-size: 1.5rem; font-weight: bold; color: var(--green); margin-bottom: 0.5rem;"><?= $r['score_total'] ?></div>
            
            <div class="ai-bar-bg" style="width: 100%; height: 20px; border-radius: 10px; overflow: hidden; display: flex; margin-bottom: 0.5rem; background: #eee;">
                <div style="width: <?= $w_load ?>%; background: #4caf50;" title="<?= $t('Load', 'লোড') ?>"></div>
                <div style="width: <?= $w_comp ?>%; background: #2196f3;" title="<?= $t('Completion Speed/Rate', 'সম্পূর্ণতার গতি/হার') ?>"></div>
                <div style="width: <?= $w_dist ?>%; background: #ff9800;" title="<?= $t('Distance', 'দূরত্ব') ?>"></div>
                <div style="width: <?= $w_rat ?>%; background: #e91e63;" title="<?= $t('Rating', 'রেটিং') ?>"></div>
                <div style="width: <?= $w_spec ?>%; background: #9c27b0;" title="<?= $t('Specialty', 'বিশেষত্ব') ?>"></div>
            </div>
            <div class="ai-legend-text" style="font-size: 0.8rem; color: #666; display: flex; flex-wrap: wrap; gap: 5px;">
                <span style="color:#4caf50;">■ <?= $t('Load', 'লোড') ?></span>
                <span style="color:#2196f3;">■ <?= $t('Completion', 'সম্পূর্ণতা') ?></span>
                <span style="color:#ff9800;">■ <?= $t('Dist', 'দূরত্ব') ?></span>
                <span style="color:#e91e63;">■ <?= $t('Rate', 'রেট') ?></span>
                <span style="color:#9c27b0;">■ <?= $t('Spec', 'বিশেষ') ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Agency Leaderboard (Live Data) -->
    <h3 style="margin-bottom: 1rem;"><?= $t('🏢 Agency Real-time Load Matrix', '🏢 এজেন্সি রিয়েল-টাইম লোড ম্যাট্রিক্স') ?></h3>
    <table class="ai-table" style="width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 2rem; background: var(--card-bg, #fff); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 1rem;"><?= $t('Agency', 'এজেন্সি') ?></th>
                <th style="padding: 1rem;"><?= $t('Zone', 'জোন') ?></th>
                <th style="padding: 1rem;"><?= $t('Active / Max', 'সক্রিয় / সর্বোচ্চ') ?></th>
                <th style="padding: 1rem;"><?= $t('Load %', 'লোড %') ?></th>
                <th style="padding: 1rem;"><?= $t('Total Comp.', 'মোট সম্পন্ন') ?></th>
                <th style="padding: 1rem;"><?= $t('Avg Hrs', 'গড় ঘণ্টা') ?></th>
                <th style="padding: 1rem;"><?= $t('Status', 'অবস্থা') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($leaderboard as $ag): 
                $loadPct = $ag['load_ratio'] * 100;
                $color = $loadPct >= 100 ? '#dc3545' : ($loadPct > 60 ? '#ffc107' : '#28a745');
                $statusText = $loadPct >= 100 ? $t('Full', 'পূর্ণ') : ($loadPct > 60 ? $t('Busy', 'ব্যস্ত') : $t('Available', 'উপলব্ধ'));
                if($ag['is_available'] == 0) { $color = '#6c757d'; $statusText = $t('Offline', 'অফলাইন'); }
            ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 1rem;"><strong><?= e($ag['agency_name']) ?></strong><br><small><?= e($ag['specialty']) ?></small></td>
                <td style="padding: 1rem;"><?= e($ag['zone_label'] ?? $t('Unassigned', 'বরাদ্দহীন')) ?></td>
                <td style="padding: 1rem;"><?= $ag['active_pickups'] ?> / <?= $ag['max_capacity'] ?></td>
                <td style="padding: 1rem;">
                    <div class="ai-load-bar" style="width: 100%; background: #e9ecef; border-radius: 4px; height: 8px;">
                        <div style="width: <?= min(100, $loadPct) ?>%; background: <?= $color ?>; height: 100%; border-radius: 4px;"></div>
                    </div>
                </td>
                <td style="padding: 1rem;"><?= $ag['total_completed'] ?></td>
                <td style="padding: 1rem;"><?= $ag['avg_completion_hrs'] ?: $t('N/A', 'না') ?></td>
                <td style="padding: 1rem;"><span style="background: <?= $color ?>; color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;"><?= $statusText ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<script>
document.getElementById('btnRetrain').addEventListener('click', function() {
    if(!confirm("<?= $t('Start background ML retraining? This will extract all historical data and run Random Forest optimization.', 'ব্যাকগ্রাউন্ড ML পুনঃপ্রশিক্ষণ শুরু করবেন? এটি সমস্ত ঐতিহাসিক ডেটা নিষ্কাশন করবে এবং Random Forest অপ্টিমাইজেশন চালাবে।') ?>")) return;
    
    this.disabled = true;
    this.innerText = "<?= $t('⏳ Training in progress...', '⏳ প্রশিক্ষণ চলছে...') ?>";
    
    fetch('retrain_trigger.php')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
            } else {
                alert("<?= $t('Error:', 'ত্রুটি:') ?> " + data.message);
            }
        })
        .catch(err => alert("<?= $t('Network error', 'নেটওয়ার্ক ত্রুটি') ?>"))
        .finally(() => {
            this.innerText = "<?= $t('⚙️ Retrain Model Now', '⚙️ মডেল পুনরায় প্রশিক্ষণ দিন') ?>";
            this.disabled = false;
        });
});
</script>

</body>
</html>
