from __future__ import annotations

import os
from datetime import date, timedelta
from typing import Any
import mysql.connector
from dotenv import load_dotenv

from environmental_engine import EWASTE_MESSAGE

load_dotenv()

DB_CONFIG = {
    "host": os.getenv("NOTUN_ALO_DB_HOST", "localhost"),
    "user": os.getenv("NOTUN_ALO_DB_USER", "root"),
    "password": os.getenv("NOTUN_ALO_DB_PASS", ""),
    "database": os.getenv("NOTUN_ALO_DB_NAME", "notun_alo"),
}


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def consistency_score(pickup_count: int) -> float:
    if pickup_count <= 1:
        return 0.1
    if pickup_count <= 4:
        return 0.5
    if pickup_count <= 7:
        return 0.8
    return 1.0


def calculate_eco_score(co2: float, water: float, energy: float, consistency: float, top_category: str) -> float:
    base = (co2 * 0.5) + (water * 0.2) + (energy * 0.2) + (consistency * 0.1)
    if top_category == "E-waste":
        base *= 1.5
    return round(base, 2)


def _fetch_rows(conn) -> list[dict[str, Any]]:
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT
            u.id AS user_id,
            u.name AS user_name,
            p.category,
            COUNT(p.id) AS pickup_count,
            SUM(p.estimated_weight) AS total_weight,
            SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)) AS co2_saved,
            SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)) AS water_saved,
            SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)) AS energy_saved,
            SUM(CASE WHEN p.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS last_month_pickups
        FROM users u
        JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
        LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
        LEFT JOIN category_averages ca ON ca.category = p.category
        WHERE u.role = 'user'
        GROUP BY u.id, u.name, p.category
    """)
    rows = cursor.fetchall()
    cursor.close()
    return rows


def build_leaderboard(conn=None) -> list[dict[str, Any]]:
    close_conn = False
    if conn is None:
        conn = get_connection()
        close_conn = True
    try:
        grouped: dict[int, dict[str, Any]] = {}
        for row in _fetch_rows(conn):
            user = grouped.setdefault(row["user_id"], {
                "user_name": row["user_name"], "pickup_count": 0, "last_month_pickups": 0,
                "total_weight": 0.0, "co2_saved": 0.0, "water_saved": 0.0, "energy_saved": 0.0, "categories": {}
            })
            category = row["category"] or "Unknown"
            co2 = float(row["co2_saved"] or 0)
            user["pickup_count"] += int(row["pickup_count"] or 0)
            user["last_month_pickups"] += int(row["last_month_pickups"] or 0)
            user["total_weight"] += float(row["total_weight"] or 0)
            user["co2_saved"] += co2
            user["water_saved"] += float(row["water_saved"] or 0)
            user["energy_saved"] += float(row["energy_saved"] or 0)
            user["categories"][category] = user["categories"].get(category, 0.0) + co2

        entries = []
        for user in grouped.values():
            top_category = max(user["categories"], key=user["categories"].get) if user["categories"] else "Unknown"
            score = calculate_eco_score(user["co2_saved"], user["water_saved"], user["energy_saved"], consistency_score(user["pickup_count"]), top_category)
            user.update({"top_category": top_category, "eco_score": score})
            entries.append(user)

        entries.sort(key=lambda item: item["eco_score"], reverse=True)
        top_ten_cutoff = max(1, int(len(entries) * 0.1)) if entries else 0
        ewaste_scores = [item["eco_score"] for item in entries if item["top_category"] == "E-waste"]
        max_ewaste = max(ewaste_scores) if ewaste_scores else None

        response = []
        for index, item in enumerate(entries, start=1):
            badges = []
            if item["last_month_pickups"] >= 5:
                badges.append("Active Recycler")
            if item["total_weight"] >= 100:
                badges.append("Eco Warrior")
            if index <= top_ten_cutoff:
                badges.append("Forest Guardian")
            if item["top_category"] == "E-waste" and item["eco_score"] == max_ewaste:
                badges.append("E-waste Hero")
            if not badges:
                badges.append("Recycler")
            response.append({
                "rank": index,
                "user_name": item["user_name"],
                "eco_score": item["eco_score"],
                "badge": ", ".join(badges),
                "co2_saved": round(item["co2_saved"], 2),
                "top_category": item["top_category"],
                "ewaste_note": EWASTE_MESSAGE if item["top_category"] == "E-waste" else None,
            })
        return response
    finally:
        if close_conn:
            conn.close()


if __name__ == "__main__":
    import json
    print(json.dumps(build_leaderboard(), indent=2))
