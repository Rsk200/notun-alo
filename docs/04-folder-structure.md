# Project Folder Structure

Complete tree and explanation of every major directory and file in the Notun Alo platform.

```
C:\xampp1\htdocs\notun_alo\
│
├── .env                          # Environment variables (local config)
├── .env.example                  # Environment template
├── .gitignore                    # Git ignore rules
│
├── index.php                     # Landing page (hero, impact ticker, shop preview)
├── chatbot_api.php               # AJAX chatbot endpoint (circuit breaker, state machine, cache, fallback)
├── chatbot.php                   # Chat interface frontend (HTML/JS)
├── dashboard.php                 # User dashboard (tier system, points, leaderboard, activity)
├── admin.php                     # Admin dashboard (stats, leaderboard, user search)
├── login.php                     # Login form + authentication
├── register.php                  # Registration form
├── logout.php                    # Session destruction
├── edit_profile.php              # Profile editing with picture upload
├── shop.php                      # Upcycle shop with points/cash pricing
├── purchase.php                  # Product purchase flow
├── about.php                     # Team/pillar information page
├── docs.php                      # Documentation viewer
├── docs_setup.php                # Documentation setup utility
├── api_impact.php                # Impact calculation API (gamification, forecast, percentile)
├── agency.php                    # Agency task list
├── agency_completed.php          # Agency completed tasks view
├── user_request_pickup.php       # Manual pickup request form
├── user_impact.php               # Detailed impact tracking page
├── user_recent_activity.php      # User pickup history
│
├── admin_add_product.php         # Add new product to shop
├── admin_edit_product.php        # Edit existing product
├── admin_inventory.php           # Product inventory management
├── admin_orders.php              # Order management with agency assignment
├── admin_pickups.php             # Pickup request management
├── admin_impact.php              # Admin sustainability dashboard
├── admin_sustainability.php      # Environmental sustainability report
├── admin_churn_table.php         # Churn prediction data table
├── admin_docs.php                # Document management
│
├── ai_chat.html                  # Standalone AI chat HTML (testing)
├── test_ai.php                   # AI testing utility
├── index.html                    # Fallback landing page
├── init_db.php                   # Database initialization
│
├── app.py                        # Flask RAG service entry point (port 5000)
├── rag_pipeline.py               # Core RAG logic (retrieval, prompt building, answer generation)
├── ingest.py                     # Document ingestion pipeline (PDF/DOCX/TXT/CSV/XLSX → ChromaDB)
├── reranker.py                   # Chunk reranker (pass-through placeholder)
├── verifier.py                   # Answer verification against source chunks
├── score_users.py                # ML churn scoring script
├── train_notun_alo_churn.py      # Churn model training (XGBoost + RandomForest)
├── train_impact_model.py         # Impact forecast model training (GradientBoostingRegressor)
├── forecast_impact.py            # User-level impact forecasting
├── impact_api.py                 # Standalone impact API (root-level, port 5003 alt)
├── impact_utils.py               # Impact calculation utilities (factors, formulas)
├── requirements.txt              # Python dependencies (RAG service)
│
├── Dockerfile                    # Multi-service container (PHP 8.2 + Apache + Python + Supervisor)
├── Dockerfile.flask              # Standalone Flask container for RAG
├── Dockerfile.rag                # Alternative RAG Dockerfile
├── docker-compose.yml            # Multi-container orchestration (PHP + Flask + MySQL)
├── render.yaml                   # Render deployment configuration
├── cloudbuild.yaml               # Google Cloud Build config
│
├── README.md                     # Project README
├── clean_merged_notun_alo.sql    # Merged SQL dump
├── emission_factors_expanded.csv # Expanded emission factors CSV
├── emission_factors.sql          # Emission factors SQL import
├── feature_query.sql             # Feature engineering SQL for churn model
├── migrations_ai.sql             # AI-related DB migrations
├── add_category.sql              # Category addition migration
│
├── notun_alo_churn_model.pkl     # Trained churn prediction model
├── impact_model.pkl              # Trained impact forecast model
├── churn_correlation_heatmap.png # Feature correlation visualization
├── feature_importance_top10.png  # Feature importance visualization
│
├── E Commerce Dataset.xlsx       # Training data for churn model
├── WhatsApp Image 2026-05-15...  # Project asset
│
├── includes/                     # Shared PHP modules
├── admin/                        # Admin-specific pages
├── ai-service/                   # Python microservices (Impact, Assignment)
├── assets/                       # CSS, JS, images
├── chroma_db/                    # ChromaDB vector store files
├── config/                       # Configuration files
├── cron/                         # Scheduled task scripts
├── database/                     # SQL dumps and migrations
├── docs/                         # Documentation (this)
├── frontend/                     # Frontend entry
├── logs/                         # Application logs
├── "Phase 1 (RAG)"/              # Knowledge base documents
├── scripts/                      # Automation scripts
├── tests/                        # Test files
├── uploads/                      # User uploads and profile pictures
├── utils/                        # Utility scripts
├── scratch/                      # Development/test scripts
├── services/                     # Service configuration
├── .sessions/                    # PHP session files (local)
├── .venv/                        # Python virtual environment
├── .matplotlib/                  # Matplotlib cache directory
│
├── ai_chat.html.rag_backup      # Backup of chatbot files
├── chatbot_api.php.rag_backup
├── chatbot.php.rag_backup
├── logout.php.chat_history_backup
└── temp_user_nav.html           # Temporary navigation
```

