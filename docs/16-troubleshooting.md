# Troubleshooting Guide — Notun Alo (নতুন আলো)

A comprehensive guide to diagnosing and resolving common issues across the Notun Alo recycling platform — from local setup problems to production deployment failures, database errors, AI service issues, and chatbot malfunctions.

---

## 1. Common Setup Issues

### 1.1 "Database connection failed"

**Symptoms**:
- `PDOException: SQLSTATE[HY000] [2002] Connection refused`
- `PDOException: SQLSTATE[HY000] [1045] Access denied for user`
- Blank page on any page that loads `config.php`

**Checklist**:

| # | Step | Command / Action |
|---|------|------------------|
| 1 | Verify `.env` exists | `Test-Path -LiteralPath ".env"` — should return `True` |
| 2 | Check `.env` values | `Get-Content .env` — verify `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME` |
| 3 | Is MySQL running? | Windows: Check Services → MySQL80; Linux: `systemctl status mysql`; Docker: `docker ps` |
| 4 | Can you connect manually? | `mysql -u root -p` — if this fails, MySQL is not running or credentials are wrong |
| 5 | Aiven users: service powered on? | Aiven Console → Services → Check status; if paused, click "Power On" and wait 2 minutes |
| 6 | Aiven users: SSL enabled? | Set `DB_SSL=true` in `.env` for Aiven connections |
| 7 | Test DB connection | `php -r "new PDO('mysql:host=localhost;dbname=notun_alo', 'root', '');"` |
| 8 | Check DB name | `mysql -u root -p -e "SHOW DATABASES;"` — ensure `notun_alo` exists |

**Environment-specific notes**:

```env
# XAMPP (default)
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=notun_alo
DB_SSL=false

# Aiven (cloud)
DB_HOST=notun-alo-project.aivencloud.com
DB_PORT=12345
DB_USER=avnadmin
DB_PASS=your_aiven_password
DB_NAME=notun_alo
DB_SSL=true
```

### 1.2 "404 Not Found" on pages

**Symptoms**:
- `http://localhost/notun_alo/dashboard` returns 404
- All URLs except `index.php` are broken

**Causes & Fixes**:

| Cause | How to Check | Fix |
|-------|-------------|-----|
| `mod_rewrite` disabled | `phpinfo()` → apache2handler → loaded modules | Enable in `httpd.conf`: `LoadModule rewrite_module modules/mod_rewrite.so` |
| `.htaccess` not allowed | Check `httpd.conf` for `AllowOverride None` | Change to `AllowOverride All` in the `<Directory>` block |
| `BASE_URL` wrong | `Get-Content .env` and check `BASE_URL` | Should match your local URL, e.g., `BASE_URL=http://localhost/notun_alo` |
| Project not in htdocs | Check `C:\xampp1\htdocs\notun_alo` exists | Move or symlink project to `C:\xampp1\htdocs\` |

**XAMPP-specific fix**:

```apache
# C:\xampp1\apache\conf\httpd.conf
# Find and update:
<Directory "C:/xampp1/htdocs">
    Options Indexes FollowSymLinks
    AllowOverride All          # ← Change from None to All
    Require all granted
</Directory>
# Uncomment:
LoadModule rewrite_module modules/mod_rewrite.so  # ← Remove #
```

Restart Apache after changes.

### 1.3 "Chatbot not responding"

**Symptoms**:
- Typing a message and clicking send does nothing
- Loading spinner appears but never resolves
- "Network Error" in browser console

**Checklist**:

| # | Step | Detail |
|---|------|--------|
| 1 | Browser console | `F12` → Console tab — check for JS errors |
| 2 | Network tab | `F12` → Network tab — filter by `chatbot_api` → check response |
| 3 | User logged in? | Check if `requireLoginJson()` returns 401 — if so, re-login |
| 4 | PHP error log | `Get-Content logs/error.log -Tail 20` — check for PHP errors |
| 5 | Pollinations status | Visit `https://text.pollinations.ai/` — check if it's reachable |
| 6 | Circuit breaker | `SELECT * FROM chatbot_circuit` — if `opened_at` is set and < 5 min ago, circuit is open |
| 7 | Session cookie | Browser dev tools → Application → Cookies — check for PHPSESSID |
| 8 | CORS issue | Check network tab for CORS errors — if present, add CORS headers to `.htaccess` |

**Quick test from command line**:

