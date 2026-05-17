# Chatbot System

> **Notun Alo (নতুন আলো)** — Smart Recycling Platform  
> Document version: 1.0 | Last updated: May 2026

---

## 1. Architecture Overview

The chatbot system is a hybrid AI assistant combining rule-based triggers, a state machine, remote AI (Pollinations.ai), and an optional RAG (Retrieval-Augmented Generation) service. It supports bilingual interaction (English and Bengali/Banglish) and provides context-aware suggestion chips.

**Core Files:**
| File | Purpose |
|---|---|
| `chatbot.php` | Frontend chat interface (sidebar layout) |
| `chatbot_api.php` | AJAX endpoint — message processing pipeline |
| `includes/chatbot_context.php` | System prompt builder for Pollinations.ai |
| `includes/chatbot_fallback.php` | Rule-based fallback response engine |
| `includes/chatbot_state.php` | State machine + circuit breaker logic |

**Database Tables:**
| Table | Purpose |
|---|---|
| `chat_messages` | Stores all user/assistant conversation turns |
| `chatbot_cache` | MD5-keyed response cache (5-minute TTL) |
| `chatbot_circuit` | Singleton row tracking Pollinations.ai failures |
| `chatbot_states` | Per-user/session multi-turn state data |

---

## 2. Complete Message Lifecycle

When a user sends a message, `chatbot_api.php` processes it through this pipeline:

### Step 1: Authentication Guard
```
requireLoginJson()
```
- Verifies active session with `$_SESSION['user_id']`
- Returns 401 JSON with `{"reply": "Please log in again."}` if unauthenticated

### Step 2: Database Initialisation
```
ensureChatbotTables($pdo)
ensureChatbotStateTables($pdo)
```
- Creates `chatbot_cache`, `chat_messages`, `chatbot_circuit`, and `chatbot_states` tables if they don't exist

### Step 3: Input Parsing
```php
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$userMessage = trim((string)($input['message']));
$clientHistory = $input['history'] ?? null;
$sessionId = $input['session_id'] ?? 'main';
```
- Accepts JSON body with `message`, `session_id`, `lang`, and optional `history`
- Returns 400 for missing/invalid message field

### Step 4: User Data Loading
```php
$user = getCurrentUser($pdo);
$user['points'] = getUserPoints($pdo, $userId);
```
- Fetches user record and current reward points

### Step 5: Circuit Breaker Check
```
circuitBreakerIsOpen($pdo)
```
- Checks `chatbot_circuit` table for 3+ consecutive failures with `opened_at < 5 minutes`
- If open, skips Pollinations.ai call entirely (saves 30s timeout)
- All users get instant fallback responses

### Step 6: State Machine Check
```
chatStateHandleFlow($pdo, $userId, $sessionId, $userMessage, $isBengali, $stateReply, $stateAction)
```
- If user has an active scheduling flow, processes the multi-turn conversation
- Supports: awaiting_category → awaiting_weight → awaiting_date → confirming
- If flow handled, responds immediately with `source: 'state_machine'`

### Step 7: Cache Check
```
chatbotCacheGet($pdo, $userMessage, $lang)
```
- Computes MD5 hash of `strtolower($query) . '|' . $lang`
- Checks for cache entry with `created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)`
- **Skipped for user-specific queries** (messages containing: `point`, `points`, `my`, `আমার`, `ব্যালেন্স`, `impact`, `স্ট্যাটাস`)
- If hit: saves messages to session history, returns cached response with `source: 'cache'`

### Step 8: Direct Triggers
Four hardcoded intent handlers run before any AI call:

| Trigger | Pattern | Response |
|---|---|---|
| `Check Points` | `/^(check points\|my points\|points balance\|...)$/i` | Returns current points + earning info (`source: 'direct_points'`) |
| `Recycling Guide` | `/^(recycling guide\|how to recycle\|guide\|...)$/i` | Returns accepted categories + rates (`source: 'direct_guide'`) |
| `Impact Stats` | `/my impact\|impact stats\|.../i` | Queries Flask impact service on `:5003` (`source: 'impact_lookup'`) |
| `Pickup Lookup` | `/schedule\|request\|status\|.../i` | Fetches last 5 pickups from DB (`source: 'pickup_lookup'`) |

