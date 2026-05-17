# System Architecture

## 1. Overall Architecture

Notun Alo follows a **client-server model** with a PHP/Apache monolith frontend+backend, optional Python Flask microservices, a single MySQL database, and a ChromaDB vector store for RAG.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           CLIENTS (Browser)                                 │
│                  HTML/CSS/JS — Dark Mode — Bilingual EN/BN                  │
└─────────────────────┬───────────────────────┬──────────────────────────────┘
                      │ HTTP/HTTPS            │ AJAX/JSON
                      ▼                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                       APACHE 2.4 WEB SERVER (Port 8080)                      │
│                          PHP 8.2 via mod_php                                │
│                                                                              │
│  ┌──────────────┐ ┌────────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ index.php    │ │ dashboard.php  │ │ admin.php    │ │ chatbot_api.php  │  │
│  │ Landing page │ │ User dashboard │ │ Admin panel  │ │ AI endpoint      │  │
│  └──────────────┘ └────────────────┘ └──────────────┘ └───────┬──────────┘  │
│                                                                              │
│  ┌──────────────┐ ┌────────────────┐ ┌──────────────┐                      │
│  │ shop.php     │ │ login.php/     │ │ api_impact   │                      │
│  │ Upcycle shop │ │ register.php   │ │ .php         │                      │
│  └──────────────┘ └────────────────┘ └──────────────┘                      │
└──────────────────────────────────┬───────────────────────────────────────────┘
                                   │ PDO
                                   ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                         MySQL 8.0 DATABASE                                   │
│                                                                              │
│  Core tables: users, pickups, products, orders, rewards                      │
│  AI tables:   chatbot_cache, chat_messages, chatbot_states, chatbot_circuit │
│  ML tables:   assignment_log, assignment_scores, model_versions              │
│  Impact:      emission_factors, category_averages, notifications             │
└──────────────────────────────────────────────────────────────────────────────┘
                                   ▲
                                   │
                    ┌──────────────┴──────────────┐
                    │     Python Flask Services    │
                    │  (Optional, independently    │
                    │   deployable)                │
                    │                              │
                    │  :5000  RAG Service          │
                    │  :5003  Impact Service       │
                    │  :5005  Assignment Service   │
                    └──────────────────────────────┘
