<?php
function detectFallbackLanguage(string $message): string {
    if (preg_match('/\p{Bengali}/u', $message)) return 'bn';
    $banglishKeywords = ['ki', 'obostha', 'kemon', 'acho', 'ami', 'tumi', 'khobor',
        'valobashi', 'dhaka', 'bangla', 'bhalo', 'ace', 'naki', 'jani', 'janina',
        'bolo', 'dite', 'nibo', 'korte', 'kivabe', 'lomba', 'bujina', 'bolben'];
    $words = preg_split('/\s+/', mb_strtolower(trim($message)));
    $matches = 0;
    foreach ($words as $w) {
        $w = preg_replace('/[^a-z]/', '', $w);
        if (in_array($w, $banglishKeywords, true)) $matches++;
    }
    if ($matches >= 2 || $matches >= count($words) * 0.4) return 'bn';
    return 'en';
}

function getLocalFallbackResponse(string $message, bool $isBengali, array $user): string {
    $name  = htmlspecialchars($user['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
    $pts   = (int)($user['points'] ?? 0);
    $lower = mb_strtolower(trim($message));

    // Language
    $lang = $isBengali ? 'bn' : detectFallbackLanguage($message);

    // ─── Scoring engine ──────────────────────────────────────────────────────
    $scores = [
        'greeting'       => 0,
        'identity'       => 0,
        'points'         => 0,
        'impact'         => 0,
        'schedule'       => 0,
        'pickup_status'  => 0,
        'guide'          => 0,
        'materials'      => 0,
        'farewell'       => 0,
        'thanks'         => 0,
        'contact'        => 0,
        'complaint'      => 0,
        'hours'          => 0,
        'location'       => 0,
        'help'           => 0,
    ];

    // ─── Intent pattern definitions ──────────────────────────────────────────
    // Each group: [pattern, score, flag]
    // flag 'w' = word boundary, 's' = substring, 'r' = regex, 'p' = prefix

    // Greetings
    $enGreetingPatterns = [
        '/\b(hi|hello|hey|howdy|good morning|good evening|good afternoon)\b/i',
        '/\b(wassup|sup|whats up|how are you|how do you do)\b/i',
    ];
    foreach ($enGreetingPatterns as $pat) {
        if (preg_match($pat, $message)) $scores['greeting'] += 5;
    }
    $bnGreetingWords = ['হ্যালো', 'হাই', 'হেলো', 'হ্যাল', 'ওহে', 'নমস্কার', 'সালাম', 'আদাব'];
    foreach ($bnGreetingWords as $w) { if (mb_stripos($lower, $w) !== false) $scores['greeting'] += 5; }

    // Single-word greetings
    $word = trim(preg_replace('/[^a-zA-Z\p{Bengali}]/u', '', $lower));
    if (in_array($word, ['hi', 'hello', 'hey', 'হাই', 'হ্যালো', 'হেলো', 'সালাম'], true)) $scores['greeting'] += 10;

    // Identity
    $idPatterns = ['/your name/i', '/who are you/i', '/what are you/i',
        '/tell me about yourself/i', '/who am i/i', '/my name/i',
        '/tumi ke/i', '/tomar name/i', '/ke tumi/i', '/amar name/i', '/name ki/i',
        '/আমাকে চেনো/i', '/তোমার নাম/i', '/কে তুমি/i', '/আমার নাম/i', '/তুমি কে/i'];
    foreach ($idPatterns as $p) { if (preg_match($p, $message)) $scores['identity'] += 8; }

    // Points
    $ptPatterns = [
        '/\b(point|points|pts)\b/i', '/\b(balance|reward|rewards)\b/i',
        '/\b(how many|earn|earned|scored)\b/i',
        '/\bপয়েন্ট\b/u', '/\bপয়েন্ট\b/u', '/\bব্যালেন্স\b/u', '/\bরিওয়ার্ড\b/u',
        '/\bকত পয়েন্ট\b/u', '/\bআছে\b.*\bপয়েন্ট\b/u', '/\bপয়েন্ট\b.*\bআছে\b/u',
        '/\bদেখাও\b.*\bপয়েন্ট\b/u', '/\bscore\b/i',
    ];
    foreach ($ptPatterns as $p) { if (preg_match($p, $message)) $scores['points'] += 5; }

    // Impact
    $imPatterns = [
        '/\b(impact|stats|statistics|environment|climate)\b/i',
        '/\b(co2|carbon|footprint|savings?|saved)\b/i',
        '/\b(water|energy|kwh|litter|kg)\b/i',
        '/\bপরিবেশ\b/u', '/\bইমপ্যাক্ট\b/u', '/\bস্ট্যাট\b/u', '/\bজলবায়ু\b/u',
        '/\bকার্বন\b/u', '/\bco2\b/i',
    ];
    foreach ($imPatterns as $p) { if (preg_match($p, $message)) $scores['impact'] += 4; }

    // Schedule a pickup
    $scPatterns = [
        '/\b(schedule|scheduling|book|arrange|set up|fix|create|new)\b.*\b(pickup|collection)\b/i',
        '/\b(pickup|collection)\b.*\b(schedule|book|arrange|need|want|request)\b/i',
        '/\bpick.?up\b/i',
        '/\bআমার\b.*\bপিকআপ\b/u', '/\bপিকআপ\b.*\bশিডিউল\b/u',
        '/\bবুক\b/u', '/\bশিডিউল\b/u', '/\bপাঠান\b/u', '/\bআসো\b/u',
        '/\bধরি\b.*\bনেও\b/u', '/\bনিয়ে\b.*\bযাও\b/u',
        '/\bকাল\b/u', '/\bআজ\b/u', '/\bআগামীকাল\b/u',
    ];
    foreach ($scPatterns as $p) { if (preg_match($p, $message)) $scores['schedule'] += 5; }

    // Pickup status
    $psPatterns = [
        '/\b(pickup|collection|request|schedule)\b.*\b(status|history|track|detail|info)\b/i',
        '/\b(status|history|track|detail|info)\b.*\b(pickup|collection|request)\b/i',
        '/\bwhere is\b.*\b(pickup|agent|truck)\b/i',
        '/\bmy\b.*\b(pickup|collection|schedule)\b/i',
        '/\bসাম্প্রতিক\b/u', '/\bপিকআপ\b.*\bস্ট্যাটাস\b/u',
        '/\bকোথায়\b/u', '/\bআমার\b.*\bপিকআপ\b/u',
    ];
    foreach ($psPatterns as $p) { if (preg_match($p, $message)) $scores['pickup_status'] += 5; }

    // Recycling guide
    $gdPatterns = [
        '/\b(guide|guideline|tutorial|how to)\b.*\b(recycl|sort|separate)\b/i',
        '/\b(recycl|recycle|recycling)\b.*\b(guide|help|tip|advice|how)\b/i',
        '/\bরিসাইক্লিং\b.*\bগাইড\b/u', '/\bকিভাবে\b.*\bরিসাইক্ল\b/u',
        '/\bগাইড\b/u', '/\bশিখান\b/u', '/\bবুঝিয়ে\b/u',
    ];
    foreach ($gdPatterns as $p) { if (preg_match($p, $message)) $scores['guide'] += 5; }
    if (in_array($word, ['guide', 'রিসাইক্লিং', 'গাইড', 'সাহায্য'], true)) $scores['guide'] += 8;

    // Accepted materials
    $mtPatterns = [
        '/\b(what|which|accepted|accept|take|collect)\b/i',
        '/\b(material|item|category|categories|type|kind|product|trash|waste)\b/i',
        '/\b(plastic|paper|metal|glass|ewaste|e.?waste|organic|electronic)\b/i',
        '/\bগ্রহণ\b/u', '/\bনেবে\b/u', '/\bনেয়\b/u', '/\bকোন\b.*\bজিনিস\b/u',
        '/\bপ্লাস্টিক\b/u', '/\bকাগজ\b/u', '/\bধাতু\b/u', '/\bকাঁচ\b/u',
    ];
    foreach ($mtPatterns as $p) { if (preg_match($p, $message)) $scores['materials'] += 3; }
    if (preg_match('/\b(glass|e.?waste|organic|ewaste|electronics|clothes|textile|battery|batteries|carton)\b/i', $message)) $scores['materials'] += 2;
    if (preg_match('/\b(not\s+accepted|what\s+you\s+don|unsupported)\b/i', $message)) $scores['materials'] += 6;

    // Farewell
    $fwPatterns = [
        '/\b(bye|goodbye|see you|see ya|talk later|take care|g2g)\b/i',
        '/\b(exit|quit|leave|end|done|finish)\b/i',
        '/\bবিদায়\b/u', '/\bআসি\b/u', '/\bযাই\b/u', '/\bপরে দেখা\b/u',
    ];
    foreach ($fwPatterns as $p) { if (preg_match($p, $message)) $scores['farewell'] += 8; }

    // Thanks
    $tkPatterns = [
        '/\b(thanks|thank you|thankyou|thx|ty|appreciate|grateful)\b/i',
        '/\bধন্যবাদ\b/u',
    ];
    foreach ($tkPatterns as $p) { if (preg_match($p, $message)) $scores['thanks'] += 8; }

    // Contact / support
    $ctPatterns = [
        '/\b(contact|support|help desk|customer care|phone|call|email|whatsapp|hotline)\b/i',
        '/\b(agent|human|real person|talk to|speak to)\b/i',
        '/\bযোগাযোগ\b/u', '/\bকল\b/u', '/\bফোন\b/u', '/\bসাপোর্ট\b/u',
    ];
    foreach ($ctPatterns as $p) { if (preg_match($p, $message)) $scores['contact'] += 6; }

    // Complaint / problem
    $cpPatterns = [
        '/\b(problem|issue|complaint|not working|error|bug|glitch)\b/i',
        '/\b(angry|frustrated|upset|annoyed|dissatisfied|unhappy)\b/i',
        '/\bসমস্যা\b/u', '/\bঅভিযোগ\b/u', '/\bঠিক\b.*\bনেই\b/u',
        '/\bহচ্ছে\b.*\bনা\b/u', '/\bহয়\b.*\bনা\b/u',
    ];
    foreach ($cpPatterns as $p) { if (preg_match($p, $message)) $scores['complaint'] += 6; }

    // Hours
    $hrPatterns = [
        '/\b(hours?|open|timing|time|schedule|working|office)\b/i',
        '/\b(operating|available|when|business)\b/i',
        '/\bসময়\b/u', '/\bখোলা\b/u', '/\bকখন\b/u',
    ];
    foreach ($hrPatterns as $p) { if (preg_match($p, $message)) $scores['hours'] += 4; }

    // Location / service area
    $lcPatterns = [
        '/\b(where|location|area|zone|region|neighbourhood|neighborhood|city|town)\b/i',
        '/\b(service|cover|serve|operate|available)\b.*\b(area|location|zone|city)\b/i',
        '/\bকোথায়\b/u', '/\bএলাকা\b/u', '/\bজোন\b/u', '/\bশহর\b/u',
        '/\bservice\b.*\b(dhaka|chittagong|sylhet|khulna|barisal|rajshahi|rangpur|mymensingh)\b/i',
    ];
    foreach ($lcPatterns as $p) { if (preg_match($p, $message)) $scores['location'] += 5; }

    // Help / what can you do
    $hlPatterns = [
        '/\b(what can you do|help me|what do you do|capabilities|features|your purpose)\b/i',
        '/\b(tell me|show me|how can you|what help|possible)\b/i',
        '/\bকি করতে পারো\b/u', '/\bকী করতে পারেন\b/u', '/\bসাহায্য\b/u',
    ];
    foreach ($hlPatterns as $p) { if (preg_match($p, $message)) $scores['help'] += 5; }
    if (in_array($word, ['help', 'সাহায্য', 'menu', 'option'], true)) $scores['help'] += 8;

    // ─── Bias short queries toward relevant intents ──────────────────────────
    $wordCount = count(preg_split('/\s+/', $message));
    if ($wordCount <= 2) {
        $scores['greeting'] *= 1.5;
        $scores['help'] *= 1.3;
    }

    // ─── Find winning intent ──────────────────────────────────────────────────
    arsort($scores);
    $topIntent = array_key_first($scores);
    $topScore  = $scores[$topIntent];

    // If confidence is too low, use generic
    if ($topScore < 4) {
        $topIntent = 'generic';
    }

    // ─── Generate response ────────────────────────────────────────────────────
    switch ($topIntent) {

        case 'greeting':
            if ($lang === 'bn') {
                $greetings = [
                    "হ্যালো $name! 😊 ভালো আছি। আপনাকে আজ কীভাবে সাহায্য করতে পারি?",
                    "নমস্কার $name! 😊 আপনার দিন শুभ হোক। আজ আমি কী করতে পারি?",
                    "হাই $name! স্বাগতম। আপনার জন্য কিছু করতে পারি? 😊",
                ];
            } else {
                $greetings = [
                    "Hello $name! 😊 I'm doing great. How can I help you today?",
                    "Hey $name! Welcome back. What can I do for you? 😊",
                    "Hi there $name! Ready to help with your recycling journey. 🌿",
                ];
            }
            return $greetings[array_rand($greetings)];

        case 'identity':
            if ($lang === 'bn') {
                return "আমি Notun Alo (নতুন আলো)। আমি আপনাকে **$name** হিসেবে চিনি এবং আপনার বর্তমানে **$pts পয়েন্ট** আছে। 😊";
            }
            return "I am Notun Alo. I know you as **$name** and you currently have **$pts points**. 😊";

        case 'points':
            if ($lang === 'bn') {
                return "🏆 আপনার বর্তমান পয়েন্ট: **$pts pts**।\nকাগজ (১৫ pts/kg), প্লাস্টিক (২০ pts/kg), এবং ধাতু (৩০ pts/kg) রিসাইকেল করে আপনি আরও পয়েন্ট পেতে পারেন।";
            }
            return "🏆 Your current points: **$pts pts**.\nYou can earn more by recycling Paper (15 pts/kg), Plastic (20 pts/kg), or Metal (30 pts/kg). 😊";

        case 'impact':
            if ($lang === 'bn') {
                return "আপনার রিসাইক্লিং কার্যক্রম আমাদের পরিবেশ বাঁচাতে সাহায্য করছে। 🌍\n\nড্যাশবোর্ডে গিয়ে আপনি দেখতে পারেন:\n• মোট রিসাইক্লড (kg)\n• CO₂ সাশ্রয় (kg)\n• পানি সাশ্রয় (L)\n• বিদ্যুৎ সাশ্রয় (kWh)\n\nবিস্তারিত জানতে আপনার Dashboard চেক করুন। ♻️";
            }
            return "Your recycling efforts are helping save the environment! 🌍\n\nCheck your Dashboard for:\n• Total recycled (kg)\n• CO₂ saved (kg)\n• Water saved (L)\n• Energy saved (kWh)\n\nView detailed impact stats on your Dashboard. ♻️";

        case 'schedule':
            if ($lang === 'bn') {
                return "আমি আপনার জন্য একটি pickup schedule করতে পারি! 😊\n\nআমাকে শুধু বলুন:\n• কোন ক্যাটাগরি (কাগজ, প্লাস্টিক, নাকি ধাতু)?\n• আনুমানিক ওজন কত kg?\n• কবে নিতে আসব?\n\nএকবারে একটি তথ্য দিন, আমি গাইড করছি।";
            }
            return "I can help schedule a pickup! 😊\n\nJust tell me:\n• Which category (Paper, Plastic, or Metal)?\n• Approximate weight in kg?\n• What date should we come?\n\nOne piece at a time — I'll guide you through it.";

        case 'pickup_status':
            if ($lang === 'bn') {
                return "আপনার সাম্প্রতিক pickup তথ্য আমার ডাটাবেসে আছে। বিস্তারিত জানতে আপনার Dashboard-এর 'Pickup History' সেকশন দেখুন, অথবা আমাকে নির্দিষ্ট pickup ID বলুন।";
            }
            return "I can look up your recent pickups. For full details, visit your Dashboard → 'Pickup History', or tell me a specific Pickup ID to check.";

        case 'guide':
            if ($lang === 'bn') {
                return "♻️ **রিসাইক্লিং গাইড:**\n\nআমরা ৩টি প্রধান ক্যাটাগরি গ্রহণ করি:\n• 📄 **কাগজ** (১৫ pts/kg)\n• 🧴 **প্লাস্টিক** (২০ pts/kg)\n• 🔩 **ধাতু** (৩০ pts/kg)\n\n**টিপস:**\n• কাগজ পরিষ্কার ও শুকনো রাখুন\n• প্লাস্টিকের বোতল ধুয়ে নিন\n• ধাতব ক্যান চ্যাপ্টা করে রাখুন\n\nপিকআপ শিডিউল করতে আমার জানান!";
            }
            return "♻️ **Recycling Guide:**\n\nWe accept 3 main categories:\n• 📄 **Paper** (15 pts/kg)\n• 🧴 **Plastic** (20 pts/kg)\n• 🔩 **Metal** (30 pts/kg)\n\n**Tips:**\n• Keep paper clean and dry\n• Rinse plastic bottles\n• Flatten metal cans\n\nLet me know to schedule a pickup!";

        case 'materials':
            if ($lang === 'bn') {
                return "আমরা বর্তমানে **৩টি ক্যাটাগরি** গ্রহণ করি:\n\n✅ **গ্রহণযোগ্য:**\n• 📄 কাগজ (১৫ pts/kg)\n• 🧴 প্লাস্টিক (২০ pts/kg)\n• 🔩 ধাতু (৩০ pts/kg)\n\n❌ **গ্রহণযোগ্য নয়:** কাঁচ, ইলেকট্রনিক বর্জ্য, জৈব বর্জ্য, ব্যাটারি, কাপড়\n\nভবিষ্যতে আরও ক্যাটাগরি যোগ হওয়ার সম্ভাবনা আছে! ♻️";
            }
            return "We currently accept **3 categories**:\n\n✅ **Accepted:**\n• 📄 Paper (15 pts/kg)\n• 🧴 Plastic (20 pts/kg)\n• 🔩 Metal (30 pts/kg)\n\n❌ **Not accepted:** Glass, E-waste, Organic waste, Batteries, Textiles\n\nMore categories may be added in the future! ♻️";

        case 'farewell':
            if ($lang === 'bn') {
                return "বিদায় $name! 😊 আপনার দিন শুभ হোক। আবার কথা হবে! ♻️";
            }
            return "Goodbye $name! 😊 Have a great day. Happy recycling! ♻️";

        case 'thanks':
            if ($lang === 'bn') {
                return "আপনাকেও ধন্যবাদ $name! 😊 আপনার রিসাইক্লিং উদ্যোগ আমাদের পৃথিবী বাঁচাতে সাহায্য করছে। সবসময় পাশে আছি! ♻️";
            }
            return "You're welcome $name! 😊 Your recycling efforts make a real difference. Happy to help anytime! ♻️";

        case 'contact':
            if ($lang === 'bn') {
                return "আপনি আমাদের সাথে যোগাযোগ করতে পারেন:\n\n📧 ইমেইল: support@notunalo.com\n📞 ফোন: +৮৮০১২৩৪৫৬৭৮৯০\n💬 WhatsApp: +৮৮০১২৩৪৫৬৭৮৯০\n\nঅথবা আপনার Dashboard থেকে Support টিকেট তৈরি করুন। 😊";
            }
            return "You can reach us at:\n\n📧 Email: support@notunalo.com\n📞 Phone: +880-1234-567890\n💬 WhatsApp: +880-1234-567890\n\nOr raise a support ticket from your Dashboard. 😊";

        case 'complaint':
            if ($lang === 'bn') {
                return "আমি আপনার সমস্যা বুঝতে পারছি এবং দুঃখিত। 🙏\n\nআমরা যত দ্রুত সম্ভব সমাধান করতে চাই। অনুগ্রহ করে:\n• আপনার Dashboard > Support Ticket এ বিস্তারিত জানান\n• অথবা support@notunalo.com এ ইমেইল করুন\n• আপনার pickup ID/NID নম্বর উল্লেখ করুন\n\nআমাদের টিম ASAP যোগাযোগ করবে।";
            }
            return "I understand you're facing an issue and I'm sorry about that. 🙏\n\nPlease:\n• Submit details via Dashboard > Support Ticket\n• Or email support@notunalo.com\n• Include your pickup ID or registered info\n\nOur team will follow up ASAP.";

        case 'hours':
            if ($lang === 'bn') {
                return "⏰ **আমাদের কার্যক্রম:**\n\n• শনি-বৃহস্পতি: সকাল ৯টা - রাত ৮টা\n• শুক্\u200dরবার: বন্ধ\n• পিকআপ সার্ভিস: প্রতিদিন সকাল ৮টা - বিকাল ৫টা\n\nছুটির দিনে পিকআপ শিডিউল করা যাবে না।";
            }
            return "⏰ **Operating Hours:**\n\n• Sat-Thu: 9 AM - 8 PM\n• Friday: Closed\n• Pickup service: Daily 8 AM - 5 PM\n\nPickups cannot be scheduled on holidays.";

        case 'location':
            if ($lang === 'bn') {
                return "📍 **সার্ভিস এলাকা:**\n\nআমরা বর্তমানে বাংলাদেশের সকল প্রধান শহরে সার্ভিস দিচ্ছি:\n• ঢাকা\n• চট্টগ্রাম\n• সিলেট\n• খুলনা\n• বরিশাল\n• রাজশাহী\n• রংপুর\n• ময়মনসিংহ\n\nআপনার এলাকায় সার্ভিস আছে কিনা নিশ্চিত হতে বলুন!";
            }
            return "📍 **Service Areas:**\n\nWe currently operate in all major cities of Bangladesh:\n• Dhaka\n• Chittagong\n• Sylhet\n• Khulna\n• Barisal\n• Rajshahi\n• Rangpur\n• Mymensingh\n\nLet me know your area to confirm service availability!";

        case 'help':
            if ($lang === 'bn') {
                return "🤖 **আমি যা করতে পারি:**\n\n• ✅ পয়েন্ট চেক করা\n• ✅ পিকআপ শিডিউল করা\n• ✅ রিসাইক্লিং গাইড\n• ✅ কোন জিনিস গ্রহণযোগ্য তা বলা\n• ✅ ইমপ্যাক্ট স্ট্যাটাস\n• ✅ পিকআপ স্ট্যাটাস\n• ✅ যোগাযোগ তথ্য\n\nআপনার প্রশ্ন জিজ্ঞাসা করুন! 😊";
            }
            return "🤖 **Here's what I can do:**\n\n• ✅ Check your points\n• ✅ Schedule a pickup\n• ✅ Recycling guide & tips\n• ✅ Accepted materials\n• ✅ Impact stats\n• ✅ Pickup status\n• ✅ Contact info\n\nAsk me anything! 😊";

        default:
            if ($lang === 'bn') {
                return "আমি আপনার প্রশ্নটি পুরোপুরি বুঝতে পারছি। 😊\n\nআমি সাহায্য করতে পারি:\n• পয়েন্ট চেক করা\n• পিকআপ শিডিউল করা\n• রিসাইক্লিং গাইড\n\nকি বিষয়ে জানতে চান? ♻️";
            }
            return "I'm not entirely sure I understood that. 😊\n\nI can help you with:\n• Checking points\n• Scheduling a pickup\n• Recycling guide\n\nWhat would you like to know? ♻️";
    }
}