```bash
# Test the chatbot API directly (Windows PowerShell)
$body = @{message="hello"} | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost/notun_alo/chatbot_api.php" `
  -Method Post -Body $body -ContentType "application/json" `
  -WebSession (New-Object Microsoft.PowerShell.Commands.WebRequestSession)
```

### 1.4 "Blank page" (White Screen of Death)

**Symptoms**: A PHP page renders completely blank — no HTML, no error message.

**Causes & Fixes**:

| Cause | Fix |
|-------|-----|
| PHP syntax error | `php -l filename.php` — fix any syntax errors |
| Fatal PHP error | Enable `display_errors` temporarily |
| Missing include | Check file paths in `require_once` / `include` statements |
| Memory limit | `php -i \| grep memory_limit` — increase in `php.ini` |
| 500 Internal Server Error | Check Apache error logs (`C:\xampp1\apache\logs\error.log`) |

**Enable error display temporarily**:

```php
// Add at the top of the problematic file:
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

**Or in php.ini**:
```ini
display_errors = On
error_reporting = E_ALL
```

---

## 2. Deployment Issues

### 2.1 "Container crashed on Render"

**Symptoms**:
- Render dashboard shows "Crash" or "Restarting" status
- Deploy logs show failure messages

**Checklist**:

| # | Step | Detail |
|---|------|--------|
| 1 | Check Render logs | Dashboard → Service → Logs tab |
| 2 | Build succeeded? | Check deploy logs for `Build successful` message |
| 3 | Memory limit | Free tier (512MB) may fail during model download — check for `Killed` in logs |
| 4 | Port binding | Ensure `PORT` env var is set and the app binds to `0.0.0.0:$PORT` |
| 5 | RAG model download | Try setting `RAG_ENABLED=false` to skip model download |
| 6 | Health check endpoint | Ensure `/health` returns 200 on the root of the app |

**Common Render fixes**:

```dockerfile
# Reduce memory usage in Dockerfile
# Use a lighter base image
FROM php:8.2-apache-bookworm-slim

# Skip RAG model download
ENV RAG_ENABLED=false

# Explicitly bind to all interfaces
CMD ["apache2-foreground"]
```

```bash
# Health check URL format for Render
# Must be http://0.0.0.0:$PORT/health
# Render will ping http://localhost:10000/health automatically
```

### 2.2 "Aiven DNS unresolvable"

**Symptoms**:
- `php -r "new PDO(...)"` throws "php_network_getaddresses: getaddrinfo failed"
- `nslookup your-db.aivencloud.com` returns "Non-existent domain"
- Application works for a while, then suddenly can't connect to database

**Cause**: Aiven free tier auto-pauses after 7 days of inactivity.

**Fix**:

```bash
# Step 1: Power on in Aiven Console
# Go to https://console.aiven.io → Services → your-service → Power On

# Step 2: Wait for DNS propagation (1–2 minutes)
Start-Sleep -Seconds 120

# Step 3: Verify DNS resolves
nslookup your-db.aivencloud.com
# Should return IP address

# Step 4: Test connection
mysql -h your-db.aivencloud.com -P 12345 -u avnadmin -p
```

**Prevention**: Set up a cron job / scheduled task to ping the database weekly:
```powershell
# Windows Task Scheduler: Run weekly
php -r "\$pdo = new PDO('mysql:host=\$env:DB_HOST;port=\$env:DB_PORT;dbname=\$env:DB_NAME', '\$env:DB_USER', '\$env:DB_PASS'); echo 'OK';"
```

### 2.3 "RAG service not responding"

**Symptoms**:
- Chatbot returns "I'm having trouble connecting to my knowledge base"
- `/health` endpoint returns 502 or times out
- RAG service returns empty responses

**Checklist**:

| # | Step | Command |
|---|------|---------|
| 1 | Is Flask running? | `ps aux \| findstr flask` (Windows) or `ps aux \| grep flask` (Linux) |
| 2 | Check Flask logs | `cat logs/rag.log` or `docker logs rag-service` |
| 3 | Model loaded? | Look for "Model loaded successfully" in logs; if missing, model download failed |
| 4 | Port available? | `netstat -ano \| findstr :5000` — ensure nothing else is on port 5000 |
| 5 | RAG enabled? | Check `RAG_ENABLED=true` (must be string `'true'`, not boolean `true`) in `.env` |
| 6 | ChromaDB healthy? | `curl http://localhost:5000/health` — should return `{"status": "healthy"}` |

