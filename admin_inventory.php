<?php
// ============================================
// admin_inventory.php - Product Inventory
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

$flash = null;

// ---- Handle Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]);
    $flash = ['type' => 'success', 'message' => 'Product deleted successfully!'];
}

// ---- Products ----
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Inventory — Notun Alo</title>
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

        <!-- Product Inventory -->
        <section class="card" data-reveal>
            <!-- Card header: title + filter controls -->
            <div class="card-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                <div>
                    <h2 class="card-title">📦 <?= $lang['inventory_management'] ?? 'Product Inventory' ?></h2>
                    <p class="card-sub"><?= $lang['inventory_sub'] ?? 'All products currently in the upcycle shop' ?></p>
                </div>
                <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                    <!-- Stock filter -->
                    <select id="stockFilter" class="filter-select" onchange="applyFilters()" title="Filter by stock">
                        <option value="">📦 All Stock</option>
                        <option value="in">✅ In Stock</option>
                        <option value="out">❌ Out of Stock</option>
                    </select>
                    <!-- Search -->
                    <div class="table-search-wrap">
                        <span class="table-search-icon">🔍</span>
                        <input
                            type="text"
                            id="inventorySearch"
                            class="table-search-input"
                            placeholder="<?= $lang['search'] ?? 'Search products...' ?>"
                            oninput="applyFilters()"
                            autocomplete="off"
                        >
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table" data-sortable id="inventoryTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $lang['product'] ?? 'Product' ?></th>
                            <th><?= $lang['points_price'] ?? 'Points Price' ?></th>
                            <th><?= $lang['cash_price'] ?? 'Cash Price' ?></th>
                            <th><?= $lang['stock'] ?? 'Stock' ?></th>
                            <th data-no-sort><?= $lang['actions'] ?? 'Actions' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $prod): ?>
                        <tr data-stock="<?= $prod['stock'] > 0 ? 'in' : 'out' ?>">
                            <td><?= en2bn($index + 1) ?></td>
                            <td>
                                <?php if ($prod['image_url']): ?>
                                    <img src="<?= e($prod['image_url']) ?>" alt="" class="thumb">
                                <?php endif; ?>
                                <?= e($prod['name']) ?>
                            </td>
                            <td><?= en2bn(number_format($prod['price_points'])) ?> <?= $lang['pts'] ?? 'pts' ?></td>
                            <td>৳<?= en2bn(number_format($prod['price_cash'], 2)) ?></td>
                            <td>
                                <span class="badge <?= $prod['stock'] > 0 ? 'badge-completed' : 'badge-pending' ?>">
                                    <?= $prod['stock'] > 0 ? en2bn($prod['stock']) . ' ' . ($lang['left'] ?? 'left') : ($lang['out_of_stock'] ?? 'Out of stock') ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_edit_product.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-accent"><?= $lang['edit'] ?? 'Edit' ?></a>
                                <form method="POST" class="inline-form" onsubmit="return confirm('<?= $lang['delete_confirm'] ?? 'Are you sure you want to delete this product?' ?>');">
                                    <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                                    <button type="submit" name="delete_product" class="btn btn-sm" style="background:#e53935;border-color:#e53935;color:#fff;"><?= $lang['delete'] ?? 'Delete' ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script src="assets/js/sortable-table.js"></script>
<script>
function applyFilters() {
    const q = document.getElementById('inventorySearch').value.trim().toLowerCase();
    const stock = document.getElementById('stockFilter').value;
    document.querySelectorAll('#inventoryTable tbody tr').forEach(row => {
        const textMatch = !q || row.textContent.toLowerCase().includes(q);
        const stockMatch = !stock || row.dataset.stock === stock;
        row.style.display = (textMatch && stockMatch) ? '' : 'none';
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
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    outline: none;
    transition: border-color 0.22s, box-shadow 0.22s;
    font-family: inherit;
}
.filter-select:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34,197,94,.14);
}
body.dark-mode .filter-select {
    background: #132314;
    border-color: #2d4a2e;
    color: #4ade80;
}
body.dark-mode .filter-select:focus {
    border-color: #22c55e;
    background: #1a2e1b;
}
</style>
</body>
</html>
