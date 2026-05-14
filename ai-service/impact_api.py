from __future__ import annotations

import logging
import os
from logging.handlers import RotatingFileHandler
from pathlib import Path
from typing import Any

import mysql.connector
from dotenv import load_dotenv
from flask import Flask, jsonify, request
from flask_cors import CORS

from environmental_engine import (
    CAR_CO2_KG_PER_KM,
    EWASTE_MESSAGE,
    PHONE_CHARGE_KWH,
    WATER_BOTTLE_LITERS,
    calculate_environmental_impact,
    load_emission_factors,
)
from forecast_engine import forecast_from_history
from leaderboard_engine import build_leaderboard

BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")
LOG_DIR = BASE_DIR / "logs"
LOG_DIR.mkdir(exist_ok=True)

handler = RotatingFileHandler(LOG_DIR / "impact_api.log", maxBytes=512_000, backupCount=3, encoding="utf-8")
handler.setFormatter(logging.Formatter("%(asctime)s - %(levelname)s - %(message)s"))
app = Flask(__name__)
app.logger.addHandler(handler)
app.logger.setLevel(logging.INFO)
CORS(app)

DB_CONFIG = {
    "host": os.getenv("NOTUN_ALO_DB_HOST") or os.getenv("DB_HOST") or "localhost",
    "user": os.getenv("NOTUN_ALO_DB_USER") or os.getenv("DB_USER") or "root",
    "password": os.getenv("NOTUN_ALO_DB_PASS") or os.getenv("DB_PASS") or "",
    "database": os.getenv("NOTUN_ALO_DB_NAME") or os.getenv("DB_NAME") or "notun_alo",
    "port": int(os.getenv("DB_PORT") or 3306)
}
FACTORS_CACHE = load_emission_factors()


def get_conn():
    return mysql.connector.connect(**DB_CONFIG)