---

## Root Files — Detailed

### Entry Points & Pages

#### `index.php` — Landing Page
The public-facing landing page. If the user is logged in, redirects to their role-based home (`dashboard.php`, `admin.php`, or `agency.php`). Otherwise renders a hero section with:
- Impact ticker showing total platform-wide points earned
- Product preview grid from the upcycle shop
- Language toggle (EN/BN) and call-to-action buttons
- Uses `assets/css/style.css` and Google Fonts (Playfair Display, DM Sans)

#### `chatbot_api.php` — AI Chatbot AJAX Endpoint
The core AI orchestration file (696 lines). Handles:
- **Auth guard**: `requireLoginJson()` — returns 401 JSON if not logged in
- **Table initialization**: Creates `chatbot_cache`, `chat_messages` if missing
- **Caching**: 5-minute TTL cache on `md5(query + lang)` key via `chatbot_cache` table
- **Circuit breaker**: `chatbot_circuit` table tracks consecutive failures; opens for 5 min after 3
- **State machine**: Multi-turn scheduling flow handled entirely in PHP (no API calls)
- **Session memory**: Stores conversation history in `chat_messages` table
- **Pollinations.ai**: HTTP POST to `text.pollinations.ai` with system prompt from `chatbot_context.php`
- **Fallback**: If Pollinations fails, calls `getLocalFallbackResponse()` from `chatbot_fallback.php`
- **Action JSON**: Can return `{"action": "schedule_pickup", ...}` for frontend handling
- **Suggestion chips**: Returns contextual suggestions like "Check my points", "Schedule a pickup"

#### `chatbot.php` — Chat Interface
Frontend HTML/JS for the chatbot. Features:
- Message bubble UI with user/assistant styling
- Typing indicator animation
- Suggestion chip buttons
- Language-aware responses
- Session-based conversation memory display

#### `dashboard.php` — User Dashboard
The main user landing page after login. Displays:
- **Greeting**: Personalized welcome with user name
- **Tier system**: Bronze (0-499), Silver (500-1499), Gold (1500-4999), Platinum (5000+) with progress bar
- **Stats cards**: Points balance, completed pickups, total kg recycled (CO₂ equivalent)
- **Leaderboard**: Top 10 recyclers by lifetime points
- **Recent activity**: Last 5 pickups with status and date
- **Quick actions**: Schedule pickup, visit shop, AI assistant

#### `admin.php` — Admin Dashboard
Admin landing page showing:
- **Platform stats**: Total waste collected (kg), total pickups, registered users, products in shop
- **Top 10 leaderboard**: Email search filter
- **Agency list**: Quick overview of all registered agencies

#### `login.php` / `register.php` — Authentication
Standard login/register forms with:
- Password hashing via `password_hash()` (bcrypt)
- Session-based auth with role assignment
- Flash messages for success/error feedback
- Redirect to role-appropriate dashboard on success

#### `shop.php` — Upcycle Shop
Product listing page with:
- Grid display with product images, names, points + cash prices
- Search by product name
- Auto-seeding of default products if none exist
- Purchase link to `purchase.php`

#### `about.php` — About Page
Team and pillar information with:
- Mission/vision statements
- Team member cards
- Core values and platform pillars

