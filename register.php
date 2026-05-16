<?php
require_once 'includes/config.php';
startSession();

if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'user';
    redirect(match ($role) {
        'admin' => 'admin.php',
        'agency' => 'agency.php',
        default => 'dashboard.php',
    });
}

$currentLang = $_SESSION['lang'] ?? 'en';
$t = function(string $en, string $bn) use ($currentLang): string {
    return $currentLang === 'bn' ? $bn : $en;
};

$error   = '';
$success = '';
if (!isDatabaseInitialized($pdo)) redirect('init_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $pass2   = $_POST['password2'] ?? '';

    if (empty($name) || empty($email) || empty($pass) || empty($address)) {
        $error = $t('Please fill in all required fields.', 'অনুগ্রহ করে সমস্ত প্রয়োজনীয় ক্ষেত্র পূরণ করুন।');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $t('Please enter a valid email address.', 'একটি বৈধ ইমেল ঠিকানা লিখুন।');
    } elseif (strlen($pass) < 6) {
        $error = $t('Password must be at least 6 characters.', 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
    } elseif ($pass !== $pass2) {
        $error = $t('Passwords do not match.', 'পাসওয়ার্ড মেলে না।');
    } else {
        $pass = trim($pass);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = $t('An account with this email already exists.', 'এই ইমেলে ইতিমধ্যে একটি অ্যাকাউন্ট বিদ্যমান।');
        } else {
            try {
                $pdo->beginTransaction();
                $hashed = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password, address, phone, role) VALUES (?, ?, ?, ?, ?, 'user')"
                );
                $stmt->execute([$name, $email, $hashed, $address, $phone]);
                $newId = $pdo->lastInsertId();
                $pdo->prepare("INSERT INTO rewards (user_id, total_points) VALUES (?, 0)")->execute([$newId]);
                $pdo->commit();
                setFlash('success', $t('Account created! Please log in.', 'অ্যাকাউন্ট তৈরি! অনুগ্রহ করে লগইন করুন।'));
                redirect('login.php');
            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = $t('Registration failed. Please try again.', 'রেজিস্ট্রেশন ব্যর্থ হয়েছে। আবার চেষ্টা করুন।');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t('Register — Notun Alo', 'নিবন্ধন — নতুন আলো') ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --auth-bg-left: linear-gradient(135deg, #064e3b, #065f46, #1D9E75); }
        body.dark-mode { --auth-bg-left: linear-gradient(135deg, #0a1a12, #0f2e1a, #14532d); }
        body.dark-mode .auth-form-panel { background: #0f1712; }
        body.dark-mode .auth-form-wrap { background: #1a2320; border-color: #374151; }
        body.dark-mode .auth-heading { color: #e5e7eb; }
        body.dark-mode .auth-sub { color: #9ca3af; }
        body.dark-mode .form-group label { color: #d1d5db; }
        body.dark-mode .auth-form input, body.dark-mode .auth-form textarea { background: #111827; border-color: #374151; color: #e5e7eb; }
        body.dark-mode .auth-form input:focus, body.dark-mode .auth-form textarea:focus { border-color: var(--brand-primary); }
        body.dark-mode .auth-switch { color: #9ca3af; }
        body.dark-mode .auth-switch a { color: #6ee7b7; }
        body.dark-mode .alert-error { background: #451a1a; border-color: #7f1d1d; color: #fca5a5; }
        body.dark-mode .benefit-list li { color: rgba(255,255,255,0.8); }
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
            <p class="brand-tagline"><?= $t('নতুন আলো — Smart Recycling Platform', 'নতুন আলো — স্মার্ট রিসাইক্লিং প্ল্যাটফর্ম') ?></p>
            <p class="brand-desc"><?= $t('Join thousands of eco-warriors turning waste into rewards and building a cleaner, greener Bangladesh through smart recycling.', 'হাজার হাজার ইকো-যোদ্ধার সাথে যোগ দিন যারা বর্জ্যকে পুরস্কারে পরিণত করছে এবং স্মার্ট রিসাইক্লিংয়ের মাধ্যমে একটি পরিচ্ছন্ন, সবুজ বাংলাদেশ গড়ছে।') ?></p>
            <ul class="benefit-list">
                <li>🌱 <?= $t('Earn reward points for every recycle', 'প্রত্যেক পুনর্ব্যবহারের জন্য পুরস্কার পয়েন্ট অর্জন করুন') ?></li>
                <li>🛍 <?= $t('Redeem points in Upcycle Shop', 'আপসাইকেল শপে পয়েন্ট রিডিম করুন') ?></li>
                <li>🚛 <?= $t('Schedule pickup anytime', 'যেকোনো সময় পিকআপ শিডিউল করুন') ?></li>
                <li>📊 <?= $t('Track your environmental impact', 'আপনার পরিবেশগত প্রভাব ট্র্যাক করুন') ?></li>
            </ul>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-form-wrap">
            <h2 class="auth-heading"><?= $t('Create Account', 'অ্যাকাউন্ট তৈরি করুন') ?></h2>
            <p class="auth-sub"><?= $t('Join the green revolution today', 'সবুজ বিপ্লবে আজই যোগ দিন') ?></p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="name"><?= $t('Full Name', 'পুরো নাম') ?></label>
                    <input type="text" id="name" name="name" placeholder="<?= $t('Enter your full name', 'আপনার পুরো নাম লিখুন') ?>"
                        value="<?= e($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email"><?= $t('Email Address', 'ইমেল ঠিকানা') ?></label>
                        <input type="email" id="email" name="email" placeholder="you@example.com"
                            value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone"><?= $t('Phone Number', 'ফোন নম্বর') ?></label>
                        <input type="text" id="phone" name="phone" placeholder="01XXXXXXXXX"
                            value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address"><?= $t('Address', 'ঠিকানা') ?></label>
                    <textarea id="address" name="address" rows="3" placeholder="<?= $t('Enter your address', 'আপনার ঠিকানা লিখুন') ?>"
                        required><?= e($_POST['address'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><?= $t('Password', 'পাসওয়ার্ড') ?></label>
                        <input type="password" id="password" name="password" placeholder="<?= $t('Minimum 6 characters', 'ন্যূনতম ৬ অক্ষর') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password2"><?= $t('Confirm Password', 'পাসওয়ার্ড নিশ্চিত করুন') ?></label>
                        <input type="password" id="password2" name="password2" placeholder="<?= $t('Repeat your password', 'পাসওয়ার্ড পুনরায় লিখুন') ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full"><?= $t('Create My Account', 'আমার অ্যাকাউন্ট তৈরি করুন') ?></button>
            </form>

            <p class="auth-switch"><?= $t('Already have an account?', 'ইতিমধ্যে একটি অ্যাকাউন্ট আছে?') ?> <a href="login.php"><?= $t('Sign In', 'সাইন ইন') ?></a></p>
            <p class="auth-switch">← <a href="index.php"><?= $t('Back to Home', 'হোম পেজে ফিরুন') ?></a></p>
        </div>
    </div>
</div>

<script>
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
