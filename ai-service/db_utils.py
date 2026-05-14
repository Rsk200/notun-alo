import os
import mysql.connector
from dotenv import load_dotenv
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")

DB_CONFIG = {
    "host": os.getenv("NOTUN_ALO_DB_HOST") or os.getenv("DB_HOST") or "localhost",
    "user": os.getenv("NOTUN_ALO_DB_USER") or os.getenv("DB_USER") or "root",
    "password": os.getenv("NOTUN_ALO_DB_PASS") or os.getenv("DB_PASS") or "",
    "database": os.getenv("NOTUN_ALO_DB_NAME") or os.getenv("DB_NAME") or "notun_alo",
    "port": int(os.getenv("DB_PORT") or 3306)
}

def get_conn():
    return mysql.connector.connect(**DB_CONFIG)

def fetch_dicts(conn, query, params=()):
    cursor = conn.cursor(dictionary=True)
    cursor.execute(query, params)
    rows = cursor.fetchall()
    cursor.close()
    return rows

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
LEFT JOIN emission_factors ef ON ef.category = p.category COLLATE utf8mb4_unicode_ci AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory COLLATE utf8mb4_unicode_ci
LEFT JOIN category_averages ca ON ca.category = p.category COLLATE utf8mb4_unicode_ci
WHERE p.user_id = %s AND p.status = 'completed'
ORDER BY p.schedule_date ASC, p.id ASC
"""
