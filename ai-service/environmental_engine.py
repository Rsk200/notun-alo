from __future__ import annotations

import csv
from functools import lru_cache
from pathlib import Path
from typing import Any

BASE_DIR = Path(__file__).resolve().parent
DATA_PATH = BASE_DIR / "data" / "environmental_factors.csv"
CAR_CO2_KG_PER_KM = 0.21
PHONE_CHARGE_KWH = 0.012
WATER_BOTTLE_LITERS = 0.5
EWASTE_MESSAGE = "Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling."

REQUIRED_COLUMNS = [
    "category",
    "subcategory",
    "co2_base_kg_per_kg",
    "co2_sa_adjusted",
    "water_liters_per_kg",
    "energy_kwh_per_kg",
    "source",
    "notes",
]


def _to_float(value: Any) -> float:
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


@lru_cache(maxsize=1)
def load_emission_factors() -> dict[str, Any]:
    rows: list[dict[str, Any]] = []
    with DATA_PATH.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        missing = [col for col in REQUIRED_COLUMNS if col not in (reader.fieldnames or [])]
        if missing:
            raise ValueError(f"Missing CSV columns: {', '.join(missing)}")
        for raw in reader:
            row = dict(raw)
            for col in ["co2_base_kg_per_kg", "co2_sa_adjusted", "water_liters_per_kg", "energy_kwh_per_kg"]:
                row[col] = _to_float(row[col])
            rows.append(row)

    by_key: dict[tuple[str, str], dict[str, Any]] = {}
    categories: dict[str, list[dict[str, Any]]] = {}
    for row in rows:
        category = str(row["category"]).strip()
        subcategory = str(row["subcategory"]).strip()
        by_key[(category.lower(), subcategory.lower())] = row
        categories.setdefault(category, []).append(row)

    averages: dict[str, dict[str, Any]] = {}
    for category, items in categories.items():
        count = max(len(items), 1)
        averages[category.lower()] = {
            "category": category,
            "subcategory": f"General_{category}",
            "co2_sa_adjusted": sum(item["co2_sa_adjusted"] for item in items) / count,
            "water_liters_per_kg": sum(item["water_liters_per_kg"] for item in items) / count,
            "energy_kwh_per_kg": sum(item["energy_kwh_per_kg"] for item in items) / count,
            "source": "category_averages",
            "notes": "Fallback category average",
        }
    return {"rows": rows, "by_key": by_key, "averages": averages}


def resolve_factor(category: str, subcategory: str | None = None) -> dict[str, Any]:
    data = load_emission_factors()
    category_clean = (category or "").strip()
    subcategory_clean = (subcategory or "").strip()
    if not category_clean:
        raise ValueError("category is required")

    if subcategory_clean:
        match = data["by_key"].get((category_clean.lower(), subcategory_clean.lower()))
        if match:
            return match

    general_name = f"General_{category_clean}"
    general_match = data["by_key"].get((category_clean.lower(), general_name.lower()))
    if general_match:
        return general_match

    fallback = data["averages"].get(category_clean.lower())
    if fallback:
        return fallback
    raise ValueError(f"No environmental factor found for category: {category_clean}")


def calculate_co2_saved(weight_kg: float, factor: float) -> float:
    return round(max(float(weight_kg), 0.0) * float(factor), 3)


def calculate_water_saved(weight_kg: float, factor: float) -> float:
    return round(max(float(weight_kg), 0.0) * float(factor), 2)


def calculate_energy_saved(weight_kg: float, factor: float) -> float:
    return round(max(float(weight_kg), 0.0) * float(factor), 3)


def calculate_environmental_impact(weight_kg: float, category: str, subcategory: str | None = None) -> dict[str, Any]:
    factor = resolve_factor(category, subcategory)
    weight = max(float(weight_kg), 0.0)
    co2 = calculate_co2_saved(weight, factor["co2_sa_adjusted"])
    water = calculate_water_saved(weight, factor["water_liters_per_kg"])
    energy = calculate_energy_saved(weight, factor["energy_kwh_per_kg"])
    is_ewaste = str(factor["category"]).lower() == "e-waste"
    return {
        "category": factor["category"],
        "subcategory": factor["subcategory"],
        "weight_kg": round(weight, 2),
        "co2_saved_kg": co2,
        "water_saved_liters": water,
        "energy_saved_kwh": energy,
        "car_trip_equivalent": round(co2 / CAR_CO2_KG_PER_KM, 0),
        "water_bottle_equivalent": round(water / WATER_BOTTLE_LITERS, 0),
        "phone_charge_equivalent": round(energy / PHONE_CHARGE_KWH, 0),
        "high_impact_badge": "High Impact Recycling" if is_ewaste else None,
        "ewaste_message": EWASTE_MESSAGE if is_ewaste else None,
    }