```

---

## 2. Frontend Architecture

The frontend is **server-rendered PHP templates** with no JavaScript framework. Each PHP page renders HTML directly using `include`-based modularization.

| Aspect | Detail |
|--------|--------|
| **Rendering** | Server-side (PHP `echo`/HTML) |
| **CSS** | `assets/css/style.css` + inline styles + dark mode variables |
| **JavaScript** | Vanilla JS + Chart.js for dashboards + html2canvas for share |
| **Icons** | Font Awesome 6.5.1 + Tabler Icons (via CDN) |
| **Fonts** | Google Fonts: Poppins, Playfair Display, DM Sans |
| **Dark Mode** | CSS variables, `body.dark-mode` class, localStorage persistence |
| **Bilingual** | Session-based `$_SESSION['lang']` toggle (EN/BN); `includes/lang.php` dictionary |
| **Responsive** | CSS media queries for tablet/mobile breakpoints |
| **Navigation** | Role-based navbar (`includes/navbar.php`) with sticky scroll-hide on mobile |

### Key Frontend Files

| File | Description |
|------|-------------|
| `index.php` | Landing page with hero, impact ticker, shop preview |
| `dashboard.php` | User dashboard: tier system, stats, leaderboard, activity |
| `chatbot.php` | Chat interface HTML/JS with message history, typing indicator, suggestion chips |
| `shop.php` | Upcycle shop with points/cash pricing, auto-seeding, search |
| `admin.php` | Admin dashboard with platform stats, leaderboard, user search |
| `about.php` | Team/pillar info page |
| `login.php` / `register.php` | Authentication forms |

---

## 3. Backend Architecture

The backend is a **PHP 8.2 monolith** organized into:

### Root-Level PHP Files (Entry Points)

| File | Purpose |
|------|---------|
| `chatbot_api.php` | AJAX chatbot endpoint — circuit breaker, state machine, caching, session memory, RAG/fallback orchestration |
| `api_impact.php` | Impact calculation API — gamification data, forecast, percentile rank, monthly analytics |
| `init_db.php` | One-time database initialization/setup |
| `logout.php` | Session destruction and cleanup |
| `edit_profile.php` | User profile editing with image upload |
| `user_request_pickup.php` | Manual pickup request form |
| `user_impact.php` | Detailed impact tracking page |
| `user_recent_activity.php` | User pickup history |
| `purchase.php` | Product purchase flow |
| `agency.php` / `agency_completed.php` | Agency task view and completion |
| `admin_*.php` | Admin panels (pickups, orders, inventory, products, churn, sustainability, docs) |

### `includes/` Directory (Shared Modules)

| File | Purpose |
|------|---------|
| `config.php` | Database connection (PDO), env loader, session helpers, auth guards, points constants |
| `chatbot_context.php` | Builds AI system prompt with user context, category rates, language rules |
| `chatbot_fallback.php` | Intent-based fallback engine — 15 intents with regex scoring + Banglish detection |
| `chatbot_state.php` | Circuit breaker (3-failure/5-min) + multi-turn scheduling state machine |
| `lang.php` | Bilingual dictionary (185+ EN keys mapped to BN translations) |
| `navbar.php` | Navigation bar with role-based menus, dark mode toggle, language switch |
| `impact_card.php` | Interactive impact card component (gamification, charts, comparison, share) |
| `admin_impact.php` | Admin environmental impact dashboard (CO₂, water, energy by category) |
| `auto_assign_v2.php` | AI-powered agency assignment with Flask API POST + SQL fallback |

### Authentication Flow

```
User → login.php → POST credentials → config.php:password_verify() → 
$_SESSION['user_id','role','name'] → redirect(dashboard|admin|agency)
```

- **Password Hashing**: `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- **Roles**: `user`, `agency`, `admin`, `super_admin`
- **Guards**: `requireLogin()`, `requireRole('admin')`, `requireLoginJson()`
- **Session**: PHP native sessions with `session_start()`

---

## 4. AI Service Architecture

Three independently deployable Python Flask microservices:

### 4.1 RAG Service (Port 5000)

```
File: app.py + rag_pipeline.py + ingest.py + reranker.py + verifier.py
```

- **Endpoints**: `GET /health`, `POST /chat`, `POST /upload`, `POST /ingest`, `GET /sources`
- **Vector Store**: ChromaDB persistent client at `chroma_db/`
- **Embedding Model**: `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`
- **LLM**: Google Gemini (if `GEMINI_API_KEY` set) → Pollinations.ai free LLM → deterministic fallback
- **Ingestion**: PDF/DOCX/TXT/CSV/XLSX → text extraction → RecursiveCharacterTextSplitter (1000 chunk, 200 overlap) → embedding → ChromaDB

### 4.2 Impact Service (Port 5003)

```
File: ai-service/impact_api.py + environmental_engine.py + forecast_engine.py + leaderboard_engine.py
```

- **Endpoints**: `GET /health`, `GET /impact?user_id=N`, `GET /forecast?user_id=N`, `GET /platform-stats`, `GET /leaderboard`
- **Emission Factors**: CSV loaded into `emission_factors` MySQL table with South Asia adjusted values
- **Forecast Model**: `impact_model.pkl` (GradientBoostingRegressor) trained on 5000+ synthetic rows
- **Leaderboard**: Eco Score = (CO₂ × 0.5) + (Water × 0.2) + (Energy × 0.2) + (Consistency × 0.1); e-waste multiplier 1.5×

### 4.3 Assignment Service (Port 5005)

```
File: ai-service/assignment_api.py + zone_clustering.py
```

- **Endpoints**: `POST /assign`
- **Scoring**: 5-factor weighted: load_ratio (35%), completion_rate (25%), distance (20%), rating (12%), specialty (8%)
- **Distance**: Haversine formula with 25 km max radius
- **Fallback**: PHP-side SQL `ORDER BY active_pickups ASC` when Flask is unavailable

