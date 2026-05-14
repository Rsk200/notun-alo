<?php
// ============================================
// user_recent_activity.php - Recent Activity (Clean/Light Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);

$stmt = $pdo->prepare("SELECT * FROM pickups WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$userId]);
$pickups = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Activity — Notun Alo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --bg-page: #F5F7F2;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); }
        .wrapper { max-width: 1000px; margin: 0 auto; padding: 40px 24px; }
        
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--brand-primary); text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 24px; transition: 0.2s; }
        .back-link:hover { transform: translateX(-5px); }

        .white-card { background: white; border: 1px solid var(--border); border-radius: 20px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
        .sub-text { font-size: 14px; color: #9CA3AF; margin-bottom: 32px; }

        .table-container { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th { text-align: left; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; padding: 16px; border-bottom: 1px solid var(--border); }
        td { padding: 16px; border-bottom: 1px solid #F9FAFB; font-size: 14px; vertical-align: middle; }
        
        .status-badge { font-size: 10px; font-weight: 700; padding: 6px 12px; border-radius: 99px; text-transform: uppercase; }
        .st-completed { background: #DCFCE7; color: #166534; }
        .st-pending { background: #FEF3C7; color: #92400E; }
        .st-scheduled { background: #DBEAFE; color: #1E40AF; }

        .empty-state { text-align: center; padding: 60px 0; color: var(--text-muted); }
        .empty-icon { font-size: 48px; margin-bottom: 16px; display: block; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="wrapper">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="white-card">
            <h1>Recent Activity</h1>
            <p class="sub-text">History of your recycling pickups and points earned.</p>

            <?php if (empty($pickups)): ?>
                <div class="empty-state">
                    <span class="empty-icon">🌱</span>
                    <p>No activity yet. Your recycling journey starts with your first pickup!</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Weight</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pickups as $p): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:var(--text-primary);"><?= e($p['category']) ?></div>
                                </td>
                                <td><?= e($p['subcategory'] ?? '—') ?></td>
                                <td><?= number_format($p['estimated_weight'], 1) ?> kg</td>
                                <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                                <td>
                                    <?php 
                                        $st = strtolower($p['status']);
                                        $cls = 'st-' . $st;
                                    ?>
                                    <span class="status-badge <?= $cls ?>"><?= e($p['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
