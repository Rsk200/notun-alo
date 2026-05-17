# Deployment Guide

> **Notun Alo (নতুন আলো)** — Smart Recycling Platform  
> Document version: 1.0 | Last updated: May 2026

---

## 1. Environment Requirements

### 1.1 Required Software

| Component | Version | Purpose |
|---|---|---|
| PHP | 8.2+ | Application runtime |
| MySQL | 8.0+ | Database |
| Python | 3.11+ | RAG service & ML scripts |
| Apache | 2.4+ | HTTP server (with mod_rewrite) |
| Composer | Latest | PHP dependency management (optional) |
| Git | Latest | Version control |

### 1.2 Required PHP Extensions
- `gd` — image processing
- `zip` — file compression
- `pdo_mysql` — database connectivity
- `curl` — HTTP requests (Pollinations.ai, RAG service)
- `mbstring` — multilingual string handling (Bengali)

### 1.3 Required Python Packages
Installed via `requirements.txt`:
- Flask & flask-cors (RAG web service)
- sentence-transformers (embedding model)
- chromadb (vector database)
- google-genai (Gemini API)
- pandas, numpy, scikit-learn (ML scripts)
- waitress (production WSGI server)
- gunicorn (Linux WSGI server)

---

## 2. Local Development (XAMPP / WAMP)

### 2.1 Initial Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url> C:\xampp1\htdocs\notun_alo\
   ```

2. **Import the database:**
   ```bash
   mysql -u root -p < database\notun_alo.sql
   ```
   Or use phpMyAdmin to import `database/notun_alo.sql`.

3. **Configure environment:**
   ```bash
   copy .env.example .env
   ```
   Edit `.env` with your local database credentials:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_USER=root
   DB_PASS=
   DB_NAME=notun_alo
   BASE_URL=http://localhost/notun_alo/
   ```

4. **Set up Python virtual environment:**
   ```powershell
   python -m venv .venv
   .\.venv\Scripts\Activate.ps1
   pip install -r requirements.txt
   ```

5. **Set up the RAG service (optional):**
   ```powershell
   # Download sentence-transformers model (one-time)
   python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2')"
   ```

### 2.2 Running Locally

1. **Start Apache + MySQL** via XAMPP Control Panel
2. **Visit the application:**
   ```
   http://localhost/notun_alo/
   ```
3. **Database initialisation:** Visit `/init_db.php` or let the app auto-initialize on first page load
4. **Start the RAG service (optional):**
   ```powershell
   .\start_rag.ps1
   ```
   Starts Flask on `http://localhost:5000`

### 2.3 Local File Structure
```
C:\xampp1\htdocs\notun_alo\
├── includes/          # PHP includes (config, helpers)
├── assets/            # CSS, JS, images
├── database/          # SQL schemas
├── admin/             # Admin panel files
├── services/          # Support services
├── cron/              # Cron job scripts
├── scripts/           # Utility scripts
├── .env               # Environment configuration
├── app.py             # Flask RAG service entry point
├── chatbot_api.php    # Chatbot AJAX endpoint
└── index.php          # Application entry point
```

### 2.4 Python Virtual Environment Notes
- **Activation:** `.venv\Scripts\Activate.ps1` (PowerShell)
- **Offline mode:** `start_rag.ps1` sets `HF_HUB_OFFLINE=1` and `TRANSFORMERS_OFFLINE=1` to use cached models
- **First startup:** Embedding model loading takes 20–40 seconds

---

## 3. Render Production Deployment

### 3.1 Prerequisites
- GitHub account with repository fork/push access
- Render.com account
- Aiven MySQL free tier account (or any MySQL 8.0+ provider)

### 3.2 Deployment Steps

1. **Push code to GitHub:**
   ```bash
   git add .
   git commit -m "Deploy: ..."
   git push origin main
   ```

