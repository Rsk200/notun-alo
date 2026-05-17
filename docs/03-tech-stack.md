# Technology Stack

Detailed inventory of every technology, library, and tool used in the Notun Alo platform.

---

## Core Technologies

| Technology | Version | Purpose | Why Chosen | Integration |
|-----------|---------|---------|------------|-------------|
| **PHP** | 8.2 | Backend + Server-rendered frontend | LAMP standard, mature session management, PDO for DB access | All business logic, authentication, DB CRUD, server-side HTML rendering |
| **Apache** | 2.4 | Web server | Docker official `php:8.2-apache` image, mod_php, URL rewriting | Serves all PHP/HTML/CSS/JS assets; configured to bind to Render's `$PORT` |
| **MySQL** | 8.0 | Primary relational database | InnoDB for ACID compliance, JSON column support, foreign key constraints, full UTF-8 mb4 | PDO across all PHP; `mysql.connector` from Python services; Aiven cloud managed |
| **Python** | 3.11 | AI/ML microservices | Rich ecosystem for NLP, ML, and vector DBs | Flask apps for RAG, Impact, and Assignment services; cron-triggered ML scripts |
| **Flask** | 3.0.3 | Python web framework | Lightweight, CORS support, simple JSON API development, low memory footprint | `app.py` (RAG), `ai-service/impact_api.py`, `ai-service/assignment_api.py` |

---

## AI & Machine Learning

| Technology | Version | Purpose | Why Chosen | Integration |
|-----------|---------|---------|------------|-------------|
| **ChromaDB** | 0.5.5 | Vector database | Persistent/local, no cloud dependency, simple Python API, efficient HNSW indexing | Stores document embeddings at `chroma_db/`; queried by `rag_pipeline.py` for similarity search |
| **sentence-transformers** | 3.0.1 | Multilingual embeddings | Model `paraphrase-multilingual-MiniLM-L12-v2` — 470MB, supports 50+ languages | Generates 384-dim embeddings for both query and documents in `ingest.py` and `rag_pipeline.py` |
| **Google Gemini AI** | `gemini-1.5-flash` | Primary LLM (optional) | Free tier available, strong Bengali language support, 1M token context | RAG answer generation via `google-generativeai` SDK; temperature=0.2, max_tokens=900 |
| **Pollinations.ai** | `llama-3.1-70b` | Free LLM fallback | Completely free, no API key required, OpenAI-compatible endpoint | `call_free_llm()` in `rag_pipeline.py` and direct HTTP POST from `chatbot_api.php` |
| **scikit-learn** | ≥1.5.0 | ML models | `LinearRegression`, `GradientBoostingRegressor`, `RandomForestClassifier`, `train_test_split`, metrics | Churn prediction model, impact forecast model, model evaluation |
| **XGBoost** | (via pip) | Gradient boosted trees | Superior performance on tabular data, handles missing values | Hybrid classifier in `train_notun_alo_churn.py` alongside RandomForest |
| **joblib** | ≥1.4.2 | Model serialization | Efficient `.pkl` persistence for scikit-learn pipelines | `notun_alo_churn_model.pkl`, `impact_model.pkl`, `ai-service/models/*.pkl` |

---

## PHP Backend Stack

| Component | Version | Purpose | Integration |
|-----------|---------|---------|-------------|
| **PDO** | (built-in) | Database abstraction | All DB queries across the entire PHP codebase; prepared statements prevent SQL injection |
| **php-mysql** | (ext) | MySQL driver for PDO | `pdo_mysql` extension; charset `utf8mb4` |
| **php-gd** | (ext) | Image processing | Profile picture resizing/manipulation |
| **php-zip** | (ext) | ZIP archive support | File upload handling |
| **php-curl** | (ext) | HTTP client | `chatbot_api.php` → Pollinations.ai; `includes/auto_assign_v2.php` → Flask Assignment API |
| **password_hash** | (built-in) | Password hashing | `PASSWORD_DEFAULT` (bcrypt) for user authentication |
| **Session** | (built-in) | State management | `$_SESSION` for auth, language preference, user state across requests |

### PHP Extensions (Dockerfile)

```dockerfile
docker-php-ext-install gd zip pdo pdo_mysql
# Also installs via apt: libpng-dev, libjpeg62-turbo-dev, libfreetype6-dev, libzip-dev
```

---

## Python Backend Stack