**Start the RAG service**:

```bash
# Local development
python ai-service/app.py
# Should output: "RAG service running on http://0.0.0.0:5000"

# Docker
docker-compose -f docker-compose.rag.yml up -d
```

---

## 3. Database Errors

### 3.1 "Unknown column 'category' in products"

**Symptom**: `shop.php` or product listing page shows SQL error.

**Cause**: The `products` table is missing the `category` column (schema version mismatch).

**Fix**:

```sql
-- Option 1: Run via MySQL client
ALTER TABLE products ADD COLUMN category VARCHAR(50) AFTER name;

-- Option 2: Run the SQL file
mysql -u root -p notun_alo < database/add_category.sql

-- Option 3: Visit init_db.php in browser
-- http://localhost/notun_alo/init_db.php
```

### 3.2 "Duplicate entry for email"

**Symptom**: Registration fails with "Duplicate entry '...' for key 'users.email'".

**Cause**: A user already registered with that email address.

**Diagnosis**:

```sql
-- Check if the email exists
SELECT id, email, created_at FROM users WHERE email = 'user@example.com';

-- Find all registrations from same IP
SELECT email, created_at, ip_address FROM users
WHERE ip_address = '192.168.1.100' ORDER BY created_at DESC;
```

**Resolution**:
1. If legitimate user: ask them to log in (use "Forgot Password" if needed)
2. If duplicate account: admin can delete the duplicate via admin panel
3. If spam: admin can block the IP address

### 3.3 "Pickup schedule fails"

**Symptom**: After completing the chatbot scheduling flow, "Failed to create pickup" error.

**Diagnosis**:

| # | Check | SQL/Fix |
|---|-------|---------|
| 1 | `pickups.category` column type | `SHOW COLUMNS FROM pickups LIKE 'category'` — should be `varchar(50)`, not `enum(...)` |
| 2 | User exists? | `SELECT id FROM users WHERE id = {user_id}` |
| 3 | Valid weight? | Weight must be > 0 and <= 9999 |
| 4 | Valid date? | Date must be today or in the future |
| 5 | Foreign key constraint? | `SHOW CREATE TABLE pickups` — check FK on `user_id` |

**Fix category column if it's ENUM**:

```php
// Run this in init_db.php or a test script
$pdo = getDbConnection();
$stmt = $pdo->query("SHOW COLUMNS FROM pickups LIKE 'category'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
if ($col && str_starts_with($col['Type'], 'enum')) {
    $pdo->exec("ALTER TABLE pickups MODIFY category VARCHAR(50)");
    echo "Modified category column to VARCHAR(50)";
}
```

### 3.4 "No products in shop"

**Symptom**: Shop page shows "No products available" even though you loaded the database.

**Diagnosis**:

```sql
-- Check if products table has data
SELECT COUNT(*) FROM products;

-- If count = 0, the table is empty
-- Check trigger condition in shop.php
```

**Auto-seeding**: `shop.php` automatically seeds 12 sample products if `SELECT COUNT(*) FROM products = 0`. If seeding fails:

```sql
-- Manually insert a test product
INSERT INTO products (name, description, points_price, stock, category, image_url)
VALUES ('Test Product', 'A test product description', 100, 10, 'plastic', 'images/test.jpg');
```

---

## 4. API Failures

### 4.1 "Pollinations.ai timeout (30s)"

**Symptom**: Chatbot takes 30+ seconds to respond, then returns a fallback message.

**Diagnosis**:

| Check | Method |
|-------|--------|
| Pollinations status | `curl -I https://text.pollinations.ai/` — should return 200 |
| Circuit breaker status | `SELECT * FROM chatbot_circuit` — check `consecutive_failures` |
| Network latency | `ping text.pollinations.ai` — should be < 200ms |

**Circuit breaker recovery**:

```sql
-- Manually reset circuit breaker
UPDATE chatbot_circuit
SET consecutive_failures = 0, opened_at = NULL, last_failure_at = NULL
WHERE id = 1;
```

### 4.2 "401 Unauthorized" on chatbot

**Symptom**: Chatbot responds with "Please log in" or browser receives 401 status.

**Causes**:

