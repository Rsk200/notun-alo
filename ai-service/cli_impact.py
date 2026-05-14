import sys
import json
import os

# Add ai-service to path so it can import local modules
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from impact_api import get_conn, ensure_schema, fetch_dicts, USER_IMPACT_SQL, HISTORY_SQL, as_float
from environmental_engine import CAR_CO2_KG_PER_KM, PHONE_CHARGE_KWH, WATER_BOTTLE_LITERS, EWASTE_MESSAGE
from forecast_engine import forecast_from_history

def get_impact(user_id):
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
    return {
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
    }

def get_forecast(user_id):
    conn = get_conn()
    try:
        ensure_schema(conn)
        history = fetch_dicts(conn, HISTORY_SQL, (user_id,))
    finally:
        conn.close()
    return {"user_id": user_id, **forecast_from_history(history)}

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Missing args"}))
        sys.exit(1)
        
    action = sys.argv[1]
    user_id = int(sys.argv[2])
    
    try:
        if action == "impact":
            print(json.dumps(get_impact(user_id)))
        elif action == "forecast":
            print(json.dumps(get_forecast(user_id)))
        else:
            print(json.dumps({"error": "Invalid action"}))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
