<?php
// ============================================
// register.php - User Registration
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
$success = '';

// Check if database is initialized
if (!isDatabaseInitialized($pdo)) {
    redirect('init_db.php');
}


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
        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, address, phone, role) VALUES (?, ?, ?, ?, ?, 'user')"
            );
            $stmt->execute([$name, $email, $hashed, $address, $phone]);
            $newId = $pdo->lastInsertId();


            // Create empty rewards row
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


    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">


    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>


    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>


<body class="auth-body">


<div class="auth-split">


    <!-- ====================================
         LEFT SIDE
    ===================================== -->
    <div class="auth-brand">


        <div class="brand-content">


            <div class="logo-mark">
                ♻
            </div>


            <h1 class="brand-title">
                Notun Alo
            </h1>


            <p class="brand-tagline">
                নতুন আলো — Smart Recycling Platform
            </p>


            <p class="brand-desc">
                Join thousands of eco-warriors turning waste into rewards
                and building a cleaner, greener Bangladesh through smart recycling.
            </p>


            <ul class="benefit-list">


                <li>
                    🌱 Earn reward points for every recycle
                </li>


                <li>
                    🛍 Redeem points in Upcycle Shop
                </li>


                <li>
                    🚛 Schedule pickup anytime
                </li>


                <li>
                    📊 Track your environmental impact
                </li>


            </ul>


        </div>


    </div>


    <!-- ====================================
         RIGHT SIDE
    ===================================== -->
    <div class="auth-form-panel">


        <div class="auth-form-wrap">


            <h2 class="auth-heading">
                Create Account
            </h2>


            <p class="auth-sub">
                Join the green revolution today
            </p>


            <!-- ERROR -->
            <?php if ($error): ?>


                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>


            <?php endif; ?>


            <!-- FORM -->
            <form method="POST" class="auth-form">


                <!-- NAME -->
                <div class="form-group">


                    <label for="name">
                        Full Name
                    </label>


                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Enter your full name"
                        value="<?= e($_POST['name'] ?? '') ?>"
                        required
                    >


                </div>


                <!-- EMAIL + PHONE -->
                <div class="form-row">


                    <div class="form-group">


                        <label for="email">
                            Email Address
                        </label>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            required
                        >


                    </div>


                    <div class="form-group">


                        <label for="phone">
                            Phone Number
                        </label>


                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            placeholder="01XXXXXXXXX"
                            value="<?= e($_POST['phone'] ?? '') ?>"
                        >


                    </div>


                </div>


                <!-- ADDRESS -->
                <div class="form-group">


                    <label for="address">
                        Address
                    </label>


                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        placeholder="Enter your address"
                        required
                    ><?= e($_POST['address'] ?? '') ?></textarea>


                </div>


                <!-- PASSWORDS -->
                <div class="form-row">


                    <div class="form-group">


                        <label for="password">
                            Password
                        </label>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimum 6 characters"
                            required
                        >


                    </div>


                    <div class="form-group">


                        <label for="password2">
                            Confirm Password
                        </label>


                        <input
                            type="password"
                            id="password2"
                            name="password2"
                            placeholder="Repeat your password"
                            required
                        >


                    </div>


                </div>


                <!-- BUTTON -->
                <button
                    type="submit"
                    class="btn btn-primary btn-full"
                >
                    Create My Account
                </button>


            </form>


            <!-- LOGIN -->
            <p class="auth-switch">


                Already have an account?


                <a href="login.php">
                    Sign In
                </a>


            </p>


            <p class="auth-switch">← <a href="index.php">Back to Home</a></p>


        </div>


    </div>


</div>


</body>
</html>



