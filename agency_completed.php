<?php
require_once 'includes/config.php';
requireRole('agency');
startSession();

$agencyId = (int)$_SESSION['user_id'];
$currentLang = $_SESSION['lang'] ?? 'en';

$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT p.*, u.name as user_name FROM pickups p
        JOIN users u ON u.id = p.user_id
        WHERE p.agency_id = ? AND p.status = 'completed'";

$params = [$agencyId];

if ($search) {
    $sql .= " AND (u.name LIKE ? OR p.category LIKE ? OR p.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'pickup') {
    $sql .= " AND (p.task_type IS NULL OR p.task_type = 'pickup')";
} elseif ($filter === 'delivery') {
    $sql .= " AND p.task_type = 'delivery'";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$completed = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Tasks — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        .completed-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
        .completed-filters { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .completed-filters input, .completed-filters select {
            padding: 10px 16px; border-radius: 10px; border: 2px solid var(--border); font-size: 0.95rem;
            background: var(--card-bg, white); color: var(--text-primary, #111); outline: none;
        }
        .completed-filters input:focus, .completed-filters select:focus { border-color: var(--brand-primary); }
        .completed-filters input { min-width: 220px; }
        .completed-filters select { min-width: 140px; cursor: pointer; }
        .completed-filters .btn-search {
            padding: 10px 20px; border-radius: 10px; border: none; background: var(--brand-primary); color: white;
            font-weight: 600; cursor: pointer; transition: 0.2s;
        }
        .completed-filters .btn-search:hover { opacity: 0.85; }
        .completed-table-wrap { background: var(--card-bg, white); border-radius: 14px; border: 1px solid var(--border); overflow: hidden; }
        .completed-table { width: 100%; border-collapse: collapse; }
        .completed-table th { background: var(--brand-light, #E6F5EE); padding: 14px 18px; text-align: left; font-size: 0.85rem; font-weight: 700; color: var(--text-secondary, #6B7280); text-transform: uppercase; letter-spacing: 0.05em; }
        .completed-table td { padding: 14px 18px; border-top: 1px solid var(--border); font-size: 0.95rem; }
        .completed-table tr:hover td { background: var(--bg-subtle, #F9FAFB); }
        .badge-cat { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 0.8rem; font-weight: 600; }
        .badge-pickup { background: #E6F5EE; color: #065F46; }
        .badge-delivery { background: #DBEAFE; color: #1E40AF; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--brand-primary); text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-bottom: 20px; }
        .back-link:hover { text-decoration: underline; }
        body.dark-mode .completed-table th { background: #0f2e1a; color: #a8b0a5; }
        body.dark-mode .completed-table { background: #0f1712; }
        body.dark-mode .completed-table td { border-color: #1f2e24; color: #88998a; }
        body.dark-mode .completed-table tr:hover td { background: #0a1a12; }
        body.dark-mode .completed-filters input, body.dark-mode .completed-filters select { background: #0f1712; border-color: #1f2e24; color: #a8b0a5; }
        body.dark-mode .completed-table-wrap { background: #0f1712; border-color: #1f2e24; }
        @media (max-width: 640px) {
            .completed-header { flex-direction: column; align-items: stretch; }
            .completed-filters { flex-direction: column; }
            .completed-filters input { min-width: auto; width: 100%; }
            .completed-table-wrap { overflow-x: auto; }
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<main class="main-content">
    <div class="container">
        <a href="agency.php" class="back-link">← <?= $currentLang === 'bn' ? 'এজেন্সি ড্যাশবোর্ডে ফিরুন' : 'Back to Agency Dashboard' ?></a>

        <div class="completed-header">
            <div>
                <h1 class="page-title" style="margin-bottom:4px;">✅ <?= $currentLang === 'bn' ? 'সম্পন্ন কাজ' : 'Completed Tasks' ?></h1>
                <p class="page-sub" style="margin:0;"><?= $currentLang === 'bn' ? 'সকল সম্পন্ন পিকআপ এবং ডেলিভারি' : 'All completed pickups and deliveries' ?></p>
            </div>
            <div class="completed-filters">
                <form method="GET" style="display:contents;">
                    <input type="text" name="search" placeholder="<?= $currentLang === 'bn' ? 'নাম, বিভাগ, বা আইডি দ্বারা খুঁজুন...' : 'Search by name, category, or ID...' ?>" value="<?= e($search) ?>">
                    <select name="filter">
                        <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>><?= $currentLang === 'bn' ? 'সব' : 'All Tasks' ?></option>
                        <option value="pickup" <?= $filter === 'pickup' ? 'selected' : '' ?>><?= $currentLang === 'bn' ? 'পিকআপ' : 'Pickups' ?></option>
                        <option value="delivery" <?= $filter === 'delivery' ? 'selected' : '' ?>><?= $currentLang === 'bn' ? 'ডেলিভারি' : 'Deliveries' ?></option>
                    </select>
                    <button type="submit" class="btn-search"><?= $currentLang === 'bn' ? 'খুঁজুন' : 'Search' ?></button>
                    <?php if ($search || $filter !== 'all'): ?>
                        <a href="agency_completed.php" style="color:var(--text-muted); font-size:0.85rem;"><?= $currentLang === 'bn' ? 'রিসেট' : 'Reset' ?></a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (empty($completed)): ?>
            <div class="empty-state" style="padding:60px 20px; text-align:center;">
                <p style="font-size:3rem; margin-bottom:16px;">📭</p>
                <p style="color:var(--text-muted); font-size:1.1rem;"><?= $currentLang === 'bn' ? 'কোনো সম্পন্ন কাজ পাওয়া যায়নি।' : 'No completed tasks found.' ?></p>
            </div>
        <?php else: ?>
            <div class="completed-table-wrap">
                <table class="completed-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?= $currentLang === 'bn' ? 'ব্যবহারকারী' : 'User' ?></th>
                            <th><?= $currentLang === 'bn' ? 'ধরন' : 'Type' ?></th>
                            <th><?= $currentLang === 'bn' ? 'বিভাগ' : 'Category' ?></th>
                            <th><?= $currentLang === 'bn' ? 'ওজন' : 'Weight' ?></th>
                            <th><?= $currentLang === 'bn' ? 'তারিখ' : 'Date' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completed as $index => $c): ?>
                        <tr>
                            <td style="font-weight:700; color:var(--text-primary);"><?= $index + 1 ?></td>
                            <td><strong><?= e($c['user_name']) ?></strong></td>
                            <td>
                                <?php if (($c['task_type'] ?? '') === 'delivery'): ?>
                                    <span class="badge-cat badge-delivery">📦 <?= $currentLang === 'bn' ? 'ডেলিভারি' : 'Delivery' ?></span>
                                <?php else: ?>
                                    <span class="badge-cat badge-pickup">♻️ <?= $currentLang === 'bn' ? 'পিকআপ' : 'Pickup' ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= translateStatus($c['category']) ?></td>
                            <td><?= number_format($c['estimated_weight'], 1) ?> KG</td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="text-align:center; margin-top:16px; color:var(--text-muted); font-size:0.9rem;">
                <?= $currentLang === 'bn' ? 'মোট' : 'Total' ?>: <?= count($completed) ?> <?= $currentLang === 'bn' ? 'টি সম্পন্ন কাজ' : 'completed tasks' ?>
            </p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
