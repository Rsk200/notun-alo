<?php
// ============================================
// login.php - User Login
// Notun Alo (New Light) Recycling Platform
// ============================================
require_once 'includes/config.php';
startSession();


// Already logged in → redirect
if (isLoggedIn()) {
    $role = $_SESSION['role'] ?? 'user';
    redirect(match($role) {
        'admin'  => 'admin.php',
        'agency' => 'agency.php',
        default  => 'dashboard.php',
    });
}


$error = '';


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
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
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


// Fetch real-time stats
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


$formattedRecycled = formatNumberShort($realStats['total_recycled'] ?? 0);
$formattedUsers = formatNumberShort($realStats['total_users'] ?? 0);
$formattedPoints = formatNumberShort($realStats['total_points'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-split">
    <!-- Left branding panel -->
    <div class="auth-brand">
        <div class="brand-content">
            <div class="logo-mark">♻</div>
            <h1 class="brand-title">Notun Alo</h1>
            <p class="brand-tagline">নতুন আলো — New Light</p>
            <p class="brand-desc">Turn your waste into rewards. Build a greener tomorrow, one pickup at a time.</p>
            <div class="brand-stats">
                <div class="stat"><span class="stat-num"><?= $formattedRecycled ?></span><span class="stat-label">KG Recycled</span></div>
                <div class="stat"><span class="stat-num"><?= $formattedUsers ?></span><span class="stat-label">Active Users</span></div>
                <div class="stat"><span class="stat-num"><?= $formattedPoints ?></span><span class="stat-label">Points Rewarded</span></div>
            </div>
        </div>
    </div>


    <!-- Right form panel -->
    <div class="auth-form-panel">
        <div class="auth-form-wrap">
            <h2 class="auth-heading">Welcome Back</h2>
            <p class="auth-sub">Sign in to your Notun Alo account</p>


            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>


            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com"
                           value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" onclick="togglePass()">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Sign In</button>
            </form>


            <p class="auth-switch">Don't have an account? <a href="register.php">Register here</a></p>
            <p class="auth-switch">← <a href="index.php">Back to Home</a></p>
        </div>
    </div>
</div>


<script>
function togglePass() {
    const p = document.getElementById('password');
    p.type = p.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>





