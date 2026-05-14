<?php
// ============================================
// admin_add_product.php - Add New Product
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

$flash = null;

// ---- Handle: Add Product ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name        = trim($_POST['prod_name'] ?? '');
    $desc        = trim($_POST['prod_desc'] ?? '');
    $pricePts    = (int)($_POST['price_points'] ?? 0);
    $priceCash   = (float)($_POST['price_cash'] ?? 0);
    $imageUrl    = trim($_POST['image_url'] ?? '');
    $stock       = (int)($_POST['stock'] ?? 0);

    if ($name && $pricePts > 0 && $priceCash > 0 && $stock >= 0) {
        $pdo->prepare(
            "INSERT INTO products (name, description, price_points, price_cash, image_url, stock) VALUES (?,?,?,?,?,?)"
        )->execute([$name, $desc, $pricePts, $priceCash, $imageUrl, $stock]);
        $flash = ['type' => 'success', 'message' => "Product \"$name\" added to the shop!"];
    } else {
        $flash = ['type' => 'error', 'message' => 'Please fill in all required product fields.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <!-- Add Product -->
        <section class="card" data-reveal>
            <div class="card-header" data-reveal>
                <h2 class="card-title" data-reveal>➕ <?= $lang['add_new_product'] ?? 'Add New Product' ?></h2>
                <p class="card-sub" data-reveal><?= $lang['add_product_sub'] ?? 'Upload an upcycled item to the shop' ?></p>
            </div>
            <form method="POST" class="product-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><?= $lang['product_name'] ?? 'Product Name' ?> <span class="req">*</span></label>
                        <input type="text" name="prod_name" placeholder="e.g. Recycled Notebook" required>
                    </div>
                    <div class="form-group">
                        <label><?= $lang['image_url'] ?? 'Image URL' ?></label>
                        <input type="url" name="image_url" placeholder="https://...">
                    </div>
                </div>
                <div class="form-group">
                    <label><?= $lang['description'] ?? 'Description' ?></label>
                    <textarea name="prod_desc" rows="2" placeholder="Brief product description..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?= $lang['points_price'] ?? 'Price (Points)' ?> <span class="req">*</span></label>
                        <input type="number" name="price_points" placeholder="e.g. 150" min="1" required>
                    </div>
                    <div class="form-group">
                        <label><?= $lang['price_bdt'] ?? 'Price (BDT)' ?> <span class="req">*</span></label>
                        <input type="number" name="price_cash" placeholder="e.g. 120" min="1" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label><?= $lang['stock'] ?? 'Stock' ?> <span class="req">*</span></label>
                        <input type="number" name="stock" placeholder="e.g. 25" min="0" required>
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary"><?= $lang['add_product_btn'] ?? 'Add Product to Shop' ?></button>
            </form>
        </section>
    </div>
</main>
</body>
</html>
