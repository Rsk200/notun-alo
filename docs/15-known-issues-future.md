# Known Issues & Future Improvements — Notun Alo (নতুন আলো)

Documentation of existing limitations, technical debt, scalability concerns, and planned improvements for the Notun Alo recycling platform.

---

## 1. Existing Limitations

### 1.1 Pollinations.ai Reliability

| Issue | Detail | Mitigation |
|-------|--------|------------|
| Frequent 502 errors | Free Pollinations API frequently returns gateway errors | Circuit breaker (3 failures → 5 min cooldown) |
| Timeout (> 30s) | Free tier has no SLA; requests can hang indefinitely | 30-second curl timeout in `callPollinations()` |
| Rate limiting | No documented rate limits, but observed throttling at > 10 req/min | Cache reduces effective request rate |
| Model availability | `llama-3.1-70b` may be replaced without notice | Fallback engine always available |

**Impact**: During Pollinations outages, users receive less intelligent fallback responses. Circuit breaker mitigates timeout waste but cannot improve fallback quality.

### 1.2 RAG Memory Constraints

| Component | RAM Usage |
|-----------|-----------|
| sentence-transformers model | ~470 MB |
| ChromaDB (in-memory index) | ~100–500 MB (depends on document count) |
| Flask runtime + dependencies | ~50 MB |
| **Total RAG service** | **~700 MB – 1.2 GB** |

**Problem**: Render free tier (512 MB RAM) cannot run the RAG service. It must be deployed on Cloud Run (minimum 1 GB) or a comparable service.

**Current workaround**: RAG deployment is separated via `docker-compose.rag.yml` and `Dockerfile.rag`. The main PHP app runs without RAG if `RAG_ENABLED=false`.

### 1.3 No Real-Time Streaming

Chatbot responses are delivered as complete messages:
1. User sends message
2. PHP calls AI API (synchronous)
3. Waits 2–5 seconds for full response
4. Returns complete JSON

**Missing**: Token-by-token streaming via Server-Sent Events (SSE) or WebSocket.

**Impact**: Poor UX for long responses. Users perceive the chatbot as slow even though first-token latency could be ~200ms with streaming.

### 1.4 Single Application Server

The PHP monolith runs as a single Apache process (or a few prefork children). There is no:
- Horizontal scaling (multiple app servers behind a load balancer)
- Stateless session handling (PHP filesystem sessions are server-local)
- Graceful degradation (if Apache goes down, entire platform is down)

**Impact**: Cannot handle traffic spikes. No high availability.

### 1.5 Aiven Auto-Pause

| Issue | Detail |
|-------|--------|
| Trigger | 7 days of inactivity on free Aiven MySQL |
| Symptom | DNS becomes unresolvable; connection refused |
| Recovery | Power on in Aiven console → wait 1–2 minutes → DNS propagates |
| Frequency | ~1× per month during development lulls |

**Workaround**: A monitoring script or cron job that pings the database weekly to prevent auto-pause.

### 1.6 Limited Context Window

| Parameter | Current Value |
|-----------|---------------|
| Messages loaded per request | 6–8 |
| Total messages stored per user | 50 (pruned) |
| Max conversation length remembered | ~8 turns |

**Impact**: Long conversations lose early context. The chatbot may repeat information or contradict earlier statements. A user discussing multiple topics across 10+ messages will lose the first few.

### 1.7 No File Upload for Waste Classification

`ai-service/waste_classifier.py` exists as a placeholder but is not integrated into the PHP frontend:
- No upload endpoint in `chatbot_api.php`
- No image input in the chatbot UI
- No `waste_classifier.py` endpoint registered in the Flask app

**Planned**: Users upload a photo of their waste → AI classifies the material → auto-fills category in pickup scheduling.

### 1.8 No Push Notifications

Users are not notified when:
- A pickup is assigned to an agency
- A pickup is marked as collected
- Points are credited
- A new product is available in the shop
- A churn risk is detected

**Current state**: Notifications exist in the database (`notifications` table) but are only displayed when the user visits the dashboard. No email, SMS, or browser push integration.

### 1.9 Reranker Placeholder

```python
# reranker.py
def rerank(query, chunks):
    return chunks  # pass-through — no actual reranking
```

