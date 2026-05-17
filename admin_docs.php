<?php
require_once 'includes/config.php';
global $pdo;

// Only Admin access
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

try {
    // Handle Form Submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_settings'])) {
            $stmt = $pdo->prepare("UPDATE docs_settings SET visibility_mode = ?, start_time = ?, end_time = ? WHERE id = 1");
            $stmt->execute([$_POST['visibility_mode'], $_POST['start_time'], $_POST['end_time']]);
            $msg = "Settings updated!";
        }

        if (isset($_POST['add_member'])) {
            $stmt = $pdo->prepare("INSERT INTO team_members (name, role, email) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['role'], $_POST['email']]);
            $msg = "Team member added!";
        }

        if (isset($_POST['update_section'])) {
            $stmt = $pdo->prepare("UPDATE docs_sections SET content = ? WHERE id = ?");
            $stmt->execute([$_POST['content'], $_POST['section_id']]);
            $msg = "Section updated!";
        }
    }

    // Fetch Current Data
    $settings = $pdo->query("SELECT * FROM docs_settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    $sections = $pdo->query("SELECT * FROM docs_sections ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $team = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('[admin_docs] ' . $e->getMessage());
    die('A database error occurred. Please check the server logs.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Docs Control Center | Notun Alo</title>
    <link rel="stylesheet" href="assets/css/docs.css">
    <style>
        .admin-panel { padding: 40px; max-width: 900px; margin: 0 auto; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid var(--docs-border); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; color: var(--docs-text-dim); font-size: 0.9rem; }
        input, select, textarea { 
            width: 100%; padding: 12px; background: #0f172a; border: 1px solid var(--docs-border); 
            color: #fff; border-radius: 8px; margin-bottom: 16px;
        }
        button { 
            background: var(--docs-accent); color: #0f172a; border: none; padding: 12px 24px; 
            border-radius: 8px; font-weight: 700; cursor: pointer;
        }
        .msg { background: rgba(34,197,94,0.1); color: #4ade80; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body class="docs-body">
    <div class="admin-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px;">
            <h1 style="margin:0">Docs Control Center</h1>
            <a href="docs.php" target="_blank" style="color:var(--docs-accent); text-decoration:none">View Live Docs ↗</a>
        </div>

        <?php if (isset($msg)): ?>
            <div class="msg"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Visibility Control -->
        <div class="card">
            <h3>Visibility & Scheduling</h3>
            <form method="POST">
                <label>Mode</label>
                <select name="visibility_mode">
                    <option value="always_on" <?php echo $settings['visibility_mode'] === 'always_on' ? 'selected' : ''; ?>>Public (Always On)</option>
                    <option value="always_off" <?php echo $settings['visibility_mode'] === 'always_off' ? 'selected' : ''; ?>>Private (Always Off)</option>
                    <option value="scheduled" <?php echo $settings['visibility_mode'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                </select>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <label>Start Date/Time</label>
                        <input type="datetime-local" name="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($settings['start_time'])); ?>">
                    </div>
                    <div>
                        <label>End Date/Time</label>
                        <input type="datetime-local" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($settings['end_time'])); ?>">
                    </div>
                </div>
                <button type="submit" name="update_settings">Update Access Control</button>
            </form>
        </div>

        <!-- Team Management -->
        <div class="card">
            <h3>Team Showcase</h3>
            <form method="POST">
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="text" name="role" placeholder="Role (e.g. Lead Engineer)" required>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <button type="submit" name="add_member">Add Member</button>
            </form>
            
            <div style="margin-top:20px; display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:10px;">
                <?php foreach ($team as $m): ?>
                    <div style="background:#1e293b; padding:10px; border-radius:8px; font-size:0.8rem;">
                        <strong><?= e($m['name']) ?></strong><br>
                        <?= e($m['role']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Content Editor -->
        <div class="card">
            <h3>Edit Content Sections</h3>
            <?php foreach ($sections as $sec): ?>
                <form method="POST" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom:20px; margin-bottom:20px;">
                    <input type="hidden" name="section_id" value="<?php echo $sec['id']; ?>">
                    <label style="color:var(--docs-accent); font-weight:700;"><?= e($sec['title']) ?></label>
                    <textarea name="content" rows="6"><?= e($sec['content']) ?></textarea>
                    <button type="submit" name="update_section">Save Changes</button>
                </form>
            <?php endforeach; ?>
        </div>

        <p style="text-align:center"><a href="admin.php" style="color:var(--docs-text-dim)">Back to Main Admin</a></p>
    </div>
</body>
</html>
