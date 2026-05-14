import sys
import json
import os
from pathlib import Path

# Add ai-service to path
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

from db_utils import get_conn, fetch_dicts, HISTORY_SQL
from forecast_engine import forecast_from_history

def get_forecast(user_id):
    conn = get_conn()
    try:
        history = fetch_dicts(conn, HISTORY_SQL, (user_id,))
    finally:
        conn.close()
    return {"user_id": user_id, **forecast_from_history(history)}

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error": "Missing args"}))
        sys.exit(1)
        
    action = sys.argv[1]
    try:
        user_id = int(sys.argv[2])
    except ValueError:
        print(json.dumps({"error": "Invalid user_id"}))
        sys.exit(1)
    
    try:
        if action == "forecast":
            print(json.dumps(get_forecast(user_id)))
        else:
            # action 'impact' is now handled by PHP directly, but we keep a stub here
            print(json.dumps({"error": "Action handled by PHP"}))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
