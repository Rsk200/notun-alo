<?php
// ============================================
// chatbot_api.php — AI Chatbot AJAX Endpoint
// Notun Alo Recycling Platform
// Uses Pollinations.ai (100% free, no API key)
// ============================================

require_once 'includes/config.php';
require_once 'includes/chatbot_context.php';
require_once 'includes/chatbot_fallback.php';

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

function callRagAssistant(string $message, bool $isBengali, array $user): ?array {
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
        'language' => $isBengali ? 'bn' : 'en',
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

try {

    // ─── 4. Load user + points ────────────────────────────────────────────────
    global $pdo;
    $user = getCurrentUser($pdo);
    if (!$user) {
        throw new RuntimeException('User session expired.');
    }
    $user['points'] = getUserPoints($pdo, (int)$user['id']);

    // 4.1 Check Quick Actions / Direct Triggers
    $lowerMsg = strtolower($userMessage);
    
    // Check Points (Strict match or 'my points' intent)
    if (preg_match('/^(check points|my points|points balance|পয়েন্ট চেক|আমার পয়েন্ট|ব্যালেন্স)$/i', trim($lowerMsg)) || $lowerMsg === 'points') {
        $pts = (int)$user['points'];
        $reply = $isBengali 
            ? "🏆 আপনার বর্তমান পয়েন্ট: **$pts pts**।\nআপনি কাগজ (৫), প্লাস্টিক (৮), অথবা ধাতু (১২) রিসাইকেল করে আরও পয়েন্ট অর্জন করতে পারেন। 😊"
            : "🏆 Your current points: **$pts pts**.\nYou can earn more by recycling Paper (5 pts/kg), Plastic (8 pts/kg), or Metal (12 pts/kg). 😊";
        echo json_encode(['reply' => $reply, 'action' => null, 'source' => 'direct_points'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Recycling Guide (Strict match)
    if (preg_match('/^(recycling guide|how to recycle|guide|রিসাইক্লিং গাইড|গাইড)$/i', trim($lowerMsg))) {
        $reply = $isBengali
            ? "♻️ **রিসাইক্লিং গাইড:**\n\nআমরা ৩টি প্রধান ক্যাটাগরি গ্রহণ করি:\n• 📄 কাগজ (৫ pts/kg)\n• 🧴 প্লাস্টিক (৮ pts/kg)\n• 🔩 ধাতু (১২ pts/kg)\n\nপিকআপ শিডিউল করতে ওজন এবং তারিখসহ আমাকে জানান।"
            : "♻️ **Recycling Guide:**\n\nWe accept 3 main categories:\n• 📄 Paper (5 pts/kg)\n• 🧴 Plastic (8 pts/kg)\n• 🔩 Metal (12 pts/kg)\n\nTo schedule a pickup, just let me know the weight and date.";
        echo json_encode(['reply' => $reply, 'action' => null, 'source' => 'direct_guide'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Impact Stats
    $impactReply = maybeAnswerImpactLookup((int)$user['id'], $userMessage, $isBengali);
    if ($impactReply !== null) {
        echo json_encode(['reply' => $impactReply, 'action' => null, 'source' => 'impact_lookup'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Pickup History / Schedule Request
    $directReply = maybeAnswerPickupLookup($pdo, (int)$user['id'], $userMessage, $isBengali);
    if ($directReply !== null) {
        echo json_encode(['reply' => $directReply, 'action' => null, 'source' => 'pickup_lookup'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($ragEnabled) {
        $rag = callRagAssistant($userMessage, $isBengali, $user);
        if ($rag !== null) {
            $reply = $rag['reply'];
            $points = (int)($user['points'] ?? 0);
            $ptsQuery = $isBengali ? 'পয়েন্ট' : 'point';
            if ($points > 0 && stripos($reply, $ptsQuery) === false && stripos($reply, '🏆') === false && preg_match('/\b(point|points|পয়েন্ট|পয়েন্ট|reward|রিওয়ার্ড|balance|ব্যালেন্স)\b/i', $userMessage)) {
                $reply .= $isBengali
                    ? "\n\n🏆 আপনার বর্তমান পয়েন্ট: {$points} pts"
                    : "\n\n🏆 Your current points: {$points} pts";
            }
            echo json_encode([
                'reply' => $reply,
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

    // ─── 7. Try Pollinations.ai (free, optional) ────────────────────────────
    $payload = json_encode([
        'model'       => 'llama-3.1-70b', // More intelligent for reasoning than default
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
        CURLOPT_TIMEOUT        => 30, // Increased for larger model
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $aiText = '';
    if (!$curlError && $httpCode === 200 && $response) {
        $decoded = json_decode($response, true);
        $aiText  = trim($decoded['choices'][0]['message']['content'] ?? '');
    }

    // ─── 8. Fallback: local rule-based responses ─────────────────────────────
    if ($aiText === '') {
        if (!$curlError && $httpCode !== 200) {
            error_log("Pollinations.ai returned HTTP {$httpCode}, using local fallback");
        } elseif ($curlError) {
            error_log("Pollinations.ai cURL error: {$curlError}, using local fallback");
        }
        $aiText = getLocalFallbackResponse($userMessage, $isBengali, $user);
    }

    // ─── 9. Detect pickup JSON in AI response (from Pollinations only) ──────
    $action = null;
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
