# Configuration Reference

> **Notun Alo (নতুন আলো)** — Smart Recycling Platform  
> Document version: 1.0 | Last updated: May 2026

---

## 1. Environment File (`.env`)

The `.env` file is the central configuration point. It is **excluded from Git** (listed in `.gitignore`). A `.env.example` is provided as a template.

### 1.1 Complete Structure

```ini
# ── Database (Aiven MySQL) ──
DB_HOST=your-aiven-mysql-host
DB_PORT=your-aiven-mysql-port
DB_USER=your-aiven-mysql-user
DB_PASS=your-aiven-mysql-password
DB_NAME=notun_alo

# ── SSL for remote/Aiven MySQL (set "true" for cloud deployments) ──
DB_SSL=true
DB_SSL_CA=/path/to/ca.pem

# ── Gemini AI for RAG Chatbot ──
GEMINI_API_KEY=your_api_key_here
GEMINI_MODEL=gemini-2.5-flash

# ── Flask RAG Service URL (for cross-service communication) ──
RAG_API_URL=http://localhost:5000
RAG_ENABLED=false

# ── Public facing URL ──
BASE_URL=http://localhost/notun_alo/

# ── Auto Assignment API URL (optional, has SQL fallback) ──
ASSIGN_API_URL=http://localhost:5005
```

### 1.2 Variable Reference

| Variable | Required | Default | Description |
|---|---|---|---|
| `DB_HOST` | Yes | `localhost` | MySQL hostname or IP |
| `DB_PORT` | Yes | `3306` | MySQL port |
| `DB_USER` | Yes | `root` | MySQL username |
| `DB_PASS` | No | (empty) | MySQL password |
| `DB_NAME` | Yes | `notun_alo` | MySQL database name |
| `DB_SSL` | No | `false` | Enable SSL for MySQL connection (`true`/`false`) |
| `DB_SSL_CA` | No | (empty) | Path to CA certificate file for SSL verification |
| `GEMINI_API_KEY` | No | (empty) | Google Gemini API key for RAG responses |
| `GEMINI_MODEL` | No | `gemini-2.5-flash` | Gemini model identifier |
| `RAG_API_URL` | No | `http://localhost:5000` | Flask RAG service endpoint |
| `RAG_ENABLED` | No | `false` | Enable RAG service queries (`true`/`false`) |
| `BASE_URL` | Yes | `http://localhost/notun_alo/` | Public base URL of the application |
| `ASSIGN_API_URL` | No | `http://localhost:5005` | Auto-assignment API endpoint (optional) |

### 1.3 Loading Mechanism (`config.php:7-22`)

The `loadEnv()` function:
1. Reads `.env` from the project root
2. Skips comment lines (starting with `#`)
3. Parses `KEY=VALUE` pairs
4. Sets values in `$_ENV`, `$_SERVER`, and the environment via `putenv()`
5. Does **not overwrite** already-set values (system environment variables take precedence)

---

## 2. PHP Constants (`includes/config.php`)

### 2.1 Points System

| Constant | Value | Unit | Description |
|---|---|---|---|
| `POINTS_PAPER` | `5` | pts/kg | Points earned per kg of paper |
| `POINTS_PLASTIC` | `8` | pts/kg | Points earned per kg of plastic |
| `POINTS_METAL` | `12` | pts/kg | Points earned per kg of metal |

These constants are used in:
- Direct response triggers in `chatbot_api.php`
- Chatbot system prompt in `chatbot_context.php`
- State machine pickup creation in `chatbot_state.php`
- Pickup JSON extraction validation in `chatbot_api.php`

### 2.2 Application Constants

| Constant | Source | Default | Description |
|---|---|---|---|
| `DB_HOST` | `$_ENV['DB_HOST']` | `localhost` | Database host |
| `DB_PORT` | `$_ENV['DB_PORT']` | `3306` | Database port |
| `DB_USER` | `$_ENV['DB_USER']` | `root` | Database user |
| `DB_PASS` | `$_ENV['DB_PASS']` | (empty) | Database password |
| `DB_NAME` | `$_ENV['DB_NAME']` | `notun_alo` | Database name |
| `SITE_NAME` | Hardcoded | `Notun Alo` | Site display name |
| `BASE_URL` | `$_ENV['BASE_URL']` | `http://localhost/notun_alo/` | Public site URL |

