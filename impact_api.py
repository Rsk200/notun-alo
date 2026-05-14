from __future__ import annotations

import os
from decimal import Decimal
from pathlib import Path

import joblib
import mysql.connector
import numpy as np
import pandas as pd
from dotenv import load_dotenv
from flask import Flask, jsonify, request
from flask_cors import CORS

from forecast_impact import forecast_user_impact
from impact_utils import (
    BD_ANNUAL_CO2_KG,
    CAR_CO2_KG_PER_KM,
    CSV_PATH,
    PHONE_CHARGE_KWH,
    load_emission_factors,
)

BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})

try:
    bundle = joblib.load(BASE_DIR / "impact_model.pkl")
except FileNotFoundError:
    bundle = None

DB_CONFIG = {
    "host": os.getenv("NOTUN_ALO_DB_HOST", os.getenv("DB_HOST", "localhost")),
    "user": os.getenv("NOTUN_ALO_DB_USER", os.getenv("DB_USER", "root")),
    "password": os.getenv("NOTUN_ALO_DB_PASS", os.getenv("DB_PASS", "")),
    "database": os.getenv("NOTUN_ALO_DB_NAME", os.getenv("DB_NAME", "notun_alo")),
}


@app.after_request
def add_cors_headers(response):
    response.headers["Access-Control-Allow-Origin"] = "*"
    response.headers["Access-Control-Allow-Headers"] = "Content-Type, Authorization"
    response.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"
    return response


def get_conn():
    return mysql.connector.connect(**DB_CONFIG)


def ensure_impact_schema(conn) -> None:
    cursor = conn.cursor()
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS emission_factors (
          id INT AUTO_INCREMENT PRIMARY KEY,
          category VARCHAR(50) NOT NULL,
          subcategory VARCHAR(100) NOT NULL,
          co2_sa_adjusted DECIMAL(6,4) NOT NULL,
          water_liters_per_kg DECIMAL(8,2) NOT NULL,
          energy_kwh_per_kg DECIMAL(6,4) NOT NULL,
          co2_equivalent_label VARCHAR(200),
          is_ewaste TINYINT(1) DEFAULT 0,
          INDEX idx_category (category),
          INDEX idx_subcategory (subcategory)
        )
        """
    )
    cursor.execute("ALTER TABLE pickups MODIFY category VARCHAR(50) NOT NULL")
    cursor.execute("SHOW COLUMNS FROM pickups LIKE 'subcategory'")
    if cursor.fetchone() is None:
        cursor.execute("ALTER TABLE pickups ADD COLUMN subcategory VARCHAR(100) NULL AFTER category")

    cursor.execute("SELECT COUNT(*) FROM emission_factors")
    count = int(cursor.fetchone()[0])
    if count == 0:
        factors = load_emission_factors(CSV_PATH)
        insert_sql = """
            INSERT INTO emission_factors
            (category, subcategory, co2_sa_adjusted, water_liters_per_kg, energy_kwh_per_kg, co2_equivalent_label, is_ewaste)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        values = []
        for row in factors.itertuples(index=False):
            km = round(float(row.co2_sa_adjusted) / CAR_CO2_KG_PER_KM)
            values.append(
                (
                    row.category,
                    row.subcategory,
                    float(row.co2_sa_adjusted),
                    float(row.water_liters_per_kg),
                    float(row.energy_kwh_per_kg),
                    f"1kg {row.subcategory} recycled = {km:.0f} km car journey saved",
                    1 if row.category == "E-waste" else 0,
                )
            )
        cursor.executemany(insert_sql, values)

    cursor.execute(
        """
        CREATE OR REPLACE VIEW category_averages AS
        SELECT
          category,
          ROUND(AVG(co2_sa_adjusted), 4) AS avg_co2,
          ROUND(AVG(water_liters_per_kg), 2) AS avg_water_liters_per_kg,
          ROUND(AVG(energy_kwh_per_kg), 4) AS avg_energy_kwh_per_kg
        FROM emission_factors
        GROUP BY category
        """
    )
    conn.commit()
    cursor.close()


def fetch_one_dict(conn, query: str, params: tuple) -> dict:
    cursor = conn.cursor(dictionary=True)
    cursor.execute(query, params)
    row = cursor.fetchone() or {}
    cursor.close()
    return row


def user_impact_query() -> str:
    return """
        SELECT
          u.name,
          COUNT(p.id) AS total_pickups,
          ROUND(COALESCE(SUM(p.estimated_weight), 0), 2) AS total_kg_recycled,
          ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0), 3) AS total_co2_saved_kg,
          ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 0), 1) AS total_water_saved_liters,
          ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 0), 2) AS total_energy_saved_kwh,
          ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0) / 590 * 100, 2) AS pct_of_bd_annual_footprint,
          ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0) / 0.21, 0) AS equivalent_car_km_saved,
          MAX(COALESCE(ef.is_ewaste, 0)) AS includes_ewaste
        FROM users u
        LEFT JOIN pickups p ON u.id = p.user_id AND p.status = 'completed'
        LEFT JOIN emission_factors ef
          ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
        LEFT JOIN category_averages ca ON p.category = ca.category
        WHERE u.id = %s
        GROUP BY u.id, u.name
    """


@app.route("/health", methods=["GET"])
def health():
    return jsonify(
        {
            "status": "ok",
            "service": "notun-alo-impact",
            "model_loaded": bundle is not None,
            "constants": {
                "bd_annual_co2_kg": BD_ANNUAL_CO2_KG,
                "car_co2_kg_per_km": CAR_CO2_KG_PER_KM,
                "phone_charge_kwh": PHONE_CHARGE_KWH,
            },
        }
    )


@app.route("/impact", methods=["GET"])
def get_impact():
    user_id = request.args.get("user_id", type=int)
    if not user_id:
        return jsonify({"error": "user_id required"}), 400

    conn = get_conn()
    try:
        ensure_impact_schema(conn)
        row = fetch_one_dict(conn, user_impact_query(), (user_id,))
    finally:
        conn.close()

    if not row or float(row.get("total_kg_recycled") or 0) == 0:
        return jsonify(
            {
                "user_id": user_id,
                "message": "no_pickups_yet",
                "total_pickups": 0,
                "total_kg_recycled": 0,
                "total_co2_saved_kg": 0,
                "total_water_saved_liters": 0,
                "total_energy_saved_kwh": 0,
                "pct_of_bd_annual_footprint": 0,
                "equivalent_car_km_saved": 0,
                "ewaste_priority_note": None,
            }
        )

    row = {
        key: (float(value) if isinstance(value, (np.floating, np.integer, Decimal)) else value)
        for key, value in row.items()
    }
    row["user_id"] = user_id
    row["ewaste_priority_note"] = (
        "E-waste priority: Mobile phone recycling saves ~37.4 kg CO2/kg, 29x higher than mixed plastic."
        if int(row.get("includes_ewaste") or 0) == 1
        else None
    )
    return jsonify(row)


@app.route("/forecast", methods=["GET"])
def get_forecast():
    user_id = request.args.get("user_id", type=int)
    if not user_id:
        return jsonify({"error": "user_id required"}), 400

    conn = get_conn()
    try:
        ensure_impact_schema(conn)
        result = forecast_user_impact(user_id, conn=conn)
    finally:
        conn.close()
    return jsonify(result)


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5003, debug=False)