The reranker module has a complete interface but performs no reranking. The cross-encoder model is not loaded due to memory constraints.

**Effect**: ChromaDB results are returned in raw similarity order. A cross-encoder could improve result relevance by 15–25%.

### 1.10 Verifier Placeholder

```python
# verifier.py
def verify(response, context):
    return {"score": 1.0, "verified": True}  # always passes
```

The verifier module always returns perfect scores. No factual grounding check is performed on RAG responses.

**Effect**: The RAG system may hallucinate facts that are not grounded in the knowledge base, and nobody checks.

---

## 2. Technical Debt

### 2.1 Mixed Concerns in `chatbot_api.php`

`chatbot_api.php` handles too many responsibilities in a single file (~600 lines):

| Responsibility | Lines | Should Be |
|----------------|-------|-----------|
| Authentication | ~30 | Middleware trait |
| Request validation | ~20 | Input validator |
| Cache management | ~50 | Cache service class |
| Language detection | ~40 | Language detector class |
| Fallback engine | ~80 | Separate service |
| State machine | ~100 | State machine class |
| AI API calls | ~70 | AI provider class |
| Response formatting | ~50 | Response builder |
| Database operations | ~60 | Repository classes |
| Session memory | ~30 | History manager |
| Suggestion generation | ~30 | Suggestion engine |

**Refactoring plan**: Extract each responsibility into its own class/trait in an `includes/services/` directory.

### 2.2 Duplicate PDO Connections

Some admin files had duplicate PDO connections:

```php
// Problem (old code):
$pdo = new PDO($dsn, $user, $pass);
$pdo = getDbConnection();  // redundant

// Fixed:
$pdo = getDbConnection();  // single instance
```

Identified in: `admin_docs.php`, `docs_setup.php`. Partially fixed — audit remaining admin files.

### 2.3 Magic Numbers

Points rates are duplicated across files instead of using constants:

| Value | Meaning | Files Using It |
|-------|---------|----------------|
| `5` | Paper points per kg | `config.php`, `chatbot_fallback.php`, `dashboard.php` |
| `8` | Plastic points per kg | `config.php`, `chatbot_fallback.php`, `dashboard.php` |
| `12` | Metal points per kg | `config.php`, `chatbot_fallback.php`, `dashboard.php` |
| `3` | Circuit breaker failure threshold | `chatbot_api.php`, `chatbot_fallback.php` |
| `300` | Circuit breaker cooldown (seconds) | `chatbot_api.php`, `chatbot_fallback.php` |
| `8` | Max messages loaded from history | `chatbot_api.php` |

**Fix**: Define constants in `config.php`:
```php
define('POINTS_PAPER', 5);
define('POINTS_PLASTIC', 8);
define('POINTS_METAL', 12);
define('CIRCUIT_BREAKER_THRESHOLD', 3);
define('CIRCUIT_BREAKER_COOLDOWN', 300);
define('MAX_HISTORY_MESSAGES', 8);
```

### 2.4 Error Handling

Some catch blocks expose raw errors to users:

```php
// Problem:
catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Fixed:
catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    respondJson(500, "An internal error occurred. Please try again.");
}
```

**Status**: Fixed in `chatbot_api.php` and `api_impact.php`. Legacy pages (`admin_*.php`, some `dashboard` pages) may still expose errors.

### 2.5 No Migration Framework

Schema changes are handled by:
- `init_db.php` — ad-hoc PHP `ALTER TABLE` calls
- `add_category.sql` — standalone SQL scripts
- `database/notun_alo.sql` — full schema dump

**Problem**: No versioning, no rollback, no automated migration pipeline. Deploying to production requires manual SQL execution.

