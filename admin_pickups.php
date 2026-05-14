<?php
// ============================================
// admin_pickups.php - Manage Pickups
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

$flash = null;

// ---- Handle: Assign Agency to Pickup ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_agency'])) {
    $pickupId = (int)($_POST['pickup_id'] ?? 0);
    $agencyId = (int)($_POST['agency_id'] ?? 0);
    if ($pickupId && $agencyId) {
        $pdo->prepare("UPDATE pickups SET agency_id = ?, status = 'assigned' WHERE id = ?")
            ->execute([$agencyId, $pickupId]);
        $flash = ['type' => 'success', 'message' => 'Agency assigned successfully!'];
    }
}

// ---- Pickups — include agency name ----
$pickups = $pdo->query(
    "SELECT p.*, u.name as user_name, u.address, u.phone, a.name as agency_name 
     FROM pickups p 
     JOIN users u ON u.id = p.user_id 
     LEFT JOIN users a ON a.id = p.agency_id
     ORDER BY p.created_at DESC"
)->fetchAll();

// ---- Agencies ----
$agencies = $pdo->query("SELECT id, name FROM users WHERE role='agency' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pickups — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sortable-table.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <!-- Manage Pickups -->
        <section class="card" data-reveal>
            <div class="card-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="card-title" data-reveal>🚛 <?= $lang['manage_pickups'] ?? 'Manage Pickups' ?></h2>
                    <p class="card-sub" data-reveal><?= $lang['manage_pickups_sub'] ?? 'Assign agencies to pending pickup requests' ?></p>
                </div>
                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                    <select id="statusFilter" class="filter-select" onchange="applyFilters()" title="Filter by status">
                        <option value="">📋 All Status</option>
                        <option value="pending">⏳ Pending</option>
                        <option value="assigned">🚚 Assigned</option>
                        <option value="completed">✅ Completed</option>
                        <option value="cancelled">❌ Cancelled</option>
                    </select>
                    <select id="categoryFilter" class="filter-select" onchange="applyFilters()" title="Filter by category">
                        <option value="">📦 All Categories</option>
                        <option value="paper">📰 Paper</option>
                        <option value="plastic">🧴 Plastic</option>
                        <option value="metal">🔧 Metal</option>
                        <option value="glass">🫙 Glass</option>
                        <option value="e-waste">💻 E-Waste</option>
                        <option value="organic">🌿 Organic</option>
                    </select>
                    <div class="table-search-wrap">
                        <span class="table-search-icon">🔍</span>
                        <input type="text" id="pickupsSearch" class="table-search-input"
                            placeholder="<?= $lang['search'] ?? 'Search pickups...' ?>"
                            oninput="applyFilters()" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table" data-sortable id="pickupsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $lang['user'] ?? 'User' ?></th>
                            <th><?= $lang['category'] ?? 'Category' ?></th>
                            <th>Subcategory</th>
                            <th><?= $lang['weight'] ?? 'Weight' ?> (KG)</th>
                            <th><?= $lang['scheduled'] ?? 'Scheduled' ?></th>
                            <th><?= $lang['status'] ?? 'Status' ?></th>
                            <th data-no-sort><?= $lang['assign_agency'] ?? 'Assign Agency' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pickups)): ?>
                            <tr><td colspan="8" class="text-center"><?= $lang['no_pickups'] ?? 'No pickups found.' ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($pickups as $index => $p): ?>
                            <tr data-status="<?= e($p['status']) ?>" data-category="<?= strtolower(e($p['category'])) ?>">
                                <td><?= en2bn($index + 1) ?></td>
                                <td>
                                    <strong><?= e($p['user_name']) ?></strong><br>
                                    <small><?= en2bn(e($p['phone'])) ?></small>
                                </td>
                                <td><?= e($p['category']) ?></td>
                                <td><?= e($p['subcategory'] ?? 'Category average') ?></td>
                                <td><?= en2bn(number_format($p['estimated_weight'], 1)) ?></td>
                                <td><?= en2bn(date('d M Y', strtotime($p['schedule_date']))) ?></td>
                                <td><span class="badge badge-<?= e($p['status']) ?>"><?= translateStatus($p['status']) ?></span></td>
                                <td>
                                    <?php if ($p['status'] === 'pending'): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="pickup_id" value="<?= $p['id'] ?>">
                                        <select name="agency_id" required>
                                            <option value=""><?= $lang['select'] ?? '— Select —' ?></option>
                                            <?php foreach ($agencies as $ag): ?>
                                                <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="assign_agency" class="btn btn-sm btn-primary"><?= $lang['assign'] ?? 'Assign' ?></button>
                                    </form>
                                    <?php elseif (!empty($p['agency_name'])): ?>
                                        <span class="agent-chip">🚚 <?= e($p['agency_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<script src="assets/js/sortable-table.js"></script>
<script>
function applyFilters() {
    const q        = document.getElementById('pickupsSearch').value.trim().toLowerCase();
    const status   = document.getElementById('statusFilter').value;
    const category = document.getElementById('categoryFilter').value;
    document.querySelectorAll('#pickupsTable tbody tr').forEach(row => {
        const textOk     = !q        || row.textContent.toLowerCase().includes(q);
        const statusOk   = !status   || row.dataset.status   === status;
        const categoryOk = !category || row.dataset.category === category;
        row.style.display = (textOk && statusOk && categoryOk) ? '' : 'none';
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
.agent-chip {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.3rem 0.75rem; border-radius: 20px;
    font-size: 0.8rem; font-weight: 600;
    background: #e0f2f1; color: #00695c; border: 1px solid #b2dfdb;
}
body.dark-mode .agent-chip { background: #0d2e2a; color: #4db6ac; border-color: #1a4a45; }
.inline-form { display: flex; gap: 0.45rem; align-items: center; flex-wrap: wrap; }
.inline-form select {
    padding: 0.4rem 0.65rem; border-radius: 8px;
    border: 1.5px solid var(--border,#d1d5db); background: var(--card-bg,#fff);
    color: var(--text,#111); font-size: 0.85rem;
}
body.dark-mode .inline-form select { background: #1a2a1b; border-color: #2d4a2e; color: #f1f5f9; }
</style>
</body>
</html>
