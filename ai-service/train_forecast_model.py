from __future__ import annotations

from pathlib import Path
import joblib
import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import GradientBoostingRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler

BASE_DIR = Path(__file__).resolve().parent
DATA_PATH = BASE_DIR / "data" / "environmental_factors.csv"
MODEL_PATH = BASE_DIR / "impact_model.pkl"


def build_synthetic_rows(total_rows: int = 6000, seed: int = 42) -> pd.DataFrame:
    rng = np.random.default_rng(seed)
    factors = pd.read_csv(DATA_PATH)
    popularity = {"Paper": 0.24, "Plastic": 0.28, "Metal": 0.16, "Glass": 0.08, "E-waste": 0.08, "Organic": 0.06, "Textile": 0.06, "Rubber": 0.02, "Wood": 0.02}
    factors["category_weight"] = factors["category"].map(popularity).fillna(0.02)
    probabilities = factors["category_weight"] / factors["category_weight"].sum()
    chosen = factors.sample(n=total_rows, replace=True, weights=probabilities, random_state=seed).reset_index(drop=True)

    months = rng.integers(1, 13, total_rows)
    pickup_count = rng.poisson(4, total_rows) + 1
    season = 1 + 0.12 * np.sin((months - 1) / 12 * 2 * np.pi)
    growth = 1 + np.linspace(0, 0.18, total_rows)
    base_weight = rng.gamma(shape=2.2, scale=2.1, size=total_rows)
    ewaste_boost = np.where(chosen["category"].to_numpy() == "E-waste", 0.45, 1.0)
    weight = np.clip(base_weight * season * growth * ewaste_boost + rng.normal(0, 0.35, total_rows), 0.1, 45)

    previous_co2 = np.maximum(weight * chosen["co2_sa_adjusted"].to_numpy() * rng.normal(0.86, 0.12, total_rows), 0)
    previous_energy = np.maximum(weight * chosen["energy_kwh_per_kg"].to_numpy() * rng.normal(0.82, 0.15, total_rows), 0)
    previous_water = np.maximum(weight * chosen["water_liters_per_kg"].to_numpy() * rng.normal(0.84, 0.14, total_rows), 0)
    noise = rng.normal(0, 0.08, total_rows)
    future_co2 = np.maximum(weight * chosen["co2_sa_adjusted"].to_numpy() * season * (1 + noise), 0)

    return pd.DataFrame({
        "category": chosen["category"],
        "subcategory": chosen["subcategory"],
        "weight": weight.round(3),
        "month": months,
        "pickup_count": pickup_count,
        "previous_co2": previous_co2.round(3),
        "previous_energy": previous_energy.round(3),
        "previous_water": previous_water.round(3),
        "target_co2": future_co2.round(3),
    })


def main() -> None:
    data = build_synthetic_rows()
    features = ["category", "subcategory", "weight", "month", "pickup_count", "previous_co2", "previous_energy", "previous_water"]
    X = data[features]
    y = data["target_co2"]
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

    preprocessor = ColumnTransformer([
        ("category", OneHotEncoder(handle_unknown="ignore"), ["category", "subcategory"]),
        ("numeric", StandardScaler(), ["weight", "month", "pickup_count", "previous_co2", "previous_energy", "previous_water"]),
    ])
    model = Pipeline([
        ("preprocessor", preprocessor),
        ("regressor", GradientBoostingRegressor(random_state=42, n_estimators=160, max_depth=3, learning_rate=0.06)),
    ])
    model.fit(X_train, y_train)
    predictions = model.predict(X_test)
    rmse = mean_squared_error(y_test, predictions) ** 0.5
    print("MAE:", round(mean_absolute_error(y_test, predictions), 3))
    print("RMSE:", round(rmse, 3))
    print("R2:", round(r2_score(y_test, predictions), 3))

    regressor = model.named_steps["regressor"]
    names = model.named_steps["preprocessor"].get_feature_names_out()
    importance = sorted(zip(names, regressor.feature_importances_), key=lambda item: item[1], reverse=True)[:12]
    print("Feature importance:")
    for name, score in importance:
        print(f"{name}: {score:.4f}")

    joblib.dump({"model": model, "features": features, "trained_for": "future trend forecasting only"}, MODEL_PATH)
    print("Saved:", MODEL_PATH)


if __name__ == "__main__":
    main()
