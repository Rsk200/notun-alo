# API Reference

---

## Chatbot API

**Endpoint:** `POST chatbot_api.php` (AJAX call)  
**Content-Type:** `application/json`  
**Authentication:** Session cookie required

### Request

```json
{
  "message": "ami kemon acho",
  "session_id": "abc123",
  "lang": "auto",
  "history": [
    { "role": "user", "content": "hello" },
    { "role": "assistant", "content": "Hello!" }
  ]
}
```

### Response (200 OK)

```json
{
  "reply": "আমি ভালো আছি! 😊 আপনি কেমন আছেন?",
  "action": null,
  "source": "pollinations",
  "suggestions": ["Check my points", "Schedule a pickup", "Recycling guide"],
  "session_id": "abc123"
}
```

### Error Response (401 Unauthorized)

```json
{
  "reply": "Please log in again.",
  "action": null,
  "suggestions": null,
  "session_id": "main"
}
```

### Action Types

| Value | Description |
|-------|-------------|
| `null` | No action needed, text-only response |
| `{ type: "pickup_scheduled", category: "Paper", weight: 5.0, date: "2026-05-18", points: 25 }` | Pickup was scheduled via chatbot state machine |

### Source Types

The `source` field indicates which subsystem generated the reply:

- `cache` — Response served from cache
- `direct_points` — Points/loyalty lookup
- `direct_guide` — Recycling guide lookup
- `impact_lookup` — Environmental impact query
- `pickup_lookup` — Pickup schedule query
- `rag` — RAG knowledge base (ChromaDB)
- `pollinations` — Pollinations.ai free API (fallback LLM)
- `fallback` — Rule-based intent matching
- `state_machine` — Scheduling state machine (no LLM)

---

## Impact API

**Endpoint:** `GET api_impact.php`

### Actions

| Parameter | Description |
|-----------|-------------|
| `?action=impact&user_id=X` | Get a user's environmental impact summary |
| `?action=monthly&user_id=X` | Monthly CO₂ breakdown by recycling category |
| `?action=percentile_rank&user_id=X` | City-based CO₂ percentile rank |
| `?action=forecast&user_id=X` | 3-month impact forecast |

### Response — Impact Summary

```json
{
  "user_id": 4,
  "name": "John Doe",
  "total_pickups": 12,
  "total_kg_recycled": 45.5,
  "total_co2_saved_kg": 12.34,
  "total_water_saved_liters": 890,
  "total_energy_saved_kwh": 5.67,
  "pct_of_bd_annual_footprint": 2.09,
  "equivalent_car_km_saved": 58,
  "includes_ewaste": 0,
  "ewaste_priority_note": null,
  "gamification": {
    "xp": 1250,
    "level": "Eco-Warrior",
    "rank": 7,
    "max_rank": 10,
    "progress_to_next": 75
  }
}
```

### Gamification Ranks (10 Tiers)

| Rank | Title | XP Required |
|------|-------|-------------|
| 1 | Eco-Seed | 0 |
| 2 | Green Sprout | 100 |
| 3 | Waste Warrior | 250 |
| 4 | Recycling Rookie | 500 |
| 5 | Eco-Enthusiast | 800 |
| 6 | Planet Pal | 1200 |
| 7 | Eco-Warrior | 1800 |
| 8 | Green Guardian | 2500 |
| 9 | Earth Champion | 3500 |
| 10 | Planet Savior | 5000 |

---

## RAG Service API (Flask — Port 5000)

All endpoints are served at `http://localhost:5000`.

### `GET /health`

Service health check.

**Response:**
```json
{
  "status": "ok",
  "service": "notun-alo-rag",
  "collection": "recycling"
}
```

### `POST /chat`

Query the RAG knowledge base.

**Request:**
```json
{
  "query": "How to recycle plastic?",
  "language": "en",
  "user_name": "John",
  "user_points": 150
}
```

**Response:**
```json
{
  "answer": "...",
  "sources": [
    {
      "filename": "plastic_guide.pdf",
      "page_number": 3
    }
  ],
  "verification": {
    "score": 1.0
  }
}
```

### `POST /upload`

Upload and index a document.

**Body:** `multipart/form-data` with a `file` field.

**Response:**
```json
{
  "message": "File uploaded and indexed.",
  "file": "doc.pdf",
  "ingestion": { }
}
```

### `POST /ingest`

Re-index all documents in the knowledge base.

**Request:**
```json
{
  "rebuild": true
}
```

**Response:**
```json
{
  "indexed_files": 15,
  "skipped_files": 12,
  "total_chunks": 4821
}
```

### `GET /sources`

View the last retrieved chunks (debugging).

---

## Assignment API (Flask — Port 5005)

All endpoints are served at `http://localhost:5005`.

### `POST /assign`

Score and assign a pickup to the best-matched collection agency.

**Request:**
```json
{
  "pickup_id": 1,
  "category": "Plastic",
  "estimated_weight": 5.0,
  "schedule_date": "2026-05-20",
  "user_lat": 23.8,
  "user_lng": 90.4
}
```

**Response:**
```json
{
  "success": true,
  "agency_id": 7,
  "agency_name": "Green Dhaka",
  "score": 0.87,
  "reason": "Load=0.30 | Dist=2.1km | Rat=4.5 | Spec=match | Zone=match",
  "model_version": "ml_v1"
}
```

### `GET /agency-scores?pickup_id=X`

Retrieve all scored agencies for a given pickup (debugging/review).

### `GET /health`

Service health check — includes model loaded status.

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Missing or invalid parameters |
| 401 | Authentication required |
| 500 | Internal server error |
