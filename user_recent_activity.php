<?php
// ============================================
// user_recent_activity.php - Recent Activity (World-Class Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'user') redirect('dashboard.php');

$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser($pdo);

// Fetch real data to pass to JS
$stmt = $pdo->prepare("SELECT id, category as name, category, created_at as date, estimated_weight as weight, status 
    FROM pickups WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$realPickups = $stmt->fetchAll();

// Map real pickups to match the JS structure
$jsActivities = [];
foreach($realPickups as $p) {
    $jsActivities[] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'category' => $p['category'],
        'address' => $user['address'] ?? 'Dhaka, Bangladesh',
        'date' => date('M j, Y', strtotime($p['date'])),
        'weight' => $p['weight'] . ' kg',
        'points' => (int)($p['weight'] * 10), // Dummy calculation for now
        'status' => strtoupper($p['status'])
    ];
}

// Fallback data if user has no pickups (to show off the UI as requested)
if (empty($jsActivities)) {
    $jsActivities = [
        ['id'=>1, 'name'=>'Paper (Mixed Paper)', 'category'=>'Paper', 'address'=>'Mirpur-10, Dhaka', 'date'=>'Oct 21, 2025', 'weight'=>'12.5 kg', 'points'=>50, 'status'=>'COMPLETED'],
        ['id'=>2, 'name'=>'Paper (Mixed Paper)', 'category'=>'Paper', 'address'=>'Mirpur-10, Dhaka', 'date'=>'Sep 15, 2025', 'weight'=>'8.2 kg', 'points'=>33, 'status'=>'COMPLETED'],
        ['id'=>3, 'name'=>'Paper (Mixed Paper)', 'category'=>'Paper', 'address'=>'Mirpur-10, Dhaka', 'date'=>'May 30, 2025', 'weight'=>'15.0 kg', 'points'=>60, 'status'=>'COMPLETED'],
        ['id'=>4, 'name'=>'Paper (Mixed Paper)', 'category'=>'Paper', 'address'=>'Mirpur-10, Dhaka', 'date'=>'May 15, 2025', 'weight'=>'Pending weigh-in', 'points'=>0, 'status'=>'PENDING']
    ];
}
$currentLang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Recent Activity — Notun Alo</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --brand-border: #6EE7B7;
            --gold: #D97706;
            --gold-bg: #FEF3C7;
            --gold-text: #92400E;
            --blue: #2563EB;
            --blue-bg: #DBEAFE;
            --blue-text: #1E40AF;
            --purple: #7C3AED;
            --purple-bg: #EDE9FE;
            --red: #DC2626;
            --red-bg: #FEE2E2;
            --red-text: #991B1B;
            --success-bg: #E6F5EE;
            --success-text: #065F46;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --border-light: #F3F4F6;
            --bg-page: #F5F7F2;
            --bg-card: #FFFFFF;
            --bg-subtle: #F9FAFB;
        }

        /* ===== PREMIUM DARK MODE ===== */
        body.dark-mode {
            --bg-page: #080f09;
            --bg-card: #0f1a10;
            --bg-subtle: #0b130c;
            --text-primary: #E2E8F0;
            --text-secondary: #94A3B8;
            --text-muted: #64748B;
            --border: #1e3222;
            --border-light: #152018;
            --brand-light: #0d2416;
            --brand-primary: #34d399;
            --success-bg: #0a1f12;
            --success-text: #34d399;
            --gold-bg: #1c1200;
            --gold: #FBBF24;
            --blue-bg: #0d1a33;
            --red-bg: #1f0a0a;
        }
        body.dark-mode .white-card {
            background: var(--bg-card) !important;
            border-color: var(--border) !important;
            box-shadow: 0 4px 30px rgba(0,0,0,0.5) !important;
        }
        body.dark-mode .activity-row:hover { background: rgba(52,211,153,0.04) !important; }
        body.dark-mode .back-link {
            background: #0d2416 !important; border-color: #1e3222 !important; color: #34d399 !important;
        }
        body.dark-mode .search-input {
            background: var(--bg-subtle) !important; border-color: var(--border) !important; color: var(--text-primary) !important;
        }
        body.dark-mode .pill {
            background: var(--bg-card) !important; border-color: var(--border) !important; color: var(--text-secondary) !important;
        }
        body.dark-mode .pill.active { background: var(--brand-primary) !important; color: white !important; border-color: var(--brand-primary) !important; }
        body.dark-mode .pg-btn {
            background: var(--bg-card) !important; border-color: var(--border) !important; color: var(--text-secondary) !important;
        }
        body.dark-mode .pg-num.active { background: var(--brand-primary) !important; color: white !important; border-color: var(--brand-primary) !important; }
        body.dark-mode .modal-card { background: var(--bg-card) !important; }
        body.dark-mode .modal-header { border-bottom-color: var(--border) !important; }
        body.dark-mode .detail-row { border-bottom-color: var(--bg-subtle) !important; }
        body.dark-mode .page-title { color: #a3e9cb !important; }
        body.dark-mode .page-subtitle { color: var(--text-muted) !important; }
        body.dark-mode .list-header { border-bottom-color: var(--border-light) !important; }
        body.dark-mode .cat-paper { background: #0a1f0d !important; color: #34d399 !important; }
        body.dark-mode .cat-plastic { background: #0d1a33 !important; color: #60a5fa !important; }
        body.dark-mode .cat-electronics { background: #1a0d33 !important; color: #a78bfa !important; }
        body.dark-mode .cat-metal { background: #1a1a1a !important; color: #9ca3af !important; }
        body.dark-mode .cat-organic { background: #1c1200 !important; color: #fbbf24 !important; }
        body.dark-mode .footer-strip {
            background: #0a1f12 !important;
            border-color: #1e3222 !important;
            color: #34d399 !important;
        }
        body.dark-mode .date-picker {
            background: var(--bg-card) !important;
            border-color: var(--border) !important;
            color: var(--text-secondary) !important;
        }
        body.dark-mode .modal-card {
            background: var(--bg-card) !important;
            border: 1px solid var(--border) !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.7) !important;
        }
        body.dark-mode .modal-header h3 { color: var(--text-primary) !important; }
        body.dark-mode .detail-label { color: var(--text-muted) !important; }
        body.dark-mode .detail-val { color: var(--text-primary) !important; }
        body.dark-mode .list-title { color: var(--text-primary) !important; }
        body.dark-mode .pickup-name { color: var(--text-primary) !important; }
        body.dark-mode .stat-num { color: var(--text-primary) !important; }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-page); color: var(--text-secondary); transition: background-color 0.4s ease, color 0.4s ease; }

        .wrapper { max-width: 960px; margin: 0 auto; padding: 32px 24px; }
        .white-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.03); padding: 24px; transition: background-color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease; }

        /* HEADER */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-top: 20px; }
        .header-left { display: flex; align-items: flex-start; gap: 20px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #065f46; background: #ecfdf5; border: 1px solid #d1fae5; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; margin-top: 6px; }
        .back-link:hover { background: #d1fae5; transform: translateX(-2px); }
        
        .header-text { display: flex; flex-direction: column; }
        .page-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #064e3b; font-weight: 700; margin-bottom: 4px; line-height: 1.1; }
        .page-subtitle { font-size: 1.05rem; color: #64748b; margin: 0; }
        
        .btn-pickup { background: var(--brand-primary); color: white; font-size: 14px; font-weight: 600; padding: 10px 20px; border-radius: 10px; text-decoration: none; transition: 0.2s; margin-top: 6px; }
        .btn-pickup:hover { background: #065F46; }

        .header-divider { height: 1px; background: var(--border); margin-top: 20px; border: none; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 24px 0; }
        .stat-card { padding: 24px 20px; display: flex !important; flex-direction: column !important; align-items: flex-start !important; justify-content: center; }
        .stat-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; flex-shrink: 0; }
        .stat-num { font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 6px; display: block; width: 100%; color: var(--text-primary); }
        .stat-label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block; width: 100%; }
        .stat-sub { font-size: 13px; font-weight: 500; color: var(--text-secondary); display: block; width: 100%; }

        /* MODAL */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 9999; opacity: 0; transition: opacity 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-card { background: white; border-radius: 16px; width: 90%; max-width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.3s; overflow: hidden; }
        .modal-overlay.active .modal-card { transform: translateY(0); }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 18px; font-weight: 700; margin: 0; color: var(--text-primary); }
        .close-btn { background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; }
        .close-btn:hover { background: var(--bg-subtle); color: var(--text-primary); }
        .modal-body { padding: 24px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--bg-subtle); font-size: 14px; }
        .detail-row:last-child { border-bottom: none; padding-bottom: 0; }
        .detail-label { color: var(--text-muted); font-weight: 500; }
        .detail-val { font-weight: 600; color: var(--text-primary); text-align: right; }

        /* FILTER BAR */
        .filter-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; padding: 16px 20px; }
        .search-wrap { flex: 1; min-width: 200px; position: relative; }
        .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .search-input { width: 100%; height: 40px; background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 10px; padding: 0 14px 0 38px; font-size: 14px; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }

        .filter-pills { display: flex; gap: 8px; flex-wrap: wrap; }
        .pill { border: 1px solid var(--border); background: white; color: var(--text-secondary); font-size: 12px; font-weight: 500; padding: 7px 14px; border-radius: 99px; cursor: pointer; transition: 0.2s; }
        .pill:hover { background: var(--bg-subtle); }
        .pill.active { background: var(--brand-primary); color: white; border-color: var(--brand-primary); }

        .date-picker { border: 1px solid var(--border); background: white; color: var(--text-secondary); font-size: 12px; padding: 7px 14px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 6px; }

        /* TIMELINE LIST */
        .activity-list-card { padding: 0; margin-bottom: 24px; overflow: hidden; }
        .list-header { padding: 20px 24px; border-bottom: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; }
        .list-title { font-size: 16px; font-weight: 600; color: var(--text-primary); }
        .btn-export { color: var(--brand-primary); font-size: 13px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px; }
        .btn-export:hover { text-decoration: underline; }

        .activity-row { display: flex; align-items: center; gap: 16px; padding: 16px 24px; border-bottom: 1px solid var(--bg-subtle); transition: 0.2s; }
        .activity-row:last-child { border-bottom: none; }
        .activity-row:hover { background: var(--bg-subtle); }

        .status-icon-circle { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .sic-completed { background: var(--brand-light); color: var(--brand-primary); }
        .sic-pending { background: var(--gold-bg); color: var(--gold); }
        .sic-scheduled { background: var(--blue-bg); color: var(--blue); }
        .sic-cancelled { background: var(--red-bg); color: var(--red); }
        .sic-assigned { background: var(--blue-bg); color: var(--blue); }

        .row-content { flex: 1; }
        .row-top { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
        .pickup-name { font-size: 15px; font-weight: 600; color: var(--text-primary); }
        .cat-badge { font-size: 11px; font-weight: 500; padding: 3px 8px; border-radius: 99px; }
        .cat-paper { background: #F0FDF4; color: #166534; }
        .cat-plastic { background: #EFF6FF; color: #1E40AF; }
        .cat-electronics { background: #EDE9FE; color: #5B21B6; }
        .cat-metal { background: #F3F4F6; color: #374151; }
        .cat-glass { background: #F0FDF4; color: #166534; }
        .cat-ewaste { background: #EDE9FE; color: #5B21B6; }
        .cat-organic { background: #FEF3C7; color: #92400E; }

        .row-meta { display: flex; gap: 16px; font-size: 12px; color: var(--text-muted); align-items: center; }
        .row-meta i { font-size: 14px; }
        .row-stats { margin-top: 6px; display: flex; gap: 16px; font-size: 12px; }

        .row-right { text-align: right; min-width: 100px; }
        .status-badge { padding: 5px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; display: inline-block; }
        .sb-completed { background: var(--success-bg); color: var(--success-text); }
        .sb-pending { background: var(--gold-bg); color: var(--gold-text); }
        .sb-scheduled { background: var(--blue-bg); color: var(--blue-text); }
        .sb-cancelled { background: var(--red-bg); color: var(--red-text); }
        .sb-assigned { background: var(--blue-bg); color: var(--blue-text); }
        .btn-details { display: block; font-size: 11px; color: var(--brand-primary); font-weight: 600; margin-top: 6px; cursor: pointer; }
        .btn-details:hover { text-decoration: underline; }

        /* IMPACT CARD */
        .impact-card { margin-bottom: 24px; }
        .impact-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-light); }
        .impact-stat { text-align: center; }
        .impact-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 12px; }
        .impact-val { font-size: 24px; font-weight: 700; display: block; }

        /* PAGINATION */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 24px; }
        .pg-btn { border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; transition: background 0.3s, color 0.3s, border-color 0.3s; }
        .pg-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .pg-num { width: 36px; height: 36px; border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; cursor: pointer; background: var(--bg-card); color: var(--text-secondary); transition: background 0.3s, color 0.3s; }
        .pg-num.active { background: var(--brand-primary); color: white; border-color: var(--brand-primary); }

        /* FOOTER STRIP */
        .footer-strip { background: var(--brand-light); border: 1px solid #D1FAE5; border-radius: 16px; padding: 18px 28px; display: flex; justify-content: space-between; align-items: center; color: #065F46; transition: background 0.3s, border-color 0.3s; }
        .btn-schedule { border: 1.5px solid var(--brand-primary); color: var(--brand-primary); background: transparent; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .btn-schedule:hover { background: var(--brand-primary); color: white; }

        @media (max-width: 900px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .impact-grid { grid-template-columns: repeat(2, 1fr); }
            .impact-stat:last-child { grid-column: span 2; }
        }
        @media (max-width: 640px) {
            .stats-grid, .impact-grid, .content-grid { grid-template-columns: 1fr; }
            .impact-stat:last-child { grid-column: span 1; }
            .title-row { flex-direction: column; align-items: flex-start; gap: 16px; }
            .activity-row { flex-direction: column; align-items: flex-start; }
            .row-right { text-align: left; }
            .row-meta { flex-wrap: wrap; }
            .footer-strip { flex-direction: column; text-align: center; gap: 12px; }
        }
    </style>
<style>
@media (max-width:767px) { .mobile-only { display:block; } .desktop-only { display:none; } }
@media (min-width:768px) { .mobile-only { display:none; } .desktop-only { display:block; } }
</style>
</head>
<body>

<?php $pageEmoji = '📋'; include 'includes/mobile_nav.php'; ?>
<?php include 'includes/navbar.php'; ?>

    <main class="wrapper">
        <header class="page-header-flex">
            <div class="header-left">
                <a href="dashboard.php" class="back-link">&larr; <?= $lang['dashboard'] ?? 'Dashboard' ?></a>
                <div class="header-text">
                    <h1 class="page-title"><?= $lang['recent_activity'] ?? 'My Recent Activity' ?></h1>
                    <p class="page-subtitle"><?= $lang['activity_hint'] ?? 'Your pickup history and statuses' ?></p>
                </div>
            </div>
            <a href="user_request_pickup.php" class="btn-pickup"><?= $lang['request_pickup'] ?? 'Request a Pickup' ?> &rarr;</a>
        </header>
        <hr class="header-divider">

        <!-- SECTION 1: STATS -->
        <div class="stats-grid">
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#EFF6FF; color:#2563EB;"><i class="ti ti-truck"></i></div>
                <span class="stat-num" id="stat-total" data-target="8">0</span>
                <span class="stat-label"><?= $currentLang === 'bn' ? 'মোট পিকআপ' : 'Total Pickups' ?></span>
                <span class="stat-sub"><?= $currentLang === 'bn' ? 'যোগদানের পর থেকে' : 'Since joining' ?></span>
            </div>
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#E6F5EE; color:#1D9E75;"><i class="ti ti-circle-check"></i></div>
                <span class="stat-num" id="stat-completed" data-target="6">0</span>
                <span class="stat-label"><?= $currentLang === 'bn' ? 'সম্পন্ন' : 'Completed' ?></span>
                <span class="stat-sub"><?= $currentLang === 'bn' ? '৭৫% সাফল্যের হার' : '75% success rate' ?></span>
            </div>
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#EDE9FE; color:#7C3AED;"><i class="ti ti-weight"></i></div>
                <span class="stat-num" id="stat-weight" data-target="66.3">0</span>
                <span class="stat-label"><?= $currentLang === 'bn' ? 'কেজি রিসাইকেল করা হয়েছে' : 'KG Recycled' ?></span>
                <span class="stat-sub"><?= $currentLang === 'bn' ? 'সব পিকআপ জুড়ে' : 'Across all pickups' ?></span>
            </div>
            <div class="white-card stat-card">
                <div class="stat-icon" style="background:#FEF3C7; color:#D97706;"><i class="ti ti-star"></i></div>
                <span class="stat-num" id="stat-points" data-target="200">0</span>
                <span class="stat-label"><?= $currentLang === 'bn' ? 'পয়েন্ট অর্জিত' : 'Points Earned' ?></span>
                <span class="stat-sub"><?= $currentLang === 'bn' ? 'ব্রোঞ্জ স্তর' : 'Bronze tier' ?></span>
            </div>
        </div>

        <!-- SECTION 2: FILTERS -->
        <div class="white-card filter-bar">
            <div class="search-wrap">
                <i class="ti ti-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="<?= $currentLang === 'bn' ? 'পিকআপ খুঁজুন...' : 'Search pickups...' ?>">
            </div>
            <div class="filter-pills" id="statusFilters">
                <div class="pill active" data-status="ALL"><?= $currentLang === 'bn' ? 'সব' : 'All' ?></div>
                <div class="pill" data-status="COMPLETED"><?= $currentLang === 'bn' ? 'সম্পন্ন' : 'Completed' ?></div>
                <div class="pill" data-status="PENDING"><?= $currentLang === 'bn' ? 'অপেক্ষমাণ' : 'Pending' ?></div>
                <div class="pill" data-status="SCHEDULED"><?= $currentLang === 'bn' ? 'নির্ধারিত' : 'Scheduled' ?></div>
                <div class="pill" data-status="CANCELLED"><?= $currentLang === 'bn' ? 'বাতিল' : 'Cancelled' ?></div>
            </div>
            <div class="date-picker" id="datePicker">
                <i class="ti ti-calendar"></i>
                <span id="dateRangeText"><?= $currentLang === 'bn' ? 'গত ৩০ দিন' : 'Last 30 days' ?></span>
                <i class="ti ti-chevron-down"></i>
            </div>
            <div style="width:100%; margin-top:10px; font-size:12px; color:var(--text-muted);" id="resultCount"><?= $currentLang === 'bn' ? '৮ টি পিকআপ দেখানো হচ্ছে' : 'Showing 8 pickups' ?></div>
        </div>

        <!-- SECTION 3: ACTIVITY LIST -->
        <div class="white-card activity-list-card">
            <div class="list-header">
                <h2 class="list-title"><?= $currentLang === 'bn' ? 'পিকআপের ইতিহাস' : 'Pickup History' ?></h2>
                <a href="#" class="btn-export" id="exportBtn"><i class="ti ti-download"></i> <?= $currentLang === 'bn' ? 'সিএসভি এক্সপোর্ট' : 'Export CSV' ?></a>
            </div>
            <div id="activityContainer">
                <!-- Injected by JS -->
            </div>
        </div>

        <!-- SECTION 4: IMPACT -->
        <div class="white-card impact-card">
            <h2 style="font-size:16px; font-weight:600;"><?= $currentLang === 'bn' ? 'আপনার পরিবেশগত অবদান' : 'Your Environmental Contribution' ?></h2>
            <p style="font-size:12px; color:var(--text-muted);"><?= $currentLang === 'bn' ? 'আপনার সম্পন্ন হওয়া পিকআপের উপর ভিত্তি করে' : 'Based on your completed pickups' ?></p>
            <div class="impact-grid">
                <div class="impact-stat">
                    <div class="impact-icon" style="background:#E6F5EE; color:#1D9E75;"><i class="ti ti-cloud"></i></div>
                    <span class="impact-val" style="color:#1D9E75;">336.5 kg</span>
                    <span style="font-size:12px; color:var(--text-muted);"><?= $currentLang === 'bn' ? 'CO₂ প্রতিরোধ' : 'CO₂ Prevented' ?></span>
                    <div style="font-size:11px; color:#1D9E75; margin-top:2px;"><?= $currentLang === 'bn' ? '= ১৪ গাড়ির ট্রিপ এড়ানো গেছে' : '= 14 car trips avoided' ?></div>
                </div>
                <div class="impact-stat">
                    <div class="impact-icon" style="background:#DBEAFE; color:#2563EB;"><i class="ti ti-droplet"></i></div>
                    <span class="impact-val" style="color:#2563EB;">8,412 L</span>
                    <span style="font-size:12px; color:var(--text-muted);"><?= $currentLang === 'bn' ? 'পানি সাশ্রয়' : 'Water Saved' ?></span>
                    <div style="font-size:11px; color:#2563EB; margin-top:2px;"><?= $currentLang === 'bn' ? '= ৫,৬০৮ বোতল' : '= 5,608 bottles' ?></div>
                </div>
                <div class="impact-stat">
                    <div class="impact-icon" style="background:#FEF3C7; color:#D97706;"><i class="ti ti-bolt"></i></div>
                    <span class="impact-val" style="color:#D97706;">1,245 kWh</span>
                    <span style="font-size:12px; color:var(--text-muted);"><?= $currentLang === 'bn' ? 'শক্তি সাশ্রয়' : 'Energy Saved' ?></span>
                    <div style="font-size:11px; color:#D97706; margin-top:2px;"><?= $currentLang === 'bn' ? '= ১,২৪৫ ফোন চার্জ' : '= 1,245 phone charges' ?></div>
                </div>
            </div>
        </div>

        <!-- SECTION 5: PAGINATION -->
        <div class="pagination">
            <button class="pg-btn" disabled>&larr; Previous</button>
            <div class="pg-num active">1</div>
            <div class="pg-num">2</div>
            <div class="pg-num">3</div>
            <button class="pg-btn">Next &rarr;</button>
        </div>

        <!-- SECTION 6: FOOTER -->
        <div class="footer-strip">
            <span style="font-weight:500;">🌿 <?= $currentLang === 'bn' ? 'চালিয়ে যান! সিলভার টিয়ার থেকে আপনি ৩০০ পয়েন্ট দূরে।' : 'Keep going! You\'re 300 pts away from Silver tier.' ?></span>
            <a href="user_request_pickup.php" class="btn-schedule"><?= $currentLang === 'bn' ? 'পরবর্তী পিকআপ শিডিউল করুন →' : 'Schedule Next Pickup →' ?></a>
        </div>
        
        <!-- DETAILS MODAL -->
        <div id="detailsModal" class="modal-overlay">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 id="modalTitle">Pickup Details</h3>
                    <button class="close-btn" onclick="closeModal()"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- content injected by JS -->
                </div>
            </div>
        </div>
    </main>

    <script>
        const lang = "<?= $currentLang ?>";
        const trans = {
            showing: lang === 'bn' ? 'টি পিকআপ দেখানো হচ্ছে' : 'pickups',
            showingPrefix: lang === 'bn' ? '' : 'Showing ',
            noMatch: lang === 'bn' ? 'আপনার মানদণ্ডের সাথে মেলে এমন কোনো পিকআপ পাওয়া যায়নি।' : 'No pickups found matching your criteria.',
            ptsEarned: lang === 'bn' ? 'পয়েন্ট অর্জিত' : 'pts earned',
            viewDetails: lang === 'bn' ? 'বিস্তারিত দেখুন' : 'View Details',
            prev: lang === 'bn' ? 'পূর্ববর্তী' : 'Previous',
            next: lang === 'bn' ? 'পরবর্তী' : 'Next',
            itemCategory: lang === 'bn' ? 'আইটেম / বিভাগ' : 'Item / Category',
            address: lang === 'bn' ? 'ঠিকানা' : 'Address',
            date: lang === 'bn' ? 'তারিখ' : 'Date',
            statusLabel: lang === 'bn' ? 'স্ট্যাটাস' : 'Status',
            weight: lang === 'bn' ? 'ওজন' : 'Weight',
            status: {
                'COMPLETED': lang === 'bn' ? 'সম্পন্ন' : 'Completed',
                'PENDING': lang === 'bn' ? 'অপেক্ষমাণ' : 'Pending',
                'ASSIGNED': lang === 'bn' ? 'এজেন্সিতে দেওয়া' : 'Assigned',
                'SCHEDULED': lang === 'bn' ? 'নির্ধারিত' : 'Scheduled',
                'CANCELLED': lang === 'bn' ? 'বাতিল' : 'Cancelled',
                'ALL': lang === 'bn' ? 'সব' : 'All'
            },
            dateRanges: [
                lang === 'bn' ? "গত ৩০ দিন" : "Last 30 days",
                lang === 'bn' ? "গত ৩ মাস" : "Last 3 months",
                lang === 'bn' ? "গত ৬ মাস" : "Last 6 months",
                lang === 'bn' ? "সব সময়" : "All time"
            ]
        };

        const activities = <?= json_encode($jsActivities) ?>;

        const container = document.getElementById('activityContainer');
        const searchInput = document.getElementById('searchInput');
        const statusFilters = document.getElementById('statusFilters');
        const resultCount = document.getElementById('resultCount');
        const exportBtn = document.getElementById('exportBtn');
        const datePicker = document.getElementById('datePicker');
        const dateRangeText = document.getElementById('dateRangeText');

        let currentFilter = 'ALL';
        let searchQuery = '';
        let currentPage = 1;
        const itemsPerPage = 5;

        function renderList() {
            const filtered = activities.filter(a => {
                const matchStatus = currentFilter === 'ALL' || a.status === currentFilter;
                const matchSearch = a.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                                    a.category.toLowerCase().includes(searchQuery.toLowerCase());
                return matchStatus && matchSearch;
            });

            resultCount.innerText = `${trans.showingPrefix}${filtered.length} ${trans.showing}`;
            const pgContainer = document.querySelector('.pagination');

            if (filtered.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:60px 24px; color:var(--text-muted);">
                        <i class="ti ti-search" style="font-size:40px; margin-bottom:12px; display:block;"></i>
                        <p>${trans.noMatch}</p>
                    </div>
                `;
                pgContainer.style.display = 'none';
                return;
            }

            const totalPages = Math.ceil(filtered.length / itemsPerPage);
            if (currentPage > totalPages) currentPage = totalPages;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const paginatedItems = filtered.slice(startIndex, startIndex + itemsPerPage);

            container.innerHTML = paginatedItems.map(a => {
                const displayStatus = trans.status[a.status] || a.status;
                return `
                <div class="activity-row">
                    <div class="status-icon-circle sic-${a.status.toLowerCase()}">
                        <i class="ti ti-${getStatusIcon(a.status)}"></i>
                    </div>
                    <div class="row-content">
                        <div class="row-top">
                            <span class="pickup-name">${a.name}</span>
                            <span class="cat-badge cat-${a.category.toLowerCase()}">${a.category}</span>
                        </div>
                        <div class="row-meta">
                            <span><i class="ti ti-map-pin"></i> ${a.address}</span>
                            <span><i class="ti ti-calendar"></i> ${a.date}</span>
                        </div>
                        ${a.status === 'COMPLETED' ? `
                        <div class="row-stats">
                            <span style="color:var(--text-secondary);"><i class="ti ti-weight"></i> ${a.weight}</span>
                            <span style="color:var(--gold); font-weight:600;"><i class="ti ti-star"></i> +${a.points} ${trans.ptsEarned}</span>
                        </div>
                        ` : ''}
                    </div>
                    <div class="row-right">
                        <span class="status-badge sb-${a.status.toLowerCase()}">${displayStatus}</span>
                        <span class="btn-details" onclick="viewDetails(${a.id})">${trans.viewDetails}</span>
                    </div>
                </div>
            `}).join('');

            renderPagination(totalPages);
        }

        function renderPagination(totalPages) {
            const pgContainer = document.querySelector('.pagination');
            if (totalPages <= 1) {
                pgContainer.style.display = 'none';
                return;
            }
            pgContainer.style.display = 'flex';
            
            let html = `<button class="pg-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">&larr; ${trans.prev}</button>`;
            
            for(let i=1; i<=totalPages; i++) {
                html += `<div class="pg-num ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</div>`;
            }
            
            html += `<button class="pg-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">${trans.next} &rarr;</button>`;
            
            pgContainer.innerHTML = html;
        }

        function changePage(page) {
            currentPage = page;
            renderList();
            // Removed window.scrollTo to prevent UI jump and breaking the page feel.
        }

        function getStatusIcon(status) {
            switch(status) {
                case 'COMPLETED': return 'circle-check';
                case 'PENDING': return 'clock';
                case 'ASSIGNED': return 'truck-delivery';
                case 'SCHEDULED': return 'calendar';
                case 'CANCELLED': return 'circle-x';
                default: return 'help';
            }
        }

        function viewDetails(id) {
            const a = activities.find(x => x.id == id);
            if (!a) return;
            
            document.getElementById('modalTitle').innerText = `${trans.viewDetails} #${a.id}`;
            const displayStatus = trans.status[a.status] || a.status;
            
            const body = document.getElementById('modalBody');
            body.innerHTML = `
                <div class="detail-row"><span class="detail-label">${trans.itemCategory}</span><span class="detail-val">${a.name} (${a.category})</span></div>
                <div class="detail-row"><span class="detail-label">${trans.address}</span><span class="detail-val">${a.address}</span></div>
                <div class="detail-row"><span class="detail-label">${trans.date}</span><span class="detail-val">${a.date}</span></div>
                <div class="detail-row"><span class="detail-label">${trans.statusLabel}</span><span class="detail-val"><span class="status-badge sb-${a.status.toLowerCase()}">${displayStatus}</span></span></div>
                <div class="detail-row"><span class="detail-label">${trans.weight}</span><span class="detail-val">${a.weight}</span></div>
                ${a.status === 'COMPLETED' ? `<div class="detail-row"><span class="detail-label">${trans.ptsEarned}</span><span class="detail-val" style="color:var(--gold);"><i class="ti ti-star"></i> +${a.points}</span></div>` : ''}
            `;
            
            document.getElementById('detailsModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('detailsModal');
            if (event.target == modal) {
                closeModal();
            }
        };

        // Search & Filter
        searchInput.oninput = (e) => { searchQuery = e.target.value; currentPage = 1; renderList(); };
        
        statusFilters.onclick = (e) => {
            if (e.target.classList.contains('pill')) {
                document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
                e.target.classList.add('active');
                currentFilter = e.target.dataset.status;
                currentPage = 1;
                renderList();
            }
        };

        // Date Picker Cycle
        let rangeIdx = 0;
        datePicker.onclick = () => {
            rangeIdx = (rangeIdx + 1) % trans.dateRanges.length;
            dateRangeText.innerText = trans.dateRanges[rangeIdx];
        };

        // Export CSV
        exportBtn.onclick = (e) => {
            e.preventDefault();
            const headers = "ID,Name,Category,Address,Date,Weight,Points,Status\n";
            const rows = activities.map(a => `${a.id},"${a.name}","${a.category}","${a.address}","${a.date}","${a.weight}",${a.points},${a.status}`).join("\n");
            const blob = new Blob([headers + rows], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'notun-alo-activity.csv';
            a.click();
        };

        // Animation
        function animateValue(id, duration) {
            const el = document.getElementById(id);
            const target = parseFloat(el.dataset.target);
            const start = 0;
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const val = progress * (target - start) + start;
                el.innerText = target % 1 === 0 ? Math.floor(val) : val.toFixed(1);
                if (progress < 1) window.requestAnimationFrame(step);
            };
            window.requestAnimationFrame(step);
        }

        window.onload = () => {
            renderList();
            animateValue('stat-total', 1200);
            animateValue('stat-completed', 1200);
            animateValue('stat-weight', 1200);
            animateValue('stat-points', 1200);
        };
    </script>
</body>
</html>