2. **Create Render Web Service:**
   - Dashboard → New → Web Service
   - Connect your GitHub repository
   - **Runtime:** Docker
   - **Dockerfile Path:** `./Dockerfile`
   - **Port:** 8080
   - **Region:** Oregon (recommended for free tier)

3. **Configure Render Environment Variables:**
   Set these in the Render Dashboard (never commit secrets):
   ```
   DB_HOST=your-aiven-mysql-host.aivencloud.com
   DB_PORT=20764
   DB_USER=avnadmin
   DB_PASS=<from-aiven-console>
   DB_NAME=defaultdb
   DB_SSL=true
   BASE_URL=https://your-app.onrender.com
   RAG_ENABLED=false
   ```

4. **Deploy:**
   - Render auto-deploys on git push to the connected branch
   - Manual deploy: Dashboard → Manual Deploy → Deploy latest commit
   - Deployment takes 2–5 minutes
   - Check logs in Render Dashboard for errors

### 3.3 Aiven MySQL Setup

1. **Create service:**
   - Go to [Aiven Console](https://console.aiven.io/)
   - Create a new MySQL service (free tier available)
   - Select a cloud provider and region

2. **Get connection details:**
   - In Aiven console, go to Service Overview
   - Copy host, port, user, and password
   - Note: SSL is required (`DB_SSL=true`)

3. **Power on the service:**
   - Free tier Aiven services auto-pause after inactivity
   - Manually start the service from the Aiven console when deploying

4. **Configure firewall:**
   - Aiven allows all IPs by default for free tier
   - For production, restrict to Render outbound IPs

### 3.4 Render Health Check
- **Health check path:** `/` (root)
- **Initial delay:** 5 minutes (allows Docker + DB init)
- **Timeout:** 30 seconds
- **Grace period:** 5 failures before marking unhealthy

---

## 4. Docker Deployment

### 4.1 Docker Images

| Image | Dockerfile | Purpose |
|---|---|---|
| `php-app` | `Dockerfile` | PHP 8.2 + Apache + Supervisor |
| `flask-app` | `Dockerfile.flask` | Flask RAG service |
| `rag-app` | `Dockerfile.rag` | RAG with pre-downloaded models |

### 4.2 Main Dockerfile (`Dockerfile`)
- **Base image:** `php:8.2-apache`
- **Extensions installed:** gd, zip, pdo_mysql
- **Python:** 3.11 with venv at `/opt/venv`
- **Port:** 8080 (Render-compatible, configured via `$PORT` env var)
- **Process manager:** Supervisor (manages Apache process)
- **RAG note:** Flask service is NOT started in the main container due to free tier memory constraints. The chatbot works via the Pollinations.ai fallback.

### 4.3 Docker Compose (`docker-compose.yml`)
Three services for local multi-container deployment:

```yaml
services:
  php-app:    # PHP + Apache on port 8080
  flask-app:  # Flask RAG on port 5000
  db:         # MySQL 8.0 on port 3306
```

**Environment variables** are inherited from the host's `.env` file.

**Usage:**
```bash
docker-compose up --build
```

### 4.4 Dockerfile.flask
- **Base image:** `python:3.11-slim`
- **Port:** 5000
- **Mode:** Development (use waitress/gunicorn for production)
- **Startup:** `python app.py`

### 4.5 Dockerfile.rag
- Extends `Dockerfile.flask` with pre-downloaded sentence-transformers model
- Reduces cold-start time by baking the model into the image
- Model: `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`

---

## 5. Git Workflow

### 5.1 Standard Workflow
```bash
# Check status
git status

# Stage changes
git add .

# Commit
git commit -m "description of changes"

# Push to GitHub
git push origin main

# Render auto-deploys (2–5 min)
# Monitor: Render Dashboard → Logs
```

### 5.2 Branch Strategy (Recommended)
- `main` — production branch (auto-deploys to Render)
- `staging` — pre-production testing
- `feature/*` — feature branches
- `hotfix/*` — urgent fixes

### 5.3 .gitignore Highlights
```
.env              # Environment configuration (secrets)
__pycache__/      # Python bytecode
.venv/            # Python virtual environment
chroma_db/        # Vector database files (regenerable)
*.pkl             # ML model files (regenerable)
uploads/          # User uploads
logs/             # Application logs
```

---

## 6. CI/CD Recommendations

### 6.1 GitHub Actions
Recommended workflow for automated testing:
```yaml
# .github/workflows/ci.yml
- PHP syntax check (lint)
- Python test suite
- MySQL schema validation
- Build Docker image
```

### 6.2 Google Cloud Build
For RAG Docker image builds:
```yaml
# cloudbuild.yaml (already exists in project)
- Build Dockerfile.rag
- Push to Google Container Registry
- Deploy to Cloud Run
```

### 6.3 Cron Jobs
For scheduled tasks (Render Cron Jobs or external service):
- Daily database backups
- Weekly ML model retraining
- Hourly cache cleanup
- Daily churn risk scoring

---

## 7. Render.yaml Configuration

```yaml
services:
  - type: web
    name: notun-alo
    runtime: docker
    dockerContext: .
    dockerfilePath: Dockerfile
    plan: free
    region: oregon
    envVars:
      - key: DB_HOST
        value: "mysql-bec79d9-xxx.h.aivencloud.com"
      - key: DB_PORT
        value: "20764"
      - key: DB_USER
        value: "avnadmin"
      - key: DB_PASS
        value: "set-this-in-dashboard"
      - key: DB_NAME
        value: "defaultdb"
      - key: DB_SSL
        value: "true"
      - key: RAG_API_URL
        value: https://notun-alo-rag-xxx-uc.a.run.app
      - key: RAG_ENABLED
        value: "false"
      - key: BASE_URL
        value: https://notun-alo.onrender.com
```
> **Important:** Store sensitive values (DB_PASS, API keys) in Render Dashboard env vars, not in render.yaml. The render.yaml file IS committed to Git.

---

## 8. Troubleshooting Deployment

### 8.1 Aiven DNS Resolution
**Problem:** "getaddrinfo failed" / "Name or service not known"  
**Solution:** Power on the Aiven service in the Aiven console. Free tier auto-pauses. The error message in config.php provides this hint automatically.

### 8.2 Empty Products Table
**Problem:** Shop page shows no products  
**Solution:** `shop.php` auto-seeds default products on first load. If the table exists but is empty, trigger seeding by accessing `/shop.php` or running the seed query manually.

### 8.3 Assets Directory (images/)
**Problem:** Broken image paths  
**Solution:** Use `assets/images/` (not `assets/img/`). An Apache alias issue may cause confusion. Always reference `assets/images/` in templates.

### 8.4 Pollinations.ai Timeouts
**Problem:** Slow/no responses from chatbot  
**Solution:** The circuit breaker auto-opens after 3 consecutive failures, providing instant fallback responses. No manual intervention needed.

### 8.5 Database Migration Issues
**Problem:** Schema mismatch on an existing deployment  
**Solution:** Visit `/init_db.php` which handles incremental fixes:
- Adds AUTO_INCREMENT on ID columns
- Adds foreign key constraints
- Converts tables to `utf8mb4_unicode_ci`
- Adds UNIQUE constraint on `users.email`

### 8.6 SSL Certificate Issues
**Problem:** Failed to connect to Aiven MySQL  
**Solution:** Aiven requires SSL. Set `DB_SSL=true` and optionally `DB_SSL_CA` to a CA certificate path. The application disables server cert verification (`PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = false`) for compatibility.

### 8.7 RAG Service Not Responding
**Problem:** RAG responses return null/error  
**Solution:** 
- Verify `RAG_ENABLED=true` and `RAG_API_URL` points to a running Flask instance
- Check Flask health: `curl http://localhost:5000/health` should return 200
- Ensure Python dependencies are installed in the venv
- The chatbot falls back gracefully — users will still get responses via Pollinations.ai or the local fallback engine
