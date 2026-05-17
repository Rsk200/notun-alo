# Testing — Notun Alo (নতুন আলো)

Comprehensive testing guide covering all layers of the Notun Alo platform: PHP backend, Python microservices, AI/chatbot systems, database, and frontend.

---

## 1. Existing Testing Structure

| Location | Type | Purpose |
|----------|------|---------|
| `tests/test_pipeline.py` | Flask RAG service tests | Health check, upload, chat endpoint validation |
| `ai-service/tests/test_environmental_system.py` | Environmental impact tests | CO₂/water/energy calculations, forecast, leaderboard |
| `scratch/test_*.py` | Ad-hoc scripts | Development experimentation, one-off validations |
| `scripts/run_automated_checks.ps1` | PowerShell automation | PHP lint + pytest runner in one command |
| `scripts/run_automated_checks.bat` | Batch wrapper | Invokes the PowerShell script from Command Prompt |

### Running Automated Checks

```powershell
# PowerShell (recommended)
.\scripts\run_automated_checks.ps1

# Command Prompt
.\scripts\run_automated_checks.bat
```

The automated check suite performs:
1. **PHP Lint** — `php -l` on every `.php` file in the project
2. **Python Tests** — `pytest` in the `ai-service/` directory
3. Exit with non-zero code if any check fails

---

## 2. Manual Testing Flow

### 2.1 PHP Lint

Run lint on every PHP file before committing:

```bash
# Lint all PHP files recursively
find . -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"

# Lint a specific file
php -l path/to/file.php
```

Expected output for valid files: `No syntax errors detected in <file>`.

### 2.2 Database Import & Verification

```bash
# Import schema
mysql -u root -p notun_alo < database/notun_alo.sql

# Verify tables
mysql -u root -p -e "USE notun_alo; SHOW TABLES;"
```

Expected tables: `users`, `pickups`, `products`, `orders`, `rewards`, `chatbot_cache`, `chat_messages`, `chatbot_states`, `chatbot_circuit`, `assignment_log`, `notifications`, `agency_stats`, `model_versions`, `emission_factors`, `category_averages`.

### 2.3 Login / Register

| Test Case | Input | Expected Result |
|-----------|-------|----------------|
| Successful registration | Valid email, password ≥6 chars, matching confirm | User created, redirected to login |
| Duplicate email | Same email twice | Error message: "Email already registered" |
| Short password | Password < 6 characters | Error: "Password must be at least 6 characters" |
| Missing fields | Empty form | Validation error per missing field |
| Password mismatch | Password ≠ Confirm password | Error: "Passwords do not match" |
| Login success | Correct credentials | Redirect to dashboard |
| Login failure | Wrong password | Error: "Invalid email or password" |

### 2.4 Dashboard

| Component | What to Verify |
|-----------|----------------|
| Stats cards | Total pickups, total kg, earned points, CO₂ prevented |
| Tier system | Current tier (Bronze/Silver/Gold/Platinum), XP progress bar |
| Recent activity | Last 5 pickups shown with status, date, category |
| Leaderboard | Top recyclers displayed, current user highlighted |
| Impact summary | CO₂, water, energy saved this month |
| Quick actions | Schedule pickup, shop, view impact links work |

### 2.5 Chatbot

| Test Category | Example Inputs |
|---------------|----------------|
| Greeting (EN) | `hello`, `hi`, `hey` |
| Greeting (BN) | `হ্যালো`, `নমস্কার` |
| Greeting (Banglish) | `kemon acho`, `ki obostha` |
| Points (EN) | `my points`, `how many points do I have` |
| Points (BN) | `আমার পয়েন্ট কত`, `পয়েন্ট চেক` |
| Points (Banglish) | `amar points koto` |
| Schedule (EN) | `I want to schedule a pickup` |
| Schedule (BN) | `পিকআপ শিডিউল করতে চাই` |
| Schedule (Banglish) | `ami pickup schedule korte chai` |
| Cancel | `cancel` (during any state machine step) |
| Impact (EN) | `what is my environmental impact` |
| Impact (BN) | `আমার পরিবেশগত প্রভাব কী` |
| Guide | `how to recycle`, `recycling guide`, `কিভাবে রিসাইকেল করব` |

### 2.6 Shop

