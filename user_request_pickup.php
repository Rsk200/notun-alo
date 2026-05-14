<?php
// ============================================
// user_request_pickup.php - Request Pickup (Clean/Light Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
require_once 'includes/auto_assign_v2.php';
requireLogin();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);
$flashMsg = null;

$impactCategories = [
    'Paper'   => ['Mixed paper', 'Newspaper', 'Cardboard / OCC', 'Office paper (HGP)'],
    'Plastic' => ['Mixed plastic', 'PET (#1 bottles)', 'HDPE (#2 bottles)', 'PP (#5)', 'LDPE (#4 film)'],
    'Metal'   => ['Mixed metal', 'Aluminium cans', 'Steel / Iron', 'Copper wire'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $subcategory = trim((string)($_POST['subcategory'] ?? ''));
    $weight   = (float)($_POST['estimated_weight'] ?? 0);
    $date     = $_POST['schedule_date'] ?? '';

    $validCats = array_keys($impactCategories);
    if (!in_array($category, $validCats, true) || $weight <= 0 || empty($date)) {
        $flashMsg = ['type' => 'error', 'message' => 'Please fill all pickup fields correctly.'];
    } elseif (strtotime($date) < strtotime('today')) {
        $flashMsg = ['type' => 'error', 'message' => 'Schedule date must be today or in the future.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO pickups (user_id, category, subcategory, estimated_weight, status, schedule_date) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$userId, $category, $subcategory !== '' ? $subcategory : null, $weight, $date]);
        $newPickupId = (int)$pdo->lastInsertId();
        $aiResult = autoAssignPickupAI($newPickupId, $pdo);
        $flashMsg = ['type' => 'success', 'message' => $aiResult['success'] ? "Assigned to {$aiResult['agency_name']}!" : "Request submitted!"];
    }
}
$flash = $flashMsg ?? getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Pickup — Notun Alo</title>
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
            --border: #E5E7EB;
            --bg-page: #F5F7F2;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 24px; }
        
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--brand-primary); text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 24px; transition: 0.2s; }
        .back-link:hover { transform: translateX(-5px); }

        .white-card { background: white; border: 1px solid var(--border); border-radius: 20px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h1 { font-size: 28px; font-weight: 800; color: var(--text-primary); margin-bottom: 8px; }
        .sub-text { font-size: 14px; color: #9CA3AF; margin-bottom: 32px; }

        .form-group { margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        select, input { width: 100%; height: 48px; background: #F9FAFB; border: 1px solid var(--border); border-radius: 12px; padding: 0 16px; font-size: 14px; outline: none; transition: 0.2s; }
        select:focus, input:focus { border-color: var(--brand-primary); background: white; box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .btn-submit { width: 100%; height: 52px; background: var(--brand-primary); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-submit:hover { background: #065F46; transform: translateY(-1px); }

        .points-box { background: var(--brand-light); border: 1px solid #D1FAE5; padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .points-label { font-size: 13px; font-weight: 600; color: #065F46; }
        .points-val { font-size: 18px; font-weight: 800; color: var(--brand-primary); }

        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FEE2E2; }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="wrapper">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <div class="white-card">
            <h1>Schedule Pickup</h1>
            <p class="sub-text">We'll collect your items within 48 hours of your chosen date.</p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Waste Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select type</option>
                        <?php foreach ($impactCategories as $cat => $subs): ?>
                            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Subcategory (Optional)</label>
                    <select id="subcategory" name="subcategory">
                        <option value="">Use category average</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Estimated Weight (KG)</label>
                    <input type="number" id="weight" name="estimated_weight" placeholder="e.g. 5.5" min="0.1" step="0.1" required>
                </div>

                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="schedule_date" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="points-box" id="pointsPreview" style="display:none">
                    <span class="points-label">Estimated Reward:</span>
                    <span class="points-val" id="previewValue">0 pts</span>
                </div>

                <button type="submit" class="btn-submit">Request Pickup</button>
            </form>
        </div>
    </main>

    <script>
        const catPoints = { Paper: 5, Plastic: 8, Metal: 12 };
        const subcategories = <?= json_encode($impactCategories) ?>;
        const catEl = document.getElementById('category');
        const subcatEl = document.getElementById('subcategory');
        const wtEl = document.getElementById('weight');
        const prev = document.getElementById('pointsPreview');
        const prevVal = document.getElementById('previewValue');

        catEl.onchange = () => {
            const cat = catEl.value;
            subcatEl.innerHTML = '<option value="">Use category average</option>';
            if (cat && subcategories[cat]) {
                subcategories[cat].forEach(s => {
                    const o = document.createElement('option');
                    o.value = o.textContent = s;
                    subcatEl.appendChild(o);
                });
            }
            updatePreview();
        };

        wtEl.oninput = updatePreview;

        function updatePreview() {
            const cat = catEl.value, wt = parseFloat(wtEl.value);
            if (cat && wt > 0) {
                prev.style.display = 'flex';
                prevVal.textContent = Math.round((catPoints[cat] || 3) * wt) + ' pts';
            } else prev.style.display = 'none';
        }
    </script>
</body>
</html>
