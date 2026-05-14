from __future__ import annotations

import joblib
import numpy as np
from sklearn.ensemble import GradientBoostingRegressor
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.model_selection import cross_val_score, train_test_split
from sklearn.preprocessing import StandardScaler

from impact_utils import (
    CAR_CO2_KG_PER_KM,
    build_feature_frame,
    category_fallback_map,
    load_emission_factors,
    resolve_factor,
    synthetic_training_data,
)

MODEL_PATH = "impact_model.pkl"


def print_data_integration_summary() -> None:
    df = load_emission_factors()
    print("STEP 1a - CSV Profile")
    print(f"Shape: {df.shape}")
    print(f"Columns: {list(df.columns)}")
    print(f"Unique categories ({df['category'].nunique()}): {', '.join(df['category'].unique())}")

    print("\nSTEP 1b - Top 5 highest-impact subcategories")
    top5 = df.sort_values("co2_sa_adjusted", ascending=False).head(5)
    for idx, row in enumerate(top5.itertuples(index=False), start=1):
        km = row.co2_sa_adjusted / CAR_CO2_KG_PER_KM
        print(
            f"{idx}. 1 kg {row.subcategory} saves {row.co2_sa_adjusted:.2f} kg CO2 = {km:.0f} km of car journey"
        )
        if row.category == "E-waste":
            print("   E-waste priority: Mobile phone recycling saves ~37.4 kg CO2/kg, 29x higher than mixed plastic.")

    print("\nSTEP 1c - NULL/unknown fallback logic")
    fallback = resolve_factor(df, "Plastic", None)
    print(f'category="Plastic", subcategory=NULL -> Plastic category mean = {fallback:.4f} kg CO2/kg')

    print("\nSTEP 1d - South Asia Adjustment Summary")
    summary = (
        df.assign(reduction_pct=(1 - df["co2_sa_adjusted"] / df["co2_base_kg_per_kg"]) * 100)
        .groupby("category")["reduction_pct"]
        .mean()
        .reset_index()
    )
    for row in summary.itertuples(index=False):
        print(f"{row.category}: {row.reduction_pct:.1f}% reduction from EPA/base factor")


def train_model() -> dict:
    factors = load_emission_factors()
    training = synthetic_training_data(factors)
    features = build_feature_frame(training.to_dict("records"))
    target = training["co2_saved"].astype(float)

    numeric_cols = ["weight_kg", "month", "frequency"]
    scaler = StandardScaler()
    features_scaled = features.copy()
    features_scaled[numeric_cols] = scaler.fit_transform(features_scaled[numeric_cols])

    x_train, x_test, y_train, y_test = train_test_split(
        features_scaled, target, test_size=0.2, random_state=42
    )

    model = GradientBoostingRegressor(
        n_estimators=200,
        learning_rate=0.05,
        max_depth=4,
        random_state=42,
    )
    model.fit(x_train, y_train)

    predictions = model.predict(x_test)
    mae = mean_absolute_error(y_test, predictions)
    rmse = mean_squared_error(y_test, predictions) ** 0.5
    r2 = r2_score(y_test, predictions)
    cv_scores = cross_val_score(model, features_scaled, target, cv=5, scoring="r2")

    print("\nSTEP 4 - GradientBoostingRegressor Evaluation")
    print(f"Synthetic rows: {len(training):,}")
    print(f"MAE: {mae:.4f}")
    print(f"RMSE: {rmse:.4f}")
    print(f"R2: {r2:.4f}")
    print(f"5-fold CV R2: mean={cv_scores.mean():.4f}, std={cv_scores.std():.4f}")

    importances = sorted(
        zip(features.columns, model.feature_importances_),
        key=lambda item: item[1],
        reverse=True,
    )
    print("\nTop 5 most important features")
    for name, score in importances[:5]:
        print(f"{name}: {score:.4f}")

    bundle = {
        "model": model,
        "scaler": scaler,
        "feature_names": list(features.columns),
        "category_fallbacks": category_fallback_map(factors),
    }
    joblib.dump(bundle, MODEL_PATH)
    print(f"\nSaved model bundle to {MODEL_PATH}")

    test_record = {
        "category": "E-waste",
        "subcategory": "Mobile phones",
        "weight_kg": 2.0,
        "month": 6,
        "frequency": 2,
    }
    test_features = build_feature_frame([test_record], bundle["feature_names"])
    test_features[numeric_cols] = scaler.transform(test_features[numeric_cols])
    predicted = float(model.predict(test_features)[0])
    expected = 2 * 37.4
    print("\nTest case: 2 kg Mobile phones (E-waste), June, frequency=2")
    print(f"Predicted CO2: {predicted:.2f} kg")
    print(f"Expected CO2: approximately {expected:.2f} kg")
    if abs(predicted - expected) / expected > 0.20:
        print("WARNING: prediction deviates more than 20% from expected physics baseline.")
    return bundle


if __name__ == "__main__":
    print_data_integration_summary()
    train_model()
