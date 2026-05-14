<?php
// ============================================
// admin_edit_product.php - Edit Product
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('admin');

$flash = null;
$productId = (int)($_GET['id'] ?? 0);

if (!$productId) {
    redirect('admin_inventory.php');
}

// ---- Handle Edit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $pts   = (int)($_POST['price_points'] ?? 0);
    $cash  = (float)($_POST['price_cash'] ?? 0);
    $img   = trim($_POST['image_url'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name && $pts > 0 && $cash > 0 && $stock >= 0) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price_points = ?, price_cash = ?, image_url = ?, stock = ? WHERE id = ?");
        $stmt->execute([$name, $desc, $pts, $cash, $img, $stock, $productId]);
        $flash = ['type' => 'success', 'message' => 'Product updated successfully!'];
    } else {
        $flash = ['type' => 'error', 'message' => 'Please provide valid values for all fields.'];
    }
}

// Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('admin_inventory.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container" style="max-width: 600px; margin: 0 auto;">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section class="card" data-reveal>
            <div class="card-header" data-reveal>
                <h2 class="card-title" data-reveal>✏ <?= $lang['edit_product'] ?? 'Edit Product' ?></h2>
                <p class="card-sub" data-reveal><?= $lang['edit_product_sub'] ?? 'Update product details below' ?></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="edit_product" value="1">
                <div class="form-group">
                    <label><?= $lang['product_name'] ?? 'Product Name' ?> <span class="req">*</span></label>
                    <input type="text" name="name" value="<?= e($product['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label><?= $lang['description'] ?? 'Description' ?></label>
                    <textarea name="description" rows="3"><?= e($product['description']) ?></textarea>
                </div>
                <div style="display:flex; gap:1rem;">
                    <div class="form-group" style="flex:1;">
                        <label><?= $lang['points_price'] ?? 'Price (Points)' ?> <span class="req">*</span></label>
                        <input type="number" name="price_points" min="1" value="<?= $product['price_points'] ?>" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label><?= $lang['price_bdt'] ?? 'Price (Cash ৳)' ?> <span class="req">*</span></label>
                        <input type="number" step="0.01" min="1" name="price_cash" value="<?= $product['price_cash'] ?>" required>
                    </div>
                </div>
                <div style="display:flex; gap:1rem;">
                    <div class="form-group" style="flex:2;">
                        <label><?= $lang['image_url'] ?? 'Image URL' ?></label>
                        <input type="url" name="image_url" value="<?= e($product['image_url']) ?>" placeholder="https://...">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label><?= $lang['stock'] ?? 'Stock Count' ?> <span class="req">*</span></label>
                        <input type="number" name="stock" min="0" value="<?= $product['stock'] ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full"><?= $lang['update_product'] ?? 'Update Product' ?></button>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="admin_inventory.php" style="color: var(--text-muted); text-decoration: none;"><?= $lang['back_to_inventory'] ?? 'Back to Inventory' ?></a>
                </div>
            </form>
        </section>

    </div>
</main>
</body>
</html>