**Suggested tool**: Use a lightweight PHP migration library (e.g., Phinx) or a simple version table:
```sql
CREATE TABLE migrations (
    version INT PRIMARY KEY,
    name VARCHAR(255),
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2.6 Inline CSS/JS

Several pages have embedded styles instead of using `style.css`:

| File | Has Inline CSS | Has Inline JS |
|------|----------------|---------------|
| `admin_docs.php` | Yes | Yes |
| `admin_churn.php` | Yes | No |
| `login.php` | Partially | No |
| `register.php` | Partially | No |
| `index.php` | Minimal | Minimal |

**Impact**: Style changes require editing multiple files. Increases page size. Harder to maintain dark mode consistency.

**Fix**: Move all styles to `style.css` and all scripts to `animations.js`.

### 2.7 Model Training Scripts

`train_impact_model.py` and `train_notun_alo_churn.py` are standalone scripts — not integrated into the deployment pipeline:

- No automated retraining trigger
- No model version tracking
- No A/B testing framework
- No performance monitoring after deployment

**Impact**: Models may become stale as user behavior patterns drift. Retraining is a manual process.

---

## 3. Scalability Concerns

### 3.1 Session Affinity

PHP file-based sessions (`session.save_handler = files`) store session data on the local filesystem:
- Cannot scale to multiple servers without shared filesystem (NFS) or Redis
- If a load balancer routes a user to a different server, their session is lost
- No built-in session replication

**Fix**: Implement Redis session storage:
```php
// php.ini
session.save_handler = redis
session.save_path = "tcp://redis:6379"
```

### 3.2 Synchronous Processing

All AI calls are synchronous HTTP requests:
```
PHP → curl → Pollinations.ai (blocks 30s max)
Python Flask → requests → Gemini (blocks 10s max)
```

**Impact**: Each AI call blocks an entire PHP process or Flask worker. Under load, all workers are occupied waiting for AI responses, causing queueing and timeouts.

**Fix**: Background job queue (Celery + Redis) for AI calls. WebSocket or polling for result delivery.

### 3.3 Database Single Point of Failure

The entire platform depends on a single MySQL instance:
- No read replicas for reporting queries
- No failover if the primary goes down
- Aiven free tier has no replication or backup guarantees

**Impact**: Database outage → entire platform becomes inaccessible. Data loss if corruption occurs and no recent backup exists.

**Fix**: Set up a replica instance (even on same tier for failover). Implement automated daily backups with 7-day retention.

### 3.4 ChromaDB Single Node

The vector database runs as a single local process:
- No replication (data loss if `chroma_db/` directory is corrupted)
- No horizontal scaling (all vectors on one machine)
- Memory-bound (cannot exceed available RAM)

**Fix**: For production scale, migrate to a managed vector database (Pinecone, Weaviate, Qdrant) or deploy ChromaDB with replication.

### 3.5 No Rate Limiting

API endpoints have no rate limiting:
- `chatbot_api.php` — unlimited requests per user
- `api_impact.php` — unlimited requests per user
- Login endpoint — unlimited attempts (brute force possible)

**Attack vectors**:
- A user could spam the chatbot API and exhaust Pollinations.ai quota
- A bot could scrape all impact data
- Brute force password guessing

**Fix**: Implement rate limiting (e.g., 30 requests/minute per user for chatbot, 5 attempts/minute for login). Use Redis or token bucket algorithm in PHP.

---

## 4. Suggested Improvements

### 4.1 Redis Integration

| Use Case | Current | With Redis |
|----------|---------|------------|
| Chatbot cache | MySQL table (5–10ms lookup) | In-memory (< 1ms lookup) |
| Session storage | Filesystem (not scalable) | Redis (shared across servers) |
| Rate limiting | Not implemented | Token bucket (10 lines of code) |
| Job queue | Not implemented | Redis + Celery (background AI calls) |
| Circuit breaker | MySQL table (10ms check) | In-memory (1ms check) |

### 4.2 WebSocket Support

**Current flow**:
```
User → HTTP POST → PHP → 5s wait → Full JSON response
```

**Proposed flow**:
```
User → WebSocket connect → PHP sends message → AI generates tokens
      → Each token pushed immediately → User sees streaming response
