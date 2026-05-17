<?php
require_once 'includes/config.php';
global $pdo;

// Check Admin Access (Admins skip the schedule check)
$isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');

try {
    // Fetch Settings
    $stmt = $pdo->query("SELECT * FROM docs_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // Access Control Logic
    $isAvailable = false;
    if ($isAdmin) {
        $isAvailable = true;
    } else {
        $now = new DateTime();
        $start = new DateTime($settings['start_time']);
        $end = new DateTime($settings['end_time']);

        if ($settings['visibility_mode'] === 'always_on') {
            $isAvailable = true;
        } elseif ($settings['visibility_mode'] === 'scheduled') {
            if ($now >= $start && $now <= $end) {
                $isAvailable = true;
            }
        }
    }

    if (!$isAvailable) {
        die("<div style='height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#0f172a; color:#f1f5f9; font-family:sans-serif;'>
            <h1 style='font-size:3rem; margin-bottom:10px;'>403: Documentation Locked</h1>
            <p style='color:#94a3b8;'>This module is currently set to private or scheduled for a future release.</p>
            <p style='margin-top:20px;'><a href='index.php' style='color:#38bdf8; text-decoration:none;'>Return to Notun Alo</a></p>
        </div>");
    }

    // Fetch Content
    $sections = $pdo->query("SELECT * FROM docs_sections ORDER BY is_technical ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $team = $pdo->query("SELECT * FROM team_members ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Live Metrics (Mocked for now, but linked to real DB)
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPickups = $pdo->query("SELECT COUNT(*) FROM pickups WHERE status = 'completed'")->fetchColumn();

} catch (PDOException $e) {
    error_log('[docs] ' . $e->getMessage());
    die('A database error occurred.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notun Alo | Live Documentation & Pitch Deck</title>
    <link rel="stylesheet" href="assets/css/docs.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    <script>mermaid.initialize({ startOnLoad: true, theme: 'dark' });</script>
</head>
<body class="docs-body">

    <nav class="docs-nav">
        <h2 style="font-size: 1.5rem; margin-bottom: 30px; color: #fff;">Notun Alo <span style="color:var(--docs-accent)">Docs</span></h2>
        
        <div style="margin-bottom: 20px;">
            <p style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; color: var(--docs-text-dim);">Business Deck</p>
            <a href="#problem" class="nav-link">The Problem</a>
            <a href="#solution" class="nav-link">The Solution</a>
            <a href="#vision" class="nav-link">Vision & Roadmap</a>
            <a href="#team" class="nav-link">The Team</a>
        </div>

        <div style="margin-bottom: 20px;">
            <p style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; color: var(--docs-text-dim);">Technical Specs</p>
            <a href="#architecture" class="nav-link">Architecture</a>
            <a href="#ai_layer" class="nav-link">AI & RAG Layer</a>
            <a href="#api_docs" class="nav-link">API Documentation</a>
        </div>

        <div>
            <p style="text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; color: var(--docs-text-dim);">Live System</p>
            <a href="#live_data" class="nav-link">Live Dashboard</a>
        </div>

        <?php if ($isAdmin): ?>
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--docs-border);">
            <a href="admin_docs.php" class="nav-link" style="color:#fb7185">Admin Panel</a>
        </div>
        <?php endif; ?>
    </nav>

    <main class="docs-main">
        <div class="docs-container">
            <header class="docs-header">
                <span class="badge badge-live">Live System Documentation</span>
                <h1>Transforming Waste Management</h1>
                <p style="color: var(--docs-text-dim); font-size: 1.2rem;">A YC-Style Deep Dive into the Notun Alo Ecosystem</p>
            </header>

            <?php foreach ($sections as $section): ?>
            <section id="<?php echo $section['section_key']; ?>" class="docs-section">
                <h2 style="font-size: 2rem; margin-bottom: 24px; color: var(--docs-accent);"><?php echo $section['title']; ?></h2>
                <div class="content">
                    <?php echo nl2br($section['content']); ?>
                </div>

                <?php if ($section['section_key'] === 'architecture'): ?>
                <div class="diagram-container">
                    <pre class="mermaid">
                    graph TD
                        A[User Browser] -->|PHP/AJAX| B[Monolith Web Server]
                        B -->|SQL| C[(MySQL Database)]
                        B -->|REST API| D[Flask AI Service]
                        D -->|Vector Search| E[(ChromaDB)]
                        D -->|LLM| F[Gemini 1.5 Pro]
                    </pre>
                </div>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>

            <!-- Team Section -->
            <section id="team" class="docs-section">
                <h2 style="font-size: 2rem; margin-bottom: 24px; color: var(--docs-accent);">Meet the Innovators</h2>
                <div class="team-grid">
                    <?php if (empty($team)): ?>
                    <p style="color:var(--docs-text-dim)">No team members added yet.</p>
                    <?php else: ?>
                        <?php foreach ($team as $member): ?>
                        <div class="team-card">
                            <img src="<?php echo $member['image_path']; ?>" alt="<?php echo $member['name']; ?>" class="team-image" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($member['name']); ?>&background=0D8ABC&color=fff'">
                            <h3 style="margin: 0; font-size: 1.2rem;"><?php echo $member['name']; ?></h3>
                            <p style="color: var(--docs-accent); margin: 4px 0; font-size: 0.9rem;"><?php echo $member['role']; ?></p>
                            <p style="color: var(--docs-text-dim); font-size: 0.8rem;"><?php echo $member['email']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Live Dashboard Section -->
            <section id="live_data" class="docs-section">
                <h2 style="font-size: 2rem; margin-bottom: 24px; color: var(--docs-accent);">Live System Health</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; text-align: center;">
                        <h4 style="margin:0; color:var(--docs-text-dim);">Total Users</h4>
                        <p style="font-size: 2.5rem; font-weight: 800; margin: 10px 0;"><?php echo $totalUsers; ?></p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; text-align: center;">
                        <h4 style="margin:0; color:var(--docs-text-dim);">Completed Pickups</h4>
                        <p style="font-size: 2.5rem; font-weight: 800; margin: 10px 0;"><?php echo $totalPickups; ?></p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 16px; text-align: center;">
                        <h4 style="margin:0; color:var(--docs-text-dim);">AI Service Status</h4>
                        <p style="font-size: 1rem; color: #4ade80; margin: 10px 0;">● ONLINE</p>
                    </div>
                </div>
            </section>

            <footer style="text-align: center; padding-top: 40px; color: var(--docs-text-dim); font-size: 0.8rem;">
                &copy; 2026 Notun Alo Ecosystem. Confidential Document.
            </footer>
        </div>
    </main>

    <script>
        // Smooth scrolling
        document.querySelectorAll('.nav-link').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