| Test Case | Steps |
|-----------|-------|
| Product listing | Verify pagination (12 items/page), images, prices, stock |
| Search | Type product name, verify filtered results |
| Category filter | Select category dropdown, verify filtered products |
| Empty search | Type non-existent product, verify "No products found" |
| Product details | Click product, verify full description, specs, price |

### 2.7 Purchase Flow

| Test Case | Expected Result |
|-----------|----------------|
| Sufficient points | Points deducted, stock decremented, order created |
| Insufficient points | Error: "Not enough points" |
| Out of stock | Error: "Product out of stock" |
| Confirm purchase | Redirect to confirmation page with order details |
| Duplicate submission | Prevented by token or redirect check |

### 2.8 Admin Panels

| Panel | What to Test |
|-------|-------------|
| User management | List, search, edit, delete users |
| Pickup oversight | View all pickups, filter by status, assign agency |
| Agency assignment | Manual assignment, view 5-factor scores |
| Churn monitor | View churn predictions, risk levels, trend charts |
| Document control | Upload/edit/delete RAG knowledge base docs |
| Inventory | Add/edit/delete products, update stock |
| Sustainability report | View aggregated impact metrics |

### 2.9 Impact API

Test `api_impact.php` directly:

```bash
# Test with various user IDs
curl "http://localhost/notun_alo/api_impact.php?user_id=1"

# Expected JSON structure:
# {
#   "co2_prevented": 12.5,
#   "water_saved": 45.2,
#   "energy_saved": 8.3,
#   "percentile_rank": 72,
#   "tier": "Silver",
#   "forecast": [...]
# }
```

Edge cases: user_id=0 (invalid), user_id with no pickups, very high user_id.

### 2.10 RAG Service

```bash
# Health check
curl http://localhost:5000/health

# Upload a document
curl -X POST -F "file=@doc.pdf" http://localhost:5000/upload

# Query
curl -X POST -H "Content-Type: application/json" \
  -d '{"query": "কিভাবে রিসাইকেল করব?", "user_id": 1}' \
  http://localhost:5000/chat
```

---

## 3. Suggested Unit Tests (PHP)

### 3.1 `config.php`

| Function | Test Case | Expected |
|----------|-----------|----------|
| `e($str)` | `"<script>alert('xss')</script>"` | `&lt;script&gt;alert('xss')&lt;/script&gt;` |
| `e($str)` | `"hello & goodbye"` | `"hello &amp; goodbye"` |
| `redirect($url)` | `"/dashboard"` | 302 header, Location set |
| `isLoggedIn()` | Session set | `true` |
| `isLoggedIn()` | No session | `false` |
| `getUserPoints($uid)` | User with 150 pts | `150` |
| `getUserPoints($uid)` | Non-existent user | `0` |

### 3.2 `chatbot_fallback.php`

| Intent | Input | Expected Category |
|--------|-------|-------------------|
| Greeting | `hello` | `greeting` |
| Greeting | `হ্যালো` | `greeting` |
| Points | `amar points koto` | `points` |
| Schedule | `pickup schedule korte chai` | `schedule` |
| Impact | `my environmental impact` | `impact` |
| Guide | `how to recycle` | `guide` |
| Cancel | `cancel` | `cancel` |
| Unknown | `asdfghjkl` | `unknown` |

Test every regex pattern with at least 3 known inputs, including edge cases (mixed case, extra whitespace, punctuation).

### 3.3 `chatbot_state.php`

| Test Case | Steps |
|-----------|-------|
| Initiate scheduling | Message matching schedule intent → state = `awaiting_category` |
| Select category | Category message → state = `awaiting_weight` |
| Enter weight | Valid number → state = `awaiting_date` |
| Enter date | Valid date → state = `confirming` |
| Confirm | "yes" → pickup created, state cleared |
| Cancel at step 1 | "cancel" → state cleared, pickup not created |
| Cancel at step 3 | "cancel" → state cleared, pickup not created |
| Invalid weight | "abc" → error message, state unchanged |
| Circuit breaker open | 3 failures → fallback response, no AI call |

### 3.4 `chatbot_api.php`

