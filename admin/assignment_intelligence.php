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
?>

<div class="container" style="padding: 2rem;">
    <h2>🧠 AI Smart Assignment Intelligence</h2>
    
    <!-- Top Stats -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <div style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4>Total AI Assignments</h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= $stats['total_ai'] ?: 0 ?></div>
            <small>This month</small>
        </div>
        <div style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4>Avg AI Confidence Score</h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= $stats['avg_score'] ?: '0.00' ?></div>
            <small>Scale 0.0 - 1.0</small>
        </div>
        <div style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4>Active Model Version</h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= e($stats['curr_version'] ?? 'N/A') ?></div>
            <small>Current predictor</small>
        </div>
        <div style="flex:1; background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h4>Agencies Available</h4>
            <div style="font-size: 2rem; font-weight: bold; color: var(--green, #2E7D32);"><?= $agencies_avail ?></div>
            <small>Ready for routing</small>
        </div>
    </div>

    <!-- Model Control Card -->
    <div style="background: var(--card-bg, #fff); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0 0 0.5rem 0;">Model Status</h4>
            <?php if($model): ?>
                <p style="margin:0;">Type: <strong><?= e($model['model_type']) ?></strong> | Trained on: <strong><?= $model['trained_on'] ?> pickups</strong> | Test MAE: <strong><?= $model['test_mae'] ?> hrs</strong> | Date: <strong><?= $model['trained_at'] ?></strong></p>
            <?php else: ?>
                <p style="margin:0; color: #dc3545;">No trained ML model found. Running in weighted-only fallback mode.</p>
            <?php endif; ?>
        </div>
        <button id="btnRetrain" style="padding: 0.75rem 1.5rem; background: var(--green, #2E7D32); color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            ⚙️ Retrain Model Now
        </button>
    </div>

    <!-- Score Breakdown Panel -->
    <h3 style="margin-bottom: 1rem;">🔬 Recent Score Breakdowns</h3>
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; overflow-x: auto;">
        <?php foreach(array_slice($recent, 0, 5) as $r): 
            if(!$r['score_total']) continue;
            // Weights logic defined in Python API
            $w_load = ($r['score_load'] * 0.35) * 100;
            $w_dist = ($r['score_distance'] * 0.20) * 100;
            $w_rat = ($r['score_rating'] * 0.12) * 100;
            $w_spec = ($r['score_specialty'] * 0.08) * 100;
            $w_comp = 100 - ($w_load + $w_dist + $w_rat + $w_spec); // The rest is completion + zone bonus
        ?>
        <div style="flex: 0 0 300px; background: var(--card-bg, #fff); padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-weight:bold; margin-bottom: 0.5rem;">Pickup #<?= $r['pickup_id'] ?> &rarr; <?= e($r['agency_name']) ?></div>
            <div style="font-size: 1.5rem; font-weight: bold; color: var(--green); margin-bottom: 0.5rem;"><?= $r['score_total'] ?></div>
            
            <div style="width: 100%; height: 20px; border-radius: 10px; overflow: hidden; display: flex; margin-bottom: 0.5rem; background: #eee;">
                <div style="width: <?= $w_load ?>%; background: #4caf50;" title="Load"></div>
                <div style="width: <?= $w_comp ?>%; background: #2196f3;" title="Completion Speed/Rate"></div>
                <div style="width: <?= $w_dist ?>%; background: #ff9800;" title="Distance"></div>
                <div style="width: <?= $w_rat ?>%; background: #e91e63;" title="Rating"></div>
                <div style="width: <?= $w_spec ?>%; background: #9c27b0;" title="Specialty"></div>
            </div>
            <div style="font-size: 0.8rem; color: #666; display: flex; flex-wrap: wrap; gap: 5px;">
                <span style="color:#4caf50;">■ Load</span>
                <span style="color:#2196f3;">■ Completion</span>
                <span style="color:#ff9800;">■ Dist</span>
                <span style="color:#e91e63;">■ Rate</span>
                <span style="color:#9c27b0;">■ Spec</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Agency Leaderboard (Live Data) -->
    <h3 style="margin-bottom: 1rem;">🏢 Agency Real-time Load Matrix</h3>
    <table style="width: 100%; text-align: left; border-collapse: collapse; margin-bottom: 2rem; background: var(--card-bg, #fff); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <thead>
            <tr style="border-bottom: 2px solid #eee;">
                <th style="padding: 1rem;">Agency</th>
                <th style="padding: 1rem;">Zone</th>
                <th style="padding: 1rem;">Active / Max</th>
                <th style="padding: 1rem;">Load %</th>
                <th style="padding: 1rem;">Total Comp.</th>
                <th style="padding: 1rem;">Avg Hrs</th>
                <th style="padding: 1rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($leaderboard as $ag): 
                $loadPct = $ag['load_ratio'] * 100;
                $color = $loadPct >= 100 ? '#dc3545' : ($loadPct > 60 ? '#ffc107' : '#28a745');
                $statusText = $loadPct >= 100 ? 'Full' : ($loadPct > 60 ? 'Busy' : 'Available');
                if($ag['is_available'] == 0) { $color = '#6c757d'; $statusText = 'Offline'; }
            ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 1rem;"><strong><?= e($ag['agency_name']) ?></strong><br><small><?= e($ag['specialty']) ?></small></td>
                <td style="padding: 1rem;"><?= e($ag['zone_label'] ?? 'Unassigned') ?></td>
                <td style="padding: 1rem;"><?= $ag['active_pickups'] ?> / <?= $ag['max_capacity'] ?></td>
                <td style="padding: 1rem;">
                    <div style="width: 100%; background: #e9ecef; border-radius: 4px; height: 8px;">
                        <div style="width: <?= min(100, $loadPct) ?>%; background: <?= $color ?>; height: 100%; border-radius: 4px;"></div>
                    </div>
                </td>
                <td style="padding: 1rem;"><?= $ag['total_completed'] ?></td>
                <td style="padding: 1rem;"><?= $ag['avg_completion_hrs'] ?: 'N/A' ?></td>
                <td style="padding: 1rem;"><span style="background: <?= $color ?>; color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;"><?= $statusText ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<script>
document.getElementById('btnRetrain').addEventListener('click', function() {
    if(!confirm("Start background ML retraining? This will extract all historical data and run Random Forest optimization.")) return;
    
    this.disabled = true;
    this.innerText = "⏳ Training in progress...";
    
    fetch('retrain_trigger.php')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                alert(data.message);
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => alert("Network error"))
        .finally(() => {
            this.innerText = "⚙️ Retrain Model Now";
            this.disabled = false;
        });
});
</script>

</body>
</html>