### Request/Response Flow: Chatbot

```
Browser (chatbot.php)
   │
   ├──► POST AJAX /chatbot_api.php
   │
   ├──► requireLoginJson()
   ├──► ensureChatbotTables() + ensureChatbotStateTables()
   ├──► chatStateHandleFlow() ← State Machine Check
   │       (scheduling flow handled entirely in PHP, no API call)
   │
   ├──► chatbotCacheGet() ← Cache Check (5-min TTL)
   │
   ├──► detectSchedulingIntent() ← Direct State Machine Trigger
   │
   ├──► detectFallbackLanguage() ← Banglish/BN detection
   │
   ├──► [If RAG_ENABLED=true] POST /chat to Flask RAG :5000
   │       ├── detect_language()
   │       ├── retrieve_chunks() → ChromaDB
   │       ├── generate_answer() → Gemini → FreeLLM → fallback
   │       └── return {answer, sources}
   │
   ├──► [Else] Pollinations.ai HTTP POST (llama-3.1-70b)
   │       ├── Circuit Breaker Check
   │       ├── Build prompt with getChatbotSystemPrompt()
   │       ├── circuitBreakerRecordSuccess/Failure
   │       └── On failure → getLocalFallbackResponse()
   │
   ├──► chatbotCacheSet() ← Cache Response
   ├──► chatMessageSave() ← Session Memory
   └──► Return JSON {reply, suggestions, action}
```

---

## 5. RAG Pipeline Flow

```
User Query (EN/BN/Banglish)
       │
       ▼
detect_language()
  ├── Unicode Bengali range check → "bn"
  ├── Banglish keyword matching → "bn"
  └── Default → "en"
       │
       ▼
retrieve_chunks(query, top_k=8)
  ├── Load embedding model (SentenceTransformer)
  ├── Encode query → vector (384-dim)
  ├── ChromaDB similarity search
  ├── Return top-8 chunks with metadata
  └── rerank_chunks() (pass-through currently)
       │
       ▼
build_prompt(query, chunks, language, user_name, points)
  ├── Format context: [Source 1: filename] text
  ├── Add user identity + points
  ├── Language-specific system instructions
  └── Anti-robotic / conversational rules
       │
       ▼
generate_answer()
  ├── Gemini API call (if GEMINI_API_KEY set)
  │     └── gemini-1.5-flash, temp=0.2, max_tokens=900
  ├── Free LLM fallback: Pollinations.ai POST
  │     └── llama-3.1-70b via text.pollinations.ai
  └── Deterministic fallback:
        ├── Greeting shortcut → "Hello {name}! ..."
        ├── Points shortcut → "Your balance is {pts} ..."
        ├── Smart snippet → Best-matching chunk text
        └── Unknown → "I couldn't find specific details on that..."
       │
       ▼
Return {answer, sources, verification}
```

---

## 6. File Processing Pipeline

```
Upload (PDF/DOCX/TXT/CSV/XLSX)
       │
       ▼
File Validation (extension check → SUPPORTED_EXTENSIONS)
       │
       ▼
Text Extraction
  ├── PDF  → PyMuPDF (fitz) per-page extraction
  ├── DOCX → python-docx paragraph extraction
  ├── TXT  → Raw text read
  ├── CSV  → pandas CSV reader
  └── XLSX → pandas Excel reader
       │
       ▼
Cleaning (null bytes, excessive whitespace via regex)
       │
       ▼
Chunking (RecursiveCharacterTextSplitter: 1000 chars, 200 overlap)
       │
       ▼
Embedding (sentence-transformers → 384-dim vector)
       │
       ▼
ChromaDB Storage (collection="recycling")
  └── Metadata: filename, page_number, source_doc, folder_category, file_type
```

---

## 7. Chat Processing Pipeline

