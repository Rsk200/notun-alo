<?php
// ============================================
// shop.php - Upcycle Shop
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
startSession();

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$points = $userId ? getUserPoints($pdo, $userId) : 0;
$flash  = null;
$currentLang = $_SESSION['lang'] ?? 'en';

// ---- Fetch Products ----
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentLang === 'bn' ? 'শপ — নতুন আলো' : 'Upcycle Shop — Notun Alo' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .shop-pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 32px; margin-bottom: 40px; }
        .shop-pagination .pg-btn { border: 1px solid var(--border); background: white; color: var(--text-secondary); padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .shop-pagination .pg-btn:hover:not(:disabled) { background: var(--bg-subtle); color: var(--brand-primary); }
        .shop-pagination .pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .shop-pagination .pg-num { width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-weight: 600; }
        .shop-pagination .pg-num.active { background: var(--brand-primary); color: white; border-color: var(--brand-primary); }
        
        .shop-controls input:focus, .shop-controls select:focus { border-color: var(--brand-primary) !important; box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
    </style>
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
                <h1 class="page-title" data-reveal><?= $currentLang === 'bn' ? '🛍 শপ' : '🛍 Upcycle Shop' ?></h1>
                <p class="page-sub"><?= $currentLang === 'bn' ? 'পরিবেশবান্ধব পণ্যের জন্য আপনার গ্রিন পয়েন্ট রিডিম করুন' : 'Redeem your green points for eco-friendly goods' ?></p>
            </div>
            <div class="points-display">
                <?php if ($userId): ?>
                <span class="points-label"><?= $currentLang === 'bn' ? 'আপনার ব্যালেন্স' : 'Your Balance' ?></span>
                <span class="points-amount">🏆 <?= $currentLang === 'bn' ? en2bn(number_format($points)) : number_format($points) ?> <?= $currentLang === 'bn' ? 'পয়েন্ট' : 'pts' ?></span>
                <?php else: ?>
                <span class="points-label"><?= $currentLang === 'bn' ? 'পণ্য দেখতে' : 'Browse Products' ?></span>
                <span class="points-amount">🔓 <a href="login.php" style="color:var(--gold-dark);"><?= $currentLang === 'bn' ? 'লগইন করুন' : 'Login to buy' ?></a></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="shop-controls" style="display: flex; gap: 16px; margin-bottom: 2rem; flex-wrap: wrap; align-items: center;">
            <input type="text" id="shopSearch" placeholder="<?= $currentLang === 'bn' ? 'পণ্যের নাম দিয়ে অনুসন্ধান করুন...' : 'Search products by name...' ?>" style="flex: 1; min-width: 250px; padding: 0.8rem 1.2rem; border-radius: 30px; border: 2px solid var(--border); font-size: 1rem; background: var(--card-bg); color: var(--text-primary); outline: none;">
            
            <select id="categoryFilter" style="padding: 0.8rem 1.2rem; border-radius: 30px; border: 2px solid var(--border); font-size: 1rem; background: var(--card-bg); color: var(--text-primary); outline: none; cursor: pointer; min-width: 180px;">
                <option value="ALL"><?= $currentLang === 'bn' ? 'সব বিভাগ' : 'All Categories' ?></option>
                <?php 
                $categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
                foreach($categories as $cat) {
                    echo "<option value=\"" . e($cat) . "\">" . e($cat) . "</option>";
                }
                ?>
            </select>
        </div>

        <!-- Product Grid -->
        <?php if (empty($products)): ?>
            <div class="empty-state card" data-reveal>
                <p class="empty-icon">🌿</p>
                <p><?= $currentLang === 'bn' ? 'এখনো কোনো পণ্য নেই। শীঘ্রই আবার চেক করুন!' : 'No products available yet. Check back soon!' ?></p>
            </div>
        <?php else: ?>
        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $prod): ?>
            <div class="product-card <?= $prod['stock'] === 0 ? 'product-card--oos' : '' ?>" data-category="<?= e($prod['category'] ?? 'General') ?>" data-reveal>
                <div class="product-img-wrap">
                    <?php if ($prod['image_url']): ?>
                        <img src="<?= e($prod['image_url']) ?>" alt="<?= e($prod['name']) ?>" class="product-img" loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">🌿</div>
                    <?php endif; ?>
                    <?php if ($prod['stock'] === 0): ?>
                        <div class="oos-overlay"><?= $currentLang === 'bn' ? 'স্টক শেষ' : 'Out of Stock' ?></div>
                    <?php endif; ?>
                </div>
                <div class="product-body">
                    <h3 class="product-name"><?= e($prod['name']) ?></h3>
                    <p class="product-desc"><?= e($prod['description']) ?></p>
                    <div class="product-prices">
                        <span class="price-pts">🏆 <?= $currentLang === 'bn' ? en2bn(number_format($prod['price_points'])) : number_format($prod['price_points']) ?> <?= $currentLang === 'bn' ? 'পয়েন্ট' : 'pts' ?></span>
                        <span class="price-cash">৳<?= $currentLang === 'bn' ? en2bn(number_format($prod['price_cash'], 2)) : number_format($prod['price_cash'], 2) ?></span>
                    </div>
                    <?php if ($prod['stock'] > 0): ?>
                    <div class="product-actions">
                        <a href="purchase.php?id=<?= $prod['id'] ?>" class="btn btn-accent btn-full"><?= $currentLang === 'bn' ? 'ক্রয় করুন' : 'Purchase' ?></a>
                    </div>
                    <?php else: ?>
                        <button class="btn btn-disabled btn-full" disabled><?= $currentLang === 'bn' ? 'স্টক শেষ' : 'Out of Stock' ?></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination Container -->
        <div id="shopPagination" class="shop-pagination"></div>
        
        <?php endif; ?>

    </div>
</main>

<script>
    const searchInput = document.getElementById('shopSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    
    const lang = "<?= $currentLang ?>";
    const prevText = lang === 'bn' ? '&larr; পূর্ববর্তী' : '&larr; Previous';
    const nextText = lang === 'bn' ? 'পরবর্তী &rarr;' : 'Next &rarr;';
    
    let currentPage = 1;
    const itemsPerPage = 12;

    function renderShop() {
        if (productCards.length === 0) return;
        
        const term = searchInput ? searchInput.value.toLowerCase() : '';
        const cat = categoryFilter ? categoryFilter.value : 'ALL';
        
        let filtered = [];
        
        productCards.forEach(card => {
            const title = card.querySelector('.product-name').textContent.toLowerCase();
            const cardCat = card.dataset.category || 'General';
            
            const matchSearch = title.includes(term);
            const matchCat = cat === 'ALL' || cardCat === cat;
            
            if (matchSearch && matchCat) {
                filtered.push(card);
            }
            card.style.display = 'none'; // hide all initially
        });
        
        const totalPages = Math.ceil(filtered.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = totalPages || 1;
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        for (let i = startIndex; i < endIndex && i < filtered.length; i++) {
            filtered[i].style.display = 'flex';
        }
        
        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const pgContainer = document.getElementById('shopPagination');
        if (!pgContainer) return;
        
        if (totalPages <= 1) {
            pgContainer.style.display = 'none';
            return;
        }
        
        pgContainer.style.display = 'flex';
        
        let html = `<button class="pg-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">${prevText}</button>`;
        
        for(let i = 1; i <= totalPages; i++) {
            html += `<button class="pg-btn pg-num ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        }
        
        html += `<button class="pg-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">${nextText}</button>`;
        
        pgContainer.innerHTML = html;
    }

    window.changePage = function(page) {
        currentPage = page;
        renderShop();
        window.scrollTo({ top: document.querySelector('.shop-controls').offsetTop - 20, behavior: 'smooth' });
    };

    if (searchInput) searchInput.addEventListener('input', () => { currentPage = 1; renderShop(); });
    if (categoryFilter) categoryFilter.addEventListener('change', () => { currentPage = 1; renderShop(); });
    
    // Initial Render
    renderShop();
</script>

</body>
</html>
