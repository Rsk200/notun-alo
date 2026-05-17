# Notun Alo (নতুন আলো) — Project Overview

**Notun Alo** (নতুন আলো, meaning *"New Light"*) is a community recycling platform connecting households in Bangladesh with collection agencies. It rewards eco-friendly behavior through a points-based incentive system and provides AI-powered tools for scheduling, education, and environmental impact tracking.

---

## 1. Core Problems Solved

| # | Problem | Solution |
|---|---------|----------|
| 1 | **No centralized recycling platform in Bangladesh** | Web-based platform connecting households → agencies → admins in a single system |
| 2 | **Language barrier (Bangla / Banglish / English)** | Bilingual UI (EN/BN) + AI chatbot that auto-detects and responds in the user's language |
| 3 | **Trust and transparency in waste collection** | Full pickup lifecycle tracking, agency assignment audit trail, notification system |
| 4 | **Lack of incentives for household recycling** | Points-per-kg rewards (Paper 5, Plastic 8, Metal 12), tier/gamification system, upcycle shop |

---

## 2. Main Features

- **AI Chatbot** — Bilingual (EN/BN/Banglish) conversational assistant for recycling queries, pickup scheduling, and platform guidance
- **RAG-Powered Q&A** — Retrieval-Augmented Generation over 27+ knowledge base documents (PDFs, CSVs, TXTs) using ChromaDB + sentence-transformers
- **Pickup Scheduling** — Multi-turn state machine scheduling (category → weight → date → confirm) with zero API dependency
- **Reward Points Shop** — Earn points by recycling; spend on eco-friendly upcycled products
- **Environmental Impact Tracking** — CO₂ prevented, water saved, energy saved, gamification tiers, leaderboards, 90-day ML forecasts
- **Admin Dashboard** — User management, pickup oversight, agency assignment, churn monitoring, inventory, sustainability reports
- **Agency Assignment System** — 5-factor scoring (load, completion rate, distance, rating, specialty) with Flask ML microservice + SQL fallback
- **Churn Prediction ML** — XGBoost/RandomForest classifier trained on e-commerce dataset, weekly retraining cron job
- **Circuit Breaker Pattern** — 3-consecutive-failure detection, 5-minute cooldown, protects against free API unreliability

---

## 3. Target Users

| User Type | Role | Access |
|-----------|------|--------|
| **Households** | Primary users | Schedule pickups, earn points, shop, track impact |
| **Collection Agencies** | Service providers | View assigned tasks, mark collections complete |
| **Platform Administrators** | Super admins | Manage users/agencies, view analytics, monitor churn |

---

## 4. High-Level System Architecture

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                             Browser (User)                                   │
│                    HTML/CSS/JS — Dark Mode — Bilingual                        │
└──────────┬──────────────────────────┬──────────────────────────┬────────────┘
           │ HTTP/GET/POST            │ AJAX/JSON               │ AJAX/JSON
           ▼                          ▼                          ▼
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────────┐
│   Apache 2.4 Web     │  │  chatbot_api.php     │  │  api_impact.php          │
│   Server (PHP 8.2)   │  │  Circuit Breaker     │  │  Impact Calculation      │
│                      │  │  State Machine       │  │  Gamification Engine     │
│  • index.php         │  │  Cache (5min TTL)    │  │                          │
│  • dashboard.php     │  │  Fallback Engine     │  └──────────────────────────┘
│  • admin.php         │  │  Session Memory      │
│  • shop.php          │  └──────────┬───────────┘
│  • login/register    │             │ Pollinations.ai (or RAG)
└──────────┬───────────┘             ▼
           │               ┌──────────────────┐
           │               │  Fallback Engine │
           │               │  (15 intents)    │
           │               └──────────────────┘
           ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                          MySQL 8.0 Database                                  │
│   users | pickups | products | orders | rewards | chatbot_cache              │
│   chat_messages | chatbot_states | assignment_log | notifications            │
│   agency_stats | model_versions | emission_factors | category_averages       │
└──────────────────────────────────────────────────────────────────────────────┘
           ▲
           │          ┌──────────────────────────────────────────┐
           │          │       Python Flask Microservices          │
           │          │  ┌─────────┐ ┌─────────┐ ┌────────────┐  │
           └──────────┤ │ RAG     │ │ Impact  │ │ Assignment │  │
                      │ │ :5000   │ │ :5003   │ │ :5005      │  │
                      │ │Chromadb  │ │sklearn  │ │5-factor    │  │
                      │ │Gemini/   │ │LinearReg│ │Haversine   │  │
                      │ │FreeLLM   │ │joblib   │ │distance    │  │
                      │ └─────────┘ └─────────┘ └────────────┘  │
                      └──────────────────────────────────────────┘