---

## 3. Database Configuration (`includes/config.php`)

### 3.1 PDO Connection Options

```php
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
```

| Option | Value | Purpose |
|---|---|---|
| `ATTR_ERRMODE` | `ERRMODE_EXCEPTION` | Throws PDOException on errors |
| `ATTR_DEFAULT_FETCH_MODE` | `FETCH_ASSOC` | Returns associative arrays |
| `ATTR_EMULATE_PREPARES` | `false` | Uses real MySQL prepared statements |

### 3.2 DSN

```
"mysql:host={DB_HOST};port={DB_PORT};dbname={DB_NAME};charset=utf8mb4"
```

**Charset:** `utf8mb4` — enables full Unicode support including Bengali characters and emoji.

### 3.3 SSL Configuration

When `DB_SSL=true`, the following options are added:
```php
PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
// Optionally:
PDO::MYSQL_ATTR_SSL_CA => $caPath  // CA certificate path
```
- Required for Aiven MySQL connections
- Server cert verification is disabled by default for compatibility

### 3.4 Connection Error Handling

```php
catch (PDOException $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'getaddrinfo') || str_contains($msg, 'Name or service not known')) {
        $msg .= ' — Check that DB_HOST is set correctly in your Render dashboard env vars.';
    }
    die(json_encode(['error' => 'Database connection failed: ' . $msg]));
}
```

---

## 4. AI Configuration

### 4.1 Pollinations.ai (Chatbot)

| Parameter | Value | Location |
|---|---|---|
| Endpoint | `https://text.pollinations.ai/openai` | `chatbot_api.php:547` |
| Model | `llama-3.1-70b` | `chatbot_api.php:541` |
| Temperature | `0.7` | `chatbot_api.php:543` |
| Private mode | `true` | `chatbot_api.php:544` |
| Request timeout | 30 seconds | `chatbot_api.php:557` |
| Connection timeout | 10 seconds | `chatbot_api.php:556` |
| SSL verification | `true` | `chatbot_api.php:558` |

### 4.2 Circuit Breaker

| Parameter | Value | Location |
|---|---|---|
| Failure threshold | 3 consecutive | `chatbot_state.php:27` |
| Cooldown period | 300 seconds (5 min) | `chatbot_state.php:29` |
| State storage | `chatbot_circuit` table | `chatbot_state.php:3-8` |

### 4.3 Response Cache

| Parameter | Value | Location |
|---|---|---|
| Cache key | `md5(strtolower(query) . '|' . lang)` | `chatbot_api.php:47` |
| TTL | 5 minutes | `chatbot_api.php:54` |
| Skip condition | User-specific queries | `chatbot_api.php:451-453` |
| Storage | `chatbot_cache` table | `chatbot_api.php:21-30` |

### 4.4 Session Memory

| Parameter | Value | Location |
|---|---|---|
| Messages loaded | 6 (function default: 8) | `chatbot_api.php:519` |
| Role types | `user`, `assistant`, `system` | `chatbot_api.php:33` |
| Storage | `chat_messages` table | `chatbot_api.php:32-41` |

---

## 5. RAG Service Configuration

### 5.1 Application Settings (`app.py`)

| Parameter | Value | Notes |
|---|---|---|
| Flask host | `0.0.0.0` | Listens on all interfaces |
| Flask port | `5000` | Default RAG service port |
| Debug mode | `true` | Development only; disable in production |

### 5.2 Embedding Model

| Parameter | Value |
|---|---|
| Model name | `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2` |
| Type | Sentence transformer (384-dim) |
| Languages | 50+ including Bengali |
| Cache location | `~/.cache/huggingface/hub/` |

### 5.3 Chunking

| Parameter | Value |
|---|---|
| Chunk size | 1000 characters |
| Chunk overlap | 200 characters |
| Strategy | Overlapping windows |

