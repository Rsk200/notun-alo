import os
import json
import logging
from math import radians, sin, cos, sqrt, atan2
import mysql.connector
from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib

app = Flask(__name__)
CORS(app)
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# Constants
WEIGHTS = {
    'load': 0.35,
    'completion': 0.25,
    'distance': 0.20,
    'rating': 0.12,
    'specialty': 0.08
}
MAX_DISTANCE_KM = 25.0

# Load Model
MODEL_PATH = 'models/completion_predictor.pkl'
predictor_data = None
if os.path.exists(MODEL_PATH):
    try:
        predictor_data = joblib.load(MODEL_PATH)
        logging.info(f"Loaded predictor model from {MODEL_PATH}")
    except Exception as e:
        logging.warning(f"Failed to load predictor model: {e}")
else:
    logging.warning(f"Predictor model not found at {MODEL_PATH}. Running in weighted-only mode.")

def get_db_connection():
    return mysql.connector.connect(
        host=os.environ.get('DB_HOST', 'localhost'),
        user=os.environ.get('DB_USER', 'root'),
        password=os.environ.get('DB_PASS', ''),
        database=os.environ.get('DB_NAME', 'notun_alo')
    )

def haversine(lat1, lon1, lat2, lon2):
    """Calculate the great circle distance in kilometers between two points on the earth."""
    if any(v is None for v in [lat1, lon1, lat2, lon2]):
        return MAX_DISTANCE_KM
    
    R = 6371.0 # Earth radius in km
    dlat = radians(lat2 - lat1)
    dlon = radians(lon2 - lon1)
    a = sin(dlat / 2)**2 + cos(radians(lat1)) * cos(radians(lat2)) * sin(dlon / 2)**2
    c = 2 * atan2(sqrt(a), sqrt(1 - a))
    return R * c

def specialty_score(agency_specialty, pickup_category):
    if not agency_specialty:
        return 0.5
    specialties = [s.strip().lower() for s in agency_specialty.split(',')]
    if pickup_category.lower() in specialties:
        return 1.0
    return 0.2

def score_agency(agency, pickup, predictor):
    # Base metrics from DB View
    load_ratio = min(float(agency['load_ratio'] or 0.0), 1.0)
    completion_rate = float(agency['completion_rate'] or 1.0)
    avg_rating = float(agency['avg_rating'] or 4.0)
    
    # Distance
    distance_km = haversine(
        float(pickup.get('user_lat')) if pickup.get('user_lat') else None, 
        float(pickup.get('user_lng')) if pickup.get('user_lng') else None,
        float(agency['lat']) if agency.get('lat') else None, 
        float(agency['lng']) if agency.get('lng') else None
    )
    
    # 5 Normalized Score Components (0.0 - 1.0)
    score_load = 1.0 - load_ratio
    score_distance = max(0.0, 1.0 - distance_km / MAX_DISTANCE_KM)
    score_rating = (avg_rating - 1.0) / 4.0
    score_spec = specialty_score(agency['specialty'], pickup['category'])
    
    # Predictor Integration for Completion Score
    predicted_hrs = None
    model_version = 'weighted_v1'
    
    if predictor and 'model' in predictor:
        try:
            # Need features: [agency_encoded, category_encoded, estimated_weight, day_of_week, hour_created, distance_km]
            le_agency = predictor['label_encoders']['agency']
            le_category = predictor['label_encoders']['category']
            
            # Safe transform with fallback to 0 if unseen label
            ag_enc = le_agency.transform([agency['agency_id']])[0] if agency['agency_id'] in le_agency.classes_ else 0
            cat_enc = le_category.transform([pickup['category']])[0] if pickup['category'] in le_category.classes_ else 0
            
            from datetime import datetime
            dt = datetime.strptime(pickup['schedule_date'], '%Y-%m-%d')
            day_of_week = dt.weekday() + 1 # 1-7
            hour_created = datetime.now().hour
            
            features = [[ag_enc, cat_enc, float(pickup['estimated_weight']), day_of_week, hour_created, distance_km]]
            predicted_hrs = predictor['model'].predict(features)[0]
            
            # Blend historical completion rate with predicted speed
            # If predicted < 48 hrs, it boosts the score.
            speed_score = max(0.0, 1.0 - min(predicted_hrs, 48.0) / 48.0)
            score_completion = completion_rate * 0.6 + speed_score * 0.4
            model_version = predictor.get('model_type', 'ml_v1')
            
        except Exception as e:
            logging.error(f"Prediction failed for agency {agency['agency_id']}: {e}")
            score_completion = completion_rate
    else:
        score_completion = completion_rate
        
    # Calculate weighted total
    score_total = (
        score_load * WEIGHTS['load'] +
        score_completion * WEIGHTS['completion'] +
        score_distance * WEIGHTS['distance'] +
        score_rating * WEIGHTS['rating'] +
        score_spec * WEIGHTS['specialty']
    )
    
    # Geographic Zone Clustering Bonus
    zone_bonus = 0.0
    # In a full system, we would query the agency_zones table.
    # Assuming agency dict contains 'zone_id' from the join.
    if 'zone_id' in pickup and 'zone_id' in agency and pickup['zone_id'] == agency['zone_id']:
        zone_bonus = 0.15
        
    final_score = min(score_total + zone_bonus, 1.0)
    
    # Reason builder
    spec_text = "match" if score_spec == 1.0 else "mismatch"
    reason = f"Load={load_ratio:.2f} | Dist={distance_km:.1f}km | Rat={avg_rating:.1f} | Spec={spec_text}"
    if zone_bonus > 0:
        reason += " | Zone=match"
        
    return {
        'agency_id': agency['agency_id'],
        'agency_name': agency['agency_name'],
        'score_total': float(final_score),
        'score_load': float(score_load),
        'score_completion': float(score_completion),
        'score_distance': float(score_distance),
        'score_rating': float(score_rating),
        'score_specialty': float(score_spec),
        'predicted_completion_hrs': float(predicted_hrs) if predicted_hrs is not None else None,
        'model_version': model_version,
        'reason': reason
    }

