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
    <meta http-equiv="refresh" content="1;url=index.php">
    <title>Logging out...</title>
</head>
<body>
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
    window.location.replace('index.php');
})();
</script>
<noscript>
    <p>You have been logged out. <a href="index.php">Go to homepage</a>.</p>
</noscript>
</body>
</html>