| Test Case | Expected Behavior |
|-----------|-------------------|
| Cache hit (same query < 5 min) | Response returned from `chatbot_cache` table |
| Cache miss | Response generated via AI or fallback |
| Cache hit after TTL expired | New response generated, cache updated |
| Points query (direct trigger) | Points returned without AI call |
| Guide query (direct trigger) | Guide returned without AI call |
| Impact query (direct trigger) | Impact data returned without AI call |
| Banglish detection | `detectFallbackLanguage()` returns `bn` for Banglish input |
| English detection | `detectFallbackLanguage()` returns `en` for pure English |
| Suggestion generation | 3 context-aware suggestions returned with response |
| Empty message | `400 Bad Request` — "Message is required" |
| Very long message (1000+ chars) | Processed without error (truncated if needed) |

### 3.5 `api_impact.php`

| Test Case | Input | Expected |
|-----------|-------|----------|
| CO₂ calculation | 5 kg paper | `5 * 0.94 = 4.7 kg CO₂` |
| Water calculation | 3 kg plastic | `3 * 167.2 = 501.6 L water` |
| Energy calculation | 2 kg metal | `2 * 11.87 = 23.74 kWh energy` |
| E-waste 29x multiplier | 1 kg mobile phone | 29× standard CO₂ factor applied |
| Percentile rank | Middle-of-pack user | Rank between 30–70 |
| Percentile rank | Top recycler | Rank 95–100 |
| Missing emission factor | Unknown category | `COALESCE` fallback to average |
| No completed pickups | New user | Zero impact values |

---

## 4. Suggested Integration Tests

### 4.1 Full Chatbot Flow

```
User: "আমি পিকআপ শিডিউল করতে চাই"
  → chatbot_api.php receives message
  → detectSchedulingIntent() matches "শিডিউল"
  → state machine creates state: awaiting_category
  → StateMachineHandler() sends category prompt
  → Response returned to user

User: "প্লাস্টিক"
  → State machine advances to awaiting_weight
  → Prompt for weight

User: "৫"
  → en2bn() already handled by fallback
  → State machine advances to awaiting_date

User: "কাল"
  → State machine parses date, advances to confirming

User: "হ্যাঁ"
  → Pickup inserted into database
  → Confirmation returned
```

**Mock dependencies**: Pollinations.ai should be mocked to return a canned response. The state machine logic itself has no external API dependency.

### 4.2 Pickup Scheduling → DB Insert → Confirmation

1. Complete the full 5-step state machine flow
2. Verify `pickups` table has the new row with correct `user_id`, `category`, `weight`, `scheduled_date`
3. Verify `chatbot_states` for the user is cleared
4. Verify user receives a confirmation notification

### 4.3 RAG Pipeline

```
Query → embed (sentence-transformers) → ChromaDB similarity search (top_k=8)
  → reranker (pass-through) → prompt construction → Gemini/Pollinations
  → response
```

Test with a query known to exist in the knowledge base and one known to be out of scope. Measure latency per stage.

### 4.4 ML Scoring (Churn Prediction)

```
Feature extraction → model.predict() → upsert to model_versions
  → verify churn_score written to users table
```

Test with feature vectors near decision boundary.

### 4.5 Auth Flow

```
Register (POST /register) → Login (POST /login) → Dashboard (GET /dashboard)
  → Protected API call (chatbot_api.php) → Logout (GET /logout)
  → Protected API call (expects 401)
```

---

## 5. AI Testing Strategy

### 5.1 RAG Accuracy

| Test Query | Expected Content Area | Acceptable? |
|------------|----------------------|-------------|
| "প্লাস্টিক কিভাবে রিসাইকেল করব?" | Plastic recycling process | Must reference knowledge base |
| "কাগজ রিসাইকেল করা যায়?" | Paper recyclability | Must answer correctly |
| "ই-বর্জ্য কী?" | E-waste definition | Must match KB definition |
| "How to recycle metal?" | Metal recycling in Bangladesh | Must be locally relevant |

Manual evaluation criteria:
- **Relevance**: Does the answer directly address the query?
- **Factual accuracy**: Is the information correct per the knowledge base?
- **Language match**: Response in the same language as the query
- **Hallucination**: No fabricated facts or statistics

### 5.2 Fallback Coverage

All 15 intent categories must return a reasonable response:

| Intent | Minimum Acceptable Response |
|--------|---------------------------|
| `greeting` | A greeting in the detected language |
| `points` | User's current point balance or login prompt |
| `schedule` | Prompt to start scheduling flow |
| `impact` | Environmental impact summary or login prompt |
| `guide` | Recycling guide overview |
| `cancel` | Confirmation of cancellation |
| `shop` | Link to shop or point balance |
| `hours` | Operating hours or "available 24/7" |
| `location` | Service area description |
| `price` | Points per kg rates |
| `contact` | Support contact information |
| `thank_you` | Acknowledgment |
| `complaint` | Escalation or support link |
| `feedback` | Thank you + request for rating |
| `unknown` | Default "I didn't understand" message |

