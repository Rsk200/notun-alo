<?php
// ============================================
// user_request_pickup.php - Request Pickup Page
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
require_once 'includes/auto_assign_v2.php';
requireLogin();
startSession();


if ($_SESSION['role'] !== 'user') redirect('dashboard.php');


$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);
$flashMsg = null;
$subcatReadyStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pickups' AND column_name = 'subcategory'");
$subcatReadyStmt->execute();
if ((int)$subcatReadyStmt->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE pickups ADD COLUMN subcategory VARCHAR(140) NULL AFTER category");
}


$impactCategories = [
    'Paper'   => ['Mixed paper', 'Newspaper', 'Cardboard / OCC', 'Office paper (HGP)'],
    'Plastic' => ['Mixed plastic', 'PET (#1 bottles)', 'HDPE (#2 bottles)', 'PP (#5)', 'LDPE (#4 film)'],
    'Metal'   => ['Mixed metal', 'Aluminium cans', 'Steel / Iron', 'Copper wire'],
];


// Handle pickup request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_pickup'])) {
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
        if ($subcategory !== '' && !in_array($subcategory, $impactCategories[$category], true)) {
            $subcategory = '';
        }
        $stmt = $pdo->prepare(
            "INSERT INTO pickups (user_id, category, subcategory, estimated_weight, status, schedule_date) VALUES (?, ?, ?, ?, 'pending', ?)"
        );
        $stmt->execute([$userId, $category, $subcategory !== '' ? $subcategory : null, $weight, $date]);
        $newPickupId = (int)$pdo->lastInsertId();
       
        // Trigger AI Smart Assignment
        $aiResult = autoAssignPickupAI($newPickupId, $pdo);
       
        if ($aiResult['success']) {
            $flashMsg = ['type' => 'success', 'message' => "Pickup request submitted and instantly assigned to {$aiResult['agency_name']}!"];
        } else {
            $flashMsg = ['type' => 'success', 'message' => 'Pickup request submitted successfully! (Pending Assignment)'];
        }
    }
}
$flash = $flashMsg ?? getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['request_pickup'] ?? 'Request a Pickup' ?> — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sortable-table.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>


<?php include 'includes/navbar.php'; ?>


<main class="main-content">
    <div class="container" style="max-width: 600px;">
       
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>


        <div class="page-header">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <a href="dashboard.php" class="btn-back"><span class="btn-back__arrow">←</span><?= $lang['dashboard'] ?? 'Dashboard' ?></a>
                <div>
                    <h1 class="page-title" data-reveal><?= $lang['request_pickup'] ?? '📦 Request a Pickup' ?></h1>
                    <p class="page-sub"><?= $lang['schedule_hint'] ?? 'Schedule your next recycling collection' ?></p>
                </div>
            </div>
        </div>


        <section class="card" data-reveal>
            <form method="POST" class="pickup-form">
                <input type="hidden" name="request_pickup" value="1">
                <div class="form-group">
                    <label for="category"><?= $lang['waste_category'] ?? 'Waste Category' ?></label>
                    <select id="category" name="category" required>
                        <option value=""><?= $lang['select_type'] ?? 'Select type' ?></option>
                        <?php foreach ($impactCategories as $cat => $subs): ?>
                            <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subcategory">Subcategory</label>
                    <select id="subcategory" name="subcategory">
                        <option value="">Use category average</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estimated_weight"><?= $lang['estimated_weight'] ?? 'Estimated Weight (KG)' ?></label>
                    <input type="number" id="estimated_weight" name="estimated_weight"
                           placeholder="e.g. 5.5" min="0.1" step="0.1" required>
                </div>
                <div class="form-group">
                    <label for="schedule_date"><?= $lang['preferred_date'] ?? 'Preferred Pickup Date' ?></label>
                    <input type="date" id="schedule_date" name="schedule_date"
                           min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="points-preview" id="pointsPreview" style="display:none">
                    <span>Estimated Points Earned: </span>
                    <strong id="previewValue">0</strong>
                </div>
                <button type="submit" class="btn btn-primary btn-full"><?= $lang['submit_request'] ?? 'Submit Request' ?></button>
            </form>
        </section>


    </div>
</main>


<script>
// Points preview calculator
const catPoints = { Paper: 5, Plastic: 8, Metal: 12 };
const subcategories = <?= json_encode($impactCategories, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const catEl  = document.getElementById('category');
const subcatEl = document.getElementById('subcategory');
const wtEl   = document.getElementById('estimated_weight');
const prev   = document.getElementById('pointsPreview');
const prevVal= document.getElementById('previewValue');


function updateSubcategories() {
    const cat = catEl.value;
    subcatEl.innerHTML = '<option value="">Use category average</option>';
    if (!cat || !subcategories[cat]) return;
    subcategories[cat].forEach((name) => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        subcatEl.appendChild(option);
    });
}


function updatePreview() {
    const cat = catEl.value, wt = parseFloat(wtEl.value);
    if (cat && wt > 0) {
        prev.style.display = 'flex';
        prevVal.textContent = Math.round((catPoints[cat] || 3) * wt) + ' pts';
    } else {
        prev.style.display = 'none';
    }
}
if(catEl && wtEl) {
    catEl.addEventListener('change', () => {
        updateSubcategories();
        updatePreview();
    });
    wtEl.addEventListener('input', updatePreview);
}
</script>
</body>
</html>





