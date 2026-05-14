<?php
// ============================================
// chatbot_api.php — AI Chatbot AJAX Endpoint
// Notun Alo Recycling Platform
// Uses Pollinations.ai (100% free, no API key)
// ============================================

require_once 'includes/config.php';
require_once 'includes/chatbot_context.php';

// ─── 1. Auth guard (JSON, not HTML redirect) ───────────────────────────────────
requireLoginJson();

// Always respond with JSON
header('Content-Type: application/json; charset=utf-8');

// ─── 2. Accept JSON body only ─────────────────────────────────────────────────
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input) || !isset($input['message'], $input['history'])) {
    http_response_code(400);
    echo json_encode(['reply' => 'Invalid request.', 'action' => null]);
    exit;
}

$userMessage = trim((string)($input['message']));
$history     = is_array($input['history']) ? $input['history'] : [];

if ($userMessage === '') {
    echo json_encode(['reply' => 'Please type a message.', 'action' => null]);
    exit;
}

// ─── 3. Detect user language for error messages ───────────────────────────────
$isBengali = (bool)preg_match('/[\x{0980}-\x{09FF}]/u', $userMessage);

$ragEnabled = strtolower((string)getenv('RAG_ENABLED') ?: '') === 'true';

function callRagAssistant(string $message, bool $isBengali): ?array {
    $payload = json_encode([
        'query' => $message,
        'language' => $isBengali ? 'bn' : 'en',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ragUrl = rtrim((string)getenv('RAG_API_URL') ?: 'http://localhost:5000', '/') . '/chat';
    $ch = curl_init($ragUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 14,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || $httpCode !== 200 || !$response) {
        error_log('[Notun Alo RAG] Flask unavailable or returned error: ' . ($curlError ?: "HTTP {$httpCode}"));
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return null;
    }

    $answer = trim((string)($decoded['answer'] ?? ''));
    $sources = $decoded['sources'] ?? [];
    $unknown = "I don't know based on the current knowledge base.";

    if ($answer === '' || $answer === $unknown || empty($sources) || !is_array($sources)) {
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
        preg_match('/history|status|recent|last|agent|agency|cash|payment|money|delayed?|late|problem|ইতিহাস|স্ট্যাটাস|সাম্প্রতিক|শেষ|এজেন্ট|মালামাল|নিতে|নেয়|নিচ্ছে|টাকা|পেমেন্ট|পাইনি|হয়নি|হয়নি|দেরি|সমস্যা|কথা ছিল/iu', $message);

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

try {

    // ─── 4. Load user + points ────────────────────────────────────────────────
    global $pdo;
    $user = getCurrentUser($pdo);
    if (!$user) {
        throw new RuntimeException('User session expired.');
    }
    $user['points'] = getUserPoints($pdo, (int)$user['id']);

    $directReply = maybeAnswerPickupLookup($pdo, (int)$user['id'], $userMessage, $isBengali);
    if ($directReply !== null) {
        echo json_encode([
            'reply' => $directReply,
            'action' => null,
            'source' => 'pickup_lookup',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($ragEnabled) {
        $rag = callRagAssistant($userMessage, $isBengali);
        if ($rag !== null) {
            echo json_encode([
                'reply' => $rag['reply'],
                'action' => null,
                'sources' => $rag['sources'],
                'source' => 'rag',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    // ─── 5. Build system prompt ───────────────────────────────────────────────
    $systemPrompt = getChatbotSystemPrompt($user);

    // ─── 6. Build messages array (OpenAI-compatible) ──────────────────────────
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
    ];

    foreach ($history as $entry) {
        if (!isset($entry['role'], $entry['content'])) continue;
        $role = $entry['role'] === 'user' ? 'user' : 'assistant';
        $messages[] = ['role' => $role, 'content' => (string)$entry['content']];
    }

    $messages[] = ['role' => 'user', 'content' => $userMessage];

    // ─── 7. Call Pollinations.ai (Free, no API key needed) ───────────────────
    $payload = json_encode([
        'model'       => 'openai',        // GPT-4o via Pollinations
        'messages'    => $messages,
        'temperature' => 0.65,
        'private'     => true,            // Don't log conversations publicly
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

    if ($curlError) {
        throw new RuntimeException("cURL error: {$curlError}");
    }

    if ($httpCode !== 200) {
        error_log("Chatbot API error HTTP {$httpCode}: {$response}");
        throw new RuntimeException("API returned HTTP {$httpCode}");
    }

    $decoded     = json_decode($response, true);
    $aiText      = trim($decoded['choices'][0]['message']['content'] ?? '');

    if ($aiText === '') {
        throw new RuntimeException('Empty response from AI');
    }

    // ─── 8. Detect pickup JSON in AI response ─────────────────────────────────
    $action = null;
    $reply  = $aiText;

    // Match the JSON block the AI is instructed to output
    if (preg_match('/\{[^{}]*"action"\s*:\s*"schedule_pickup"[^{}]*\}/s', $aiText, $matches)) {
        $jsonBlock  = $matches[0];
        $pickupData = json_decode($jsonBlock, true);

        if (is_array($pickupData) && isset($pickupData['category'], $pickupData['weight'], $pickupData['date'])) {

            // ── PHP-side re-validation ────────────────────────────────────────
            $allowedCats = ['Paper', 'Plastic', 'Metal'];
            $rates       = ['Paper' => POINTS_PAPER, 'Plastic' => POINTS_PLASTIC, 'Metal' => POINTS_METAL];

            $category = ucfirst(strtolower(trim((string)$pickupData['category'])));
            $weight   = round((float)$pickupData['weight'], 2);
            $dateStr  = trim((string)$pickupData['date']);

            // Validate category
            if (!in_array($category, $allowedCats, true)) {
                throw new RuntimeException("Invalid category: {$category}");
            }

            // Validate weight
            if ($weight < 0.1 || $weight > 100.0) {
                throw new RuntimeException("Weight out of range: {$weight}");
            }

            // Validate date (must be tomorrow or later)
            $selectedDate = DateTime::createFromFormat('Y-m-d', $dateStr);
            $tomorrow     = new DateTime('tomorrow');
            $tomorrow->setTime(0, 0, 0);

            if (!$selectedDate || $selectedDate < $tomorrow) {
                throw new RuntimeException("Date must be tomorrow or later: {$dateStr}");
            }

            $formattedDate = $selectedDate->format('Y-m-d');

            // ── Insert into pickups table ──────────────────────────────────────
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

            // ── Calculate estimated points ────────────────────────────────────
            $rate      = $rates[$category];
            $estPoints = (int)round($weight * $rate);
            $dateLabel = $selectedDate->format('d M Y');

            // ── Build confirmation reply ──────────────────────────────────────
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

    // ─── 9. Return response ───────────────────────────────────────────────────
    echo json_encode([
        'reply'  => $reply,
        'action' => $action,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    // Log full error server-side only
    error_log('[Notun Alo Chatbot] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    echo json_encode([
        'reply'  => langError($isBengali),
        'action' => null,
    ], JSON_UNESCAPED_UNICODE);
}