#### `api_impact.php` — Impact Calculation API
JSON API (369 lines) serving:
- **`action=impact`**: Gamification data (level, XP, CO₂/water/energy saved, car trip equivalents)
- **`action=forecast`**: 90-day forecast data for Chart.js line chart
- **`action=percentile_rank`**: City-level percentile ranking with animated banner
- **`action=monthly`**: Monthly CO₂ breakdown by category for bar chart

### AI/ML Scripts

#### `app.py` — Flask RAG Service
Flask web application (port 5000) with endpoints:
- `GET /health` — Health check with collection info
- `POST /chat` — RAG query endpoint, calls `answer_query()` from `rag_pipeline.py`
- `POST /upload` — File upload and ingestion
- `POST /ingest` — Trigger document ingestion (optionally rebuild)
- `GET /sources` — View latest retrieved chunks

#### `rag_pipeline.py` — RAG Logic
Core RAG implementation (265 lines):
- `get_embedding_model()` — Lazy-loads SentenceTransformer (singleton)
- `get_collection()` — Lazy-loads ChromaDB collection
- `detect_language()` — Unicode Bengali range + Banglish keyword matching
- `retrieve_chunks()` — Embed query → ChromaDB similarity search → rerank
- `build_prompt()` — Constructs LLM prompt with context + user info + rules
- `generate_answer()` — Gemini → Pollinations.ai → deterministic fallback
- `answer_query()` — Full pipeline entry point
- `call_free_llm()` — HTTP POST to Pollinations.ai OpenAI-compatible endpoint

#### `ingest.py` — Document Ingestion
File processing pipeline (275 lines):
- Supports PDF (PyMuPDF), DOCX (python-docx), TXT, CSV, XLSX (pandas/openpyxl)
- Chunking via `RecursiveCharacterTextSplitter` (1000 chars, 200 overlap)
- Cleaning via regex (null bytes, excessive whitespace)
- Embedding + storage in ChromaDB `recycling` collection
- SHA-256 deduplication to avoid re-indexing identical files

#### `score_users.py` — Churn Scoring
Loads `notun_alo_churn_model.pkl`, fetches user features via SQL (`feature_query.sql`), and writes churn probability scores back to the database with risk labels (high > 0.70, medium ≥ 0.40, low < 0.40).

#### `train_notun_alo_churn.py` — Churn Model Training
Trains a hybrid XGBoost/RandomForest classifier on the e-commerce dataset (350 lines):
- Data cleaning, feature engineering, train/test split
- ColumnTransformer with OneHotEncoder + StandardScaler
- Model evaluation: accuracy, precision, recall, F1, ROC-AUC
- Outputs confusion matrix, feature importance plot, correlation heatmap
- Saves pipeline + feature columns to `notun_alo_churn_model.pkl`

#### `train_impact_model.py` — Impact Forecast Training
Trains a `GradientBoostingRegressor` on synthetic data from emission factors:
- Generates 5000+ synthetic rows with category weighting, seasonality, growth trend
- Saves to `impact_model.pkl` with StandardScaler

#### `forecast_impact.py` — Impact Forecasting
User-level forecasting using LinearRegression:
- Fetches 6 months of user pickup history from MySQL
- Cold-start fallback uses synthetic training data
- Returns 3-month forecast of CO₂, water, and energy savings

### Infrastructure

#### `Dockerfile` — Main Container
Multi-service Docker image based on `php:8.2-apache`:
- Installs PHP extensions: gd, zip, pdo, pdo_mysql
- Creates Python venv at `/opt/venv`
- Installs Python dependencies from `requirements.txt`
- Configures Apache to bind to Render's `$PORT` (default 8080)
- Sets up `supervisord` to run Apache in foreground
- Flask RAG is **disabled** on free tier (memory constraint)

#### `Dockerfile.flask` — Flask Container
Lightweight Python 3.11 image:
- Copies requirements + application code
- Runs Flask on configurable `$PORT` (default 5000)
- Used for Cloud Run deployment or separate Render service

#### `docker-compose.yml` — Local Orchestration
Three services:
- `php-app`: PHP/Apache on port 8080
- `flask-app`: Flask RAG on port 5000
- `db`: MySQL 8.0 on port 3306 with named volume

#### `render.yaml` — Render Deployment
Single web service configuration:
- Docker runtime, free plan, Oregon region
- Aiven MySQL environment variables
- Optional Cloud Run RAG URL
- RAG disabled by default

---

## `includes/` Directory — Shared PHP Modules

