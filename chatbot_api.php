<?php
// ============================================
// chatbot_api.php — AI Chatbot AJAX Endpoint
// Notun Alo Recycling Platform
// With caching, session memory, and suggestion chips
// ============================================

require_once 'includes/config.php';
require_once 'includes/chatbot_context.php';
require_once 'includes/chatbot_fallback.php';
require_once 'includes/chatbot_state.php';

// ─── Auth guard ────────────────────────────────────────────────────────────────
requireLoginJson();
header('Content-Type: application/json; charset=utf-8');

// ─── DB table initialisation ───────────────────────────────────────────────────
global $pdo;

function ensureChatbotTables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS chatbot_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cache_key VARCHAR(64) NOT NULL UNIQUE,
        response_text TEXT NOT NULL,
        suggestions JSON DEFAULT NULL,
        lang VARCHAR(5) NOT NULL DEFAULT 'en',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cache_key (cache_key),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(64) NOT NULL DEFAULT 'main',
        role ENUM('user','assistant','system') NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (user_id, session_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
ensureChatbotTables($pdo);
ensureChatbotStateTables($pdo);

// ─── Cache helpers ─────────────────────────────────────────────────────────────
function chatbotCacheKey(string $query, string $lang): string {
    return md5(mb_strtolower(trim($query)) . '|' . $lang);
}

function chatbotCacheGet(PDO $pdo, string $query, string $lang): ?array {
    $key = chatbotCacheKey($query, $lang);
    $stmt = $pdo->prepare(
        "SELECT response_text, suggestions FROM chatbot_cache
         WHERE cache_key = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return [
        'reply'       => $row['response_text'],
        'suggestions' => $row['suggestions'] ? json_decode($row['suggestions'], true) : null,
    ];
}

function chatbotCacheSet(PDO $pdo, string $query, string $lang, string $response, ?array $suggestions): void {
    $key = chatbotCacheKey($query, $lang);
    $stmt = $pdo->prepare(
        "INSERT INTO chatbot_cache (cache_key, response_text, suggestions, lang)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE response_text = VALUES(response_text), suggestions = VALUES(suggestions), created_at = NOW()"
    );
    $stmt->execute([$key, $response, $suggestions ? json_encode($suggestions) : null, $lang]);
}

// ─── Session memory helpers ────────────────────────────────────────────────────
function chatMessageSave(PDO $pdo, int $userId, string $sessionId, string $role, string $content): void {
    $stmt = $pdo->prepare(
        "INSERT INTO chat_messages (user_id, session_id, role, content) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $sessionId, $role, $content]);
}

function chatMessageLoadHistory(PDO $pdo, int $userId, string $sessionId, int $limit = 8): array {
    $stmt = $pdo->prepare(
        "SELECT role, content FROM chat_messages
         WHERE user_id = ? AND session_id = ?
         ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([$userId, $sessionId, $limit]);
    $rows = $stmt->fetchAll();
    return array_reverse(array_map(fn($r) => ['role' => $r['role'], 'content' => $r['content']], $rows));
}

// ─── Suggestion chip generator ─────────────────────────────────────────────────
function generateSuggestions(string $reply, ?string $source, bool $isBengali, bool $hasPickupAction): array {
    if ($hasPickupAction) {
        return $isBengali
            ? ['পয়েন্ট চেক করুন', 'ড্যাশবোর্ড দেখুন', 'রিসাইক্লিং গাইড']
            : ['Check Points', 'View Dashboard', 'Recycling Guide'];
    }
    $source = $source ?? '';

    $pointsChipsBN = ['পিকআপ শিডিউল', 'রিসাইক্লিং গাইড', 'ইমপ্যাক্ট স্ট্যাটাস'];
    $pointsChipsEN = ['Schedule Pickup', 'Recycling Guide', 'Impact Stats'];

    $guideChipsBN = ['পিকআপ শিডিউল', 'পয়েন্ট চেক', 'যোগাযোগ'];
    $guideChipsEN = ['Schedule Pickup', 'Check Points', 'Contact Support'];

    $pickupChipsBN = ['পয়েন্ট চেক', 'রিসাইক্লিং গাইড', 'সাহায্য'];
    $pickupChipsEN = ['Check Points', 'Recycling Guide', 'Help'];

    $genericBN = ['পয়েন্ট চেক', 'পিকআপ শিডিউল', 'রিসাইক্লিং গাইড'];
    $genericEN = ['Check Points', 'Schedule Pickup', 'Recycling Guide'];

    return match ($source) {
        'direct_points', 'points' => $isBengali ? $pointsChipsBN : $pointsChipsEN,
        'direct_guide', 'guide'   => $isBengali ? $guideChipsBN : $guideChipsEN,
        'pickup_lookup', 'schedule' => $isBengali ? $pickupChipsBN : $pickupChipsEN,
        default => $isBengali ? $genericBN : $genericEN,
    };
}

// ─── Accept JSON body ──────────────────────────────────────────────────────────
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['reply' => 'Invalid request.', 'action' => null, 'suggestions' => null]);
    exit;
}

$userMessage = trim((string)($input['message']));
$clientHistory = is_array($input['history'] ?? null) ? $input['history'] : null;
$sessionId   = trim((string)($input['session_id'] ?? 'main'));
if ($sessionId === '') $sessionId = 'main';

if ($userMessage === '') {
    echo json_encode(['reply' => 'Please type a message.', 'action' => null, 'suggestions' => null]);
    exit;
}

// ─── Language detection (Bengali script + Banglish + mixed-script) ─────────────
function detectDominantLanguage(string $message): array {
    $message = trim($message);
    if ($message === '') return ['lang' => 'en', 'confidence' => 1.0];

    $bnChars = preg_match_all('/\p{Bengali}/u', $message);
    $enChars = preg_match_all('/[a-zA-Z]/', $message);
    $words = preg_split('/\s+/', $message);
    $totalChars = mb_strlen($message);

    // Banglish keyword detection
    $banglishWords = ['ki', 'obostha', 'kemon', 'acho', 'ami', 'tumi', 'khobor',
        'valobashi', 'dhaka', 'bangla', 'bhalo', 'ace', 'naki', 'jani', 'janina',
        'bolo', 'dite', 'nibo', 'korte', 'kivabe', 'lomba', 'bujina', 'bolben',
        'hobe', 'kono', 'jonno', 'mone', 'hole', 'dorkar', 'chai', 'chaile',
        'thakbe', 'parbo', 'dibo', 'nibe', 'asbe', 'jaabe', 'bujhlam'];
    $banglishScore = 0;
    foreach ($words as $w) {
        $w = preg_replace('/[^a-z]/', '', mb_strtolower($w));
        if (in_array($w, $banglishWords, true)) {
            $banglishScore++;
        }
    }
    $banglishRatio = $totalChars > 0 ? $banglishScore / max(count($words), 1) : 0;

    // Bengali unicode ratio
    $bnRatio = $totalChars > 0 ? $bnChars / $totalChars : 0;

    // Mixed script: has both Bengali and English characters
    $hasMixed = $bnChars > 0 && $enChars > 0;

    // Decision logic
    if ($bnRatio > 0.3) {
        // Strong Bengali presence
        return ['lang' => 'bn', 'confidence' => min(1.0, 0.5 + $bnRatio)];
    }
    if ($banglishRatio >= 0.3) {
        // Strong Banglish — treat as Bengali
        return ['lang' => 'bn', 'confidence' => min(0.9, 0.4 + $banglishRatio)];
    }
    if ($hasMixed && $banglishRatio >= 0.15) {
        // Mixed script with some Banglish cues
        return ['lang' => 'bn', 'confidence' => 0.6];
    }
    if ($banglishScore >= 2) {
        return ['lang' => 'bn', 'confidence' => 0.7];
    }
    // Default English
    return ['lang' => 'en', 'confidence' => 1.0];
}

$langResult = detectDominantLanguage($userMessage);
$lang = $langResult['lang'];
$langConfidence = $langResult['confidence'];
$isBengali = $lang === 'bn';

$ragEnabled = strtolower((string)getenv('RAG_ENABLED') ?: '') === 'true';

function callRagAssistant(string $message, string $lang, array $user): ?array {
    $ragUrl = rtrim((string)getenv('RAG_API_URL') ?: 'http://localhost:5000', '/');

    $chWarm = curl_init($ragUrl . '/health');
    curl_setopt_array($chWarm, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    curl_exec($chWarm);
    $warmHttp = curl_getinfo($chWarm, CURLINFO_HTTP_CODE);
    curl_close($chWarm);

    if ($warmHttp !== 200) {
        error_log('[Notun Alo RAG] Health check failed, skipping RAG');
        return null;
    }

    $payload = json_encode([
        'query' => $message,
        'language' => $lang,
        'user_name' => $user['name'] ?? '',
        'user_points' => (int)($user['points'] ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($ragUrl . '/chat');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200 || !$response) {
        error_log('[Notun Alo RAG] Flask returned error: ' . ($curlError ?: "HTTP {$httpCode}"));
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return null;
    }

    $answer = trim((string)($decoded['answer'] ?? ''));
    $sources = $decoded['sources'] ?? [];
    
    // Check for our new natural unknown answers
    $unknownEN = "I couldn't find specific details on that in my current records, but I can help with recycling info, points, or pickups! 🌿";
    $unknownBN = "এই বিষয়ে আমার কাছে এখন পর্যাপ্ত তথ্য নেই, তবে আমি আপনাকে রিসাইক্লিং, পয়েন্ট বা পিকআপ নিয়ে সাহায্য করতে পারি। 🌿";

    if ($answer === '' || $answer === $unknownEN || $answer === $unknownBN || empty($sources) || !is_array($sources)) {
        return null;
    }

    return [
        'reply' => $answer,
        'sources' => $sources,
    ];
}




function chatbotStatusLabel(string $status, bool $isBengali): string {
    $status = strtolower($status);
    if ($isBengali) {
        return match ($status) {
            'pending' => 'অপেক্ষমাণ',
            'assigned' => 'এজেন্সির কাছে assigned',
            'completed' => 'সম্পন্ন',
            default => $status,
        };
    }
    return match ($status) {
        'pending' => 'Pending',
        'assigned' => 'Assigned to agency',
        'completed' => 'Completed',
        default => ucfirst($status),
    };
}

function maybeAnswerPickupLookup(PDO $pdo, int $userId, string $message, bool $isBengali): ?string {
    $looksLikePickupQuestion =
        preg_match('/schedule|request|history|status|recent|last|agent|agency|cash|payment|money|delayed?|late|problem|শিডিউল|অনুরোধ|ইতিহাস|স্ট্যাটাস|সাম্প্রতিক|শেষ|এজেন্ট|মালামাল|নিতে|নেয়|নিচ্ছে|টাকা|পেমেন্ট|পাইনি|হয়নি|হয়নি|দেরি|সমস্যা|কথা ছিল/iu', $message);

    if (!$looksLikePickupQuestion) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT id, category, estimated_weight, status, schedule_date, created_at
         FROM pickups
         WHERE user_id = ?
         ORDER BY schedule_date DESC, created_at DESC
         LIMIT 5"
    );
    $stmt->execute([$userId]);
    $pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$pickups) {
        return $isBengali
            ? "আপনার নামে এখনো কোনো pickup রেকর্ড পাওয়া যায়নি। আপনি চাইলে এখন একটি pickup schedule করতে পারেন।"
            : "I could not find any pickup records for your account yet. You can schedule a pickup now if you want.";
    }

    $isPaymentQuestion = (bool)preg_match('/cash|payment|money|টাকা|পেমেন্ট|পাইনি/iu', $message);
    $isProblemQuestion = (bool)preg_match('/agent|agency|এজেন্ট|মালামাল|নিতে|নেয়|নিচ্ছে|সমস্যা|হয়নি|হয়নি|late|delay|delayed|দেরি|কথা ছিল/iu', $message);

    if ($isBengali) {
        $lines = ["আপনার সাম্প্রতিক pickup রেকর্ড:"];
        foreach ($pickups as $pickup) {
            $lines[] = "#{$pickup['id']} - {$pickup['category']} {$pickup['estimated_weight']} kg, তারিখ: {$pickup['schedule_date']}, status: " . chatbotStatusLabel($pickup['status'], true);
        }
        if ($isPaymentQuestion) {
            $lines[] = "";
            $lines[] = "নোট: এই chatbot pickup status ও reward points তথ্য দেখাতে পারে। সরাসরি cash payment status এই ডাটাবেসে নেই, তাই টাকা সম্পর্কিত সমস্যা হলে Dashboard বা support এ যোগাযোগ করুন।";
        } elseif ($isProblemQuestion) {
            $lines[] = "";
            $lines[] = "যদি এজেন্ট accepted category (Paper, Plastic, Metal) নিতে না চায় বা pickup দেরি হয়, অনুগ্রহ করে pickup ID সহ support এ জানান।";
        }
        return implode("
", $lines);
    }

    $lines = ["Your recent pickup records:"];
    foreach ($pickups as $pickup) {
        $lines[] = "#{$pickup['id']} - {$pickup['category']} {$pickup['estimated_weight']} kg, date: {$pickup['schedule_date']}, status: " . chatbotStatusLabel($pickup['status'], false);
    }
    if ($isPaymentQuestion) {
        $lines[] = "";
        $lines[] = "Note: this chatbot can show pickup status and reward-point information. Direct cash payment status is not stored here, so please contact support from your Dashboard for money/payment issues.";
    } elseif ($isProblemQuestion) {
        $lines[] = "";
        $lines[] = "If an agent refuses accepted categories (Paper, Plastic, Metal) or a pickup is delayed, please contact support with the pickup ID.";
    }
    return implode("
", $lines);
}

function langError(bool $isBengali, string $en = 'Sorry, something went wrong. Please try again.'): string {
    return $isBengali
        ? 'দুঃখিত, একটি প্রযুক্তিগত সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।'
        : $en;
}

function maybeAnswerImpactLookup(int $userId, string $message, bool $isBengali): ?string {
    // Only trigger if specifically asking about THEIR impact or stats
    $looksLikeImpactQuery = preg_match('/\b(my impact|impact stats|impact status|my stats|impact score|my co2|my savings|ইমপ্যাক্ট স্ট্যাটাস|আমার পরিসংখ্যান|আমার ইমপ্যাক্ট)\b/iu', $message) 
        || (trim(strtolower($message)) === 'impact stats');
    if (!$looksLikeImpactQuery) return null;

    $impactUrl = "http://localhost:5003/impact?user_id=" . $userId;
    $ch = curl_init($impactUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $data = json_decode($response, true);
    if (!isset($data['total_kg_recycled']) || $data['total_kg_recycled'] == 0) {
        return "You haven't completed any recycling yet. Start today to see your environmental impact! / আপনি এখনও কোনো রিসাইক্লিং সম্পন্ন করেননি। আজই শুরু করুন এবং আপনার পরিবেশগত প্রভাব দেখুন! 🌿";
    }

    $kg = number_format($data['total_kg_recycled'], 1);
    $co2 = number_format($data['total_co2_saved_kg'], 1);
    $water = number_format($data['total_water_saved_liters']);
    $energy = number_format($data['total_energy_saved_kwh'], 1);
    $carKm = number_format($data['equivalent_car_km_saved'] ?? 0);

    return "🌍 **Your Environmental Impact Stats / আপনার পরিবেশগত প্রভাব:**\n\n"
         . "• ♻️ Total Recycled: **$kg kg** / মোট রিসাইক্লড\n"
         . "• ☁️ CO2 Saved: **$co2 kg** / কার্বন সাশ্রয়\n"
         . "• 💧 Water Saved: **$water L** / জল সাশ্রয়\n"
         . "• ⚡ Energy Saved: **$energy kWh** / বিদ্যুৎ সাশ্রয়\n"
         . "• 🚗 Car trip equivalent: **$carKm km** / গাড়ির সমান সাশ্রয়\n\n"
         . "Thank you for saving the planet! / পৃথিবী বাঁচাতে সাহায্য করার জন্য ধন্যবাদ! 🌿";
}

// ─── Shared response helper (save + cache + output) ──────────────────────────
function respondJson(PDO $pdo, int $userId, string $sessionId, string $lang,
    string $reply, string $userMessage, ?array $action = null,
    ?string $source = null, ?array $sources = null): void {

    // Save user message to session history
    chatMessageSave($pdo, $userId, $sessionId, 'user', $userMessage);
    // Save assistant response
    chatMessageSave($pdo, $userId, $sessionId, 'assistant', $reply);

    // Cache static responses (not user-specific)
    if ($source !== null && $source !== 'impact_lookup' && $source !== 'rag') {
        $suggestions = generateSuggestions($reply, $source, $lang === 'bn', $action !== null);
        chatbotCacheSet($pdo, $userMessage, $lang, $reply, $suggestions);
    }

    $suggestions = generateSuggestions($reply, $source, $lang === 'bn', $action !== null);

    $out = [
        'reply'       => $reply,
        'action'      => $action,
        'source'      => $source,
        'suggestions' => $suggestions,
        'session_id'  => $sessionId,
    ];
    if ($sources !== null) $out['sources'] = $sources;

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {

    // ─── Load user + points ────────────────────────────────────────────────────
    global $pdo;
    $user = getCurrentUser($pdo);
    if (!$user) {
        throw new RuntimeException('User session expired.');
    }
    $userId = (int)$user['id'];
    $user['points'] = getUserPoints($pdo, $userId);

    // ─── Circuit breaker ───────────────────────────────────────────────────────
    $circuitOpen = circuitBreakerIsOpen($pdo);
    if ($circuitOpen) {
        error_log('[Notun Alo Chatbot] Circuit open (3+ consecutive failures), skipping Pollinations');
    }

    // ─── State machine: multi-turn scheduling flow ───────────────────────────
    $stateReply = '';
    $stateAction = null;
    if (chatStateHandleFlow($pdo, $userId, $sessionId, $userMessage, $isBengali, $stateReply, $stateAction)) {
        respondJson($pdo, $userId, $sessionId, $lang, $stateReply, $userMessage, $stateAction, 'state_machine');
    }

    // ─── Check cache first (skipped for user-specific queries) ─────────────────
    $lowerMsg = strtolower($userMessage);
    $isUserQuery = preg_match('/\b(point|points|my\s|আমার|ব্যালেন্স|impact|স্ট্যাটাস)\b/i', $lowerMsg);
    if (!$isUserQuery) {
        $cached = chatbotCacheGet($pdo, $userMessage, $lang);
        if ($cached !== null) {
            // Still save messages for session continuity
            chatMessageSave($pdo, $userId, $sessionId, 'user', $userMessage);
            chatMessageSave($pdo, $userId, $sessionId, 'assistant', $cached['reply']);

            echo json_encode([
                'reply'       => $cached['reply'],
                'action'      => null,
                'source'      => 'cache',
                'suggestions' => $cached['suggestions'],
                'session_id'  => $sessionId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // ─── Direct Triggers ───────────────────────────────────────────────────────

    // Check Points
    if (preg_match('/^(check points|my points|points balance|পয়েন্ট চেক|আমার পয়েন্ট|ব্যালেন্স)$/i', trim($lowerMsg)) || $lowerMsg === 'points') {
        $pts = (int)$user['points'];
        $reply = $isBengali
            ? "🏆 আপনার বর্তমান পয়েন্ট: **$pts pts**।\nআপনি কাগজ (১৫), প্লাস্টিক (২০), অথবা ধাতু (৩০) রিসাইকেল করে আরও পয়েন্ট অর্জন করতে পারেন। 😊"
            : "🏆 Your current points: **$pts pts**.\nYou can earn more by recycling Paper (15 pts/kg), Plastic (20 pts/kg), or Metal (30 pts/kg). 😊";
        respondJson($pdo, $userId, $sessionId, $lang, $reply, $userMessage, null, 'direct_points');
    }

    // Recycling Guide
    if (preg_match('/^(recycling guide|how to recycle|guide|রিসাইক্লিং গাইড|গাইড)$/i', trim($lowerMsg))) {
        $reply = $isBengali
            ? "♻️ **রিসাইক্লিং গাইড:**\n\nআমরা ৩টি প্রধান ক্যাটাগরি গ্রহণ করি:\n• 📄 কাগজ (১৫ pts/kg)\n• 🧴 প্লাস্টিক (২০ pts/kg)\n• 🔩 ধাতু (৩০ pts/kg)\n\nপিকআপ শিডিউল করতে ওজন এবং তারিখসহ আমাকে জানান।"
            : "♻️ **Recycling Guide:**\n\nWe accept 3 main categories:\n• 📄 Paper (15 pts/kg)\n• 🧴 Plastic (20 pts/kg)\n• 🔩 Metal (30 pts/kg)\n\nTo schedule a pickup, just let me know the weight and date.";
        respondJson($pdo, $userId, $sessionId, $lang, $reply, $userMessage, null, 'direct_guide');
    }

    // Impact Stats
    $impactReply = maybeAnswerImpactLookup($userId, $userMessage, $isBengali);
    if ($impactReply !== null) {
        respondJson($pdo, $userId, $sessionId, $lang, $impactReply, $userMessage, null, 'impact_lookup');
    }

    // Pickup History / Schedule Request
    $pickupReply = maybeAnswerPickupLookup($pdo, $userId, $userMessage, $isBengali);
    if ($pickupReply !== null) {
        respondJson($pdo, $userId, $sessionId, $lang, $pickupReply, $userMessage, null, 'pickup_lookup');
    }

    // RAG assistant
    if ($ragEnabled) {
        $rag = callRagAssistant($userMessage, $lang, $user);
        if ($rag !== null) {
            $reply = $rag['reply'];
            $points = (int)($user['points'] ?? 0);
            $ptsQuery = $isBengali ? 'পয়েন্ট' : 'point';
            if ($points > 0 && stripos($reply, $ptsQuery) === false && stripos($reply, '🏆') === false && preg_match('/\b(point|points|পয়েন্ট|পয়েন্ট|reward|রিওয়ার্ড|balance|ব্যালেন্স)\b/i', $userMessage)) {
                $reply .= $isBengali
                    ? "\n\n🏆 আপনার বর্তমান পয়েন্ট: {$points} pts"
                    : "\n\n🏆 Your current points: {$points} pts";
            }
            respondJson($pdo, $userId, $sessionId, $lang, $reply, $userMessage, null, 'rag', $rag['sources'] ?? null);
        }
    }

    // ─── Load session memory ───────────────────────────────────────────────────
    $history = $clientHistory !== null ? $clientHistory : chatMessageLoadHistory($pdo, $userId, $sessionId, 6);

    // ─── Build system prompt ───────────────────────────────────────────────────
    $systemPrompt = getChatbotSystemPrompt($user, $lang);

    // ─── Build messages array (OpenAI-compatible) ──────────────────────────────
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    foreach ($history as $entry) {
        if (!isset($entry['role'], $entry['content'])) continue;
        $role = $entry['role'] === 'user' ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => (string)$entry['content']];
    }

    $messages[] = ['role' => 'user', 'content' => $userMessage];

    // ─── Try Pollinations.ai (skipped when circuit is open) ────────────────────
    $aiText = '';
    if (!$circuitOpen) {
        $payload = json_encode([
            'model'       => 'llama-3.1-70b',
            'messages'    => $messages,
            'temperature' => 0.7,
            'private'     => true,
        ]);

        $ch = curl_init('https://text.pollinations.ai/openai');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$curlError && $httpCode === 200 && $response) {
            $decoded = json_decode($response, true);
            $aiText  = trim($decoded['choices'][0]['message']['content'] ?? '');
        }

        // Record circuit state
        if ($aiText !== '') {
            circuitBreakerRecordSuccess($pdo);
        } else {
            circuitBreakerRecordFailure($pdo);
            if (!$curlError && $httpCode !== 200) {
                error_log("Pollinations.ai returned HTTP {$httpCode}, using local fallback");
            } elseif ($curlError) {
                error_log("Pollinations.ai cURL error: {$curlError}, using local fallback");
            }
        }
    }

    // ─── Fallback: local rule-based responses ─────────────────────────────────
    if ($aiText === '') {
        $aiText = getLocalFallbackResponse($userMessage, $isBengali, $user);
    }

    // ─── State machine activation (scheduling intent, no active flow) ────────
    $action = null;
    if (detectSchedulingIntent($userMessage)) {
        chatStateStartSchedule($pdo, $userId, $sessionId, $userMessage, $isBengali, $aiText, $action);
    }

    // ─── Detect pickup JSON in AI response ────────────────────────────────────
    $reply  = $aiText;

    if (preg_match('/\{[^{}]*"action"\s*:\s*"schedule_pickup"[^{}]*\}/s', $aiText, $matches)) {
        $jsonBlock  = $matches[0];
        $pickupData = json_decode($jsonBlock, true);

        if (is_array($pickupData) && isset($pickupData['category'], $pickupData['weight'], $pickupData['date'])) {
            $allowedCats = ['Paper', 'Plastic', 'Metal'];
            $rates       = ['Paper' => POINTS_PAPER, 'Plastic' => POINTS_PLASTIC, 'Metal' => POINTS_METAL];

            $category = ucfirst(strtolower(trim((string)$pickupData['category'])));
            $weight   = round((float)$pickupData['weight'], 2);
            $dateStr  = trim((string)$pickupData['date']);

            if (!in_array($category, $allowedCats, true)) {
                throw new RuntimeException("Invalid category: {$category}");
            }
            if ($weight < 0.1 || $weight > 100.0) {
                throw new RuntimeException("Weight out of range: {$weight}");
            }

            $selectedDate = DateTime::createFromFormat('Y-m-d', $dateStr);
            $tomorrow     = new DateTime('tomorrow');
            $tomorrow->setTime(0, 0, 0);

            if (!$selectedDate || $selectedDate < $tomorrow) {
                throw new RuntimeException("Date must be tomorrow or later: {$dateStr}");
            }

            $formattedDate = $selectedDate->format('Y-m-d');

            $stmt = $pdo->prepare(
                "INSERT INTO pickups (user_id, category, estimated_weight, status, schedule_date)
                 VALUES (:uid, :cat, :wt, 'pending', :dt)"
            );
            $stmt->execute([
                ':uid' => $user['id'],
                ':cat' => $category,
                ':wt'  => $weight,
                ':dt'  => $formattedDate,
            ]);

            $rate      = $rates[$category];
            $estPoints = (int)round($weight * $rate);
            $dateLabel = $selectedDate->format('d M Y');

            if ($isBengali) {
                $reply = "✅ আপনার pickup সফলভাবে schedule হয়েছে!\n\n"
                       . "📋 বিবরণ:\n"
                       . "• ধরন: {$category}\n"
                       . "• পরিমাণ: {$weight} kg\n"
                       . "• তারিখ: {$dateLabel}\n\n"
                       . "🏆 আনুমানিক পয়েন্ট: +{$estPoints} pts\n"
                       . "আপনার বর্তমান মোট: {$user['points']} pts";
            } else {
                $reply = "✅ Your pickup has been scheduled!\n\n"
                       . "📋 Details:\n"
                       . "• Type: {$category}\n"
                       . "• Weight: {$weight} kg\n"
                       . "• Date: {$dateLabel}\n\n"
                       . "🏆 Estimated points: +{$estPoints} pts\n"
                       . "Your current total: {$user['points']} pts";
            }

            $action = [
                'type'     => 'pickup_scheduled',
                'category' => $category,
                'weight'   => $weight,
                'date'     => $formattedDate,
                'points'   => $estPoints,
            ];
        }
    }

    // ─── Low-confidence bilingual disambiguation ──────────────────────────────
    $wordCount = count(preg_split('/\s+/', $userMessage));
    if ($langConfidence < 0.6 && $wordCount >= 2) {
        $bilingualNote = $lang === 'bn'
            ? "\n\n---\n(English: I detected Bangla/Banglish in your message. If you prefer English, just type in English. 😊)"
            : "\n\n---\n(বাংলায়: আমি আপনার মেসেজে ইংরেজি শনাক্ত করেছি। আপনি যদি বাংলায় লিখতে চান, দয়া করে বাংলায় লিখুন। 😊)";
        if (strpos($reply, '(English:') === false && strpos($reply, '(বাংলায়:') === false) {
            $reply .= $bilingualNote;
        }
    }

    // ─── Send response ─────────────────────────────────────────────────────────
    respondJson($pdo, $userId, $sessionId, $lang, $reply, $userMessage, $action);

} catch (Throwable $e) {
    error_log('[Notun Alo Chatbot] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    $catchLang = isset($lang) ? ($lang === 'bn') : false;
    $catchSid  = isset($sessionId) ? $sessionId : 'main';

    echo json_encode([
        'reply'       => langError($catchLang),
        'action'      => null,
        'suggestions' => null,
        'session_id'  => $catchSid,
    ], JSON_UNESCAPED_UNICODE);
}
