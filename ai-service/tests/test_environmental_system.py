from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from environmental_engine import calculate_environmental_impact, calculate_co2_saved, calculate_water_saved, calculate_energy_saved
from forecast_engine import forecast_from_history
from leaderboard_engine import calculate_eco_score, consistency_score


def test_formulas_are_deterministic():
    assert calculate_co2_saved(5, 1.3) == 6.5
    assert calculate_water_saved(5, 5.8) == 29.0
    assert calculate_energy_saved(5, 82) == 410.0


def test_category_fallback_logic():
    impact = calculate_environmental_impact(2, "Plastic", None)
    assert impact["subcategory"] == "General_Plastic"
    assert impact["co2_saved_kg"] > 0


def test_ewaste_highlighting():
    impact = calculate_environmental_impact(1, "E-waste", "Mobile phones")
    assert impact["high_impact_badge"] == "High Impact Recycling"
    assert "29x" in impact["ewaste_message"]


def test_leaderboard_scoring():
    assert consistency_score(1) == 0.1
    assert consistency_score(4) == 0.5
    assert consistency_score(7) == 0.8
    assert consistency_score(8) == 1.0
    normal = calculate_eco_score(10, 10, 10, 1, "Plastic")
    ewaste = calculate_eco_score(10, 10, 10, 1, "E-waste")
    assert ewaste == round(normal * 1.5, 2)


def test_forecasting_ranges():
    result = forecast_from_history([], horizon=3)
    assert result["cold_start"] is True
    assert len(result["forecast"]) == 3
    assert all(item["co2_saved_kg"] >= 0 for item in result["forecast"])


def test_sql_files_include_required_queries():
    sql = (ROOT / "impact_queries.sql").read_text(encoding="utf-8")
    assert "User Impact Summary" in sql
    assert "Monthly CO2 Trend" in sql
    assert "Top Recyclers Leaderboard" in sql
    assert "COALESCE" in sql
