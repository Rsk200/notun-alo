# ♻️ Notun Alo (নতুন আলো) — Bangladesh's Community Recycling Platform

**Notun Alo** ("New Light") is a full-stack community recycling platform connecting households in Bangladesh with collection agencies. It features an AI-powered bilingual chatbot (Bengali/English/Banglish), RAG knowledge base, reward points economy, environmental impact tracking, ML-based churn prediction, and smart agency assignment.

## 🚀 Features

- **🤖 AI Chatbot** — Bilingual (EN/BN/Banglish) assistant with intent-based fallback, circuit breaker for reliability, multi-turn scheduling state machine
- **📚 RAG Knowledge Base** — 27 documents indexed in ChromaDB, multilingual embeddings, Gemini AI integration
- **💰 Reward Points** — Earn points per kg recycled (Paper 15, Plastic 20, Metal 30), redeem in Upcycle Shop
- **🌍 Environmental Impact** — Track CO₂ saved, water saved, energy saved with gamification (10 ranks)
- **📊 ML Predictions** — Churn risk scoring, impact forecasting, smart agency assignment
- **🏪 Upcycle Shop** — Redeem points for eco-friendly products
- **👥 Agency Portal** — Task management, collection tracking, performance scoring
- **📱 Responsive Design** — Mobile-first, dark mode, bilingual toggle

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.2, Apache, MySQL 8.0 |
| AI / ML | Python 3.11, Flask, ChromaDB, sentence-transformers |
| LLM | Google Gemini AI, Pollinations.ai (fallback) |
| Frontend | HTML5, CSS3, JavaScript, Tabler Icons |
| ML Models | scikit-learn (churn prediction, impact forecasting) |
| Deployment | Docker, Render, Aiven MySQL, Google Cloud Run |

## 📋 Prerequisites

- PHP 8.2+ (extensions: gd, zip, pdo_mysql, curl)
- MySQL 8.0+
- Python 3.11+
- Apache with mod_rewrite (or nginx)
- Git

## 🔧 Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/your-org/notun-alo.git
cd notun-alo

# 2. Import the database
mysql -u root -p notun_alo < database/notun_alo.sql

# 3. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Start Apache and MySQL (e.g., via XAMPP)

# 5. Initialize database (optional — visit in browser)
# Open http://localhost/notun_alo/init_db.php

# 6. (Optional) Start the RAG service
pip install -r requirements.txt
python ingest.py
python app.py

# 7. Visit the platform
# http://localhost/notun_alo/
```

## 📚 Documentation

Full documentation is in the `docs/` directory:

| Document | Description |
|----------|-------------|
| Project Overview | System overview, features, innovations |
| System Architecture | Architecture diagrams, data flow |
| Tech Stack | Every technology explained |
| Database Schema | ER diagrams, tables, relationships |
| AI & RAG System | End-to-end pipeline documentation |
| Chatbot System | Circuit breaker, state machine, caching |
| API Reference | All endpoints with request / response |
| Deployment Guide | Docker, Render, Aiven, Cloud Run |
| Troubleshooting | Common issues and solutions |

## 🧪 Testing

```bash
# PHP lint
php -l *.php includes/*.php admin/*.php

# Python tests
pytest tests/
pytest ai-service/tests/
```

## 🌐 Deployment

### Render (Production)

```bash
# Push to GitHub — Render auto-deploys from main
git add .
git commit -m "Deploy latest changes"
git push origin main
```

### Docker

```bash
docker compose up -d
```

### RAG on Cloud Run

```powershell
.\deploy_to_cloudrun.ps1
```

## 👥 Team

Built by **GhostRiders** for the **ULAB Buildfest 2026** hackathon.

## 📄 License

This project was created for educational purposes as part of ULAB Buildfest 2026.
