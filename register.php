<?php
// ============================================
// register.php - User Registration (Clean/Light Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
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

$error   = '';
if (!isDatabaseInitialized($pdo)) redirect('init_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $pass2   = $_POST['password2'] ?? '';

    if (empty($name) || empty($email) || empty($pass) || empty($address)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, address, phone, role) VALUES (?, ?, ?, ?, ?, 'user')");
            $stmt->execute([$name, $email, $hashed, $address, $phone]);
            $newId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO rewards (user_id, total_points) VALUES (?, 0)")->execute([$newId]);
            setFlash('success', 'Account created! Please log in.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Notun Alo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F5F7F2; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .register-card { background: white; border: 1px solid #E5E7EB; border-radius: 20px; width: 100%; max-width: 540px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .logo-wrap { width: 48px; height: 48px; background: #E6F5EE; color: #1D9E75; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 24px; }
        .register-title { font-size: 24px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 8px; }
        .register-sub { font-size: 14px; color: #6B7280; text-align: center; margin-bottom: 32px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        input, textarea { width: 100%; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 12px 16px; font-size: 14px; outline: none; transition: 0.2s; font-family: inherit; }
        input:focus, textarea:focus { border-color: #1D9E75; background: white; box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .btn-submit { width: 100%; height: 48px; background: #1D9E75; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-submit:hover { background: #065F46; }
        
        .error-box { background: #FEF2F2; color: #B91C1C; border: 1px solid #FEE2E2; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center; }
        .footer-link { display: block; text-align: center; margin-top: 24px; font-size: 14px; color: #6B7280; text-decoration: none; }
        .footer-link strong { color: #1D9E75; }
        @media (max-width: 500px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="logo-wrap"><i class="ti ti-recycle"></i></div>
        <h1 class="register-title">Join Notun Alo</h1>
        <p class="register-sub">Start your recycling journey today</p>

        <?php if ($error): ?>
            <div class="error-box"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" placeholder="John Doe" value="<?= e($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="john@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="01XXXXXXXXX" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Pickup Address</label>
                <textarea id="address" name="address" rows="2" placeholder="Street, City, Postcode" required><?= e($_POST['address'] ?? '') ?></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label for="password2">Confirm Password</label>
                    <input type="password" id="password2" name="password2" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <a href="login.php" class="footer-link">Already have an account? <strong>Sign in</strong></a>
        <a href="index.php" class="footer-link">← Back to Home</a>
    </div>

</body>
</html>