def ensure_schema(conn) -> None:
    cursor = conn.cursor()

    def column_exists(table: str, column: str) -> bool:
        cursor.execute(f"SHOW COLUMNS FROM {table} LIKE %s", (column,))
        return cursor.fetchone() is not None

    def safe_execute(sql: str) -> None:
        try:
            cursor.execute(sql)
        except mysql.connector.Error as exc:
            message = str(exc).lower()
            if "duplicate key name" in message or "duplicate column name" in message:
                return
            raise

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS emission_factors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(80) NOT NULL,
            subcategory VARCHAR(140) NOT NULL,
            co2_base_kg_per_kg DECIMAL(10,4) NOT NULL,
            co2_sa_adjusted DECIMAL(10,4) NOT NULL,
            water_liters_per_kg DECIMAL(12,4) NOT NULL,
            energy_kwh_per_kg DECIMAL(12,4) NOT NULL,
            source VARCHAR(180) NOT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_emission_factor (category, subcategory),
            INDEX idx_emission_category (category),
            INDEX idx_emission_subcategory (subcategory)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    """)
    safe_execute("ALTER TABLE emission_factors MODIFY category VARCHAR(80) NOT NULL")
    safe_execute("ALTER TABLE emission_factors MODIFY subcategory VARCHAR(140) NOT NULL")
    if not column_exists("emission_factors", "co2_base_kg_per_kg"):
        safe_execute("ALTER TABLE emission_factors ADD COLUMN co2_base_kg_per_kg DECIMAL(10,4) NOT NULL DEFAULT 0 AFTER subcategory")
    if not column_exists("emission_factors", "source"):
        safe_execute("ALTER TABLE emission_factors ADD COLUMN source VARCHAR(180) NOT NULL DEFAULT 'unknown' AFTER energy_kwh_per_kg")
    if not column_exists("emission_factors", "notes"):
        safe_execute("ALTER TABLE emission_factors ADD COLUMN notes TEXT NULL AFTER source")
    safe_execute("ALTER TABLE emission_factors MODIFY co2_base_kg_per_kg DECIMAL(10,4) NOT NULL")
    safe_execute("ALTER TABLE emission_factors MODIFY co2_sa_adjusted DECIMAL(10,4) NOT NULL")
    safe_execute("ALTER TABLE emission_factors MODIFY water_liters_per_kg DECIMAL(12,4) NOT NULL")
    safe_execute("ALTER TABLE emission_factors MODIFY energy_kwh_per_kg DECIMAL(12,4) NOT NULL")
    safe_execute("CREATE UNIQUE INDEX uq_emission_factor ON emission_factors(category, subcategory)")
    safe_execute("CREATE INDEX idx_emission_category ON emission_factors(category)")
    safe_execute("CREATE INDEX idx_emission_subcategory ON emission_factors(subcategory)")

    cursor.execute("SHOW COLUMNS FROM pickups LIKE 'subcategory'")
    if cursor.fetchone() is None:
        cursor.execute("ALTER TABLE pickups ADD COLUMN subcategory VARCHAR(140) NULL AFTER category")
    insert_sql = """
        INSERT INTO emission_factors
        (category, subcategory, co2_base_kg_per_kg, co2_sa_adjusted, water_liters_per_kg, energy_kwh_per_kg, source, notes)
        VALUES (%s,%s,%s,%s,%s,%s,%s,%s)
        ON DUPLICATE KEY UPDATE
            co2_base_kg_per_kg = VALUES(co2_base_kg_per_kg),
            co2_sa_adjusted = VALUES(co2_sa_adjusted),
            water_liters_per_kg = VALUES(water_liters_per_kg),
            energy_kwh_per_kg = VALUES(energy_kwh_per_kg),
            source = VALUES(source),
            notes = VALUES(notes)
    """
    values = []
    for row in FACTORS_CACHE["rows"]:
        values.append((row["category"], row["subcategory"], row["co2_base_kg_per_kg"], row["co2_sa_adjusted"], row["water_liters_per_kg"], row["energy_kwh_per_kg"], row["source"], row["notes"]))
    cursor.executemany(insert_sql, values)
    cursor.execute("""
        CREATE OR REPLACE VIEW category_averages AS
        SELECT category,
               ROUND(AVG(co2_sa_adjusted), 4) AS avg_co2,
               ROUND(AVG(water_liters_per_kg), 4) AS avg_water_liters_per_kg,
               ROUND(AVG(energy_kwh_per_kg), 4) AS avg_energy_kwh_per_kg
        FROM emission_factors
        GROUP BY category
    """)
    conn.commit()
    cursor.close()


def fetch_dicts(conn, query: str, params: tuple = ()) -> list[dict[str, Any]]:
    cursor = conn.cursor(dictionary=True)
    cursor.execute(query, params)
    rows = cursor.fetchall()
    cursor.close()
    return rows


def as_float(value: Any) -> float:
    try:
        return float(value or 0)
    except (TypeError, ValueError):
        return 0.0


USER_IMPACT_SQL = """
SELECT
    u.id AS user_id,
    u.name AS user_name,
    COUNT(p.id) AS total_pickups,
    COALESCE(SUM(p.estimated_weight), 0) AS total_kg_recycled,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 0) AS co2_saved_kg,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 0) AS water_saved_liters,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 0) AS energy_saved_kwh,
    SUM(CASE WHEN p.category = 'E-waste' THEN 1 ELSE 0 END) AS ewaste_pickups
FROM users u
LEFT JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE u.id = %s
GROUP BY u.id, u.name
"""

PLATFORM_SQL = """
SELECT
    COUNT(DISTINCT p.user_id) AS active_users,
    COUNT(p.id) AS total_pickups,
    COALESCE(SUM(p.estimated_weight), 0) AS total_kg_recycled,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 0) AS co2_saved_kg,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 0) AS water_saved_liters,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 0) AS energy_saved_kwh
FROM pickups p
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE p.status = 'completed'
"""

CATEGORY_SQL = """
SELECT
    p.category,
    COALESCE(SUM(p.estimated_weight), 0) AS total_kg,
    COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 0) AS co2_saved_kg
FROM pickups p
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE p.status = 'completed'
GROUP BY p.category
ORDER BY co2_saved_kg DESC
"""

HISTORY_SQL = """
SELECT
    p.category,
    COALESCE(NULLIF(p.subcategory, ''), CONCAT('General_', p.category)) AS subcategory,
    p.estimated_weight AS weight,
    p.schedule_date,
    p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0) AS co2_saved_kg,
    p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0) AS water_saved_liters,
    p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0) AS energy_saved_kwh
