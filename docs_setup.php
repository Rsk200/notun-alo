<?php
require_once 'includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Docs Settings Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS docs_settings (
        id INT PRIMARY KEY DEFAULT 1,
        is_public TINYINT(1) DEFAULT 0,
        start_time DATETIME DEFAULT '2026-06-10 00:00:00',
        end_time DATETIME DEFAULT '2026-06-14 23:59:59',
        visibility_mode ENUM('always_on', 'always_off', 'scheduled') DEFAULT 'scheduled',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insert default settings if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM docs_settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO docs_settings (id, is_public, visibility_mode) VALUES (1, 0, 'scheduled')");
    }

    // 2. Team Members Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        role VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        image_path VARCHAR(255) DEFAULT 'assets/avatars/default.png',
        display_order INT DEFAULT 0
    )");

    // 3. Docs Sections Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS docs_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_key VARCHAR(50) UNIQUE NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        is_technical TINYINT(1) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Seed Initial YC-Style Content
    $sections = [
        ['problem', 'The Problem', 'Waste management in Bangladesh is fragmented, inefficient, and lacks transparency. Consumers have no incentive to recycle, and recycling agencies struggle with inconsistent supply chains.', 0],
        ['solution', 'The Solution', 'Notun Alo is a hyper-local AI-driven recycling ecosystem that gamifies sustainability. We provide a seamless bridge between consumers and recycling agencies through an intelligent RAG-backed assistant.', 0],
        ['vision', 'The Vision', 'To become the operating system for a zero-waste Bangladesh, turning every household into a verified sustainability node.', 0],
        ['architecture', 'System Architecture', 'Our system uses a decoupled microservice architecture. A PHP/MySQL monolith handles transactional business logic, while a Flask-based AI service manages RAG retrieval and predictive analytics.', 1],
        ['ai_layer', 'AI & RAG Pipeline', 'We utilize a Hybrid RAG approach. Knowledge is indexed in ChromaDB using sentence-transformers, and reasoning is handled by Gemini 1.5 with strict grounding protocols.', 1]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO docs_sections (section_key, title, content, is_technical) VALUES (?, ?, ?, ?)");
    foreach ($sections as $s) {
        $stmt->execute($s);
    }

    echo "Docs database infrastructure initialized successfully.";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