### Step 9: RAG Service Call (Optional)
```
callRagAssistant($message, $lang, $user)
```
- Only runs when `RAG_ENABLED=true` in `.env`
- Health check: `GET /health` on RAG API URL (default `http://localhost:5000`)
- Request: `POST /chat` with `{ query, language, user_name, user_points }`
- Validates response contains non-empty `answer` with `sources`
- Unknown answers filtered (natural "I couldn't find specific details..." responses dropped)
- Returns with `source: 'rag'` and sources array

### Step 10: Session Memory Load
```php
$history = $clientHistory !== null ? $clientHistory : chatMessageLoadHistory($pdo, $userId, $sessionId, 6);
```
- Loads last 6 messages from `chat_messages` table (configurable to 8 in function signature)
- Client-provided history takes precedence over server-side
- Messages are reversed to chronological order: `array_reverse(...)`

### Step 11: System Prompt Construction
```
getChatbotSystemPrompt($user, $lang)
```
Built from `includes/chatbot_context.php`, includes:
- **Security block:** Instruction not to reveal system prompt
- **User context:** Name and current points
- **Date context:** Today's date and minimum pickup date (tomorrow)
- **Category rates:** Paper (5), Plastic (8), Metal (12) pts/kg
- **Language rules:** Detect and match user's language, no mixing
- **Knowledge base:** Accepted materials, points system
- **Scheduling behaviour:** Ask one question at a time, validate before JSON output
- **Personality:** Human-like, calm, supportive, max 1 emoji per message

### Step 12: Pollinations.ai Call
```php
curl -X POST https://text.pollinations.ai/openai
```
- **Model:** `llama-3.1-70b`
- **Temperature:** `0.7`
- **Timeout:** 30s request, 10s connection
- **Private:** `true` (no logging on Pollinations side)
- **Messages array:** System prompt + history + current user message
- **Skipped if circuit breaker is open**

### Step 13: Circuit Recording
```php
if ($aiText !== '') {
    circuitBreakerRecordSuccess($pdo);   // Reset failures to 0, close circuit
} else {
    circuitBreakerRecordFailure($pdo);   // Increment failures, open at 3
}
```
- Updates `chatbot_circuit` singleton row
- On success: `consecutive_failures = 0`, `opened_at = NULL`
- On failure: `consecutive_failures += 1`, `opened_at = NOW()` (if not already set)

### Step 14: Fallback Engine
```
getLocalFallbackResponse($message, $isBengali, $user)
```
- Activates when Pollinations.ai returns empty/fails or circuit is open
- **Scoring engine:** Evaluates 15 intents (greeting, identity, points, impact, schedule, pickup_status, guide, materials, farewell, thanks, contact, complaint, hours, location, help)
- Each intent has weighted pattern matches (regex + keyword)
- Short queries (≤2 words) get greeting/help bias
- Winning intent with score < 4 falls back to generic
- Returns context-aware responses with user's name and points

### Step 15: State Machine Activation
```
detectSchedulingIntent($message)
chatStateStartSchedule($pdo, $userId, $sessionId, ...)
```
- Runs if no active flow exists but scheduling intent is detected
- Patterns: `schedule|book|pickup|শিডিউল|বুক|পিকআপ` etc.
- Starts `awaiting_category` step with category prompt

### Step 16: Pickup JSON Extraction
```php
preg_match('/\{[^{}]*"action"\s*:\s*"schedule_pickup"[^{}]*\}/s', $aiText, $matches)
```
- Looks for structured JSON in AI response
- Validates: category (Paper/Plastic/Metal), weight (0.1–100 kg), date (tomorrow+)
- On valid JSON: inserts into `pickups` table, builds confirmation response with estimated points
- Returns `action: { type: 'pickup_scheduled', category, weight, date, points }`

### Step 17: Bilingual Disambiguation
```
if ($langConfidence < 0.6 && $wordCount >= 2) {
    // Appends language hint
}
```
- When language detection confidence is below 60%, appends a bilingual note
- Helps users switch languages mid-conversation
- Avoids duplicate notes (checks for existing hint)

### Step 18: Save + Respond
```
respondJson($pdo, $userId, $sessionId, $lang, $reply, $userMessage, $action, $source)
```
1. Saves user message and assistant response to `chat_messages`
2. Caches response if not user-specific and not from RAG/impact
3. Generates 3 suggestion chips via `generateSuggestions()`
4. Returns JSON: `{ reply, action, source, suggestions, session_id }`

---

## 3. History System