| Library | Version | Purpose | Integration |
|---------|---------|---------|-------------|
| **flask-cors** | 4.0.1 | Cross-Origin Resource Sharing | All Flask apps enable CORS for AJAX from PHP frontend |
| **python-dotenv** | 1.0.1 | Environment variable loading | `app.py`, `rag_pipeline.py`, `ingest.py`, `impact_api.py`, `assignment_api.py` |
| **google-generativeai** | 0.7.2 | Gemini API client | `rag_pipeline.py:generate_answer()` — primary LLM for RAG |
| **PyMuPDF (fitz)** | ≥1.26.0 | PDF text extraction | `ingest.py` — extracts text per-page from PDF documents |
| **python-docx** | 1.1.2 | DOCX text extraction | `ingest.py` — reads Word documents |
| **pandas** | ≥2.3.0 | Data analysis | `ingest.py` (CSV/XLSX), `score_users.py` (feature engineering), `impact_utils.py` |
| **openpyxl** | 3.1.5 | Excel file support | `ingest.py` — XLSX file reading |
| **langchain-text-splitters** | 0.2.2 | Document chunking | `ingest.py` — `RecursiveCharacterTextSplitter` (1000 chunk, 200 overlap) |
| **requests** | (stdlib) | HTTP client | `rag_pipeline.py` — Pollinations.ai free LLM calls |
| **mysql-connector-python** | ≥9.0.0 | MySQL from Python | `score_users.py`, `impact_api.py`, `assignment_api.py` — direct DB access |
| **reportlab** | 4.2.2 | PDF generation | Report/document generation |
| **pytest** | 8.3.2 | Testing | `tests/test_pipeline.py` — RAG pipeline tests |
| **matplotlib** | (via pip) | Visualization | `train_notun_alo_churn.py` — feature importance plots |
| **seaborn** | (via pip) | Statistical visualization | `train_notun_alo_churn.py` — correlation heatmaps |
| **numpy** | (via pip) | Numerical computing | Impact calculations, array operations |

---

## Frontend Stack

| Library | Version | Purpose | Integration |
|---------|---------|---------|-------------|
| **Font Awesome** | 6.5.1 | UI icons | CDN link in `includes/navbar.php`; used across all pages for navigation and actions |
| **Tabler Icons** | (CDN) | SVG icons | `includes/impact_card.php` — environmental impact icons |
| **Google Fonts** | (CDN) | Typography | Poppins (primary), Playfair Display (headings), DM Sans (alternate) |
| **Chart.js** | (CDN) | Charts & graphs | `includes/impact_card.php` — forecast line chart, monthly bar chart |
| **html2canvas** | 1.4.1 | Screenshot capture | `includes/impact_card.php` — share progress image generation |
| **Vanilla JS** | ES6 | Client logic | DOM manipulation, AJAX fetch, dark mode toggle, tab switching, count-up animations |

---

## Infrastructure & DevOps

| Technology | Version | Purpose | Integration |
|-----------|---------|---------|-------------|
| **Docker** | 24+ | Containerization | `Dockerfile` (PHP/Apache + Python), `Dockerfile.flask` (standalone Flask), `docker-compose.yml` (multi-service) |
| **Supervisor** | (apt) | Process manager | `Dockerfile` — runs Apache in foreground inside container via `supervisord` |
| **Render** | (cloud) | Cloud hosting | Free tier Docker deployment; `render.yaml` config with env vars |
| **Aiven** | (cloud) | Managed MySQL | Free tier MySQL 8.0 with SSL, automatic backups, daily point-in-time recovery |
| **Google Cloud Run** | (cloud) | Serverless containers | Optional deployment for Flask RAG service using `Dockerfile.flask` |
| **Git** | (SCM) | Version control | Git-based CI/CD; auto-deploy to Render on push to `main` |

---

## Development & Testing

| Tool | Purpose | Integration |
|------|---------|-------------|
| **XAMPP** | Local development environment | Apache + MySQL + PHP for Windows development |
| **phpMyAdmin** | Database management | Local MySQL admin UI |
| **Python venv** | Virtual environment | `.venv/` — isolates Python dependencies per project |
| **pytest** | Python testing | `python -m pytest tests/` — RAG pipeline tests |
| **PowerShell scripts** | Automation | `start_rag.ps1`, `deploy_to_cloudrun.ps1`, `enable_apis.ps1`, `run_automated_checks.ps1` |
| **Cron jobs** | Scheduled tasks | `cron/reassign_pending.php` (every 10 min), `cron/retrain_model.php` (weekly) |

