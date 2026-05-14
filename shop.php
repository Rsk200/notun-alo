<?php
// ============================================
// shop.php - Upcycle Shop
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();
startSession();

$userId = (int)$_SESSION['user_id'];
$points = getUserPoints($pdo, $userId);
$flash  = null;



// ---- Fetch Products ----
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcycle Shop — Notun Alo</title>
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

        <div class="page-header">
            <div>
                <h1 class="page-title" data-reveal><?= $lang['shop'] ?? '🛍 Upcycle Shop' ?></h1>
                <p class="page-sub"><?= $lang['trash_to_treasure'] ?? 'Redeem your green points for eco-friendly goods' ?></p>
            </div>
            <div class="points-display">
                <span class="points-label"><?= $lang['reward_points'] ?? 'Your Balance' ?></span>
                <span class="points-amount">🏆 <?= number_format($points) ?> <?= $lang['pts'] ?? 'pts' ?></span>
            </div>
        </div>

        <!-- Search Bar -->
        <div style="margin-bottom: 2rem;">
            <input type="text" id="shopSearch" placeholder="<?= $lang['search_products'] ?? 'Search products by name...' ?>" style="width: 100%; max-width: 500px; padding: 0.8rem 1.2rem; border-radius: 30px; border: 2px solid var(--border); font-size: 1rem; background: var(--card-bg); color: var(--text);">
        </div>

        <!-- Product Grid -->
        <?php if (empty($products)): ?>
            <div class="empty-state card" data-reveal>
                <p class="empty-icon">🌿</p>
                <p>No products available yet. Check back soon!</p>
            </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $prod): ?>
            <div class="product-card <?= $prod['stock'] === 0 ? 'product-card--oos' : '' ?>" data-reveal>
                <div class="product-img-wrap">
                    <?php if ($prod['image_url']): ?>
                        <img src="<?= e($prod['image_url']) ?>" alt="<?= e($prod['name']) ?>" class="product-img" loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">🌿</div>
                    <?php endif; ?>
                    <?php if ($prod['stock'] === 0): ?>
                        <div class="oos-overlay">Out of Stock</div>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <h3 class="product-name"><?= e($prod['name']) ?></h3>
                    <p class="product-desc"><?= e($prod['description']) ?></p>
                    <div class="product-prices">
                        <span class="price-pts">🏆 <?= number_format($prod['price_points']) ?> pts</span>
                        <span class="price-cash">৳<?= number_format($prod['price_cash'], 2) ?></span>
                    </div>
                    <?php if ($prod['stock'] > 0): ?>
                    <div class="product-actions">
                        <a href="purchase.php?id=<?= $prod['id'] ?>" class="btn btn-accent btn-full">Purchase</a>
                    </div>
                    <?php else: ?>
                        <button class="btn btn-disabled btn-full" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<script>
    // Search Bar Logic
    const searchInput = document.getElementById('shopSearch');
    const productCards = document.querySelectorAll('.product-card');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            productCards.forEach(card => {
                const title = card.querySelector('.product-name').textContent.toLowerCase();
                if (title.includes(term)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
</script>

</body>
</html>