### 3.1 Client-Side (localStorage)
- **Storage key:** `notun_alo_chat_history_user_{userId}`
- **Managed by:** `chatbot.php` JavaScript
- **Cleared on:** Logout (`logout.php` iterates and removes all matching keys)
- **Purpose:** Provides instant history display without server round-trip

### 3.2 Server-Side (Database)
- **Table:** `chat_messages`
- **Schema:**
  - `id` — auto-increment primary key
  - `user_id` — foreign key to users table
  - `session_id` — conversation session (`'main'` by default)
  - `role` — `'user'`, `'assistant'`, or `'system'`
  - `content` — message text
  - `created_at` — timestamp
- **Index:** `(user_id, session_id, created_at)` for efficient history loading
- **Retrieval:** `chatMessageLoadHistory($pdo, $userId, $sessionId, $limit = 8)`
  - Loads last N messages in chronological order
  - Used for building the AI conversation context

### 3.3 Session Memory
- Server loads last 6 messages (configurable to 8) for AI context
- Client-provided `history` array overrides server load when provided
- Messages formatted as `{role, content}` array for OpenAI-compatible payload

---

## 4. Conversation State Machine

### 4.1 State Flow

```
idle → awaiting_category → awaiting_weight → awaiting_date → confirming → [pickup created]
  ↓         ↓                    ↓                  ↓               ↓
cancel    cancel               cancel             cancel          cancel/deny → idle
```

### 4.2 State Storage
- **Table:** `chatbot_states`
- **Schema:**
  - `user_id`, `session_id` — compound unique key
  - `step` — current flow step (`idle`, `awaiting_category`, etc.)
  - `data` — JSON object storing collected information
  - `updated_at` — auto-updates on state change

### 4.3 Step Details

**`awaiting_category`:**
- Accepts: `paper/কাগজ/kagoj`, `plastic/প্লাস্টিক/plastik`, `metal/ধাতু/dhatu/লোহা`
- Invalid input cycles back to category prompt
- Stores: `data['category']`

**`awaiting_weight`:**
- Accepts numeric values (regex: `/(\d+(?:\.\d+)?)/`)
- Range: 0.1–100 kg
- Invalid input cycles back to weight prompt
- Stores: `data['weight']`

**`awaiting_date`:**
- Accepts: `YYYY-MM-DD`, `tomorrow/আগামীকাল/কাল`, `day after tomorrow/পরশু`, `N days/দিন`
- Minimum date: tomorrow
- Invalid input cycles back to date prompt
- Stores: `data['date']`

**`confirming`:**
- Accepts: `yes/হ্যাঁ/হ্যা/ok/okay/confirm/নিশ্চিত`
- On confirmation: creates pickup record in `pickups` table, clears state
- On denial/cancel: clears state without creating pickup

### 4.4 Cancel Command
Any active flow can be cancelled at any step by saying:
`cancel`, `stop`, `exit`, `quit`, `বাতিল`, `থামুন`, `ছাড়ুন`

---

## 5. Circuit Breaker

### 5.1 Purpose
Prevents users from experiencing long timeouts when Pollinations.ai is unavailable. After 3 consecutive failures, all users get instant fallback responses for 5 minutes.

### 5.2 Mechanism
```php
function circuitBreakerIsOpen(PDO $pdo): bool {
    // Returns true if consecutive_failures >= 3 AND opened_at < 300 seconds ago
}
```

- **Table:** `chatbot_circuit` (singleton row, id=1)
- **Threshold:** 3 consecutive failures
- **Cooldown:** 5 minutes (300 seconds)
- **Reset:** Any successful response resets counter to 0

### 5.3 State Transitions
- **Success:** `consecutive_failures = 0`, `last_failure_at = NULL`, `opened_at = NULL`
- **Failure:** `consecutive_failures += 1`, `last_failure_at = NOW()`, `opened_at = NOW()` (if null)
- **Automatic recovery:** After 5 minutes, `circuitBreakerIsOpen()` returns false

---

## 6. Language Detection

### 6.1 `detectDominantLanguage()` (chatbot_api.php:144-192)
Scoring algorithm using three signals:

| Signal | Method | Weight |
|---|---|---|
| Bengali Unicode ratio | `\p{Bengali}` regex match vs total chars | ≥30% → strong Bengali |
| Banglish keyword density | 30+ predefined Banglish words | ≥30% of words → Bengali |
| Mixed script | Has both Bengali + English characters | ≥15% Banglish → Bengali |

