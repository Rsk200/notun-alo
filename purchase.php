<?php
// ============================================
// purchase.php - Purchase Product
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

$userId = (int)$_SESSION['user_id'];
$points = getUserPoints($pdo, $userId);
$flash  = null;

$productId = (int)($_GET['id'] ?? 0);
if (!$productId) {
    redirect('shop.php');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('shop.php');
}

// Handle Purchase Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_purchase'])) {
    $paymentType = $_POST['payment_type'] ?? '';

    // Re-check stock to prevent race condition
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $currentStock = $stmt->fetchColumn();

    if ($currentStock <= 0) {
        $flash = ['type' => 'error', 'message' => 'Sorry, this product is currently out of stock.'];
    } else {
        if ($paymentType === 'points') {
            if ($points < $product['price_points']) {
                $flash = ['type' => 'error', 'message' => 'Points are not sufficient.'];
            } else {
                // Sufficient points, process payment
                // Ensure points never become negative (checked above)
                $pdo->prepare("UPDATE rewards SET total_points = total_points - ? WHERE user_id = ?")
                    ->execute([$product['price_points'], $userId]);
                $pdo->prepare("INSERT INTO orders (user_id, product_id, payment_type, status) VALUES (?, ?, 'points', 'confirmed')")
                    ->execute([$userId, $productId]);
                $pdo->prepare("UPDATE products SET stock = stock - 1 WHERE id = ?")->execute([$productId]);

                $points -= $product['price_points'];
                $flash = ['type' => 'success', 'message' => 'Payment successful! Order placed using points.'];
            }
        } elseif ($paymentType === 'cash') {
            $pdo->prepare("INSERT INTO orders (user_id, product_id, payment_type, status) VALUES (?, ?, ?, 'pending')")
                ->execute([$userId, $productId, $paymentType]);
            $pdo->prepare("UPDATE products SET stock = stock - 1 WHERE id = ?")->execute([$productId]);
            
            $flash = ['type' => 'success', 'message' => 'Order placed! Prepare cash on delivery of ৳' . number_format($product['price_cash'], 2) . '.'];
        } else {
            $flash = ['type' => 'error', 'message' => 'Invalid payment method selected.'];
        }
        
        // Update product variable to reflect new stock
        $product['stock']--;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="main-content">
    <div class="container" style="max-width: 600px; margin: 0 auto;">

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php if ($flash['type'] === 'success'): ?>
                <div style="margin-top: 1rem; text-align: center;">
                    <a href="shop.php" class="btn btn-outline">Back to Shop</a>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$flash || $flash['type'] !== 'success'): ?>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Checkout</h2>
                <p class="card-sub">Review your product and select payment method</p>
            </div>
            
            <div style="display: flex; gap: 1rem; align-items: center; margin: 1rem 0; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                <?php if ($product['image_url']): ?>
                    <img src="<?= e($product['image_url']) ?>" alt="Product" style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover;">
                <?php endif; ?>
                <div>
                    <h3 style="margin-bottom: 0.5rem;"><?= e($product['name']) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;"><?= e($product['description']) ?></p>
                    <p style="margin-top: 0.5rem;"><strong>🏆 <?= number_format($product['price_points']) ?> pts</strong> or <strong>৳<?= number_format($product['price_cash'], 2) ?></strong></p>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <p><strong>Your Current Points:</strong> <?= number_format($points) ?> pts</p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="payment_type">Payment Method <span class="req">*</span></label>
                    <select name="payment_type" id="payment_type" required>
                        <option value="">— Select —</option>
                        <option value="points">Reward Points (<?= number_format($product['price_points']) ?> pts)</option>
                        <option value="cash">Cash on Delivery (৳<?= number_format($product['price_cash'], 2) ?>)</option>
                    </select>
                </div>
                
                <?php if ($product['stock'] > 0): ?>
                    <button type="submit" name="confirm_purchase" class="btn btn-primary btn-full">Confirm Purchase</button>
                <?php else: ?>
                    <button type="button" class="btn btn-disabled btn-full" disabled>Out of Stock</button>
                <?php endif; ?>
                <a href="shop.php" style="display: block; text-align: center; margin-top: 1rem; color: var(--text-muted); text-decoration: none;">Cancel and return to shop</a>
            </form>
        </section>
        <?php endif; ?>

    </div>
</main>
</body>
</html>