```
User Message
       │
       ▼
Circuit Breaker Check
  ├── Is open? consecutive_failures >= 3 AND opened_at < 5 min ago
  │     └── YES → Skip API, use fallback engine
  └── NO → Continue
       │
       ▼
State Machine Check
  ├── Active scheduling flow? → Handle entirely in PHP
  │     (awaiting_category → awaiting_weight → awaiting_date → confirming)
  └── Idle → Continue to API
       │
       ▼
Cache Check (5-min TTL on md5(query + lang))
  ├── Hit → Return cached response
  └── Miss → Continue
       │
       ▼
Direct Triggers (scheduling intent detection)
  ├── Match → Start state machine, return first prompt
  └── No match → Continue
       │
       ▼
RAG (if enabled and Flask available)
  ├── Success → Return answer
  └── Fail → Continue to Pollinations.ai
       │
       ▼
Pollinations.ai (free LLM)
  ├── Success → circuitBreakerRecordSuccess() → Cache → Return
  └── Fail → circuitBreakerRecordFailure() → Continue
       │
       ▼
Fallback Engine (15 intents via regex scoring)
  ├── Best intent match → Return localized response
  └── Generic fallback → "I'm not sure I understood..."
       │
       ▼
Return JSON {reply, suggestions, action}
```

---

## 8. Deployment Architecture

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           PRODUCTION DEPLOYMENT                              │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────┐                        │
│  │  RENDER (Free Tier: Oregon)                     │                        │
│  │                                                 │                        │
│  │  ┌─────────────────────────────────────────┐    │                        │
│  │  │  Docker Container (notun-alo)           │    │                        │
│  │  │  ├── Apache 2.4 (Port 8080)             │    │                        │
│  │  │  ├── PHP 8.2 + extensions               │    │                        │
│  │  │  ├── Python 3.11 + venv                 │    │                        │
│  │  │  ├── Supervisor (process manager)       │    │                        │
│  │  │  └── Flask RAG (optional, memory-heavy) │    │                        │
│  │  └─────────────────────────────────────────┘    │                        │
│  │                                                 │                        │
│  └─────────────────────────────────────────────────┘                        │
│                          │                                                  │
│                          ▼                                                  │
│  ┌─────────────────────────────────────────────────┐                        │
│  │  AIVEN MySQL 8.0 (Free Tier)                    │                        │
│  │  ├── SSL connection                             │                        │
│  │  ├── UTF-8 mb4 encoding                         │                        │
│  │  └── Managed backups                           │                        │
│  └─────────────────────────────────────────────────┘                        │
│                                                                              │
│  ┌─────────────────────────────────────────────────┐ (Optional)             │
│  │  GOOGLE CLOUD RUN                               │                        │
│  │  ├── Flask RAG Service (:5000)                  │                        │
│  │  ├── Auto-scaling, serverless                   │                        │
│  │  └── Dockerfile.flask                           │                        │
│  └─────────────────────────────────────────────────┘                        │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Deployment Components

| Component | Host | Plan | Notes |
|-----------|------|------|-------|
| **Web Server** | Render | Free (Docker) | Single container with Apache + PHP + Python |
| **Database** | Aiven | Free | MySQL 8.0, SSL, auto-backups |
| **RAG Service** | Render/Cloud Run | Free/Paid | Optional; disabled by default on free tier |
| **CI/CD** | GitHub → Render | Auto-deploy | Push to `main` triggers rebuild |

### Key Deployment Files

| File | Purpose |
|------|---------|
| `Dockerfile` | Multi-service container: PHP 8.2-apache + Python venv + Supervisor |
| `Dockerfile.flask` | Standalone Flask container for RAG service |
| `docker-compose.yml` | Local multi-container orchestration (PHP + Flask + MySQL) |
| `render.yaml` | Render deployment config with env vars |
| `cloudbuild.yaml` | Google Cloud Build config |
| `.env.example` | Environment template for local development |

---

## 9. Database Relationships