---

## Data & Knowledge Base

| Asset | Format | Count | Purpose |
|-------|--------|-------|---------|
| **Knowledge Base** | PDF, TXT, CSV, XLSX, DOCX | 27+ files | Source documents for RAG chatbot covering Bangladesh waste context, recycling guides, environmental laws, statistics |
| **Emission Factors** | CSV (MySQL) | 30+ rows | CO₂, water, and energy factors per material category/subcategory with South Asia adjustment |
| **E-commerce Dataset** | XLSX | 1 file | Training data for churn prediction ML model (customer features + churn labels) |
| **SQL Dump** | `.sql` | 2 files | `database/notun_alo.sql` (full schema + seed data), `ai-service/emission_factors.sql` (impact factors) |

---

## Complete Requirements Files

### `requirements.txt` (Root — RAG Service)

```
flask==3.0.3
flask-cors==4.0.1
python-dotenv==1.0.1
chromadb==0.5.5
sentence-transformers==3.0.1
langchain-text-splitters==0.2.2
google-generativeai==0.7.2
PyMuPDF>=1.26.0
python-docx==1.1.2
pandas>=2.3.0
openpyxl==3.1.5
pytest==8.3.2
reportlab==4.2.2
scikit-learn>=1.5.0
joblib>=1.4.2
mysql-connector-python>=9.0.0
```

### `ai-service/requirements.txt` (Impact Service)

```
flask
flask-cors
mysql-connector-python
scikit-learn
pandas
numpy
joblib
matplotlib
```

---

## Environment Variables (`.env`)

```env
# ── Database (Local XAMPP) ──
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=notun_alo

# ── Gemini AI for RAG Chatbot ──
# GEMINI_API_KEY=your_actual_key_here
GEMINI_MODEL=gemini-1.5-flash

# ── Flask RAG Service URL ──
RAG_API_URL=http://localhost:5000
RAG_ENABLED=true

# ── Public facing URL ──
BASE_URL=http://localhost/notun_alo/
```

### Production Environment (Render Dashboard)

| Variable | Value | Notes |
|----------|-------|-------|
| `DB_HOST` | Aiven hostname | e.g., `mysql-bec79d9-xxx.aivencloud.com` |
| `DB_PORT` | Aiven port | e.g., `20764` |
| `DB_USER` | `avnadmin` | Aiven admin user |
| `DB_PASS` | (secret) | Set in Render Dashboard |
| `DB_NAME` | `defaultdb` | Aiven default database |
| `DB_SSL` | `true` | SSL required for Aiven |
| `RAG_API_URL` | Cloud Run URL | Optional RAG service endpoint |
| `RAG_ENABLED` | `false` | Disabled by default on free tier |
| `BASE_URL` | `https://notun-alo.onrender.com` | Production URL |

---

## Database: MySQL 8.0 Tables

| Table | Engine | Rows (est.) | Purpose |
|-------|--------|-------------|---------|
| `users` | InnoDB | ~50 | User accounts with roles, location, profile |
| `pickups` | InnoDB | ~200 | Recycling pickup requests with status lifecycle |
| `products` | InnoDB | ~20 | Upcycle shop products |
| `orders` | InnoDB | ~50 | Product purchase orders |
| `rewards` | InnoDB | ~50 | User points balances |
| `chatbot_cache` | InnoDB | ~500 | AI response cache (5-min TTL) |
| `chat_messages` | InnoDB | ~2000 | Chat session history |
| `chatbot_states` | InnoDB | ~20 | Multi-turn scheduling state machine |
| `chatbot_circuit` | InnoDB | 1 | Circuit breaker state singleton |
| `assignment_log` | InnoDB | ~100 | AI assignment audit trail |
| `assignment_scores` | InnoDB | ~100 | Per-agency scoring breakdown |
| `agency_stats` | InnoDB | ~10 | Agency performance view |
| `agency_zones` | InnoDB | ~10 | Service area zones |
| `model_versions` | InnoDB | ~10 | ML model version tracking |
| `notifications` | InnoDB | ~500 | In-app notifications |
| `emission_factors` | InnoDB | ~30 | Environmental impact factors |
| `category_averages` | (view) | ~5 | Fallback avg emission values |