```

**Implementation options**:
- **Ratchet** (PHP WebSocket library) — native PHP, no extra infrastructure
- **Node.js/Socket.io** — requires Node runtime alongside PHP
- **Server-Sent Events** — simpler than WebSocket, unidirectional only

### 4.3 Background Job Queue

| Job | Queue | Worker | Priority |
|-----|-------|--------|----------|
| AI chat generation | `chatbot:generate` | Python worker | High |
| Impact forecast update | `impact:forecast` | Python worker | Low |
| Churn prediction | `ml:churn` | Python worker | Low |
| Notification dispatch | `notify:send` | PHP worker | Medium |
| Database cleanup | `db:cleanup` | PHP worker | Low |

**Infrastructure**: RabbitMQ or Redis + Celery for Python tasks, PHP `exec` or Supervisor for PHP tasks.

### 4.4 CI/CD Pipeline

| Stage | Tool | Action |
|-------|------|--------|
| Lint | GitHub Actions | `php -l` on all PHP files |
| Unit tests | PHPUnit / pytest | Run test suites |
| Build | GitHub Actions | `docker build` |
| Push | GitHub Actions | Push to Docker Hub / GHCR |
| Deploy | Render Deploy Hook | Trigger deployment |
| Smoke test | GitHub Actions | Verify health endpoints |

### 4.5 Monitoring

| Metric | Tool | Alert Threshold |
|--------|------|-----------------|
| PHP error rate | Sentry | > 1% of requests |
| API latency | New Relic / custom | P95 > 10s |
| Pollinations failure rate | Custom log | > 50% in 5 min window |
| Database connection count | MySQL `SHOW STATUS` | > 80% of max_connections |
| Disk usage | Server monitoring | > 90% |
| Cache hit ratio | Custom metric | < 20% |

**Minimal monitoring setup**:
1. Log all PHP errors to `logs/error.log`
2. Log all AI API calls to `logs/ai.log` (with timing)
3. Log all chatbot requests to `logs/chatbot.log`
4. Create `admin_monitor.php` dashboard showing error counts, cache hit rates, circuit breaker status

### 4.6 CDN Integration

| Asset | CDN Route | Cache TTL |
|-------|-----------|-----------|
| CSS | `cdn.notunalo.com/css/style.css` | 1 year (immutable) |
| JS | `cdn.notunalo.com/js/animations.js` | 1 year (immutable) |
| Images | `cdn.notunalo.com/images/*` | 1 month |
| Fonts | `cdn.notunalo.com/fonts/*` | 1 year |

**Implementation**: Use CloudFlare (free plan) or a simple CDN like jsDelivr for open-source assets.

### 4.7 Automated Database Backups

```bash
#!/bin/bash
# Daily backup script
BACKUP_DIR="/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS \
    --single-transaction --quick --lock-tables=false \
    notun_alo | gzip > $BACKUP_DIR/notun_alo_$DATE.sql.gz

# Retain last 7 daily + 4 weekly backups
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

**Cron**: `0 3 * * * /scripts/backup_db.sh`

### 4.8 API Versioning

**Current**: `POST /chatbot_api.php`
**Proposed**: `POST /v1/chat` with proper routing via `.htaccess`

```apache
RewriteRule ^v1/chat$ chatbot_api.php [L,QSA]
RewriteRule ^v1/impact/(\d+)$ api_impact.php?user_id=$1 [L,QSA]
RewriteRule ^v1/shop$ shop.php [L,QSA]
```

**Benefits**:
- Backward-compatible API evolution
- Cleaner URL structure
- Easier for third-party integrations
- Swagger/OpenAPI documentation possible

---

## 5. AI Enhancement Ideas

### 5.1 Cross-Encoder Reranker

**Current**: ChromaDB returns results sorted by cosine similarity (raw embedding distance)

**Proposed**: Cross-encoder scores each result for relevance to the specific query

| Metric | Current | With Cross-Encoder |
|--------|---------|-------------------|
| NDCG@5 | ~0.65 | ~0.85 |
| Latency | +0ms | +1–2s |
| RAM | 0MB | +500MB |
| Implementation | Pass-through | `cross-encoder/ms-marco-MiniLM-L-6-v2` |

**Trade-off**: 1–2 seconds of additional latency for significantly better result relevance. Only worthwhile if users value accuracy over speed.

### 5.2 Image Classification

`ai-service/waste_classifier.py` uses a placeholder model. A real implementation would:

```python
from transformers import pipeline

classifier = pipeline("image-classification",
    model="google/vit-base-patch16-224")

def classify_waste(image_path):
    result = classifier(image_path)
    categories = {
        'plastic_bottle': 'plastic',
        'newspaper': 'paper',
        'aluminum_can': 'metal',
        'cardboard': 'paper',
        'glass_bottle': 'glass'
    }
    return categories.get(result[0]['label'], 'mixed')
```

**Integration into product**:
1. Camera icon in chatbot UI
2. Upload triggers classification API
3. Result pre-fills category in scheduling state machine
4. Estimated weight based on category averages

### 5.3 Voice Interface

**For Bengali users with low literacy**:
- Speech-to-text: Google Speech-to-Text API (Bengali supported)
- Text-to-speech: Responsive voice in Bengali
- Integration: Microphone button in chatbot UI

```javascript
// Browser speech recognition for Bengali
const recognition = new webkitSpeechRecognition();
recognition.lang = 'bn-BD';
recognition.onresult = (event) => {
    const text = event.results[0][0].transcript;
    sendChatMessage(text);
};
```

### 5.4 Sentiment Analysis

Detect user frustration and escalate:

```python
from transformers import pipeline

sentiment = pipeline("sentiment-analysis",
    model="nlptown/bert-base-multilingual-uncased-sentiment")

def detect_frustration(message):
    result = sentiment(message)
    score = result[0]['score']
    label = result[0]['label']
    # label: 1 star (very negative) to 5 star (very positive)
    if label in ['1 star', '2 star'] and score > 0.7:
        return ESCALATE_TO_HUMAN
    return CONTINUE_CHATBOT
```

**Actions on escalation**:
- Notify admin via `notifications` table
- Offer callback request
- Provide emergency contact number

### 5.5 Multi-modal RAG

Support queries that combine image + text:
- "Can I recycle this?" + photo of item
- "What material is this?" + photo
- "Is this e-waste?" + photo

**Architecture**:
1. Image → CLIP embedding → ChromaDB multi-modal search
2. Text + image → combined prompt → LLM
3. Multi-modal model: LLaVA or GPT-4V

### 5.6 Fine-Tuned Model

Instead of prompting a general-purpose LLM, fine-tune a small model on recycling data:

| Aspect | General LLM (Gemini) | Fine-Tuned Model |
|--------|---------------------|-------------------|
| Latency | 2–5s | 200–500ms |
| Cost | Free (limited) | Free (self-hosted) |
| Accuracy | Good (general) | Excellent (domain) |
| Bengali quality | Good | Native |
| Hallucination | Moderate | Low |
| RAM | 0 (API call) | 2–7 GB |

**Model choices**: Llama-3.2-3B, Gemma-2-2B, or a Bengali-specific model like `sagor-llama-3-8b-bengali`.

### 5.7 Automated Knowledge Base Updates

**Current**: Knowledge base is static — documents must be manually uploaded via admin panel.

**Proposed**: Web scraper that periodically crawls:
- Bangladesh Department of Environment website
- Waste management regulations updates
- Recycling best practices
- Local news about recycling initiatives

```python
# Scraper skeleton
scrapy crawl recycling_news -o news_documents.json
# → convert to text → chunk → embed → upsert to ChromaDB
```

**Cron**: Weekly updates with admin approval before deployment.

---

## 6. Roadmap

### Phase 1 (Current) — Foundation
- ✅ Bilingual chatbot with fallback engine
- ✅ Basic RAG pipeline (static KB)
- ✅ Pickup scheduling state machine
- ✅ Points and rewards system
- ✅ Environmental impact tracking
- ✅ Admin dashboard
- ✅ Circuit breaker pattern

### Phase 2 — Reliability & Scale
- [ ] Redis integration (cache + sessions + rate limiting)
- [ ] Background job queue for AI calls
- [ ] Database read replicas
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Automated backups
- [ ] API versioning

### Phase 3 — User Experience
- [ ] Real-time streaming (WebSocket/SSE)
- [ ] Push notifications (email + browser)
- [ ] Voice interface for Bengali users
- [ ] File upload for waste classification
- [ ] Dark mode refinements
- [ ] Mobile app (PWA)

### Phase 4 — Intelligence
- [ ] Cross-encoder reranker
- [ ] Fine-tuned small LLM for recycling
- [ ] Sentiment analysis and escalation
- [ ] Multi-modal RAG (image + text)
- [ ] Automated knowledge base updates
- [ ] Churn prediction with intervention

### Phase 5 — Scale
- [ ] Horizontal scaling (multiple app servers)
- [ ] Managed vector database (Pinecone/Qdrant)
- [ ] Multi-region deployment
- [ ] Third-party API for external integrations
- [ ] White-label solution for other cities