| Cause | Check | Fix |
|-------|-------|-----|
| Session expired | `SELECT * FROM sessions WHERE session_id = ?` | Re-login |
| Cookie cleared | Browser → Cookies → PHPSESSID exists? | Re-login |
| Wrong session handler | `phpinfo()` → session.save_handler | Should be `files` or `redis` |
| Session path not writable | Check `session.save_path` in `php.ini` | Ensure directory exists and is writable |

### 4.3 "No data" from impact API

**Symptom**: `api_impact.php?user_id=1` returns all zeros or "no data".

**Diagnosis**:

```sql
-- Check if user has completed pickups
SELECT COUNT(*) FROM pickups
WHERE user_id = 1 AND status = 'completed';

-- Check emission factors table has data
SELECT * FROM emission_factors LIMIT 5;

-- Check category averages
SELECT * FROM category_averages LIMIT 5;
```

**Fix**:

1. Ensure some pickups have `status = 'completed'`
2. Seed `emission_factors` table:
```sql
INSERT INTO emission_factors (category, co2_per_kg, water_per_kg, energy_per_kg)
VALUES
('paper', 0.94, 10.2, 2.3),
('plastic', 2.5, 167.2, 11.87),
('metal', 4.2, 45.0, 11.87),
('glass', 0.6, 8.5, 1.5),
('ewaste', 29.0, 500.0, 50.0),
('mixed', 1.5, 50.0, 5.0);
```

---

## 5. AI Failures

### 5.1 Gemini API errors

**Symptom**: `callGemini()` returns error — "API key not valid" or "Quota exceeded".

**Diagnosis**:

```bash
# Test Gemini API key
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"contents":[{"parts":[{"text":"Hello"}]}]}' \
  "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=YOUR_API_KEY"
```

**Fixes**:

| Issue | Fix |
|-------|-----|
| Invalid API key | Regenerate key at https://aistudio.google.com/apikey |
| Quota exceeded | Free tier: 60 requests/minute — wait or upgrade |
| Billing not enabled | Enable billing at Google Cloud Console |
| API not enabled | Enable "Generative Language API" in GCP console |

**Fallback behavior**: If Gemini fails, the chatbot automatically falls back to Pollinations.ai (if circuit breaker is closed).

### 5.2 RAG returns "I couldn't find specific details"

**Symptom**: RAG-powered responses say "I couldn't find specific details about this in my knowledge base."

**Causes**:

| Cause | Check | Fix |
|-------|-------|-----|
| Query out of scope | Try a query you know is in the KB | Add document to KB |
| Ingestion failed | `python ingest.py --rebuild` shows errors | Check document format |
| ChromaDB empty | `python -c "import chromadb; c=chromadb.PersistentClient(); print(c.get_collection('notun_alo_docs').count())"` | Re-run ingestion |
| Embedding model changed | Dimension mismatch error in logs | `python ingest.py --rebuild` |

**Rebuild knowledge base**:

```bash
cd ai-service
python ingest.py --rebuild
# This re-chunks and re-embeds all documents
```

### 5.3 Banglish not detected correctly

**Symptom**: "kemon acho" is treated as English and returns an English response.

**Diagnosis**:

```php
// Test detection directly
$message = "kemon acho";
$lang = detectFallbackLanguage($message);
echo $lang;  // Should print "bn"
```

**Fixes**:

1. **Add more Banglish keywords** to `chatbot_fallback.php`:
```php
$banglish_keywords = [
    'kemon', 'acho', 'ki', 'obostha', 'bhalo', 'nai',
    'ami', 'tumi', 'apni', 'kothay', 'koto', 'kar',
    'jabo', 'asbo', 'dorkar', 'chai', 'hobe', 'kore',
    'dite', 'nibo', 'dewa', 'newa', 'thakbe', 'hoye',
    // Add:
    'ase', 'ache', 'hobe', 'ceye', 'korte', 'korbo',
    'dite', 'pawa', 'jabe', 'bole', 'dike', 'theke'
];
```

2. **Lower confidence threshold**:
```php
// In detectFallbackLanguage()
if ($confidence >= 0.5) {  // Change from 0.6 to 0.5
    return 'bn';
}
```

3. **Add "bilingual disambiguation note"** to the response:
```php
// When confidence is 50-60%
$disclaimer = "I detected both English and Bengali in your message. "
    . "I'll respond in English, but feel free to ask in Bengali!";
```

---

## 6. Chatbot Failures

### 6.1 "Reply is empty" or null

**Symptom**: Chatbot returns `{"reply": ""}` or `{"reply": null}`.

