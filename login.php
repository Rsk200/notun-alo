<?php
// ============================================
// login.php - User Login (Clean/Light Redesign)
// Notun Alo (New Light) Recycling Platform
// ============================================
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

$error = '';
if (!isDatabaseInitialized($pdo)) redirect('init_db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            setFlash('success', 'Welcome back!');
            redirect(match($user['role']) {
                'admin'  => 'admin.php',
                'agency' => 'agency.php',
                default  => 'dashboard.php',
            });
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Notun Alo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F5F7F2; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: white; border: 1px solid #E5E7EB; border-radius: 20px; width: 100%; max-width: 440px; padding: 40px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); }
        .logo-wrap { width: 48px; height: 48px; background: #E6F5EE; color: #1D9E75; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 24px; }
        .login-title { font-size: 24px; font-weight: 700; color: #111827; text-align: center; margin-bottom: 8px; }
        .login-sub { font-size: 14px; color: #6B7280; text-align: center; margin-bottom: 32px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px; }
        input { width: 100%; height: 46px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 0 16px; font-size: 14px; outline: none; transition: 0.2s; }
        input:focus { border-color: #1D9E75; background: white; box-shadow: 0 0 0 3px rgba(29,158,117,0.1); }
        
        .btn-submit { width: 100%; height: 48px; background: #1D9E75; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-submit:hover { background: #065F46; }
        
        .error-box { background: #FEF2F2; color: #B91C1C; border: 1px solid #FEE2E2; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: center; }
        .footer-link { display: block; text-align: center; margin-top: 24px; font-size: 14px; color: #6B7280; text-decoration: none; }
        .footer-link strong { color: #1D9E75; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-wrap"><i class="ti ti-recycle"></i></div>
        <h1 class="login-title">Welcome Back</h1>
        <p class="login-sub">Sign in to your Notun Alo account</p>

        <?php if ($error): ?>
            <div class="error-box"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-submit">Sign In</button>
        </form>

        <a href="register.php" class="footer-link">Don't have an account? <strong>Register here</strong></a>
        <a href="index.php" class="footer-link">← Back to Home</a>
    </div>

</body>
</html>
