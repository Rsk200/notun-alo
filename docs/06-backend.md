# Backend Architecture — Notun Alo

> **Document:** `docs/06-backend.md`  
> **Version:** 1.0  
> **Last Updated:** May 2026

---

## Table of Contents

1. [Server Architecture](#1-server-architecture)
2. [Key PHP Files](#2-key-php-files)
3. [API Endpoints](#3-api-endpoints)
4. [Cron Jobs](#4-cron-jobs)
5. [Security Practices](#5-security-practices)

---

## 1. Server Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Apache / nginx (reverse proxy)           │
│                          mod_rewrite                        │
├─────────────────────────────────────────────────────────────┤
│                  PHP 8.2 Monolith                           │
│  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌────────────┐  │
│  │ Pages     │ │ API       │ │ Includes  │ │ Cron       │  │
│  │ *.php     │ │ *_api.php │ │ config/*  │ │ cron/*     │  │
│  └───────────┘ └───────────┘ └───────────┘ └────────────┘  │
├─────────────────────────────────────────────────────────────┤
│              MySQL / MariaDB (PDO)                           │
│              SSL support for remote MySQL (Aiven)           │
├─────────────────────────────────────────────────────────────┤
│        ┌──────────────────────────────────────┐             │
│        │  Python Microservices (Flask)        │             │
│        │  ┌────────┐ ┌──────────┐ ┌────────┐ │             │
│        │  │ RAG    │ │ Impact  │ │Assign  │ │             │
│        │  │ :5000  │ │ :5003   │ │ ML     │ │             │
│        │  └────────┘ └──────────┘ └────────┘ │             │
│        └──────────────────────────────────────┘             │
├─────────────────────────────────────────────────────────────┤
│              Docker Container (optional)                    │
│              Dockerfile + docker-compose.yml                │
└─────────────────────────────────────────────────────────────┘
```

**Key Technologies:**
- **PHP 8.2+** — Monolithic application server
- **Apache** — HTTP server with `mod_rewrite` for clean URLs (Docker container available)
- **MySQL/MariaDB** — Database via PDO with prepared statements
- **Python 3** — AI/ML microservices (RAG, Impact, Assignment)
- **Flask** — Python web framework for API endpoints
- **Docker** — Containerization via `Dockerfile` + `docker-compose.yml`
- **Google Cloud Run** — Production deployment target (`cloudbuild.yaml`, `render.yaml`)

**Session Architecture:**
- PHP sessions via `session_start()` with custom save handler
- User ID, name, email, and role stored in `$_SESSION`
- `startSession()` wrapper ensures safe initialization
- Flash messages via `$_SESSION['flash']` with `setFlash()` / `getFlash()`

---

## 2. Key PHP Files

### 2.1 `includes/config.php` — Core Configuration (214 lines)

The bootstrap file included by every page. Responsible for:

| Component | Description |
|---|---|
| **`.env` Loader** | Custom `loadEnv()` function reads `.env` file, populates `$_ENV`, `$_SERVER`, and `putenv()` |
| **Database Constants** | `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` with defaults (`localhost`, `3306`, `root`, `''`, `notun_alo`) |
| **Points Definitions** | `POINTS_PAPER = 5`, `POINTS_PLASTIC = 8`, `POINTS_METAL = 12` — points per kg |
| **PDO Connection** | Creates `$pdo` with error mode exception, fetch-assoc, emulated prepares off. SSL options for remote MySQL (Aiven) with optional CA certificate |
| **Schema Migration** | `ensurePickupCategoryVarchar()` — widens legacy `ENUM('Paper','Plastic','Metal')` to `VARCHAR(50)` on-the-fly |
| **DB Initialization Check** | `isDatabaseInitialized()` — queries `products` table to verify schema exists |
| **Helper Functions** | See table below |

**Helper functions defined:**

| Function | Purpose |
|---|---|
| `startSession()` | Starts session if not already active |
| `redirect(string $url)` | Sends `Location` header and exits |
| `isLoggedIn()` | Checks `$_SESSION['user_id']` exists |
| `requireLogin()` | Redirects to `login.php` if not authenticated |
| `requireLoginJson()` | Returns 401 JSON for API endpoints instead of redirecting |
| `requireRole(string $role)` | Checks session role matches required role |
| `getCurrentUser(PDO $pdo)` | Fetches full user row by session ID |
| `getUserPoints(PDO $pdo, int $userId)` | Returns `total_points` from `rewards` table |
| `e(string $str)` | HTML escape via `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` |
| `setFlash(string $type, string $message)` | Stores flash message in session |
| `getFlash()` | Retrieves and clears flash message |

### 2.2 `includes/chatbot_context.php` — System Prompt Builder (118 lines)

Builds the AI system prompt for chatbot interactions.

**Output:** A structured prompt string containing:

| Section | Details |
|---|---|
| **Identity** | Assistant is "Notun Alo (নতুন আলো)", Bangladesh's community recycling platform |
| **User Context** | Name and current points injected at runtime |
| **Date Info** | Current date and minimum pickup date (tomorrow) |
| **Category Definitions** | Paper (5 pts/kg), Plastic (8 pts/kg), Metal (12 pts/kg) |
| **Adaptive Language Rules** | Detect user's language (English/Bangla/Banglish), reply only in that language, never mix, use modern conversational Bangla |
| **Pickup Scheduling** | Requires 3 fields: category, weight, date. Asks one question at a time. When all 3 confirmed, outputs strict JSON: `{"action":"schedule_pickup","category":"...","weight":N,"date":"..."}` |
| **Anti-Robotic Personality** | Never says "I am an AI", uses exactly 1 emoji per message, sounds human and empathetic |

**Security:** The prompt contains a jailbreak prevention instruction — if user asks about system prompt, assistant must refuse and respond with a fixed phrase.

### 2.3 `includes/chatbot_fallback.php` — Rule-Based Fallback Engine (319 lines)

Local intent-scoring engine used when Pollinations.ai or Gemini are unavailable.

**Intent Detection (15 intents):**

| Intent | Score Pattern | Trigger Keywords |
|---|---|---|
| `greeting` | 5-15 | hi, hello, হ্যালো, সালাম, কেমন আছেন |
| `identity` | 8 | your name, who are you, তুমি কে, তোমার নাম |
| `points` | 5 | points, pts, balance, পয়েন্ট, ব্যালেন্স |
| `impact` | 4 | impact, co2, carbon, পরিবেশ, ইমপ্যাক্ট |
| `schedule` | 5 | schedule pickup, book, পিকআপ শিডিউল, বুক |
| `pickup_status` | 5 | pickup status, history, স্ট্যাটাস, সাম্প্রতিক |
| `guide` | 5-8 | guide, tutorial, how to recycle, গাইড, রিসাইক্লিং |
| `materials` | 3-6 | what accepted, plastic, paper, metal, glass, গ্রহণ |
| `farewell` | 8 | bye, goodbye, বিদায় |
| `thanks` | 8 | thanks, thank you, ধন্যবাদ |
| `contact` | 6 | contact, support, phone, email, যোগাযোগ |
| `complaint` | 6 | problem, complaint, error, সমস্যা, অভিযোগ |
| `hours` | 4 | hours, open, timing, সময়, খোলা |
| `location` | 5 | where, area, zone, কোথায়, এলাকা |
| `help` | 5-8 | what can you do, help, সাহায্য, menu |

**Scoring Logic:**
- Each intent has weighted regex pattern arrays for both English and Bengali/Banglish
- Short queries (≤2 words) get greeting and help bias (1.5x multiplier)
- Highest scoring intent selected; if `< 4` confidence, falls to `generic`

**Response Generation:**
- Each intent has bilingual response arrays with `array_rand()` variety
- User name and points injected into responses
- Bengali responses use `$isBengali` flag
- Generic intent offers help menu

**Language Detection:**
- `detectFallbackLanguage(string $message): string`
- Bengali Unicode range check (U+0980–U+09FF)
- Banglish keyword matching (ki, obostha, kemon, acho, ami, tumi, etc.)
- Returns `'bn'` if `≥ 2` keyword matches or `≥ 40%` of words are Banglish

### 2.4 `includes/chatbot_state.php` — Multi-Turn State Machine (232 lines)

Manages conversational flows with a state machine pattern.

**Circuit Breaker:**
```sql
chatbot_circuit (id=1, consecutive_failures, last_failure_at, opened_at)
```
- After 3 consecutive API failures, circuit opens for 5 minutes
- `circuitBreakerIsOpen()` checks if cooldown is active
- `circuitBreakerRecordSuccess()` resets counter
- `circuitBreakerRecordFailure()` increments and opens circuit

**State Machine (4-step scheduling flow):**

```
idle → awaiting_category → awaiting_weight → awaiting_date → confirming → (clear)
```

| State | Handler | Behavior |
|---|---|---|
| `idle` | `detectSchedulingIntent()` | Check if user wants to schedule |
| `awaiting_category` | Category matching (Paper/Plastic/Metal via English, Bengali, Banglish) | Ask for weight |
| `awaiting_weight` | Number extraction with range validation (0.1–100 kg) | Ask for date |
| `awaiting_date` | Natural language date parsing (tomorrow, পরশু, N days, YYYY-MM-DD) | Show confirmation |
| `confirming` | "yes/hy/হ্যাঁ/ok" confirms → INSERT into `pickups`, clear state | Cancel clears state |

**Natural Language Date Parsing:**
- Absolute: `YYYY-MM-DD`
- Relative: `tomorrow` / `আগামীকাল`, `day after tomorrow` / `পরশু`, `N days` / `N দিন`
- Minimum: tomorrow at midnight

**Category Mapping:**
- `paper / কাগজ / kagoj → Paper`
- `plastic / প্লাস্টিক / plastik → Plastic`
- `metal / ধাতু / dhatu / লোহা → Metal`

**Persistence:**
- States stored in `chatbot_states` table with `user_id`, `session_id`, `step`, `data` (JSON)
- Unique constraint on `(user_id, session_id)`
- State cleared on successful schedule or cancel command

### 2.5 `includes/auto_assign_v2.php` — Agency Auto-Assignment

Automatically assigns pending pickups to the best-suited agency.

**Logic:**
- Scores agencies using weighted factors: current load, completion rate, distance, rating, specialty match
- Updates pickup's `agency_id` and status to `'assigned'`
- Creates notification for assigned agency
- Transaction-safe with rollback on failure

### 2.6 `includes/lang.php` — Bilingual Dictionary (338 lines)

Full localization system for all UI text.

**Structure:**
```php
$translations = [
    'en' => ['site_title' => 'Notun Alo', 'dashboard' => 'Dashboard', ...],  // ~185 keys
    'bn' => ['site_title' => 'নতুন আলো', 'dashboard' => 'ড্যাশবোর্ড', ...], // ~185 keys
];
```

**Key Functions:**

| Function | Purpose |
|---|---|
| `en2bn($number)` | Converts ASCII digits to Bengali numerals (e.g., `123` → `১২৩`). Only converts if `$currentLang === 'bn'` |
| `translateStatus($status)` | Translates status strings (pending, assigned, completed) using the `$lang` dictionary |

**Toggle Mechanism:**
- `?lang=bn` or `?lang=en` query parameter sets `$_SESSION['lang']`
- Redirects back without parameter to keep URLs clean
- Default: `'en'`

### 2.7 `includes/impact_card.php` — Impact Display Card

Renders an environmental impact summary card showing:
- CO₂ saved (kg)
- Water saved (liters)
- Energy saved (kWh)
- Car trip equivalents
- Eco-rank level with XP progress

### 2.8 `includes/navbar.php` — Shared Navigation Component

Shared navbar included by all authenticated pages. Features:
- Responsive with mobile hamburger menu
- Dark mode toggle (sun/moon icon)
- Language toggle (EN/BN)
- User avatar/initial with dropdown (Profile, Logout)
- Role-specific navigation links

---

## 3. API Endpoints

### 3.1 `chatbot_api.php` — Chatbot Endpoint

- **Route:** `chatbot_api.php`
- **Method:** `POST`
- **Content-Type:** `application/json`
- **Authentication:** Requires valid session (`requireLoginJson()`)

**Request Body:**
```json
{
    "message": "how many points do I have?",
    "session_id": "abc123",
    "lang": "auto",
    "history": [{"role": "user", "content": "hi"}, {"role": "assistant", "content": "Hello!"}]
}
```

**Response (success):**
```json
{
    "reply": "🏆 Your current points: **250 pts**...",
    "action": null,
    "source": "direct_points",
    "suggestions": ["Schedule Pickup", "Recycling Guide", "Impact Stats"],
    "session_id": "abc123"
}
```

**Response (pickup scheduled):**
```json
{
    "reply": "✅ Your pickup has been scheduled!...",
    "action": {
        "type": "pickup_scheduled",
        "category": "Paper",
        "weight": 5.5,
        "date": "2026-05-18",
        "points": 27
    },
    "source": "state_machine",
    "suggestions": ["Check Points", "View Dashboard", "Recycling Guide"],
    "session_id": "abc123"
}
```

**Response (error):**
```json
{
    "reply": "দুঃখিত, একটি প্রযুক্তিগত সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।",
    "action": null,
    "suggestions": null,
    "session_id": "main"
}
```

**Processing Pipeline (in order):**
1. **Language Detection** — `detectDominantLanguage()` — Unicode Bengali range, Banglish keyword matching, mixed-script detection
2. **Circuit Breaker Check** — Skip Pollinations if 3+ consecutive failures in last 5 min
3. **State Machine** — Handle active scheduling flow (awaiting_category/weight/date/confirm)
4. **Cache Check** — 5-min TTL cache via `chatbot_cache` table (skipped for user-specific queries)
5. **Direct Triggers** — Exact-match shortcuts for "check points", "recycling guide", "impact stats", pickup lookup
6. **RAG Service** — If `RAG_ENABLED=true`, forwards to Flask on `http://localhost:5000/chat` with health check
7. **Pollinations.ai** — Sends full conversation history to `https://text.pollinations.ai/openai` with `llama-3.1-70b` model
8. **Rule-Based Fallback** — `getLocalFallbackResponse()` intent scoring engine
9. **Scheduling Detection** — Check if user intent is to schedule, start state machine
10. **JSON Parsing** — Extract `{"action":"schedule_pickup"}` from AI response, validate and insert pickup

**Caching:**
- Key: `md5(strtolower(message) + '|' + lang)`
- TTL: 5 minutes
- Skipped for user-specific queries (points, my impact)

**History:**
- Stores all user/assistant exchanges in `chat_messages` table
- Loads last 6-8 messages for Pollinations context
- Accepts optional `history` array from client for localStorage-based history

### 3.2 `api_impact.php` — Environmental Impact Endpoint

- **Route:** `api_impact.php?action={action}&user_id={id}`
- **Method:** `GET`
- **Authentication:** Public (requires valid `user_id`)

**Actions:**

| Action | Description | Parameters |
|---|---|---|
| `impact` | Full user impact calculation | `user_id` |
| `monthly` | Monthly CO₂ by category (last 12 months) | `user_id` |
| `percentile_rank` | User's rank vs others in same city | `user_id` |
| `forecast` | 90-day impact forecast (via Python CLI) | `user_id` |

**`impact` response:**
```json
{
    "user_id": 3,
    "user_name": "Test User",
    "total_pickups": 10,
    "completed_pickups": 8,
    "total_kg_recycled": 45.5,
    "this_month_kg": 12.3,
    "co2_saved_kg": 54.6,
    "water_saved_liters": 1200,
    "energy_saved_kwh": 340,
    "car_trip_equivalent": 260,
    "water_bottle_equivalent": 2400,
    "phone_charge_equivalent": 28333,
    "gamification": {
        "xp": 546,
        "level_name": "Eco-Seed",
        "level_number": 1,
        "next_level_name": "Eco-Sprout",
        "next_level_xp": 100,
        "progress_percent": 54.6,
        "points_to_next": 46,
        "next_rank_msg": "46 XP to Eco-Sprout"
    },
    "high_impact_badge": null,
    "ewaste_message": "Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling."
}
```

**Gamification Levels (10 tiers):**

| Level | XP Required | Title |
|---|---|---|
| 1 | 0 | Eco-Seed |
| 2 | 100 | Eco-Sprout |
| 3 | 300 | Eco-Sapling |
| 4 | 1,000 | Eco-Tree |
| 5 | 2,500 | Eco-Forest |
| 6 | 6,000 | Eco-Guardian |
| 7 | 15,000 | Earth Hero |
| 8 | 35,000 | Climate Commander |
| 9 | 75,000 | Atmosphere Architect |
| 10 | 150,000 | Planet Savior |

### 3.3 `admin/assignment_intelligence.php` — AI Assignment Scoring

- **Route:** `admin/assignment_intelligence.php`
- **Method:** `GET`
- **Auth:** `requireRole('admin')`

Visualizes the ML scoring system for agency assignments:
- Score breakdown per agency (load, completion, distance, rating, specialty)
- Predicted completion hours
- Model version tracking
- Historical assignment log

### 3.4 `admin/retrain_trigger.php` — Manual Retraining

- **Route:** `admin/retrain_trigger.php`
- **Method:** `POST` / `GET`
- **Auth:** `requireRole('admin')`

Triggers manual retraining of ML models:
- Kicks off `cron/retrain_model.php` via HTTP
- Returns training job status
- Logs training metrics to `model_versions` table

---

## 4. Cron Jobs

### 4.1 `cron/reassign_pending.php` — Stale Pickup Reassignment

- **Schedule:** Every 5–10 minutes
- **Purpose:** Reassigns pending pickups that have been stale for >10 minutes

**Logic:**
1. Find pickups with `status = 'pending'` and `created_at < NOW() - INTERVAL 10 MINUTE`
2. For each, reset `agency_id` to NULL if previously assigned
3. Call `auto_assign_v2.php` logic to find a new agency
4. Log reassignment in `assignment_log` with method `'reassign_cron'`

### 4.2 `cron/retrain_model.php` — Weekly ML Retraining

- **Schedule:** Weekly (Sunday 2:00 AM)
- **Purpose:** Retrains churn prediction model with latest data

**Logic:**
1. Fetch all user data (pickup history, points, recency)
2. Train Random Forest classifier on churn features
3. Score all users with new model
4. Update `user_ml_scores` table
5. Register new model version in `model_versions` table
6. Log training metrics (MAE, accuracy, feature importance)

---

## 5. Security Practices

| Practice | Implementation |
|---|---|
| **SQL Injection Prevention** | All queries use PDO prepared statements with named or positional parameters. No raw string interpolation in SQL |
| **XSS Prevention** | `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` via `e()` helper for all HTML output. CSP headers planned |
| **Password Hashing** | `password_hash($password, PASSWORD_BCRYPT)` for storage, `password_verify()` for authentication |
| **Session Security** | `session_start()` with HTTP-only cookies. Session-based auth with role checks on every admin/agency page |
| **API Authentication** | `requireLoginJson()` returns 401 JSON with no HTML redirect for API endpoints |
| **Error Leak Prevention** | Database errors logged via `error_log()`, not displayed to users. User-facing errors use generic messages |
| **CORS Headers** | Flask services configured with `flask-cors` for cross-origin requests |
| **SSL/TLS** | PDO SSL support for remote MySQL (Aiven). SSL verification with optional CA certificate |
| **File Upload Security** | `secure_filename()` from Werkzeug for sanitizing uploaded filenames. Extension whitelist |
| **Input Validation** | Server-side validation on all forms: email format via `filter_var()`, password length, required fields, CSRF on state-changing operations |
| **Role-Based Access** | `requireRole('admin')` and `requireRole('agency')` guards on all privileged pages |
| **Logging** | `error_log()` for all exceptions, API failures, security events. Separate log files for RAG (`rag.log`), impact API |
