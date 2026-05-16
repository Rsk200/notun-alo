<?php
function getLocalFallbackResponse(string $message, bool $isBengali, array $user): string {
    $name  = htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
    $pts   = (int)($user['points'] ?? 0);
    $lower = mb_strtolower(trim($message));

    // Simple language detection (check for Bangla characters)
    $hasBangla = (bool)preg_match('/\p{Bengali}/u', $message);

    $bnGreetings   = ['হ্যালো', 'হাই', 'হেলো', 'কেমন', 'কে খবর', 'খবর কি'];
    $enGreetings   = ['hello', 'hi', 'hey', 'how are you', 'whats up', 'sup'];
    $bnIdentity    = ['তোমার নাম', 'কে তুমি', 'আমার নাম', 'তুমি কে'];
    $enIdentity    = ['your name', 'who are you', 'who am i', 'my name'];
    $bnPoints      = ['পয়েন্ট', 'পয়েন্ট', 'ব্যালেন্স', 'রিওয়ার্ড'];
    $enPoints      = ['points', 'balance', 'reward', 'how many points'];
    $bnImpact      = ['ইমপ্যাক্ট', 'স্ট্যাট', 'পরিবেশ', 'জলবায়ু'];
    $enImpact      = ['impact', 'stats', 'environment', 'climate', 'co2'];

    // 1. Identity
    $isIdentity = false;
    foreach ($bnIdentity as $i) { if (mb_stripos($lower, $i) !== false) $isIdentity = true; }
    foreach ($enIdentity as $i) { if (mb_stripos($lower, $i) !== false) $isIdentity = true; }
    if ($isIdentity) {
        if ($hasBangla) return "আমি Notun Alo (নতুন আলো)। আমি আপনাকে **$name** হিসেবে চিনি এবং আপনার বর্তমানে **$pts পয়েন্ট** আছে। 😊";
        return "I am Notun Alo. I know you as **$name** and you currently have **$pts points**. 😊";
    }

    // 2. Greetings
    $isGreeting = false;
    foreach ($bnGreetings as $g) { if (mb_stripos($lower, $g) !== false) $isGreeting = true; }
    foreach ($enGreetings as $g) { if (mb_stripos($lower, $g) !== false) $isGreeting = true; }
    if ($isGreeting) {
        if ($hasBangla) return "হ্যালো $name! 😊 ভালো আছি। আপনাকে আজ কীভাবে সাহায্য করতে পারি?";
        return "Hello $name! 😊 I'm doing well. How can I help you today?";
    }

    // 3. Impact / Stats
    $isImpact = false;
    foreach ($bnImpact as $p) { if (mb_stripos($lower, $p) !== false) $isImpact = true; }
    foreach ($enImpact as $p) { if (mb_stripos($lower, $p) !== false) $isImpact = true; }
    if ($isImpact) {
        if ($hasBangla) return "আপনার রিসাইক্লিং কার্যক্রম আমাদের পরিবেশ বাঁচাতে সাহায্য করছে। আপনার ড্যাশবোর্ডে গিয়ে আপনি আপনার ব্যক্তিগত ইমপ্যাক্ট স্ট্যাটাস দেখতে পারেন। 🌍";
        return "Your recycling efforts are helping save the environment! You can view your detailed impact stats on your Dashboard. 🌍";
    }

    // 4. Points
    $isPoints = false;
    foreach ($bnPoints as $p) { if (mb_stripos($lower, $p) !== false) $isPoints = true; }
    foreach ($enPoints as $p) { if (mb_stripos($lower, $p) !== false) $isPoints = true; }
    if ($isPoints) {
        if ($hasBangla) return "🏆 আপনার বর্তমান পয়েন্ট: **$pts pts**। কাগজ (৫), প্লাস্টিক (৮), এবং ধাতু (১২) রিসাইকেল করে আপনি আরও পয়েন্ট পেতে পারেন।";
        return "🏆 Your current points: **$pts pts**. You can earn more by recycling Paper (5), Plastic (8), or Metal (12).";
    }

    // Generic direct response
    if ($hasBangla) {
        return "আমি আপনার প্রশ্নটি বুঝতে পারছি। রিসাইক্লিং, আপনার পয়েন্ট বা পিকআপ শিডিউল নিয়ে আমি আপনাকে সাহায্য করতে পারি। ♻️";
    }
    return "I understand your request. I can assist you with recycling info, checking your points, or scheduling a pickup. ♻️";
}
