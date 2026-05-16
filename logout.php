<?php
// logout.php
require_once 'includes/config.php';
startSession();

session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="1;url=index.php">
    <title>Logging out — Notun Alo</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            background: var(--green-dark);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin: 0;
            font-family: var(--font-body);
        }
        .logout-wrap {
            animation: fadeIn 0.8s ease-out;
        }
        .logout-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            animation: rotate 2s linear infinite;
        }
        h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        p {
            opacity: 0.7;
            font-size: 1rem;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="logout-wrap">
        <div class="logout-icon">♻</div>
        <h1>Logging out...</h1>
        <p>Building a greener Bangladesh, one step at a time.</p>
    </div>

    <script>
    (function () {
        try {
            const prefix = 'notun_alo_chat_history_user_';
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith(prefix)) {
                    localStorage.removeItem(key);
                }
            });
        } catch (err) {
            console.error('Logout storage clear failed:', err);
        }
        setTimeout(() => {
            window.location.replace('index.php');
        }, 1200);
    })();
    </script>
    <noscript>
        <p>You have been logged out. <a href="index.php" style="color:var(--gold);">Go to homepage</a>.</p>
    </noscript>
</body>
</html>