### 5.4 Retrieval

| Parameter | Value |
|---|---|
| Top-k chunks | 8 |
| Vector store | ChromaDB |
| Collection name | `recycling` |
| Storage location | `chroma_db/` directory |

### 5.5 Gemini (for RAG)

| Parameter | Value |
|---|---|
| Model | `gemini-2.5-flash` (configurable via `GEMINI_MODEL` env var) |
| Temperature | 0.2 |
| Max output tokens | 900 |
| API key | From `GEMINI_API_KEY` env var |

### 5.6 Dockerfile.flask

| Parameter | Value |
|---|---|
| Base image | `python:3.11-slim` |
| Port | 5000 |
| Start command | `python app.py` |

### 5.7 Dockerfile.rag

| Parameter | Value |
|---|---|
| Extends | `Dockerfile.flask` |
| Pre-downloaded model | `paraphrase-multilingual-MiniLM-L12-v2` |
| Purpose | Reduce cold-start time |

---

## 6. Docker Configuration

### 6.1 Main Dockerfile (`Dockerfile`)

| Parameter | Value |
|---|---|
| Base image | `php:8.2-apache` |
| PHP extensions | `gd`, `zip`, `pdo_mysql` |
| Python | 3.11 with venv at `/opt/venv` |
| Apache modules | `mod_rewrite` enabled |
| Port | `8080` (configurable via `$PORT` env var) |
| Process manager | Supervisor |
| Working directory | `/var/www/html` |
| User | `www-data` |

### 6.2 Supervisor Configuration

```ini
[supervisord]
nodaemon=true
user=root

[program:apache]
command=/usr/local/bin/start-apache.sh
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stderr_logfile=/dev/stderr
```

### 6.3 Apache Port Script

The `start-apache.sh` script dynamically adjusts Apache to use the Render-provided `$PORT`:
```bash
APACHE_PORT="${PORT:-8080}"
sed -i "s/Listen 80/Listen ${APACHE_PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${APACHE_PORT}>/g" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
```

### 6.4 Docker Compose Services

| Service | Image Source | Port | Depends On |
|---|---|---|---|
| `php-app` | `Dockerfile` | 8080 | db |
| `flask-app` | `Dockerfile.flask` | 5000 | db |
| `db` | `mysql:8.0` | 3306 | — |

**Volume:** `db_data:/var/lib/mysql` (persistent database storage)

---

## 7. Render Deployment Configuration (`render.yaml`)

| Parameter | Value |
|---|---|
| Service type | `web` |
| Runtime | `docker` |
| Plan | `free` |
| Region | `oregon` |
| Dockerfile | `./Dockerfile` |
| Health check path | `/` |
| Port | 8080 (via `$PORT` env) |

### Render Environment Variables

| Key | Example Value | Notes |
|---|---|---|
| `DB_HOST` | `mysql-xxx.aivencloud.com` | Aiven MySQL host |
| `DB_PORT` | `20764` | Aiven MySQL port |
| `DB_USER` | `avnadmin` | Aiven MySQL user |
| `DB_PASS` | (set in dashboard) | Never commit to Git |
| `DB_NAME` | `defaultdb` | Aiven default database |
| `DB_SSL` | `true` | Required for Aiven |
| `RAG_API_URL` | `https://notun-alo-rag-xxx.a.run.app` | Cloud Run RAG endpoint |
| `RAG_ENABLED` | `false` | Disabled on free tier |
| `BASE_URL` | `https://notun-alo.onrender.com` | Public URL |

---

## 8. Application Settings

### 8.1 Language / Localisation (`includes/lang.php`)

| Setting | Default | Description |
|---|---|---|
| Session key | `$_SESSION['lang']` | Stores user language preference |
| Supported languages | `en`, `bn` | English and Bengali |
| Toggle | `?lang=en` or `?lang=bn` | Query parameter to switch |
| Number conversion | `en2bn()` | Converts digits to Bengali script |

### 8.2 Helper Functions (`includes/config.php:107-211`)

