<?php
// ============================================
// agency.php - Agency Driver Portal
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireRole('agency');
startSession();

$agencyId = (int)$_SESSION['user_id'];
$flash    = null;

// ---- Handle: Mark as Collected ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_collected'])) {
    $pickupId = (int)($_POST['pickup_id'] ?? 0);
    $weight   = (float)($_POST['actual_weight'] ?? 0);

    if (
        ($pickupId && $weight > 0)
        || ($pickupId && ($_POST['task_type'] ?? '') === 'delivery')
    ) {
        // Mark as completed
        if (($_POST['task_type'] ?? '') === 'delivery') {

    // Mark delivery task completed
    $pdo->prepare("
        UPDATE pickups
        SET status = 'completed'
        WHERE id = ? AND agency_id = ?
    ")->execute([$pickupId, $agencyId]);

    // Mark related order delivered
    $pdo->prepare("
        UPDATE orders
        SET status = 'delivered'
        WHERE user_id = (
            SELECT user_id FROM pickups WHERE id = ?
        )
        AND agency_id = ?
        AND status = 'assigned'
    ")->execute([$pickupId, $agencyId]);

} else {

    // Normal pickup completion
    $pdo->prepare("
        UPDATE pickups
        SET status = 'completed',
            estimated_weight = ?
        WHERE id = ? AND agency_id = ?
    ")->execute([$weight, $pickupId, $agencyId]);

}

        // Get pickup category and user
        $pickup = $pdo->prepare("SELECT user_id, category FROM pickups WHERE id = ?");
        $pickup->execute([$pickupId]);
        $pickupData = $pickup->fetch();

        if ($pickupData) {
            $pts = match($pickupData['category']) {
                'Paper'   => POINTS_PAPER,
                'Plastic' => POINTS_PLASTIC,
                'Metal'   => POINTS_METAL,
                default   => 5,
            };
            $earnedPts = (int)round($pts * $weight);
            $uid       = (int)$pickupData['user_id'];

            // Upsert rewards
            $pdo->prepare(
                "INSERT INTO rewards (user_id, total_points, lifetime_points) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                    total_points = total_points + VALUES(total_points),
                    lifetime_points = lifetime_points + VALUES(lifetime_points)"
            )->execute([$uid, $earnedPts, $earnedPts]);
        }

        $flash = ['type' => 'success', 'message' => "Pickup #$pickupId marked as collected! User rewarded."];
    }
}

// ---- Fetch assigned pickups ----
$assigned = $pdo->prepare(
    "SELECT 
    p.*,
    u.name as user_name,
    u.address,
    u.phone,
    o.product_id,
    pr.name AS product_name
    FROM pickups p

    JOIN users u 
        ON u.id = p.user_id

    LEFT JOIN orders o
        ON o.user_id = p.user_id
        AND o.agency_id = p.agency_id
        AND o.status = 'assigned'

    LEFT JOIN products pr
        ON pr.id = o.product_id

    WHERE p.agency_id = ?
    AND p.status = 'assigned'

    ORDER BY p.created_at DESC"
);
$assigned->execute([$agencyId]);
$tasks = $assigned->fetchAll();

// ---- Fetch completed pickups ----
$done = $pdo->prepare(
    "SELECT p.*, u.name as user_name FROM pickups p
     JOIN users u ON u.id = p.user_id
     WHERE p.agency_id = ? AND p.status = 'completed'
     ORDER BY p.created_at DESC LIMIT 10"
);
$done->execute([$agencyId]);
$completed = $done->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agency Portal — Notun Alo</title>
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

        <div class="page-header">
            <div>
                <h1 class="page-title" data-reveal><?= $lang['agency_portal'] ?? 'Agency Portal' ?> 🚛</h1>
                <p class="page-sub"><?= $lang['assigned_tasks'] ?? 'Your assigned pickup tasks' ?> — <?= e($_SESSION['name']) ?></p>
            </div>
            <div class="badge badge-assigned" style="font-size:1rem;padding:.5rem 1rem;">
                <?= en2bn(count($tasks)) ?> <?= $lang['pending_tasks'] ?? 'Pending Tasks' ?>
            </div>
        </div>

        <!-- Active Tasks -->
        <section class="card" data-reveal>
            <div class="card-header" data-reveal>
                <h2 class="card-title" data-reveal>📋 <?= $lang['assigned_tasks'] ?? 'Assigned Tasks' ?></h2>
                <p class="card-sub" data-reveal><?= $lang['tasks_requiring_collection'] ?? 'Tasks requiring collection' ?></p>
            </div>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <p class="empty-icon">🎉</p>
                    <p><?= $lang['all_caught_up'] ?? 'All caught up! No pending tasks assigned to you.' ?></p>
                </div>
            <?php else: ?>
                <div class="task-list">
                    <?php foreach ($tasks as $t): ?>
                    <div class="task-card" data-reveal>
                        <div class="task-card__header">
                            <span class="task-cat">
                                    <?php if (($t['task_type'] ?? '') === 'delivery'): ?>
                                        📦 For Delivery
                                    <?php else: ?>
                                        🚚 For Pickup
                                    <?php endif; ?>
                                </span>
                                <span class="task-id">
                                    <?= ($t['task_type'] ?? '') === 'delivery' ? 'Delivery' : 'Pickup' ?>
                                    #<?= $t['id'] ?>
                                </span>
                        </div>
                        <div class="task-card__body">
                            <div class="task-info-row">
                                <span>👤</span>
                                <div>
                                    <strong><?= e($t['user_name']) ?></strong>
                                    <p><?= e($t['phone']) ?></p>
                                </div>
                            </div>
                            <div class="task-info-row">
                                <span>
                                    <?= ($t['task_type'] ?? '') === 'delivery' ? '📦' : '♻️' ?>
                                </span>
                                <div>
                                    <p>
                                        <strong>
                                            <?= ($t['task_type'] ?? '') === 'delivery' ? 'Product:' : 'Category:' ?>
                                        </strong>
                                        <?= ($t['task_type'] ?? '') === 'delivery'
                                            ? e($t['product_name'] ?? '')
                                            : e($t['category'])
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <div class="task-info-row">
                                <span>📍</span>
                                <p><?= e($t['address']) ?></p>
                            </div>
                            <div class="task-info-row">
                                <span>📅</span>
                                <p><?= $lang['scheduled'] ?? 'Scheduled:' ?> <strong><?= en2bn(date('d M Y', strtotime($t['schedule_date']))) ?></strong></p>
                            </div>
                                <?php if (($t['task_type'] ?? '') !== 'delivery'): ?>

                                <div class="task-info-row">
                                    <span>⚖</span>

                                    <p>
                                        <?= $lang['weight'] ?? 'Est. Weight:' ?>
                                        <strong><?= en2bn(number_format($t['estimated_weight'], 1)) ?> KG</strong>
                                    </p>
                                </div>

                                <?php endif; ?>
                            </div>
                        <div class="task-card__footer">
                            <form method="POST" class="collect-form">
                                <input type="hidden" name="pickup_id" value="<?= $t['id'] ?>">
                                <input type="hidden" name="task_type" value="<?= e($t['task_type']) ?>">
                                    <?php if (($t['task_type'] ?? '') !== 'delivery'): ?>
                                    <div class="form-group">
                                        <label><?= $lang['actual_weight_collected'] ?? 'Actual Weight Collected (KG)' ?></label>

                                        <input type="number"
                                            name="actual_weight"
                                            step="0.1"
                                            min="0.1"
                                            placeholder="e.g. 5.5"
                                            required>
                                    </div>
                                    <?php endif; ?>
                                <button type="submit" name="mark_collected" class="btn btn-primary btn-full">
                                    <?= ($t['task_type'] ?? '') === 'delivery'
                                        ? '📦 Mark as Delivered'
                                        : '✅ Mark as Collected'
                                    ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Completed Tasks -->
        <section class="card" data-reveal>
            <div class="card-header" data-reveal>
                <h2 class="card-title" data-reveal>✅ <?= $lang['recently_completed'] ?? 'Recently Completed' ?></h2>
                <p class="card-sub" data-reveal><?= $lang['last_10_completed'] ?? 'Last 10 completed pickups' ?></p>
            </div>
            <?php if (empty($completed)): ?>
                <div class="empty-state">
                    <p><?= $lang['no_pickups'] ?? 'No completed pickups yet.' ?></p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data-table" data-sortable>
                        <thead>
                            <tr><th>#</th><th><?= $lang['user'] ?? 'User' ?></th><th><?= $lang['category'] ?? 'Category' ?></th><th><?= $lang['weight'] ?? 'Weight' ?></th><th><?= $lang['date'] ?? 'Date' ?></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed as $index => $c): ?>
                            <tr>
                                <td><?= en2bn($index + 1) ?></td>
                                <td><?= e($c['user_name']) ?></td>
                                <td><?= translateStatus($c['category']) ?></td>
                                <td><?= en2bn(number_format($c['estimated_weight'], 1)) ?> KG</td>
                                <td><?= en2bn(date('d M Y', strtotime($c['created_at']))) ?></td>
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
</body>
</html>
