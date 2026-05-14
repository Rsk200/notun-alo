from __future__ import annotations

import pandas as pd
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
CSV_PATH = BASE_DIR / "data" / "environmental_factors.csv"
REQUIRED_COLUMNS = [
    "category", "subcategory", "co2_base_kg_per_kg", "co2_sa_adjusted",
    "water_liters_per_kg", "energy_kwh_per_kg", "source", "notes"
]


def main() -> None:
    df = pd.read_csv(CSV_PATH, encoding="utf-8-sig")
    print("Loaded rows:", len(df))
    missing_cols = [col for col in REQUIRED_COLUMNS if col not in df.columns]
    if missing_cols:
        raise SystemExit(f"Schema error. Missing columns: {missing_cols}")
    print("Schema OK")

    duplicates = df.duplicated(subset=["category", "subcategory"]).sum()
    print("Duplicate category/subcategory rows:", int(duplicates))

    missing_values = df[REQUIRED_COLUMNS].isna().sum()
    print("Missing values by column:")
    print(missing_values.to_string())

    numeric_cols = ["co2_base_kg_per_kg", "co2_sa_adjusted", "water_liters_per_kg", "energy_kwh_per_kg"]
    for col in numeric_cols:
        df[col] = pd.to_numeric(df[col], errors="coerce")
    if df[numeric_cols].isna().any().any():
        raise SystemExit("Numeric validation failed. Check factor columns.")

    print("Unique categories:", ", ".join(sorted(df["category"].unique())))
    print("Category averages:")
    print(df.groupby("category")[["co2_sa_adjusted", "water_liters_per_kg", "energy_kwh_per_kg"]].mean().round(3).to_string())

    print("Top 5 highest-impact subcategories by South Asia adjusted CO2:")
    print(df.sort_values("co2_sa_adjusted", ascending=False).head(5)[["category", "subcategory", "co2_sa_adjusted"]].to_string(index=False))

    df["sa_adjustment_ratio"] = df["co2_sa_adjusted"] / df["co2_base_kg_per_kg"]
    print("South Asia adjustment summary:")
    print(df["sa_adjustment_ratio"].describe().round(3).to_string())

    mobile = df[(df["category"] == "E-waste") & (df["subcategory"].str.lower() == "mobile phones")]["co2_sa_adjusted"].iloc[0]
    plastic = df[(df["category"] == "Plastic") & (df["subcategory"].str.lower() == "mixed plastic")]["co2_sa_adjusted"].iloc[0]
    print("E-waste comparison summary:")
    print(f"Mobile phone recycling is {mobile / plastic:.1f}x higher impact than mixed plastic recycling.")


if __name__ == "__main__":
    main()