**Diagnosis**:

```php
// Add debug logging to chatbot_api.php
error_log("AI Response raw: " . print_r($aiResponse, true));
error_log("Fallback result: " . print_r($fallbackResult, true));
error_log("Final AI text: " . ($aiText ?? 'NULL'));
```

**Common causes**:

| Cause | Fix |
|-------|-----|
| `$aiText` variable is empty after AI call | Check if AI returned empty string |
| Fallback engine didn't match any intent | Expand intent patterns |
| JSON parsing failure | Log raw AI response to check format |
| `respondJson()` called with null | Trace code path before response |

### 6.2 State machine not activating

**Symptom**: "I want to schedule a pickup" returns a help message instead of starting the scheduling flow.

**Diagnosis**:

```php
// Test detectSchedulingIntent()
$message = "ami pickup schedule korte chai";
if (detectSchedulingIntent($message)) {
    echo "Scheduling intent detected!";
} else {
    echo "Not detected.";
}
```

**Check scheduling patterns** in `chatbot_fallback.php`:

```php
$schedulingPatterns = [
    '/schedule|শিডিউল|pickup|পিকআপ|pick.?up/i',
    '/ami.+pickup/i',               // Banglish
    '/pickup.+korte.?chai/i',        // Banglish
    '/schedule.+korte.?chai/i',      // Banglish
];
```

**Common issues**:
1. Pattern doesn't match the exact user phrasing — add more patterns
2. Function not being called — check the chain in `chatbot_api.php`
3. `chatbot_states` table not created — run `init_db.php`

### 6.3 Circuit breaker not working

**Symptom**: Chatbot keeps trying Pollinations.ai even after repeated failures.

**Diagnosis**:

```sql
-- Check circuit breaker table
SELECT * FROM chatbot_circuit;

-- Check if table exists
SHOW TABLES LIKE 'chatbot_circuit';
```

**Fixes**:

| Issue | Fix |
|-------|-----|
| Table doesn't exist | `init_db.php` creates it — visit in browser |
| `consecutive_failures` not incrementing | Check `logFailure()` calls in `chatbot_api.php` |
| `opened_at` not checked | Verify `isCircuitOpen()` logic in `chatbot_fallback.php` |
| Cooldown expired too fast | Check `opened_at + 300 seconds` comparison |

**Manual circuit breaker reset**:

```sql
-- Force circuit breaker open
UPDATE chatbot_circuit
SET opened_at = NOW() - INTERVAL 1 MINUTE,  -- Opens for 4 more minutes
    consecutive_failures = 3
WHERE id = 1;

-- Force circuit breaker closed
UPDATE chatbot_circuit
SET consecutive_failures = 0, opened_at = NULL, last_failure_at = NULL
WHERE id = 1;
```

---

## 7. Vector DB Issues

### 7.1 ChromaDB corrupted

**Symptoms**:
- `chromadb.errors.ChromaDBException` in logs
- RAG returns "Internal error" or empty results
- `get_collection()` raises `ValueError`

**Fix** (complete reset):

```bash
# Step 1: Stop the RAG service
# Ctrl+C or docker stop rag-service

# Step 2: Delete the chroma_db directory
Remove-Item -Recurse -Force "chroma_db/"

# Step 3: Restart the RAG service
python ai-service/app.py

# Step 4: Re-ingest documents
python ai-service/ingest.py --rebuild

# Step 5: Verify health
curl http://localhost:5000/health
# Expected: {"status": "healthy", "documents": 27}
```

### 7.2 "No chunks found"

**Symptoms**:
- RAG returns "I couldn't find specific details" for ALL queries
- `Collection.count()` returns 0

**Diagnosis**:

```python
# Check collection status
import chromadb
client = chromadb.PersistentClient(path="chroma_db")
try:
    collection = client.get_collection("notun_alo_docs")
    print(f"Document count: {collection.count()}")
except Exception as e:
    print(f"Collection error: {e}")
```

**Fixes**:

| Cause | Fix |
|-------|-----|
| Ingestion never ran | `python ingest.py --rebuild` |
| Ingestion failed silently | Check `logs/rag.log` for errors during ingestion |
| Wrong collection name | Verify `collection_name = "notun_alo_docs"` in `rag_pipeline.py` |
| Documents directory empty | Ensure `Phase 1 (RAG)/` has PDFs, CSVs, or TXTs |

### 7.3 Embedding dimension mismatch