| File | Lines | Purpose |
|------|-------|---------|
| `config.php` | 214 | Core configuration: env loader, PDO connection, helper functions (startSession, redirect, requireLogin, getCurrentUser, getUserPoints), points constants, pickup category safeguard |
| `chatbot_context.php` | 118 | AI system prompt builder — generates dynamic prompt with user name, points, category rates, language rules, scheduling instructions |
| `chatbot_fallback.php` | 319 | Intent-based fallback engine — detects 15 intent categories via regex scoring (greeting, identity, points, impact, schedule, pickup_status, guide, materials, farewell, thanks, contact, complaint, hours, location, help) + Banglish language detection |
| `chatbot_state.php` | 232 | Circuit breaker (3-failure/5-min cooldown) + multi-turn scheduling state machine (awaiting_category → awaiting_weight → awaiting_date → confirming) with Bengali/English responses |
| `lang.php` | 338 | Bilingual dictionary — 185+ translation keys mapping English to Bengali; number conversion (en2bn); session-based language toggle via `?lang=bn` or `?lang=en` |
| `navbar.php` | 636 | Responsive navigation bar with role-based menus (user/agency/admin), language switch, dark mode toggle with localStorage persistence, scroll-hide on mobile, Font Awesome icons |
| `impact_card.php` | 707 | Interactive impact card component — gamification hero (level, XP bar, next tier), CO₂ metric, water/energy supporting metrics, AI insight callout, tabbed analytics (90-day forecast chart + monthly bar chart), city comparison bars, percentile rank banner, share progress image generator |
| `admin_impact.php` | 37 | Admin environmental impact dashboard — aggregate CO₂/water/energy stats, per-category breakdown table with e-waste high-impact badge, dark mode overrides |
| `auto_assign_v2.php` | 146 | AI-powered agency assignment — POSTs pickup data to Flask Assignment API (port 5005) with 3-second timeout; falls back to SQL `ORDER BY active_pickups ASC` if API fails; transactional assignment with audit log + notifications |

---

## `admin/` Directory — Admin Pages

| File | Purpose |
|------|---------|
| `assignment_intelligence.php` | AI transparency dashboard — shows assignment statistics, recent assignments with scores, agency leaderboard, model version info |
| `retrain_trigger.php` | Manual ML model retraining trigger for admins |

---

## `ai-service/` Directory — Python Microservices

### Impact Service

| File | Purpose |
|------|---------|
| `impact_api.py` | Flask app (port 5003) — endpoints: `/health`, `/impact?user_id=N`, `/forecast?user_id=N`, `/platform-stats`, `/leaderboard`; auto-schema repair on startup |
| `environmental_engine.py` | Core calculation logic — `calculate_environmental_impact()` uses emission factors for CO₂, water, energy; car trip and phone charge equivalents |
| `forecast_engine.py` | Time-series forecasting — extrapolates user history into 3-month projections |
| `leaderboard_engine.py` | Leaderboard computation — eco score formula with consistency modifier, e-waste 1.5× multiplier, badge assignment |
| `train_forecast_model.py` | Trains forecast predictor on synthetic data |
| `train_predictor.py` | Alternative predictor training script |
| `zone_clustering.py` | Service area zone clustering for geographic assignment |
| `validate_environmental_data.py` | Data validation — checks schema, duplicates, missing values, category averages |
| `cli_impact.py` | Command-line impact calculation tool |
| `db_utils.py` | Database utility functions |

### Assignment Service

| File | Purpose |
|------|---------|
| `assignment_api.py` | Flask app (port 5005) — `POST /assign` with 5-factor scoring (load 35%, completion 25%, distance 20%, rating 12%, specialty 8%); haversine distance calculation; optional ML completion predictor |
| `zone_clustering.py` | Geographic zone clustering for smarter agency assignment |

### Placeholders (Future)

| File | Purpose |
|------|---------|
| `placeholders/waste_classifier.py` | Future AI waste classification module (placeholder) |
| `placeholders/nasa_validator.py` | Future NASA satellite data validator (placeholder) |

### Supporting Files

| File | Purpose |
|------|---------|
| `requirements.txt` | Python dependencies (flask, scikit-learn, pandas, etc.) |
| `emission_factors.sql` | Emission factors SQL import |
| `impact_queries.sql` | Reference SQL queries for impact calculations |
| `dashboard_chart.js` | Impact dashboard chart configuration |
| `.env.example` | Environment template for AI service |
| `impact_model.pkl` | Trained forecast model |
| `logs/` | Service logs |
| `models/` | ML model storage |
| `data/` | Data files |
| `tests/` | Test files |
| `README.md` | Service documentation |