FROM pickups p
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE p.user_id = %s AND p.status = 'completed'
ORDER BY p.schedule_date ASC, p.id ASC
"""


@app.errorhandler(Exception)
def handle_error(error):
    app.logger.exception("Unhandled API error: %s", error)
    return jsonify({"error": "service_error", "message": str(error)}), 500


@app.get("/health")
def health():
    return jsonify({
        "status": "ok",
        "service": "notun-alo-impact-api",
        "factors_loaded": len(FACTORS_CACHE["rows"]),
        "constants": {"car_kg_co2_per_km": CAR_CO2_KG_PER_KM, "phone_charge_kwh": PHONE_CHARGE_KWH, "water_bottle_liters": WATER_BOTTLE_LITERS},
    })


@app.get("/impact")
def impact():
    user_id = request.args.get("user_id", type=int)
    if not user_id:
        return jsonify({"error": "user_id is required"}), 400
    conn = get_conn()
    try:
        ensure_schema(conn)
        rows = fetch_dicts(conn, USER_IMPACT_SQL, (user_id,))
    finally:
        conn.close()
    row = rows[0] if rows else {}
    co2 = as_float(row.get("co2_saved_kg"))
    water = as_float(row.get("water_saved_liters"))
    energy = as_float(row.get("energy_saved_kwh"))
    has_ewaste = int(row.get("ewaste_pickups") or 0) > 0
    return jsonify({
        "user_id": user_id,
        "user_name": row.get("user_name", "User"),
        "total_pickups": int(row.get("total_pickups") or 0),
        "total_kg_recycled": round(as_float(row.get("total_kg_recycled")), 2),
        "co2_saved_kg": round(co2, 2),
        "water_saved_liters": round(water, 2),
        "energy_saved_kwh": round(energy, 2),
        "car_trip_equivalent": round(co2 / CAR_CO2_KG_PER_KM),
        "water_bottle_equivalent": round(water / WATER_BOTTLE_LITERS),
        "phone_charge_equivalent": round(energy / PHONE_CHARGE_KWH),
        "high_impact_badge": "High Impact Recycling" if has_ewaste else None,
        "ewaste_message": EWASTE_MESSAGE,
    })


@app.get("/forecast")
def forecast():
    user_id = request.args.get("user_id", type=int)
    if not user_id:
        return jsonify({"error": "user_id is required"}), 400
    conn = get_conn()
    try:
        ensure_schema(conn)
        history = fetch_dicts(conn, HISTORY_SQL, (user_id,))
    finally:
        conn.close()
    return jsonify({"user_id": user_id, **forecast_from_history(history)})


@app.get("/platform-stats")
def platform_stats():
    conn = get_conn()
    try:
        ensure_schema(conn)
        platform = fetch_dicts(conn, PLATFORM_SQL)[0]
        categories = fetch_dicts(conn, CATEGORY_SQL)
    finally:
        conn.close()
    co2 = as_float(platform.get("co2_saved_kg"))
    water = as_float(platform.get("water_saved_liters"))
    energy = as_float(platform.get("energy_saved_kwh"))
    return jsonify({
        "active_users": int(platform.get("active_users") or 0),
        "total_pickups": int(platform.get("total_pickups") or 0),
        "total_kg_recycled": round(as_float(platform.get("total_kg_recycled")), 2),
        "co2_saved_kg": round(co2, 2),
        "water_saved_liters": round(water, 2),
        "energy_saved_kwh": round(energy, 2),
        "car_trip_equivalent": round(co2 / CAR_CO2_KG_PER_KM),
        "water_bottle_equivalent": round(water / WATER_BOTTLE_LITERS),
        "phone_charge_equivalent": round(energy / PHONE_CHARGE_KWH),
        "category_distribution": [{"category": row["category"], "total_kg": round(as_float(row["total_kg"]), 2), "co2_saved_kg": round(as_float(row["co2_saved_kg"]), 2)} for row in categories],
        "ewaste_message": EWASTE_MESSAGE,
    })


@app.get("/leaderboard")
def leaderboard():
    conn = get_conn()
    try:
        ensure_schema(conn)
        data = build_leaderboard(conn)
    finally:
        conn.close()
    return jsonify({"leaderboard": data})


if __name__ == "__main__":
    host = os.getenv("IMPACT_API_HOST", "127.0.0.1")
    port = int(os.getenv("IMPACT_API_PORT", "5003"))
    debug = os.getenv("FLASK_DEBUG", "false").lower() == "true"
    app.run(host=host, port=port, debug=debug)