### 5.3 Circuit Breaker

Test sequence:
1. Mock Pollinations.ai to return `502 Bad Gateway`
2. Send 3 consecutive messages
3. After 3rd failure, verify circuit is open (`opened_at` set in `chatbot_circuit` table)
4. Send 4th message — verify fallback response returned immediately (no AI call)
5. Wait 5 minutes (or mock time)
6. Send message — verify circuit is closed (half-open / reset)

### 5.4 Language Detection

| Input | Expected Language | Notes |
|-------|-------------------|-------|
| `hello, how are you?` | `en` | Pure English |
| `আপনি কেমন আছেন?` | `bn` | Pure Bengali Unicode |
| `kemon acho?` | `bn` | Banglish — detected via keyword |
| `আমার points কত?` | `bn` | Mixed script (Bengali + English) |
| `ki obostha?` | `bn` | Banglish |
| `Hello, আমি pickup schedule করতে চাই` | `bn` | Primary Bengali intent with English words |
| `dokan e ki products ache?` | `bn` | Banglish |
| `ঠিক আছে, thanks` | `bn` | Mixed |

Detection should be based on Unicode Bengali character ratio + keyword scoring.

### 5.5 State Machine

| Step | Valid Input | Invalid Input |
|------|-------------|---------------|
| Init | "schedule pickup" | "hello" (no state change) |
| Category | "plastic", "প্লাস্টিক", "paper" | "xyz" (error, re-prompt) |
| Weight | "5", "৫", "2.5" | "abc", "-1" (error, re-prompt) |
| Date | "2025-12-25", "tomorrow", "কাল" | "never", "abc" (error, re-prompt) |
| Confirm | "yes", "হ্যাঁ", "confirm" | "no" (return to scheduling) |
| Cancel (any step) | "cancel", "বাতিল" | State cleared |

---

## 6. Chatbot Testing Strategy

### 6.1 Bilingual Support

All of these must work correctly:
- `hello` → English greeting + help prompt
- `kemon acho` → Bengali/Banglish greeting + help prompt
- `আপনি কেমন আছেন` → Bengali greeting + help prompt
- `how do I recycle` → English guide response
- `কিভাবে রিসাইকেল করব` → Bengali guide response
- `ki vabe recycle korbo` → Banglish guide response

### 6.2 Scheduling Flow

Test the complete flow in all three languages:
- `ami ekta pickup schedule korte chai` (Banglish)
- `আমি একটি পিকআপ শিডিউল করতে চাই` (Bengali)
- `I want to schedule a pickup` (English)

All should initiate the state machine.

### 6.3 Cancel Flow

At every state machine step, `cancel` must:
1. Clear the chatbot state
2. Return confirmation message: "Pickup request cancelled"
3. Not create any pickup record

### 6.4 Points Query

| Input | Expected |
|-------|----------|
| `amar points koto` | Points balance |
| `my points` | Points balance |
| `পয়েন্ট চেক` | Points balance |
| `how many points` | Points balance |

Response should include exact point count and tier information.

### 6.5 Cache Behavior

1. Send query: "how to recycle paper"
2. Verify response is generated and stored in `chatbot_cache`
3. Send same query within 5 minutes
4. Verify response comes from cache (no AI call)
5. Verify cache key: `md5(query + "_" + detected_lang)`

### 6.6 Circuit Breaker Behavior

1. Send query while Pollinations is healthy → normal AI response
2. Simulate Pollinations failure → fallback response returned
3. After 3 consecutive failures → circuit opens
4. Subsequent queries → instant fallback (< 1s)
5. After 5 minutes → circuit resets → normal flow resumes

---

## 7. Edge Case Testing