@app.route('/assign', methods=['POST'])
def assign_pickup():
    data = request.json
    required = ['pickup_id', 'category', 'estimated_weight', 'schedule_date']
    if not data or not all(k in data for k in required):
        return jsonify({"success": False, "reason": "Missing required fields"}), 400
        
    conn = None
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        
        # Query available agencies with capacity
        cursor.execute("""
            SELECT s.*, z.zone_id 
            FROM agency_stats s
            LEFT JOIN agency_zones z ON s.agency_id = z.agency_id
            WHERE s.is_available = 1 AND s.load_ratio < 1.0
        """)
        agencies = cursor.fetchall()
        
        if not agencies:
            return jsonify({"success": False, "reason": "no_agency_available"})
            
        # Score all agencies
        scored_agencies = [score_agency(ag, data, predictor_data) for ag in agencies]
        
        # Sort DESC by total score
        scored_agencies.sort(key=lambda x: x['score_total'], reverse=True)
        winner = scored_agencies[0]
        
        # Insert audit trail for all scored agencies
        insert_q = """
            INSERT INTO assignment_scores 
            (pickup_id, agency_id, score_total, score_load, score_completion, 
             score_distance, score_rating, score_specialty, predicted_completion_hrs, model_version)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """
        audit_data = []
        for s in scored_agencies:
            audit_data.append((
                data['pickup_id'], s['agency_id'], s['score_total'], s['score_load'],
                s['score_completion'], s['score_distance'], s['score_rating'], s['score_specialty'],
                s['predicted_completion_hrs'], s['model_version']
            ))
            
        cursor.executemany(insert_q, audit_data)
        conn.commit()
        
        return jsonify({
            "success": True,
            "agency_id": winner['agency_id'],
            "agency_name": winner['agency_name'],
            "score": winner['score_total'],
            "reason": winner['reason'],
            "predicted_completion_hrs": winner['predicted_completion_hrs'],
            "model_version": winner['model_version']
        })
        
    except Exception as e:
        logging.error(f"/assign error: {e}")
        return jsonify({"success": False, "reason": f"Internal server error: {e}"}), 500
    finally:
        if conn and conn.is_connected():
            cursor.close()
            conn.close()

@app.route('/agency-scores', methods=['GET'])
def get_agency_scores():
    pickup_id = request.args.get('pickup_id')
    if not pickup_id:
        return jsonify({"success": False, "reason": "pickup_id required"}), 400
        
    conn = None
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("""
            SELECT s.*, u.name as agency_name
            FROM assignment_scores s
            JOIN users u ON s.agency_id = u.id
            WHERE s.pickup_id = %s
            ORDER BY s.score_total DESC
        """, (pickup_id,))
        scores = cursor.fetchall()
        return jsonify({"success": True, "scores": scores})
    except Exception as e:
        return jsonify({"success": False, "reason": str(e)}), 500
    finally:
        if conn and conn.is_connected():
            cursor.close()
            conn.close()

@app.route('/health', methods=['GET'])
def health_check():
    conn = None
    agencies_count = 0
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT COUNT(*) FROM users WHERE role='agency' AND is_available=1")
        agencies_count = cursor.fetchone()[0]
    except Exception as e:
        pass
    finally:
        if conn and conn.is_connected():
            cursor.close()
            conn.close()
            
    return jsonify({
        "status": "ok",
        "model_loaded": predictor_data is not None,
        "agencies_available": agencies_count,
        "model_version": predictor_data['model_type'] if predictor_data else "weighted_v1"
    })

if __name__ == '__main__':
    # Test with:
    # curl -X POST http://localhost:5005/assign -H "Content-Type: application/json" -d '{"pickup_id":1, "category":"Plastic", "estimated_weight": 5.0, "schedule_date":"2026-05-20", "user_lat": 23.8, "user_lng": 90.4}'
    app.run(host='0.0.0.0', port=5005, debug=False)