```
┌──────────────┐       ┌──────────────────┐       ┌──────────────┐
│    users     │       │    pickups        │       │   products   │
├──────────────┤       ├──────────────────┤       ├──────────────┤
│ id (PK)      │◄──────│ user_id (FK)     │       │ id (PK)      │
│ name         │       │ id (PK)          │       │ name         │
│ email        │       │ agency_id (FK)   │──┐    │ points_price │
│ password     │       │ category          │  │    │ cash_price   │
│ role         │       │ estimated_weight  │  │    │ stock        │
│ lat/lng      │       │ schedule_date     │  │    │ image_url    │
│ picture_url  │       │ status (pending/  │  │    │ description  │
│ phone        │       │   assigned/       │  │    │ created_at   │
│ address      │       │   completed)      │  │    └──────┬───────┘
└──────────────┘       │ subcategory       │  │           │
       │               │ created_at        │  │           │
       │               └───────────────────┘  │           │
       │                                      │           │
       ▼                                      │           ▼
┌──────────────┐       ┌───────────────────┐  │  ┌──────────────┐
│   rewards    │       │  assignment_log   │  │  │   orders     │
├──────────────┤       ├───────────────────┤  │  ├──────────────┤
│ user_id (FK) │       │ id (PK)           │  │  │ id (PK)      │
│ total_points │       │ pickup_id (FK)────┘  │  │ user_id (FK) │
│ lifetime_    │       │ agency_id (FK)───────┘  │ product_id(FK)│
│   points     │       │ method (ai/           │  │ payment_type │
│ created_at   │       │   fallback_sql)       │  │ status       │
│ updated_at   │       │ score_total           │  │ agency_id(FK)│
└──────────────┘       │ model_version         │  │ created_at   │
                       │ assigned_at           │  └──────────────┘
                       └───────────────────────┘
                                │
                                ▼
                       ┌──────────────────┐
                       │  agency_stats     │
                       ├──────────────────┤
                       │ agency_id (PK,FK) │
                       │ is_available      │
                       │ load_ratio        │
                       │ active_pickups    │
                       │ completion_rate   │
                       │ avg_rating        │
                       │ total_completed   │
                       └──────────────────┘

┌──────────────────┐  ┌──────────────────┐  ┌──────────────────────┐
│ chatbot_cache    │  │chat_messages     │  │ chatbot_states       │
├──────────────────┤  ├──────────────────┤  ├──────────────────────┤
│ cache_key (UQ)   │  │ id (PK)          │  │ id (PK)              │
│ response_text    │  │ user_id          │  │ user_id              │
│ suggestions (JSON)│  │ session_id       │  │ session_id           │
│ lang             │  │ role (user/      │  │ step (idle/awaiting_ │
│ created_at       │  │   assistant)     │  │   category/weight/   │
└──────────────────┘  │ content          │  │   date/confirming)   │
                      │ created_at       │  │ data (JSON)          │
┌──────────────────┐  └──────────────────┘  │ created_at           │
│ chatbot_circuit  │                        │ updated_at           │
├──────────────────┤  ┌──────────────────┐  └──────────────────────┘
│ id (PK, default1)│  │ notifications   │
│ consecutive_     │  ├──────────────────┤  ┌──────────────────┐
│   failures       │  │ id (PK)          │  │ model_versions   │
│ last_failure_at  │  │ user_id (FK)     │  ├──────────────────┤
│ opened_at        │  │ title            │  │ id (PK)          │
└──────────────────┘  │ message          │  │ version_tag      │
                      │ is_read          │  │ model_type       │
┌──────────────────┐  │ created_at       │  │ trained_on       │
│ emission_factors │  └──────────────────┘  │ is_active        │
├──────────────────┤                        │ metrics (JSON)   │
│ id (PK)          │                        │ trained_at       │
│ category         │  ┌──────────────────┐  └──────────────────┘
│ subcategory      │  │agency_zones      │
│ co2_sa_adjusted  │  ├──────────────────┤
│ water_liters_    │  │ agency_id (FK)   │
│   per_kg         │  │ zone_label       │
│ energy_kwh_per_kg│  │ lat/lng          │
└──────────────────┘  └──────────────────┘
```