| Edge Case | Expected Behavior |
|-----------|-------------------|
| Empty message (`""`) | `400 Bad Request` — not a crash |
| Message with only whitespace (`"   "`) | Treated as empty → validation error |
| Very long message (1000+ characters) | Truncated or processed without memory overflow |
| Bengali numerals (`"৫ kg plastic"`) | `en2bn()` or `bn2en()` handles conversion correctly |
| XSS attempts (`"<script>alert('xss')</script>"`) | Output escaped via `e()` function |
| SQL injection (`"'; DROP TABLE users; --"`) | Blocked by PDO prepared statements |
| Concurrent requests (same user, 2 tabs) | State machine handles race conditions |
| Session expiration during multi-turn flow | User re-prompted to login; state preserved |
| Special characters (`"!@#$%^&*()"`) | Processed safely; no regex errors |
| Unicode injection (`"<סקריפט>"`) | HTML-escaped in output |
| Emoji in message (`"♻️ recycle please"`) | Processed; emoji preserved or stripped safely |
| Numbers as words (`"five kg"`) | Parsed via regex; may fail → re-prompt |
| Negative weight (`"-5 kg"`) | Validation rejects negative values |
| Zero weight (`"0 kg"`) | Validation rejects zero values |
| Past date (`"2020-01-01"`) | Validation rejects past dates |
| Very large weight (`"999999 kg"`) | Validation sets reasonable maximum |

---

## 8. Performance Testing

### 8.1 Pollinations Timeout Handling

| Scenario | Expected Latency |
|----------|-----------------|
| Pollinations healthy | 2–5 seconds |
| Pollinations slow (near 30s) | Circuit breaker triggers after 3 failures |
| Pollinations down (502) | Immediate fallback (< 500ms) |
| Circuit breaker open | < 100ms (no external call) |

### 8.2 Cache Hit Ratio

| Test | Expected |
|------|----------|
| 10 unique queries, repeat twice | Hit ratio: 50% (first = miss, second = hit) |
| 100 random queries | Measure hit ratio over time |
| User-specific queries (points, impact) | Always miss (not cached) |

### 8.3 RAG Retrieval Latency

| Operation | Expected Time |
|-----------|---------------|
| Embedding (sentence-transformers) | 200–500ms |
| ChromaDB similarity search (top_k=8) | 50–150ms |
| Reranker (pass-through) | < 1ms |
| Gemini generation | 1–3 seconds |
| Pollinations generation | 2–5 seconds |
| **Total RAG response** | **2–4 seconds** |

### 8.4 Database Query Optimization

| Query | Index Used | Expected Time |
|-------|-----------|---------------|
| `SELECT * FROM pickups WHERE user_id = ?` | `idx_user_id` on `pickups` | < 5ms |
| `SELECT * FROM users WHERE email = ?` | `UNIQUE` index on `email` | < 2ms |
| `SELECT * FROM orders WHERE user_id = ?` | FK index on `user_id` | < 5ms |
| `SELECT * FROM chat_messages WHERE user_id = ?` | Full scan (no index) | < 50ms (small table) |

**Recommendation**: Add index on `chat_messages.user_id` for large-scale deployments.

### 8.5 API Response Size

| Endpoint | Max Response Size | Current Typical |
|----------|------------------|-----------------|
| `chatbot_api.php` | 10 KB | 2–5 KB |
| `api_impact.php` | 50 KB | 5–15 KB |
| `shop.php` (product list) | 100 KB | 20–50 KB |
| `dashboard.php` (stats) | 200 KB (HTML) | 50–100 KB |

Ensure chatbot responses remain under 10 KB to maintain fast mobile experience.

---

## 9. Security Testing

| Test | Expected |
|------|----------|
| SQL injection via message field | Blocked — PDO prepared statements |
| XSS via message content | Escaped — `e()` function on output |
| CSRF on form submission | Token validation where implemented |
| Session fixation | Session regenerated after login |
| Brute force login | No rate limiting (known limitation) |
| Direct access to admin pages | Redirected — `isAdmin()` check |
| API without session | `401 Unauthorized` via `requireLoginJson()` |
| Booking for another user | Blocked — `user_id` from session only |
| Manipulating points via API | Blocked — points calculated server-side |

---

## 10. Test Data Fixtures

For reproducible tests, the following fixture data should exist:

### Users
- 1 test admin (email: `admin@test.com`, role: `admin`)
- 3 test households (different tier levels)
- 1 test agency

### Pickups
- 5 completed pickups (various categories, weights, dates)
- 1 pending pickup
- 1 in-progress pickup

### Products
- 3 products in shop (different categories, prices, stock levels)

### Orders
- 2 completed orders
- 1 pending order

### Chatbot
- 10 cached queries (various languages)
- 1 open circuit breaker (for testing)
- 1 active state machine session
