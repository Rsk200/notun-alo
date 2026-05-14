from __future__ import annotations

import csv
from pathlib import Path
from typing import Iterable

import numpy as np
import pandas as pd

BASE_DIR = Path(__file__).resolve().parent
CSV_PATH = BASE_DIR / "emission_factors_expanded.csv"
BD_ANNUAL_CO2_KG = 590
CAR_CO2_KG_PER_KM = 0.21
PHONE_CHARGE_KWH = 0.012


def load_emission_factors(path: Path | str = CSV_PATH) -> pd.DataFrame:
    """Load the supplied CSV, including the current split-header variant."""
    path = Path(path)
    with path.open(newline="", encoding="utf-8-sig") as handle:
        rows = list(csv.reader(handle))

    if rows and rows[0] == [
        "category",
        "subcategory",
        "co2_base_kg_per_kg",
        "co2_sa_adjusted",
        "water_liters_per_kg",
        "energy_kwh_per_kg",
        "sourc",
    ]:
        rows = [
            [
                "category",
                "subcategory",
                "co2_base_kg_per_kg",
                "co2_sa_adjusted",
                "water_liters_per_kg",
                "energy_kwh_per_kg",
                "source",
                "notes",
            ],
            *rows[2:],
        ]

    df = pd.DataFrame(rows[1:], columns=rows[0])
    numeric_cols = [
        "co2_base_kg_per_kg",
        "co2_sa_adjusted",
        "water_liters_per_kg",
        "energy_kwh_per_kg",
    ]
    for col in numeric_cols:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df["subcategory"] = df["subcategory"].fillna("").replace("", np.nan)
    return df.dropna(subset=["category", "subcategory", "co2_sa_adjusted"]).reset_index(drop=True)


def category_fallback_map(df: pd.DataFrame) -> dict[str, float]:
    return df.groupby("category")["co2_sa_adjusted"].mean().round(4).to_dict()


def normalized_subcategory(category: str, subcategory: str | None) -> str:
    cleaned = (subcategory or "").strip()
    return cleaned if cleaned else f"General_{category}"


def resolve_factor(df: pd.DataFrame, category: str, subcategory: str | None) -> float:
    subcategory = (subcategory or "").strip()
    if subcategory:
        match = df[(df["category"] == category) & (df["subcategory"] == subcategory)]
        if not match.empty:
            return float(match.iloc[0]["co2_sa_adjusted"])
    return float(category_fallback_map(df).get(category, 0.0))


def synthetic_training_data(df: pd.DataFrame, records_per_subcategory: int = 200, seed: int = 42) -> pd.DataFrame:
    rng = np.random.default_rng(seed)
    records: list[dict[str, float | int | str]] = []
    for _, row in df.iterrows():
        weights = np.clip(rng.normal(2.5, 1.2, records_per_subcategory), 0.1, 20)
        months = rng.integers(1, 13, records_per_subcategory)
        frequencies = np.clip(rng.poisson(3, records_per_subcategory), 1, 15)
        for weight, month, frequency in zip(weights, months, frequencies):
            records.append(
                {
                    "category": row["category"],
                    "subcategory": normalized_subcategory(row["category"], row["subcategory"]),
                    "weight_kg": float(weight),
                    "month": int(month),
                    "frequency": int(frequency),
                    "co2_saved": float(weight * row["co2_sa_adjusted"]),
                }
            )
    return pd.DataFrame(records)


def build_feature_frame(records: Iterable[dict], feature_names: list[str] | None = None) -> pd.DataFrame:
    frame = pd.DataFrame(records)
    encoded = pd.get_dummies(frame[["category", "subcategory"]], prefix=["category", "subcategory"])
    numeric = frame[["weight_kg", "month", "frequency"]].astype(float)
    features = pd.concat([numeric, encoded], axis=1)
    if feature_names is not None:
        features = features.reindex(columns=feature_names, fill_value=0)
    return features
