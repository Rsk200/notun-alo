from __future__ import annotations

from datetime import date
from pathlib import Path
from typing import Any
import joblib
import pandas as pd

from environmental_engine import calculate_environmental_impact, load_emission_factors

BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "impact_model.pkl"


def _load_model() -> dict[str, Any] | None:
    if not MODEL_PATH.exists():
        return None
    return joblib.load(MODEL_PATH)


def _next_months(horizon: int = 3) -> list[str]:
    start = pd.Period(date.today().strftime("%Y-%m"), freq="M")
    return [str(start + i) for i in range(1, horizon + 1)]


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
    months = _next_months(horizon)
    model_bundle = _load_model()
    if not history:
        baseline = calculate_environmental_impact(2.0, "Plastic", "Mixed plastic")
        return {
            "trend": "Stable",
            "confidence": "low",
            "cold_start": True,
            "forecast": [
                {"month": month, "co2_saved_kg": round(baseline["co2_saved_kg"] * (1 + 0.03 * i), 2), "water_saved_liters": baseline["water_saved_liters"], "energy_saved_kwh": baseline["energy_saved_kwh"]}
                for i, month in enumerate(months, start=1)
            ],
        }

    frame = pd.DataFrame(history)
    frame["co2_saved_kg"] = pd.to_numeric(frame.get("co2_saved_kg", 0), errors="coerce").fillna(0)
    frame["water_saved_liters"] = pd.to_numeric(frame.get("water_saved_liters", 0), errors="coerce").fillna(0)
    frame["energy_saved_kwh"] = pd.to_numeric(frame.get("energy_saved_kwh", 0), errors="coerce").fillna(0)
    top_category = frame.groupby("category")["co2_saved_kg"].sum().sort_values(ascending=False).index[0]
    top_subcategory = frame[frame["category"] == top_category].groupby("subcategory")["co2_saved_kg"].sum().sort_values(ascending=False).index[0]
    avg_weight = max(float(pd.to_numeric(frame.get("weight", 2.0), errors="coerce").fillna(2.0).mean()), 0.1)
    pickup_count = int(len(frame))
    previous_co2 = float(frame["co2_saved_kg"].tail(3).mean())
    previous_energy = float(frame["energy_saved_kwh"].tail(3).mean())
    previous_water = float(frame["water_saved_liters"].tail(3).mean())

    output = []
    for i, month in enumerate(months, start=1):
        if model_bundle:
            features = pd.DataFrame([{
                "category": top_category,
                "subcategory": top_subcategory,
                "weight": avg_weight * (1 + 0.04 * i),
                "month": int(month[-2:]),
                "pickup_count": pickup_count,
                "previous_co2": previous_co2,
                "previous_energy": previous_energy,
                "previous_water": previous_water,
            }])
            predicted_co2 = max(float(model_bundle["model"].predict(features)[0]), 0.0)
            factor = calculate_environmental_impact(1, top_category, top_subcategory)
            co2_per_kg = max(factor["co2_saved_kg"], 0.001)
            predicted_weight = predicted_co2 / co2_per_kg
            impact = calculate_environmental_impact(predicted_weight, top_category, top_subcategory)
        else:
            impact = calculate_environmental_impact(avg_weight * (1 + 0.04 * i), top_category, top_subcategory)
        output.append({"month": month, "co2_saved_kg": impact["co2_saved_kg"], "water_saved_liters": impact["water_saved_liters"], "energy_saved_kwh": impact["energy_saved_kwh"]})

    return {
        "trend": detect_trend([item["co2_saved_kg"] for item in output]),
        "confidence": "high" if len(history) >= 8 else "medium" if len(history) >= 3 else "low",
        "cold_start": False,
        "category": top_category,
        "subcategory": top_subcategory,
        "forecast": output,
    }
