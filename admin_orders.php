<?php
// ============================================
// admin_orders.php - Manage Orders
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

$flash = null;

// ---- Handle: Assign Agency to Order ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_agency'])) {
    $orderId  = (int)($_POST['order_id']  ?? 0);
    $agencyId = (int)($_POST['agency_id'] ?? 0);

    if ($orderId && $agencyId) {
        // Update order status + agency
        $pdo->prepare("UPDATE orders SET agency_id = ?, status = 'assigned' WHERE id = ?")
            ->execute([$agencyId, $orderId]);

        // Fetch order info to create a delivery task in pickups
        $stmt = $pdo->prepare("
            SELECT o.user_id, p.name AS product_name
            FROM orders o
            JOIN products p ON p.id = o.product_id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order) {
            $pdo->prepare("
                INSERT INTO pickups
                    (user_id, agency_id, category, estimated_weight, status, schedule_date, task_type)
                VALUES
                    (?, ?, ?, 0, 'assigned', CURDATE(), 'delivery')
            ")->execute([$order['user_id'], $agencyId, $order['product_name']]);
        }

        $flash = ['type' => 'success', 'message' => 'Agency assigned to order successfully!'];
    }
}

// ---- Orders — JOIN agency name so we can display who is assigned ----
$orders = $pdo->query(
    "SELECT o.*,
            p.name  AS product_name,
            p.price_points, p.price_cash,
            u.name  AS user_name,
            u.address, u.phone,
            ag.name AS agency_name
     FROM orders o
     JOIN products p  ON p.id  = o.product_id
     JOIN users u     ON u.id  = o.user_id
     LEFT JOIN users ag ON ag.id = o.agency_id
     ORDER BY o.created_at DESC"
)->fetchAll();

// ---- Agencies ----
$agencies = $pdo->query("SELECT id, name FROM users WHERE role='agency' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sortable-table.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Assigned agent chip */
        .agent-chip {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem; font-weight: 600;
            background: #e0f2f1; color: #00695c;
            border: 1px solid #b2dfdb;
            white-space: nowrap;
        }
        body.dark-mode .agent-chip { background: #0d2e2a; color: #4db6ac; border-color: #1a4a45; }

        /* Inline select + button */
        .assign-form { display: flex; gap: 0.45rem; align-items: center; flex-wrap: wrap; }
        .assign-form select {
            padding: 0.4rem 0.65rem;
            border-radius: 8px;
            border: 1.5px solid var(--border, #d1d5db);
            background: var(--card-bg, #fff);
            color: var(--text, #111);
            font-size: 0.85rem;
        }
        .assign-form select:focus { outline: none; border-color: #22c55e; }
        body.dark-mode .assign-form select { background: #1a2a1b; border-color: #2d4a2e; color: #f1f5f9; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <!-- Manage Orders -->
        <section class="card" data-reveal>
            <div class="card-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 class="card-title">📦 <?= $lang['manage_orders'] ?? 'Manage Orders' ?></h2>
                    <p class="card-sub"><?= $lang['manage_orders_sub'] ?? 'View product purchases and assign delivery agencies' ?></p>
                </div>
                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                    <select id="statusFilter" class="filter-select" onchange="applyFilters()" title="Filter by status">
                        <option value="">📋 All Status</option>
                        <option value="pending">⏳ Pending</option>
                        <option value="confirmed">✅ Confirmed</option>
                        <option value="assigned">🚚 Assigned</option>
                        <option value="completed">🎉 Completed</option>
                        <option value="cancelled">❌ Cancelled</option>
                    </select>
                    <select id="paymentFilter" class="filter-select" onchange="applyFilters()" title="Filter by payment">
                        <option value="">💳 All Payment</option>
                        <option value="points">🏆 Points</option>
                        <option value="cash">💵 Cash</option>
                    </select>
                    <div class="table-search-wrap">
                        <span class="table-search-icon">🔍</span>
                        <input type="text" id="ordersSearch" class="table-search-input"
                            placeholder="<?= $lang['search'] ?? 'Search orders...' ?>"
                            oninput="applyFilters()" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table" data-sortable id="ordersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $lang['user'] ?? 'User' ?></th>
                            <th><?= $lang['product'] ?? 'Product' ?></th>
                            <th><?= $lang['payment'] ?? 'Payment' ?></th>
                            <th><?= $lang['date'] ?? 'Date' ?></th>
                            <th><?= $lang['status'] ?? 'Status' ?></th>
                            <th data-no-sort><?= $lang['assign_agency'] ?? 'Assign / Agent' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center"><?= $lang['no_orders_found'] ?? 'No orders found.' ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $index => $o): ?>
                            <tr data-status="<?= e($o['status']) ?>" data-payment="<?= e($o['payment_type']) ?>">
                                <td><?= en2bn($index + 1) ?></td>
                                <td>
                                    <strong><?= e($o['user_name']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= e($o['phone']) ?></small>
                                </td>
                                <td><?= e($o['product_name']) ?></td>
                                <td><?= translateStatus($o['payment_type']) ?></td>
                                <td><?= en2bn(date('d M Y', strtotime($o['created_at']))) ?></td>
                                <td><span class="badge badge-<?= e($o['status']) ?>"><?= translateStatus($o['status']) ?></span></td>
                                <td>
                                    <?php if (in_array($o['status'], ['pending', 'confirmed'])): ?>
                                        <form method="POST" class="assign-form">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="agency_id" required>
                                                <option value=""><?= $lang['select'] ?? '— Select —' ?></option>
                                                <?php foreach ($agencies as $ag): ?>
                                                    <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_agency" class="btn btn-sm btn-primary"><?= $lang['assign'] ?? 'Assign' ?></button>
                                        </form>
                                    <?php elseif (!empty($o['agency_name'])): ?>
                                        <span class="agent-chip">🚚 <?= e($o['agency_name']) ?></span>
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
    const q      = document.getElementById('ordersSearch').value.trim().toLowerCase();
    const status  = document.getElementById('statusFilter').value;
    const payment = document.getElementById('paymentFilter').value;
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
        const textOk    = !q      || row.textContent.toLowerCase().includes(q);
        const statusOk  = !status  || row.dataset.status  === status;
        const paymentOk = !payment || row.dataset.payment === payment;
        row.style.display = (textOk && statusOk && paymentOk) ? '' : 'none';
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
</body>
</html>