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
$currentLang = $_SESSION['lang'] ?? 'en';

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
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
            --bg-card: #FFFFFF;
        }

        /* ===== DARK MODE ===== */
        body.dark-mode {
            --bg-page: #080f09;
            --bg-card: #0f1a10;
            --text-primary: #E2E8F0;
            --text-secondary: #94A3B8;
            --border: #1e3222;
            --brand-light: #0d2416;
            --brand-primary: #34d399;
        }
        body.dark-mode .white-card {
            background: var(--bg-card) !important;
            border-color: var(--border) !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.4) !important;
        }
        body.dark-mode label { color: #94A3B8 !important; }
        body.dark-mode select, body.dark-mode input {
            background: #0b130c !important;
            border-color: #1e3222 !important;
            color: #E2E8F0 !important;
        }
        body.dark-mode .points-box {
            background: #0d2416 !important;
            border-color: #1e3222 !important;
        }
        body.dark-mode .points-label { color: #34d399 !important; }
        body.dark-mode .back-link {
            background: #0d2416 !important;
            border-color: #1e3222 !important;
            color: #34d399 !important;
        }
        body.dark-mode .page-title { color: #a3e9cb !important; }
        body.dark-mode .page-subtitle { color: #64748b !important; }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); transition: background-color 0.4s ease, color 0.4s ease; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 40px 24px; }
        
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #065f46; background: #ecfdf5; border: 1px solid #d1fae5; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; }
        .back-link:hover { background: #d1fae5; transform: translateX(-2px); }

        .page-header-flex { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 24px; padding-top: 20px; }
        .header-text { display: flex; flex-direction: column; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #064e3b; font-weight: 700; margin-bottom: 4px; line-height: 1.1; }
        .page-subtitle { font-size: 1.05rem; color: #64748b; margin: 0; }

        .white-card { background: var(--bg-card, white); border: 1px solid var(--border); border-radius: 20px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: background-color 0.4s ease, border-color 0.4s ease; }

        .form-group { margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; transition: color 0.3s; }
        select, input { width: 100%; height: 48px; background: #F9FAFB; border: 1px solid var(--border); border-radius: 12px; padding: 0 16px; font-size: 14px; outline: none; transition: 0.2s; }
        select:focus, input:focus { border-color: var(--brand-primary); background: white; box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .btn-submit { width: 100%; height: 52px; background: var(--brand-primary); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-submit:hover { background: #065F46; transform: translateY(-1px); }

        .points-box { background: var(--brand-light); border: 1px solid #D1FAE5; padding: 16px; border-radius: 12px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; transition: background-color 0.3s, border-color 0.3s; }
        .points-label { font-size: 13px; font-weight: 600; color: #065F46; }
        .points-val { font-size: 18px; font-weight: 800; color: var(--brand-primary); }

        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEF2F2; color: #B91C1C; border: 1px solid #FEE2E2; }
    </style>
<style>
@media (max-width:767px) { .mobile-only { display:block; } .desktop-only { display:none; } }
@media (min-width:768px) { .mobile-only { display:none; } .desktop-only { display:block; } }
</style>
</head>
<body>

<?php $pageEmoji = '🚛'; include 'includes/mobile_nav.php'; ?>
<div class="desktop-only"><?php include 'includes/navbar.php'; ?></div>

<main class="wrapper">
        <header class="page-header-flex">
            <a href="dashboard.php" class="back-link">&larr; <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
            <div class="header-text">
                <h1 class="page-title"><?= $lang['request_pickup'] ?? 'Request a Pickup' ?></h1>
                <p class="page-subtitle"><?= $lang['schedule_hint'] ?? 'Schedule your next recycling collection' ?></p>
            </div>
        </header>
        
        <div class="white-card">

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><?= $lang['waste_category'] ?? 'Waste Category' ?></label>
                    <select id="category" name="category" required>
                        <option value=""><?= $lang['select_type'] ?? '— Select type —' ?></option>
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
                    <label><?= $lang['estimated_weight'] ?? 'Estimated Weight (KG)' ?></label>
                    <input type="number" id="weight" name="estimated_weight" placeholder="e.g. 5.5" min="0.1" step="0.1" required>
                </div>

                <div class="form-group">
                    <label><?= $lang['preferred_date'] ?? 'Preferred Pickup Date' ?></label>
                    <input type="date" name="schedule_date" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="points-box" id="pointsPreview" style="display:none">
                    <span class="points-label">Estimated Reward:</span>
                    <span class="points-val" id="previewValue">0 pts</span>
                </div>

                <button type="submit" class="btn-submit"><?= $lang['submit_request'] ?? 'Submit Request' ?></button>
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