| Function | Purpose |
|---|---|
| `startSession()` | Starts session if not already active |
| `redirect($url)` | HTTP redirect with exit |
| `isLoggedIn()` | Checks `$_SESSION['user_id']` |
| `requireLogin()` | Redirects to login if not authenticated |
| `requireLoginJson()` | Returns 401 JSON for API endpoints |
| `requireRole($role)` | Restricts access by role |
| `getCurrentUser($pdo)` | Fetches full user record |
| `getUserPoints($pdo, $userId)` | Returns current reward points |
| `e($str)` | XSS-safe output via `htmlspecialchars` |
| `setFlash($type, $message)` | Stores flash message in session |
| `getFlash()` | Retrieves and clears flash message |
| `isDatabaseInitialized($pdo)` | Checks if core tables exist |
| `ensurePickupCategoryVarchar($pdo)` | Fixes legacy ENUM column |
| `loadEnv($path)` | Parses .env file into environment |

---

## 9. Chatbot Configuration Constants

### 9.1 Language Detection Thresholds

| Parameter | Value | Location |
|---|---|---|
| Bengali unicode threshold | >0.3 ratio | `chatbot_api.php:175` |
| Banglish keyword threshold | ≥0.3 ratio | `chatbot_api.php:179` |
| Mixed script threshold | ≥0.15 ratio | `chatbot_api.php:183` |
| Low confidence boundary | <0.6 confidence | `chatbot_api.php:672` |

### 9.2 Banglish Keyword List

30+ keywords in `chatbot_api.php:154-158`:
```
ki, obostha, kemon, acho, ami, tumi, khobor, valobashi, dhaka, bangla,
bhalo, ace, naki, jani, janina, bolo, dite, nibo, korte, kivabe,
lomba, bujina, bolben, hobe, kono, jonno, mone, hole, dorkar, chai,
chaile, thakbe, parbo, dibo, nibe, asbe, jaabe, bujhlam
```

### 9.3 State Machine Steps

| Step | Description |
|---|---|
| `idle` | No active flow |
| `awaiting_category` | Waiting for material category |
| `awaiting_weight` | Waiting for weight in kg |
| `awaiting_date` | Waiting for pickup date |
| `confirming` | Awaiting user confirmation |

---

## 10. Database Table Summary

| Table | Purpose | Key Columns |
|---|---|---|
| `users` | User accounts | `id`, `name`, `email`, `password`, `role`, `phone`, `address` |
| `rewards` | Reward points | `user_id`, `total_points`, `lifetime_points` |
| `pickups` | Pickup requests | `id`, `user_id`, `category`, `estimated_weight`, `status`, `schedule_date`, `agency_id` |
| `products` | Shop products | `id`, `name`, `description`, `price`, `points_price`, `image`, `category`, `stock` |
| `orders` | Product orders | `id`, `user_id`, `product_id`, `agency_id`, `total_points`, `status` |
| `chat_messages` | Chat history | `user_id`, `session_id`, `role`, `content`, `created_at` |
| `chatbot_cache` | Response cache | `cache_key` (MD5), `response_text`, `suggestions`, `created_at` |
| `chatbot_circuit` | Circuit breaker | `consecutive_failures`, `last_failure_at`, `opened_at` |
| `chatbot_states` | State machine | `user_id`, `session_id`, `step`, `data` (JSON) |
| `emission_factors` | CO2/water/energy factors | `category`, `co2_per_kg`, `water_per_kg`, `energy_per_kg` |
| `user_ml_scores` | ML churn predictions | `user_id`, `churn_score`, `last_interaction`, `engagement_score` |
| `assignment_log` | AI assignment audit | `pickup_id`, `agency_id`, `method`, `score_total`, `model_version` |
| `assignment_scores` | Assignment scores | `pickup_id`, `agency_id`, `score_load`, `score_distance`, `score_rating`, `score_specialty` |
| `agency_stats` | Agency availability | `agency_id`, `is_available`, `current_load`, `service_area` |
| `user_rank_cache` | Rank/city leaderboard | `user_id`, `percentile`, `city`, `total_in_city`, `metric` |