---

## `assets/` Directory — Static Resources

| File/Dir | Purpose |
|----------|---------|
| `css/style.css` | Main stylesheet — dark mode variables, landing page, dashboard, forms, tables |
| `css/docs.css` | Documentation page styling |
| `css/leaderboard.css` | Leaderboard-specific styling |
| `css/sortable-table.css` | Sortable table styles |
| `js/animations.js` | Frontend animations (count-up, transitions) |
| `js/sortable-table.js` | Table sorting functionality |
| `images/NA_logo.png` | Notun Alo logo |
| `images/auth-bg-wa.jpg` | Authentication background |
| `img/auth-bg.png` | Alternative auth background |
| `img/auth-bg-final.png` | Final auth background |

---

## `chroma_db/` — Vector Database

Persistent ChromaDB storage directory containing:
- HNSW index files for the `recycling` collection
- Document embeddings (384-dim from sentence-transformers)
- Metadata storage (filename, page, source, category)
- Created/updated by `ingest.py` and queried by `rag_pipeline.py`

---

## `Phase 1 (RAG)/` — Knowledge Base

27+ source documents organized into subdirectories:

| Subdirectory | Contents |
|-------------|----------|
| `Bangladesh waste and recycling context/` | Academic papers on Bangladesh waste management (4 PDFs + 1 TXT) |
| `environmental laws and ngo documents/` | Environmental regulations and NGO reports (2 PDFs) |
| `paper/` | Paper recycling guides and handbooks (4 PDFs) |
| `Plastic recycling guides/` | Plastic recycling best practices (4 PDFs) |
| `Waste Report/` | Waste management reports and Wikipedia articles (5 PDFs + 1 TXT) |
| Root | FAQ text file, chatbot Q&A PDFs, CSV/XLSX datasets (plastic waste, e-waste, material consumption) |

---

## `cron/` — Scheduled Tasks

| File | Schedule | Purpose |
|------|----------|---------|
| `reassign_pending.php` | Every 10 min | Finds pickups pending >10 min and auto-assigns via AI or SQL fallback |
| `retrain_model.php` | Weekly (Sun 2 AM) | Triggers Python model retraining, requires 10+ completed pickups to proceed |

---

## `database/` — SQL Files

| File | Purpose |
|------|---------|
| `notun_alo.sql` | Full database dump with schema + seed data (users, pickups, products, orders, etc.) |
| `alter_pickups_category_to_varchar.sql` | Migration to convert ENUM category to VARCHAR(50) for extensibility |

---

## `logs/` — Application Logs

| File | Purpose |
|------|---------|
| `flask_err.log` | Flask RAG service error log |
| `flask_rag_err.log` | RAG-specific error log |
| `flask_rag_out.log` | RAG service stdout |
| `rag.log` | Detailed RAG pipeline logging (rotating, 1MB each, 5 backups) |

---

## `uploads/` — User Uploads

| Path | Purpose |
|------|---------|
| `profile_pictures/` | User-uploaded profile photos |
| `rag_test_note.txt` | Test upload for RAG ingestion |

---

## `tests/` — Test Files

| File | Purpose |
|------|---------|
| `test_pipeline.py` | RAG pipeline unit tests (pytest) |

---

## `scratch/` — Development Scripts

| File | Purpose |
|------|---------|
| `test_api.py` | API endpoint testing |
| `test_chroma.py` | ChromaDB connection/query testing |
| `test_env.py` | Environment variable testing |
| `test_model.py` | ML model loading/testing |
| `test_new_knowledge.py` | Knowledge base testing |
| `test_retrieval.py` | RAG retrieval testing |

---

## `scripts/` — Automation

| File | Purpose |
|------|---------|
| `run_automated_checks.bat` | Windows batch file for automated checks |
| `run_automated_checks.ps1` | PowerShell script for automated checks |

---

## Additional Root Directories

| Directory | Purpose |
|-----------|---------|
| `config/` | Configuration files (currently holds `.gitkeep`) |
| `docs/` | Project documentation (this directory) |
| `frontend/` | Frontend entry point (`index.php`) |
| `services/` | Service configuration (currently holds `.gitkeep`) |
| `utils/` | Utility scripts (currently holds `.gitkeep`) |
| `frontend/` | Alternative frontend entry |
