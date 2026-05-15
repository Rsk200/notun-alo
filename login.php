<?php
require_once 'includes/config.php';
startSession();

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'user';
    redirect(match($role) {
        'admin'  => 'admin.php',
        'agency' => 'agency.php',
        default  => 'dashboard.php',
    });
}

$currentLang = $_SESSION['lang'] ?? 'en';
$t = function(string $en, string $bn) use ($currentLang): string {
    return $currentLang === 'bn' ? $bn : $en;
};

$error = '';
if (!isDatabaseInitialized($pdo)) redirect('init_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = $t('Please fill in all fields.', 'অনুগ্রহ করে সমস্ত ক্ষেত্র পূরণ করুন।');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            setFlash('success', $t('Welcome back, ', 'স্বাগতম, ') . $user['name'] . '!');
            redirect(match($user['role']) {
                'admin'  => 'admin.php',
                'agency' => 'agency.php',
                default  => 'dashboard.php',
            });
        } else {
            $error = $t('Invalid email or password.', 'ইমেল বা পাসওয়ার্ড ভুল।');
        }
    }
}

$statsQuery = $pdo->query("
    SELECT 
        (SELECT SUM(estimated_weight) FROM pickups WHERE status = 'completed') as total_recycled,
        (SELECT COUNT(id) FROM users WHERE role = 'user') as total_users,
        (SELECT SUM(lifetime_points) FROM rewards) as total_points
");
$realStats = $statsQuery->fetch();

function formatNumberShort($num) {
    $num = (float)$num;
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'K';
    return (int)$num;
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t('Login — Notun Alo', 'লগইন — নতুন আলো') ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --auth-bg-left: linear-gradient(135deg, #064e3b, #065f46, #1D9E75); }
        body.dark-mode { --auth-bg-left: linear-gradient(135deg, #0a1a12, #0f2e1a, #14532d); }
        body.dark-mode .auth-form-panel { background: #0f1712; }
        body.dark-mode .auth-form-wrap { background: #1a2320; border-color: #374151; }
        body.dark-mode .auth-heading { color: #e5e7eb; }
        body.dark-mode .auth-sub { color: #9ca3af; }
        body.dark-mode .form-group label { color: #d1d5db; }
        body.dark-mode .auth-form input { background: #111827; border-color: #374151; color: #e5e7eb; }
        body.dark-mode .auth-form input:focus { border-color: var(--brand-primary); }
        body.dark-mode .auth-switch { color: #9ca3af; }
        body.dark-mode .auth-switch a { color: #6ee7b7; }
        body.dark-mode .alert-error { background: #451a1a; border-color: #7f1d1d; color: #fca5a5; }
        .auth-top-bar {
            position: absolute; top: 20px; right: 24px; z-index: 10;
            display: flex; align-items: center; gap: 10px;
        }
        .auth-top-bar a, .auth-top-bar span {
            color: white; text-decoration: none; font-weight: 600; font-size: 0.85rem;
            padding: 4px 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 6px; cursor: pointer;
        }
        .auth-top-bar a.active, .auth-top-bar span.active { background: rgba(255,255,255,0.15); }
        body.dark-mode .auth-top-bar a, body.dark-mode .auth-top-bar span { color: #e5e7eb; border-color: #4b5563; }
        body.dark-mode .auth-top-bar a.active, body.dark-mode .auth-top-bar span.active { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body class="auth-body">
<div class="auth-split">
    <div class="auth-top-bar">
        <a href="?lang=bn" class="<?= $currentLang === 'bn' ? 'active' : '' ?>">বাং</a>
        <a href="?lang=en" class="<?= $currentLang === 'en' ? 'active' : '' ?>">EN</a>
        <span id="authThemeToggle">🌙</span>
    </div>

    <div class="auth-brand">
        <div class="brand-content">
            <div class="logo-mark">♻</div>
            <h1 class="brand-title">Notun Alo</h1>
            <p class="brand-tagline"><?= $t('নতুন আলো — New Light', 'নতুন আলো — নতুন আলো') ?></p>
            <p class="brand-desc"><?= $t('Turn your waste into rewards. Build a greener tomorrow, one pickup at a time.', 'আপনার বর্জ্যকে পুরস্কারে পরিণত করুন। এক পিকআপ করে একটি সবুজ আগামী গড়ুন।') ?></p>
            <div class="brand-stats">
                <div class="stat"><span class="stat-num"><?= formatNumberShort($realStats['total_recycled'] ?? 0) ?></span><span class="stat-label"><?= $t('KG Recycled', 'কেজি পুনর্ব্যবহৃত') ?></span></div>
                <div class="stat"><span class="stat-num"><?= formatNumberShort($realStats['total_users'] ?? 0) ?></span><span class="stat-label"><?= $t('Active Users', 'সক্রিয় ব্যবহারকারী') ?></span></div>
                <div class="stat"><span class="stat-num"><?= formatNumberShort($realStats['total_points'] ?? 0) ?></span><span class="stat-label"><?= $t('Points Rewarded', 'পুরস্কৃত পয়েন্ট') ?></span></div>
            </div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap">
            <h2 class="auth-heading"><?= $t('Welcome Back', 'ফিরে আসার জন্য স্বাগতম') ?></h2>
            <p class="auth-sub"><?= $t('Sign in to your Notun Alo account', 'আপনার নতুন আলো অ্যাকাউন্টে সাইন ইন করুন') ?></p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email"><?= $t('Email Address', 'ইমেল ঠিকানা') ?></label>
                    <input type="email" id="email" name="email" placeholder="you@example.com"
                           value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password"><?= $t('Password', 'পাসওয়ার্ড') ?></label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" onclick="togglePass()">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full"><?= $t('Sign In', 'সাইন ইন') ?></button>
            </form>

            <p class="auth-switch"><?= $t("Don't have an account?", 'অ্যাকাউন্ট নেই?') ?> <a href="register.php"><?= $t('Register here', 'এখানে নিবন্ধন করুন') ?></a></p>
            <p class="auth-switch">← <a href="index.php"><?= $t('Back to Home', 'হোম পেজে ফিরুন') ?></a></p>
        </div>
    </div>
</div>

<script>
function togglePass() { const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password'; }
const toggle = document.getElementById('authThemeToggle');
if (toggle) {
    const saved = localStorage.getItem('theme');
    if (saved === 'dark') { document.body.classList.add('dark-mode'); toggle.textContent = '☀️'; }
    toggle.onclick = () => {
        document.body.classList.toggle('dark-mode');
        toggle.textContent = document.body.classList.contains('dark-mode') ? '☀️' : '🌙';
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    };
}
</script>
</body>
</html>