```

---

## 5. Technology Overview

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | PHP 8.2 | All business logic, authentication, DB access, server-rendered UI |
| **Web Server** | Apache 2.4 | Serves all PHP/HTML/CSS/JS via Docker official image |
| **Database** | MySQL 8.0 | InnoDB, JSON support, foreign keys, UTF-8 mb4 |
| **AI/RAG Service** | Python 3.11 + Flask 3.0.3 | RAG pipeline, document ingestion, query embedding/retrieval |
| **Vector Store** | ChromaDB 0.5.5 | Persistent local vector database for document embeddings |
| **Embeddings** | sentence-transformers (`paraphrase-multilingual-MiniLM-L12-v2`) | Multilingual (50+ languages), 470MB model |
| **Primary LLM** | Google Gemini (`gemini-1.5-flash`, optional) | Free tier, Bengali support, RAG answer generation |
| **Free LLM Fallback** | Pollinations.ai (`llama-3.1-70b`) | Completely free, no API key required |
| **ML Models** | scikit-learn, XGBoost, joblib | Churn prediction, impact forecasting, model persistence |
| **Impact Service** | Flask + emission_factors CSV + LinearRegression | Environmental metrics, 90-day forecast, leaderboard |
| **Assignment Service** | Flask + haversine + 5-factor weighted scoring | AI agency assignment with SQL fallback |
| **Hosting** | Render (Docker) + Aiven (MySQL) + optional Cloud Run | Free-tier deployment |
| **Containerization** | Docker | Multi-service containers (PHP/Apache + Flask) via docker-compose |

---

## 6. AI / RAG Overview

The platform uses a **two-tier AI system**:

### Tier 1: PHP-Level Chatbot (`chatbot_api.php`)
- **Cache**: 5-minute TTL on exact-match queries via `chatbot_cache` table
- **Circuit Breaker**: Tracks consecutive failures; opens for 5 min after 3 failures
- **State Machine**: Multi-turn scheduling flow (category → weight → date → confirm) with zero API dependency
- **Fallback Engine**: 15 intent categories (greeting, points, impact, schedule, etc.) with regex scoring + Banglish detection
- **Session Memory**: `chat_messages` table stores recent conversation history

### Tier 2: Python RAG Service (`app.py` + `rag_pipeline.py`)
- **Ingestion**: 27+ knowledge base documents → text extraction → chunking (1000 chars, 200 overlap) → embedding → ChromaDB
- **Retrieval**: Query → sentence-transformers embedding → ChromaDB similarity search (top-k=8) → reranker → source formatting
- **Generation**: Prompt built with context + user info → Gemini (if key configured) → Pollinations.ai (free fallback) → deterministic fallback
- **Language Detection**: Unicode Bengali range + Banglish keyword matching

---

## 7. Key Innovations

| Innovation | Description |
|-----------|-------------|
| **Circuit Breaker Pattern** | Protects against free API (Pollinations.ai) unreliability; 3-failure threshold, 5-min cooldown |
| **Banglish Language Detection** | Detects Bengali transliterated in Latin script (e.g., "kemon acho") using keyword matching + ratio analysis |
| **Multi-Turn State Machine** | Complete pickup scheduling flow in pure PHP (category → weight → date → confirm) with 0 external API calls |
| **Dual-Fallback Architecture** | PHP intent engine (15 regex intents) + Python RAG (contextual retrieval) ensure chatbot never fails hard |
| **5-Factor Agency Scoring** | Load ratio (35%), completion rate (25%), distance (20%), rating (12%), specialty (8%) — with haversine distance |
| **Churn Prediction ML** | XGBoost/RandomForest hybrid trained on real e-commerce data; weekly retraining via cron; admin monitoring dashboard |
| **Environmental Gamification** | User tiers (Bronze → Silver → Gold → Platinum), XP bars, percentile ranking vs city, shareable impact cards |
| **E-waste 29x Logic** | Mobile phone recycling flagged with 29× higher impact than mixed plastic; badge system for high-impact recyclers |

---

## 8. System Requirements

| Component | Requirement |
|-----------|-------------|
| PHP | 8.2+ with gd, zip, pdo_mysql, curl |
| MySQL | 8.0+ (MariaDB 10.4+ compatible) |
| Python | 3.11+ |
| Docker | 24+ (for containerized deployment) |
| RAM | 1 GB minimum (2 GB recommended with RAG) |
| Disk | 5 GB+ (includes ChromaDB + ML models + knowledge base) |
