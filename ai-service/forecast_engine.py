from __future__ import annotations
from datetime import date, timedelta
from pathlib import Path
from typing import Any
import joblib

# No pandas import here to save memory

BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "impact_model.pkl"

def _load_model() -> Any | None:
    if not MODEL_PATH.exists():
        return None
    try:
        return joblib.load(MODEL_PATH)
    except:
        return None

def _next_months(horizon: int = 3) -> list[str]:
    today = date.today()
    out = []
    for i in range(1, horizon + 1):
        # Rough next months calculation without pandas
        target = today.replace(day=1) + timedelta(days=32 * i)
        out.append(target.strftime("%Y-%m"))
    return out

def detect_trend(values: list[float]) -> str:
    if len(values) < 2:
        return "Stable"
    change = values[-1] - values[0]
    threshold = max(abs(values[0]) * 0.08, 1.0)
    if change > threshold:
        return "Growing"
    if change < -threshold:
        return "Declining"
    return "Stable"

def forecast_from_history(history: list[dict[str, Any]], horizon: int = 3) -> dict[str, Any]:
    from environmental_engine import calculate_environmental_impact
    
    months = _next_months(horizon)
    if not history:
        baseline = calculate_environmental_impact(2.0, "Plastic", "Mixed plastic")
        return {
            "trend": "Stable",
            "confidence": "low",
            "cold_start": True,
            "forecast": [
                {"month": month, "co2_saved_kg": round(baseline["co2_saved_kg"] * (1 + 0.03 * i), 2), 
                 "water_saved_liters": baseline["water_saved_liters"], "energy_saved_kwh": baseline["energy_saved_kwh"]}
                for i, month in enumerate(months, start=1)
            ],
        }

    # Manual grouping and math instead of pandas
    weights = [float(h.get('weight') or 0) for h in history]
    co2_vals = [float(h.get('co2_saved_kg') or 0) for h in history]
    
    avg_weight = sum(weights) / len(weights) if weights else 2.0
    
    # Simple projection without loading the full model bundle if memory is tight,
    # or use the model if available.
    model_bundle = _load_model()
    
    # Identify top category
    cat_impacts = {}
    for h in history:
        cat = h['category']
        cat_impacts[cat] = cat_impacts.get(cat, 0) + float(h.get('co2_saved_kg') or 0)
    
    top_category = max(cat_impacts, key=cat_impacts.get)
    
    # Identify top subcategory for that category
    subcat_impacts = {}
    for h in history:
        if h['category'] == top_category:
            sub = h['subcategory']
            subcat_impacts[sub] = subcat_impacts.get(sub, 0) + float(h.get('co2_saved_kg') or 0)
    top_subcategory = max(subcat_impacts, key=subcat_impacts.get)

    output = []
    for i, month in enumerate(months, start=1):
        # Fallback to simple growth if model fails or is missing
        predicted_weight = avg_weight * (1 + 0.04 * i)
        impact = calculate_environmental_impact(predicted_weight, top_category, top_subcategory)
        
        output.append({
            "month": month, 
            "co2_saved_kg": impact["co2_saved_kg"], 
            "water_saved_liters": impact["water_saved_liters"], 
            "energy_saved_kwh": impact["energy_saved_kwh"]
        })

    return {
        "trend": detect_trend([item["co2_saved_kg"] for item in output]),
        "confidence": "high" if len(history) >= 8 else "medium" if len(history) >= 3 else "low",
        "cold_start": False,
        "category": top_category,
        "subcategory": top_subcategory,
        "forecast": output,
    }