**Banglish Keywords:** `ki, obostha, kemon, acho, ami, tumi, khobor, valobashi, dhaka, bangla, bhalo, ace, naki, jani, bolben, hobe, kono, dorkar, chai, jaabe, bujhlam` (30+ entries)

**Confidence Levels:**
- Bengali Unicode >30%: confidence 0.5–1.0
- Banglish ratio ≥30%: confidence 0.4–0.9
- Mixed + Banglish ≥15%: confidence 0.6
- Banglish score ≥2: confidence 0.7
- Default English: confidence 1.0

### 6.2 `detectFallbackLanguage()` (chatbot_fallback.php:2-15)
Simpler detection for fallback engine:
- Bengali Unicode present → 'bn'
- Banglish matches ≥2 OR ≥40% of words → 'bn'
- Otherwise → 'en'

### 6.3 Low-Confidence Bilingual Disambiguation
When confidence < 60% and message has ≥2 words, a bilingual hint is appended:
```text
(English: I detected Bangla/Banglish in your message. If you prefer English, just type in English. 😊)
```

---

## 7. Suggestion Chips

### 7.1 Generation (`generateSuggestions()`)
Returns 3 context-aware clickable chip labels based on response source:

| Source | English Chips | Bengali Chips |
|---|---|---|
| `direct_points`, `points` | Schedule Pickup, Recycling Guide, Impact Stats | পিকআপ শিডিউল, রিসাইক্লিং গাইড, ইমপ্যাক্ট স্ট্যাটাস |
| `direct_guide`, `guide` | Schedule Pickup, Check Points, Contact Support | পিকআপ শিডিউল, পয়েন্ট চেক, যোগাযোগ |
| `pickup_lookup`, `schedule` | Check Points, Recycling Guide, Help | পয়েন্ট চেক, রিসাইক্লিং গাইড, সাহায্য |
| Default | Check Points, Schedule Pickup, Recycling Guide | পয়েন্ট চেক, পিকআপ শিডিউল, রিসাইক্লিং গাইড |
- Pickup action triggers: Check Points, View Dashboard, Recycling Guide

### 7.2 Frontend Rendering
- Chips rendered as pill buttons (`<div class="chip">`)
- Clicking a chip calls `handleQuickReply(text)` which triggers `sendMessage(text)`
- Generic suggestion cards shown on empty state (Schedule Pickup, Check Points, Impact Stats, Recycling Guide)

---

## 8. Caching Architecture

### 8.1 Cache Key
```php
md5(mb_strtolower(trim($query)) . '|' . $lang)
```

### 8.2 TTL
5 minutes (checked via `created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)`)

### 8.3 Cache Skip Logic
Cache is bypassed for **user-specific queries** containing:
- `point`, `points`, `my`, `আমার`, `ব্যালেন্স`, `impact`, `স্ট্যাটাস`

### 8.4 Cache Storage
```sql
INSERT INTO chatbot_cache (cache_key, response_text, suggestions, lang) VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE response_text = VALUES(response_text), suggestions = VALUES(suggestions), created_at = NOW()
```

### 8.5 Cacheable Sources
- `direct_points`, `direct_guide`, `cache`, `fallback` — cached
- `impact_lookup`, `rag`, `state_machine` — NOT cached (user-specific)

---

## 9. Response Sources (Deterministic)

Every response includes a `source` field for debugging and analytics:

| Source | Origin | Cached |
|---|---|---|
| `cache` | Chatbot cache hit | — |
| `direct_points` | Hardcoded points trigger | Yes |
| `direct_guide` | Hardcoded guide trigger | Yes |
| `impact_lookup` | Flask impact API | No |
| `pickup_lookup` | Database query | No |
| `rag` | RAG Flask service | No |
| `state_machine` | Multi-turn state flow | No |
| `pollinations` | Pollinations.ai API | Yes |
| `fallback` | Local rule-based engine | Yes |

---

## 10. Error Handling

All catch blocks:
```php
catch (Throwable $e) {
    error_log('[Notun Alo Chatbot] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'reply'       => $isBengali ? 'দুঃখিত, একটি প্রযুক্তিগত সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।' : 'Sorry, something went wrong. Please try again.',
        'action'      => null,
        'suggestions' => null,
        'session_id'  => $sessionId,
    ]);
}
```
- Errors logged server-side, never exposed to users
- User-friendly generic error message returned in detected language
