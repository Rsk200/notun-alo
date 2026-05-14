from __future__ import annotations

from datetime import date

import numpy as np
import pandas as pd
from sklearn.linear_model import LinearRegression

from impact_utils import category_fallback_map, load_emission_factors, synthetic_training_data


def get_user_history(user_id: int, conn) -> pd.DataFrame:
    query = """
        SELECT
          DATE_FORMAT(p.schedule_date, '%Y-%m') AS month,
          p.category,
          ROUND(SUM(p.estimated_weight), 2) AS kg_recycled,
          ROUND(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 3) AS co2_saved
        FROM pickups p
        LEFT JOIN emission_factors ef
          ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
        LEFT JOIN category_averages ca ON p.category = ca.category
        WHERE p.user_id = %s
          AND p.status = 'completed'
          AND p.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month, p.category
        ORDER BY month ASC, p.category ASC
    """
    return pd.read_sql(query, conn, params=(user_id,))


def _next_months(start: str | None, horizon: int = 3) -> list[str]:
    base = pd.Period(start or date.today().strftime("%Y-%m"), freq="M")
    return [str(base + i) for i in range(1, horizon + 1)]


def _cold_start_forecast(user_id: int, history: pd.DataFrame, horizon: int = 3) -> dict:
    factors = load_emission_factors()
    training = synthetic_training_data(factors)
    monthly = (
        training.groupby(["category", "month"], as_index=False)
        .agg(kg_recycled=("weight_kg", "sum"), co2_saved=("co2_saved", "sum"))
        .groupby("category", as_index=False)
        .agg(kg_recycled=("kg_recycled", "median"), co2_saved=("co2_saved", "median"))
    )

    if not history.empty:
        preferred_categories = history.groupby("category")["kg_recycled"].sum().sort_values(ascending=False).index[:1]
        baseline = monthly[monthly["category"].isin(preferred_categories)]
    else:
        baseline = monthly[monthly["category"] == "Paper"]
    if baseline.empty:
        baseline = monthly.head(1)

    kg = float(baseline["kg_recycled"].median())
    co2 = float(baseline["co2_saved"].median())
    last_month = str(history["month"].max()) if not history.empty else None
    forecast = []
    for index, month in enumerate(_next_months(last_month, horizon), start=1):
        forecast.append(
            {
                "month": month,
                "predicted_co2_kg": round(co2 * (1 + 0.03 * index), 2),
                "predicted_kg_recycled": round(kg * (1 + 0.02 * index), 2),
                "confidence": "low",
            }
        )
    return {
        "user_id": user_id,
        "history_months": int(history["month"].nunique()) if not history.empty else 0,
        "forecast": forecast,
        "data_quality": "estimated (insufficient history)",
    }


def forecast_user_impact(user_id: int, conn=None, history: pd.DataFrame | None = None, horizon: int = 3) -> dict:
    if history is None:
        if conn is None:
            raise ValueError("Either conn or history must be provided.")
        history = get_user_history(user_id, conn)

    history = history.copy()
    history_months = int(history["month"].nunique()) if not history.empty else 0
    if history_months < 3:
        return _cold_start_forecast(user_id, history, horizon)

    monthly = history.groupby("month", as_index=False).agg(
        co2_saved=("co2_saved", "sum"),
        kg_recycled=("kg_recycled", "sum"),
    )
    monthly["period"] = pd.PeriodIndex(monthly["month"], freq="M")
    all_months = pd.period_range(monthly["period"].min(), monthly["period"].max(), freq="M")
    monthly = (
        monthly.set_index("period")
        .reindex(all_months)
        .rename_axis("period")
        .reset_index()
    )
    monthly["month"] = monthly["period"].astype(str)
    monthly[["co2_saved", "kg_recycled"]] = monthly[["co2_saved", "kg_recycled"]].fillna(0)

    dominant_category = history.groupby("category")["co2_saved"].sum().idxmax()
    categories = sorted(load_emission_factors()["category"].unique())
    category_encoded = categories.index(dominant_category) if dominant_category in categories else 0

    rows = []
    for idx in range(3, len(monthly)):
        period = monthly.loc[idx, "period"]
        rows.append(
            {
                "lag1_co2": monthly.loc[idx - 1, "co2_saved"],
                "lag2_co2": monthly.loc[idx - 2, "co2_saved"],
                "lag3_co2": monthly.loc[idx - 3, "co2_saved"],
                "month_of_forecast": period.month,
                "category_encoded": category_encoded,
                "target_co2": monthly.loc[idx, "co2_saved"],
                "target_kg": monthly.loc[idx, "kg_recycled"],
            }
        )

    if len(rows) < 2:
        return _cold_start_forecast(user_id, history, horizon)

    training = pd.DataFrame(rows)
    feature_cols = ["lag1_co2", "lag2_co2", "lag3_co2", "month_of_forecast", "category_encoded"]
    co2_model = LinearRegression().fit(training[feature_cols], training["target_co2"])
    kg_model = LinearRegression().fit(training[feature_cols], training["target_kg"])

    co2_lags = list(monthly["co2_saved"].tail(3).astype(float))
    kg_lags = list(monthly["kg_recycled"].tail(3).astype(float))
    last_period = monthly["period"].iloc[-1]
    forecast = []
    for step in range(1, horizon + 1):
        forecast_period = last_period + step
        features = pd.DataFrame(
            [
                {
                    "lag1_co2": co2_lags[-1],
                    "lag2_co2": co2_lags[-2],
                    "lag3_co2": co2_lags[-3],
                    "month_of_forecast": forecast_period.month,
                    "category_encoded": category_encoded,
                }
            ]
        )
        predicted_co2 = max(0.0, float(co2_model.predict(features)[0]))
        predicted_kg = max(0.0, float(kg_model.predict(features)[0]))
        forecast.append(
            {
                "month": str(forecast_period),
                "predicted_co2_kg": round(predicted_co2, 2),
                "predicted_kg_recycled": round(predicted_kg, 2),
                "confidence": "high" if history_months >= 6 else "medium",
            }
        )
        co2_lags.append(predicted_co2)
        kg_lags.append(predicted_kg)

    return {
        "user_id": user_id,
        "history_months": history_months,
        "forecast": forecast,
        "data_quality": "sufficient",
    }


if __name__ == "__main__":
    factors = category_fallback_map(load_emission_factors())
    paper_factor = factors["Paper"]
    months = pd.period_range("2025-11", periods=6, freq="M").astype(str)
    weights = [2.0, 2.7, 3.1, 4.0, 4.6, 5.3]
    sample_history = pd.DataFrame(
        {
            "month": months,
            "category": ["Paper"] * 6,
            "kg_recycled": weights,
            "co2_saved": [round(w * paper_factor, 3) for w in weights],
        }
    )
    print("Test case: user with 6 months of Paper recycling trending upward")
    print(forecast_user_impact(101, history=sample_history))
