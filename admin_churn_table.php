<?php
require_once 'includes/config.php';
requireLogin();

global $pdo;
$currentUser = getCurrentUser($pdo);
if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$message = '';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS user_ml_scores (
        user_id INT NOT NULL PRIMARY KEY,
        churn_score DECIMAL(6,5) NOT NULL,
        risk_label ENUM('low', 'medium', 'high') NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_ml_scores_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB;
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bonus_user_id'])) {
    $bonusUserId = (int) $_POST['bonus_user_id'];

    $stmt = $pdo->prepare("
        INSERT INTO rewards (user_id, total_points)
        VALUES (?, 50)
        ON DUPLICATE KEY UPDATE total_points = total_points + 50
    ");
    $stmt->execute([$bonusUserId]);
    $message = '50 bonus points sent successfully.';
}

$stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        s.churn_score,
        s.risk_label,
        COALESCE(GREATEST(DATEDIFF(CURDATE(), MAX(CASE WHEN p.status = 'completed' THEN p.schedule_date END)), 0), 999) AS days_since_pickup
    FROM user_ml_scores s
    INNER JOIN users u ON u.id = s.user_id
    LEFT JOIN pickups p ON p.user_id = u.id
    WHERE s.risk_label = 'high'
      AND u.role = 'user'
    GROUP BY u.id, u.name, u.email, s.churn_score, s.risk_label
    ORDER BY s.churn_score DESC
");
$highRiskUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scoreCount = (int) $pdo->query("SELECT COUNT(*) AS cnt FROM user_ml_scores")->fetch()['cnt'];

function churnBadgeClass(float $score): string {
    if ($score > 0.70) {
        return 'badge-red';
    }
    if ($score >= 0.40) {
        return 'badge-yellow';
    }
    return 'badge-green';
}
?>

<style>
.churn-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}
.churn-table th,
.churn-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
}
.churn-badge {
    display: inline-block;
    min-width: 72px;
    padding: 4px 8px;
    border-radius: 6px;
    color: #111827;
    font-weight: 700;
    text-align: center;
}
.badge-red {
    background: #fecaca;
    color: #991b1b;
}
.badge-yellow {
    background: #fef3c7;
    color: #92400e;
}
.badge-green {
    background: #dcfce7;
    color: #166534;
}
.bonus-button {
    border: 0;
    border-radius: 6px;
    padding: 8px 12px;
    background: #15803d;
    color: #ffffff;
    cursor: pointer;
    font-weight: 700;
}
.bonus-message {
    margin-top: 12px;
    color: #166534;
    font-weight: 700;
}
</style>

<?php if ($message): ?>
    <div class="bonus-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
    <div>
        <h2 style="margin: 0; color: var(--green-dark);">AI Churn Risk Monitor</h2>
        <p style="margin: 0.35rem 0 0; color: var(--text-muted);">Users most likely to become inactive, based on pickup and reward behavior.</p>
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
        <!-- Risk filter -->
        <select id="riskFilter" class="filter-select" onchange="applyChurnFilters()" title="Filter by risk level">
            <option value="">⚠️ All Risk Levels</option>
            <option value="high">🔴 High (&gt;70%)</option>
            <option value="medium">🟡 Medium (40-70%)</option>
            <option value="low">🟢 Low (&lt;40%)</option>
        </select>
        <div class="table-search-wrap">
            <span class="table-search-icon">🔍</span>
            <input
                type="text"
                id="churnSearch"
                class="table-search-input"
                placeholder="Search name or email..."
                oninput="applyChurnFilters()"
                autocomplete="off"
            >
        </div>
        <div style="font-weight: 700; color: var(--text-muted); white-space:nowrap;"><?= (int) $scoreCount ?> scored users</div>
    </div>
</div>

<?php if ($scoreCount === 0): ?>
    <div style="padding: 1rem; border: 1px solid #f59e0b; background: #fffbeb; border-radius: 8px; color: #92400e; margin-bottom: 1rem;">
        No ML scores found yet. Run <strong>python score_users.py</strong> from the project folder once, then refresh this dashboard.
    </div>
<?php endif; ?>

<table class="churn-table" id="churnTable">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Days Since Pickup</th>
            <th>Churn Score</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$highRiskUsers): ?>
            <tr>
                <td colspan="5">No high-risk users found.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($highRiskUsers as $user): ?>
            <?php
                $score = (float) $user['churn_score'];
                $scorePercent = round($score * 100);
            ?>
            <tr data-score-pct="<?= $scorePercent ?>">
                <td><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $user['days_since_pickup'] ?></td>
                <td>
                    <span class="churn-badge <?= churnBadgeClass($score) ?>">
                        <?= $scorePercent ?>%
                    </span>
                </td>
                <td>
                    <form method="post">
                        <input type="hidden" name="bonus_user_id" value="<?= (int) $user['id'] ?>">
                        <button class="bonus-button" type="submit">Send 50 Bonus Points</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
function applyChurnFilters() {
    const q    = document.getElementById('churnSearch').value.trim().toLowerCase();
    const risk = document.getElementById('riskFilter').value;
    document.querySelectorAll('#churnTable tbody tr').forEach(row => {
        const textOk = !q || row.textContent.toLowerCase().includes(q);
        const pct = parseInt(row.dataset.scorePct, 10);
        let riskOk = true;
        if (risk === 'high')   riskOk = pct > 70;
        if (risk === 'medium') riskOk = pct >= 40 && pct <= 70;
        if (risk === 'low')    riskOk = pct < 40;
        row.style.display = (textOk && riskOk) ? '' : 'none';
    });
}
</script>
<style>
.filter-select {
    padding: 0.45rem 0.75rem;
    border-radius: 10px;
    border: 1.5px solid #d1fae5;
    background: #f0fdf4;
    color: #15803d;
    font-size: 0.88rem; font-weight: 600;
    cursor: pointer; outline: none;
    transition: border-color 0.22s, box-shadow 0.22s;
    font-family: inherit;
}
.filter-select:focus { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.14); }
body.dark-mode .filter-select { background: #132314; border-color: #2d4a2e; color: #4ade80; }
body.dark-mode .filter-select:focus { border-color: #22c55e; background: #1a2e1b; }
</style>
