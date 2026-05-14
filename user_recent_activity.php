<?php
// ============================================
// user_recent_activity.php - Recent Pickups List
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);

// Fetch recent pickups
$stmt = $pdo->prepare(
    "SELECT p.*, u.name as user_name FROM pickups p
     JOIN users u ON u.id = p.user_id
     WHERE p.user_id = ? 
     ORDER BY p.created_at DESC LIMIT 20"
);
$stmt->execute([$userId]);
$pickups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['recent_activity'] ?? 'My Recent Activity' ?> — Notun Alo</title>
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
                <a href="dashboard.php" class="btn-back">
                    <span class="btn-back__arrow">←</span>
                    <?= $lang['dashboard'] ?? 'Dashboard' ?>
                </a>
                <div>
                    <h1 class="page-title" data-reveal><?= $lang['recent_activity'] ?? 'My Recent Activity' ?></h1>
                    <p class="page-sub"><?= $lang['activity_hint'] ?? 'Your pickup history and statuses' ?></p>
                </div>
            </div>
        </div>

        <section class="card" data-reveal>
            <div class="card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                <div>
                    <h2 class="card-title" data-reveal>✅ <?= $lang['recently_completed'] ?? 'Recently Completed' ?></h2>
                    <p class="card-sub" data-reveal><?= $lang['last_10_completed'] ?? 'Last completed pickups' ?></p>
                </div>
                <div class="table-search-wrap">
                    <span class="table-search-icon">🔍</span>
                    <input
                        type="text"
                        id="tableSearchInput"
                        class="table-search-input"
                        placeholder="<?= $lang['search'] ?? 'Search...' ?>"
                        oninput="filterTable(this.value)"
                        autocomplete="off"
                    >
                </div>
            </div>
            
            <?php if (empty($pickups)): ?>
                <div class="empty-state">
                    <p class="empty-icon">🌱</p>
                    <p><?= $lang['no_pickups'] ?? 'No pickups yet. Request your first one!' ?></p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table" data-sortable id="activityTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= $lang['user'] ?? 'User' ?></th>
                                <th><?= $lang['category'] ?? 'Category' ?></th>
                                <th><?= $lang['weight'] ?? 'Weight' ?></th>
                                <th><?= $lang['date'] ?? 'Date' ?></th>
                                <th><?= $lang['status'] ?? 'Status' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pickups as $index => $p): ?>
                            <tr>
                                <td><?= en2bn($index + 1) ?></td>
                                <td><?= e($p['user_name']) ?></td>
                                <td><?= translateStatus($p['category']) ?></td>
                                <td><?= en2bn(number_format($p['estimated_weight'], 1)) ?> KG</td>
                                <td><?= en2bn(date('d M Y', strtotime($p['created_at']))) ?></td>
                                <td><span class="badge badge-<?= e($p['status']) ?>"><?= translateStatus($p['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </section>

    </div>
</main>
<script src="assets/js/sortable-table.js"></script>
<script>
function filterTable(query) {
    const q = query.trim().toLowerCase();
    const rows = document.querySelectorAll('#activityTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}
</script>
</body>
</html>
