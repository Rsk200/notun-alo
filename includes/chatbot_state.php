<?php
function ensureChatbotStateTables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS chatbot_circuit (
        id INT PRIMARY KEY DEFAULT 1,
        consecutive_failures INT NOT NULL DEFAULT 0,
        last_failure_at TIMESTAMP NULL,
        opened_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("INSERT IGNORE INTO chatbot_circuit (id, consecutive_failures) VALUES (1, 0)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chatbot_states (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(64) NOT NULL DEFAULT 'main',
        step VARCHAR(32) NOT NULL DEFAULT 'idle',
        data JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_session (user_id, session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ─── Circuit Breaker ──────────────────────────────────────────────────────────
function circuitBreakerIsOpen(PDO $pdo): bool {
    $row = $pdo->query("SELECT consecutive_failures, opened_at FROM chatbot_circuit WHERE id = 1")->fetch();
    if (!$row) return false;
    if ((int)$row['consecutive_failures'] < 3) return false;
    if (!$row['opened_at']) return false;
    return (time() - strtotime($row['opened_at'])) < 300;
}

function circuitBreakerRecordSuccess(PDO $pdo): void {
    $pdo->exec("UPDATE chatbot_circuit SET consecutive_failures = 0, last_failure_at = NULL, opened_at = NULL WHERE id = 1");
}

function circuitBreakerRecordFailure(PDO $pdo): void {
    $pdo->exec("UPDATE chatbot_circuit SET
        consecutive_failures = consecutive_failures + 1,
        last_failure_at = NOW(),
        opened_at = IF(opened_at IS NULL, NOW(), opened_at)
        WHERE id = 1");
}

// ─── State Machine ────────────────────────────────────────────────────────────
function chatStateGet(PDO $pdo, int $userId, string $sessionId): ?array {
    $stmt = $pdo->prepare("SELECT step, data FROM chatbot_states WHERE user_id = ? AND session_id = ?");
    $stmt->execute([$userId, $sessionId]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['data'] = $row['data'] ? json_decode($row['data'], true) : [];
    return $row;
}

function chatStateSet(PDO $pdo, int $userId, string $sessionId, string $step, array $data = []): void {
    $stmt = $pdo->prepare(
        "INSERT INTO chatbot_states (user_id, session_id, step, data) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE step = VALUES(step), data = VALUES(data), updated_at = NOW()"
    );
    $stmt->execute([$userId, $sessionId, $step, json_encode($data)]);
}

function chatStateClear(PDO $pdo, int $userId, string $sessionId): void {
    $stmt = $pdo->prepare("DELETE FROM chatbot_states WHERE user_id = ? AND session_id = ?");
    $stmt->execute([$userId, $sessionId]);
}

// ─── Multi-turn scheduling flow ──────────────────────────────────────────────
function chatStateHandleFlow(PDO $pdo, int $userId, string $sessionId, string $message, bool $isBengali, string &$reply, ?array &$action): bool {
    $state = chatStateGet($pdo, $userId, $sessionId);
    if (!$state || $state['step'] === 'idle') return false;

    $data = $state['data'];
    $step = $state['step'];
    $lower = mb_strtolower(trim($message));

    // Cancel command
    if (preg_match('/^(cancel|stop|exit|quit|বাতিল|থামুন|ছাড়ুন)$/iu', $lower)) {
        chatStateClear($pdo, $userId, $sessionId);
        $reply = $isBengali
            ? 'ঠিক আছে, বাতিল করা হয়েছে। আবার শুরু করতে বলবেন! 😊'
            : 'Okay, cancelled. Let me know if you want to start again! 😊';
        return true;
    }

    $catMap = [
        'paper'  => 'Paper', 'কাগজ' => 'Paper', 'kagoj'  => 'Paper',
        'plastic'=> 'Plastic', 'প্লাস্টিক' => 'Plastic', 'plastik' => 'Plastic',
        'metal'  => 'Metal', 'ধাতু' => 'Metal', 'dhatu'  => 'Metal', 'লোহা' => 'Metal',
    ];

    switch ($step) {

        case 'awaiting_category':
            $category = null;
            foreach ($catMap as $key => $val) {
                if (mb_stripos($lower, $key) !== false) { $category = $val; break; }
            }
            if (!$category) {
                $reply = $isBengali
                    ? 'আমরা শুধু 📄কাগজ, 🧴প্লাস্টিক এবং 🔩ধাতু গ্রহণ করি। কোনটি নিতে চান?'
                    : 'We accept 📄Paper, 🧴Plastic, and 🔩Metal. Which one?';
                return true;
            }
            $data['category'] = $category;
            chatStateSet($pdo, $userId, $sessionId, 'awaiting_weight', $data);
            $reply = $isBengali
                ? "{$category}! ঠিক আছে। কত kg দিবেন? (যেমন: ২, ৫.৫)"
                : "{$category}! How many kg? (e.g., 2, 5.5)";
            return true;

        case 'awaiting_weight':
            if (preg_match('/(\d+(?:\.\d+)?)/', $message, $m)) {
                $weight = (float)$m[1];
                if ($weight < 0.1 || $weight > 100) {
                    $reply = $isBengali
                        ? 'ওজন ০.১ kg থেকে ১০০ kg এর মধ্যে হতে হবে। আবার বলুন।'
                        : 'Weight must be between 0.1 kg and 100 kg. Please try again.';
                    return true;
                }
                $data['weight'] = $weight;
                $minDate = date('Y-m-d', strtotime('+1 day'));
                chatStateSet($pdo, $userId, $sessionId, 'awaiting_date', $data);
                $reply = $isBengali
                    ? "{$weight} kg! কোন দিন নিতে আসব? (সর্বনিম্ন: {$minDate})"
                    : "{$weight} kg! What date? (Minimum: {$minDate})";
                return true;
            }
            $reply = $isBengali
                ? 'দয়া করে সংখ্যায় দিন (যেমন: ২, ৫.৫)।'
                : 'Please give a number (e.g., 2, 5.5).';
            return true;

        case 'awaiting_date':
            $parsedDate = null;
            if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $message, $dm)) {
                $parsedDate = sprintf('%04d-%02d-%02d', $dm[1], $dm[2], $dm[3]);
            } else {
                $l = mb_strtolower($message);
                if (preg_match('/\b(আগামীকাল|tomorrow|কাল\b)/u', $l)) {
                    $parsedDate = date('Y-m-d', strtotime('+1 day'));
                } elseif (preg_match('/\b(পরশু|day\s+after\s+tomorrow)/iu', $l)) {
                    $parsedDate = date('Y-m-d', strtotime('+2 days'));
                } elseif (preg_match('/(\d+)\s*(দিন|days?)/iu', $l, $dd)) {
                    $parsedDate = date('Y-m-d', strtotime("+{$dd[1]} days"));
                }
            }
            if (!$parsedDate) {
                $minDate = date('Y-m-d', strtotime('+1 day'));
                $reply = $isBengali
                    ? "তারিখ দিন (YYYY-MM-DD, যেমন {$minDate}, অথবা 'আগামীকাল')।"
                    : "Give a date (YYYY-MM-DD like {$minDate}, or 'tomorrow').";
                return true;
            }
            $ts = strtotime($parsedDate);
            $minTs = strtotime('+1 day midnight');
            if ($ts < $minTs) {
                $reply = $isBengali
                    ? 'তারিখ অবশ্যই আগামীকাল বা তার পরে হতে হবে।'
                    : 'Date must be tomorrow or later.';
                return true;
            }
            $data['date'] = $parsedDate;
            chatStateSet($pdo, $userId, $sessionId, 'confirming', $data);

            $cat = $data['category'];
            $wt  = $data['weight'];
            $dt  = $parsedDate;
            $rates = ['Paper' => POINTS_PAPER, 'Plastic' => POINTS_PLASTIC, 'Metal' => POINTS_METAL];
            $pts   = (int)round($wt * ($rates[$cat] ?? 5));

            $reply = $isBengali
                ? "✅ **নিশ্চিতকরণ:**\n• ধরন: {$cat}\n• ওজন: {$wt} kg\n• তারিখ: {$dt}\n• আনুমানিক পয়েন্ট: +{$pts} pts\n\n'হ্যাঁ' লিখুন নিশ্চিত করতে, 'না' লিখুন বাতিল করতে।"
                : "✅ **Confirmation:**\n• Type: {$cat}\n• Weight: {$wt} kg\n• Date: {$dt}\n• Estimated points: +{$pts} pts\n\nType 'yes' to confirm, 'no' to cancel.";
            return true;

        case 'confirming':
            if (preg_match('/^(yes|হ্যাঁ|হ্যা|হ|ঠিক আছে|ok|okay|confirm|নিশ্চিত)$/iu', trim($lower))) {
                $cat  = $data['category'];
                $wt   = $data['weight'];
                $dt   = $data['date'];
                $rates  = ['Paper' => POINTS_PAPER, 'Plastic' => POINTS_PLASTIC, 'Metal' => POINTS_METAL];
                $rate   = $rates[$cat] ?? 5;
                $estPts = (int)round($wt * $rate);
                $label  = date('d M Y', strtotime($dt));

                $stmt = $pdo->prepare("INSERT INTO pickups (user_id, category, estimated_weight, status, schedule_date) VALUES (?, ?, ?, 'pending', ?)");
                $stmt->execute([$userId, $cat, $wt, $dt]);

                $reply = $isBengali
                    ? "✅ আপনার pickup সফলভাবে schedule হয়েছে!\n\n📋 বিবরণ:\n• ধরন: {$cat}\n• পরিমাণ: {$wt} kg\n• তারিখ: {$label}\n\n🏆 আনুমানিক পয়েন্ট: +{$estPts} pts"
                    : "✅ Pickup scheduled!\n\n📋 Details:\n• Type: {$cat}\n• Weight: {$wt} kg\n• Date: {$label}\n\n🏆 Estimated points: +{$estPts} pts";

                $action = ['type' => 'pickup_scheduled', 'category' => $cat, 'weight' => $wt, 'date' => $dt, 'points' => $estPts];
                chatStateClear($pdo, $userId, $sessionId);
                return true;
            }
            chatStateClear($pdo, $userId, $sessionId);
            $reply = $isBengali
                ? 'ঠিক আছে, বাতিল করা হয়েছে। আবার শুরু করতে বলবেন! 😊'
                : 'Okay, cancelled. Let me know if you want to start again! 😊';
            return true;
    }

    return false;
}

// ─── Scheduling intent detection ─────────────────────────────────────────────
function detectSchedulingIntent(string $message): bool {
    $lower = mb_strtolower(trim($message));
    $patterns = [
        '/\b(schedule|scheduling|book|arrange|set.?up|fix|create|new)\b.*\b(pickup|collection)\b/i',
        '/\b(pickup|collection)\b.*\b(schedule|book|arrange|need|want|request)\b/i',
        '/\bpick.?up\b/i',
        '/\bআমার\b.*\bপিকআপ\b/u', '/\bপিকআপ\b.*\bশিডিউল\b/u',
        '/\bবুক\b/u', '/\bশিডিউল\b/u', '/\bপাঠান\b/u', '/\bআসো\b/u',
        '/\bপিকআপ\b/u',
        '/\b(need|want|would like)\b.*\b(recycle|throw|give|donate|dispose)\b/i',
        '/\brecycle\b.*\b(paper|plastic|metal|bottle|can)\b/i',
    ];
    foreach ($patterns as $pat) {
        if (preg_match($pat, $message)) return true;
    }
    return false;
}

function chatStateStartSchedule(PDO $pdo, int $userId, string $sessionId, string $message, bool $isBengali, string &$reply, ?array &$action): void {
    chatStateSet($pdo, $userId, $sessionId, 'awaiting_category', []);
    $reply = $isBengali
        ? "📦 আমরা ৩ টি উপাদান গ্রহণ করি:\n• 📄 কাগজ\n• 🧴 প্লাস্টিক\n• 🔩 ধাতু\n\nকোনটি দিতে চান?"
        : "📦 We accept 3 materials:\n• 📄 Paper\n• 🧴 Plastic\n• 🔩 Metal\n\nWhich one would you like to give?";
    $action = null;
}