**Symptom**: `chromadb.errors.InvalidDimensionException: Embedding dimension X does not match collection dimension Y`.

**Cause**: A different embedding model was used to create the collection than the one currently configured.

**Diagnosis**:

```python
# Check current model dimension
from sentence_transformers import SentenceTransformer
model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')
print(f"Model dimension: {model.get_sentence_embedding_dimension()}")

# Check collection dimension
collection = client.get_collection("notun_alo_docs")
print(f"Collection dimension: {collection.metadata.get('dimension')}")
```

**Fix**:

```bash
# Must rebuild from scratch
Remove-Item -Recurse -Force "chroma_db/"
python ai-service/ingest.py --rebuild
```

---

## 8. Authentication Issues

### 8.1 "Session expired" frequently

**Symptoms**:
- Need to re-login every few minutes
- Chatbot returns 401 mid-conversation

**Causes & Fixes**:

| Setting | Current (if low) | Recommended | Where to Change |
|---------|------------------|-------------|-----------------|
| `session.gc_maxlifetime` | 1440 (24 min) | 86400 (24 hours) | `php.ini` |
| `session.cookie_lifetime` | 0 (browser close) | 86400 | `php.ini` |
| `session.gc_probability` | 1 | 1 (fine) | `php.ini` |
| `session.gc_divisor` | 100 | 1000 (less frequent GC) | `php.ini` |

```ini
; php.ini recommended settings
session.gc_maxlifetime = 86400
session.cookie_lifetime = 86400
session.gc_probability = 1
session.gc_divisor = 1000
session.save_path = "C:/xampp1/tmp/sessions"  ; Ensure this directory exists
```

### 8.2 "Cannot login after register"

**Symptoms**:
- Registration succeeds (redirect to login page)
- Login fails with "Invalid email or password"

**Diagnosis**:

```sql
-- Check if user was actually created
SELECT id, email, password, role FROM users WHERE email = 'user@example.com';

-- Check if rewards row was created
SELECT * FROM rewards WHERE user_id = (SELECT id FROM users WHERE email = 'user@example.com');

-- Check password hashing
-- password_hash() creates 60-char bcrypt hash
SELECT LENGTH(password) FROM users WHERE email = 'user@example.com';
-- Should return 60
```

**Common fixes**:

| Issue | Fix |
|-------|-----|
| `password_hash()` fails | Check PHP has bcrypt support: `php -m \| findstr bcrypt` |
| Rewards INSERT fails | Check `rewards` table schema matches INSERT statement |
| Email verification required | Some versions require email verification — check `users.is_verified` |
| Session not started | Ensure `session_start()` is called on login page before `header()` redirect |

---

## 9. Build Errors

### 9.1 Docker build fails on Render

**Symptoms**:
- Build log shows "Killed" message
- Build takes > 15 minutes and times out
- "MemoryError" during Python package installation

**Diagnosis**: Render free tier has 512MB RAM and 1 CPU. Sentence-transformers model download + pip install can exceed this.

**Fixes**:

```dockerfile
# Option 1: Skip RAG model in main Dockerfile
FROM php:8.2-apache
# ... PHP setup only
ENV RAG_ENABLED=false

# Create separate Dockerfile.rag for RAG
# Deploy RAG as separate service on Cloud Run
```

```yaml
# Option 2: Use build stages
# render.yaml
services:
  - type: web
    name: notun-alo
    env: docker
    dockerfilePath: Dockerfile
    envVars:
      - key: RAG_ENABLED
        value: "false"

  - type: web
    name: notun-alo-rag
    env: docker
    dockerfilePath: Dockerfile.rag
    plan: starter  # $7/mo — 1GB RAM
```

### 9.2 `pip install` fails

**Symptoms**:
- `pip install -r requirements.txt` fails
- "Could not find a version that satisfies the requirement"
- "Microsoft Visual C++ 14.0 or greater is required"

**Fixes**:

| Issue | Fix |
|-------|-----|
| Python version mismatch | `python --version` — must be 3.11+ |
| Package compatibility | Check `requirements.txt` versions against Python version |
| Build tools missing (Windows) | Install Visual C++ Build Tools |
| Network issues | Use `--timeout=120` or set `PIP_INDEX_URL` to a mirror |
| Offline environment | Pre-download packages: `pip download -r requirements.txt -d ./pip-cache` |

**Requirements compatibility for Python 3.11+**:

```
flask==3.0.3
chromadb==0.5.5
sentence-transformers==3.0.1
numpy<2.0.0
pandas==2.2.1
scikit-learn==1.4.1
requests==2.31.0
python-dotenv==1.0.1
gunicorn==21.2.0
```

---

## 10. Environment & Configuration

### 10.1 `.env` file reference

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=notun_alo
DB_SSL=false

# Application
BASE_URL=http://localhost/notun_alo
APP_ENV=development

# AI
GEMINI_API_KEY=your_gemini_api_key  # Optional
RAG_ENABLED=false                    # Must be string 'true' or 'false'

# If using RAG service
RAG_SERVICE_URL=http://localhost:5000
```

### 10.2 File permission checklist

| Path | Required Permission | Purpose |
|------|---------------------|---------|
| `logs/` | Write | PHP error logs, AI call logs |
| `images/` | Write | Product images upload |
| `chroma_db/` | Write | Vector database persistence |
| `cache/` | Write | Percentile rank cache files |
| PHP session path | Write | Session data storage |

### 10.3 PHP extension checklist

```bash
# Verify required PHP extensions
php -m | findstr /C:"pdo_mysql"
php -m | findstr /C:"gd"
php -m | findstr /C:"zip"
php -m | findstr /C:"curl"
php -m | findstr /C:"mbstring"
php -m | findstr /C:"json"

# Expected output:
# pdo_mysql
# gd
# zip
# curl
# mbstring
# json
```

---

## 11. Quick Diagnostic Commands

### PHP

```bash
# Check PHP syntax
php -l filename.php

# Test DB connection
php -r "new PDO('mysql:host=localhost;dbname=notun_alo', 'root', ''); echo 'OK';"

# Run a PHP file in isolation
php -r "include 'config.php'; echo 'Config loaded successfully';"

# Check PHP configuration
php -i | findstr /C:"Loaded Configuration File"
```

### MySQL

```bash
# List all tables
mysql -u root -p -e "USE notun_alo; SHOW TABLES;"

# Check table schema
mysql -u root -p -e "USE notun_alo; DESCRIBE users;"

# Count records
mysql -u root -p -e "USE notun_alo; SELECT COUNT(*) FROM users;"

# Check for errors
mysql -u root -p -e "SHOW ENGINE INNODB STATUS\G"
```

### Python / RAG

```bash
# Check Flask app
curl http://localhost:5000/health

# Test embedding
python -c "from sentence_transformers import SentenceTransformer; m = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2'); print(m.encode('test').shape)"

# Check ChromaDB
python -c "import chromadb; c=chromadb.PersistentClient('chroma_db'); print(c.list_collections())"
```

### Network

```bash
# Check if Pollinations is reachable
curl -I https://text.pollinations.ai/ --connect-timeout 5

# Check if Gemini is reachable
curl -I https://generativelanguage.googleapis.com/ --connect-timeout 5

# Test port availability
netstat -ano | findstr :5000
netstat -ano | findstr :3306
```

---

## 12. Error Reference

| Error Message | Likely Cause | Quick Fix |
|---------------|--------------|-----------|
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL not running | Start MySQL service |
| `SQLSTATE[HY000] [1045] Access denied` | Wrong DB credentials | Check `.env` values |
| `SQLSTATE[42S02] Base table or view not found` | Table doesn't exist | Run `init_db.php` |
| `SQLSTATE[23000] Integrity constraint violation` | Duplicate key or FK violation | Check related table |
| `Undefined variable $pdo` | `config.php` not included | Add `require_once 'includes/config.php'` |
| `Call to undefined function getDbConnection()` | `config.php` version mismatch | Update `config.php` |
| `Maximum execution time of 30 seconds exceeded` | AI call timeout | Increase `max_execution_time` or check circuit breaker |
| `Allowed memory size of 134217728 bytes exhausted` | Too much memory used | Increase `memory_limit` or optimize query |
| `Cannot modify header information` | Output before `header()` call | Check for whitespace before `<?php` |
| `404 Not Found` | Rewrite rule or path issue | Check `.htaccess` and `BASE_URL` |
| `500 Internal Server Error` | PHP fatal error | Check Apache/PHP error logs |
| `502 Bad Gateway` | Upstream service down | Check Pollinations or RAG status |
| `JSON_ERROR_SYNTAX` | Invalid JSON in API response | Log raw response for inspection |
