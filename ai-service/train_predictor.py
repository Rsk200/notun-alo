import os
import sys
import logging
from datetime import datetime
import pandas as pd
import numpy as np
import mysql.connector
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
from sklearn.preprocessing import LabelEncoder
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(message)s')

def get_db_connection():
    return mysql.connector.connect(
        host=os.environ.get('DB_HOST', 'localhost'),
        user=os.environ.get('DB_USER', 'root'),
        password=os.environ.get('DB_PASS', ''),
        database=os.environ.get('DB_NAME', 'notun_alo')
    )

def haversine_vectorized(lat1, lon1, lat2, lon2):
    """Vectorized haversine distance computation."""
    R = 6371.0
    lat1, lon1, lat2, lon2 = map(np.radians, [lat1, lon1, lat2, lon2])
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    a = np.sin(dlat/2.0)**2 + np.cos(lat1) * np.cos(lat2) * np.sin(dlon/2.0)**2
    c = 2 * np.arcsin(np.sqrt(a))
    return R * c

def main():
    logging.info("Starting ML Completion Time Predictor training...")
    
    # 1. Connect and Extract Data
    conn = get_db_connection()
    query = """
        SELECT 
            p.agency_id, 
            p.category, 
            p.estimated_weight, 
            DAYOFWEEK(p.schedule_date) as day_of_week, 
            HOUR(p.created_at) as hour_created, 
            TIMESTAMPDIFF(HOUR, p.created_at, p.updated_at) as completion_hrs,
            u_user.lat as user_lat, 
            u_user.lng as user_lng,
            u_agency.lat as agency_lat, 
            u_agency.lng as agency_lng
        FROM pickups p
        JOIN users u_user ON p.user_id = u_user.id
        JOIN users u_agency ON p.agency_id = u_agency.id
        WHERE p.status = 'completed'
        AND TIMESTAMPDIFF(HOUR, p.created_at, p.updated_at) BETWEEN 0.5 AND 72
    """
    df = pd.read_sql(query, conn)
    conn.close()
    
    n_samples = len(df)
    logging.info(f"Extracted {n_samples} valid completed pickups.")
    
    # 2. Check Sufficiency
    if n_samples < 10:
        logging.error("Need 10+ completed pickups. Exiting.")
        sys.exit(0)
    if n_samples < 200:
        logging.warning("Model trained on sparse data, predictions may be unreliable.")
        
    # 3. Feature Engineering
    df = df.dropna(subset=['user_lat', 'user_lng', 'agency_lat', 'agency_lng', 'category', 'agency_id'])
    df['distance_km'] = haversine_vectorized(
        df['user_lat'], df['user_lng'], df['agency_lat'], df['agency_lng']
    )
    
    le_agency = LabelEncoder()
    df['agency_encoded'] = le_agency.fit_transform(df['agency_id'])
    
    le_cat = LabelEncoder()
    df['category_encoded'] = le_cat.fit_transform(df['category'])
    
    features = ['agency_encoded', 'category_encoded', 'estimated_weight', 'day_of_week', 'hour_created', 'distance_km']
    X = df[features]
    y = df['completion_hrs']
    
    # 4. Train Models
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    models = {
        'RF': RandomForestRegressor(n_estimators=100, random_state=42),
        'GB': GradientBoostingRegressor(n_estimators=100, learning_rate=0.1, random_state=42)
    }
    
    best_model = None
    best_name = ""
    best_mae = float('inf')
    
    for name, model in models.items():
        model.fit(X_train, y_train)
        preds = model.predict(X_test)
        mae = mean_absolute_error(y_test, preds)
        rmse = np.sqrt(mean_squared_error(y_test, preds))
        r2 = r2_score(y_test, preds)
        cv_scores = cross_val_score(model, X, y, cv=5, scoring='neg_mean_absolute_error')
        
        logging.info(f"--- Model: {name} ---")
        logging.info(f"MAE: {mae:.2f} hrs, RMSE: {rmse:.2f} hrs, R²: {r2:.2f}")
        logging.info(f"5-fold CV MAE: {-cv_scores.mean():.2f} ± {cv_scores.std():.2f} hrs")
        
        if mae < best_mae:
            best_mae = mae
            best_model = model
            best_name = name
            
    logging.info(f"*** Selected {best_name} as the winning model (MAE={best_mae:.2f}) ***")
    
    # 5. Save Model
    os.makedirs('models', exist_ok=True)
    model_path = 'models/completion_predictor.pkl'
    
    save_data = {
        'model': best_model,
        'label_encoders': {'agency': le_agency, 'category': le_cat},
        'feature_names': features,
        'trained_on': len(df),
        'trained_at': datetime.now().isoformat(),
        'model_type': best_name,
        'test_mae': float(best_mae)
    }
    joblib.dump(save_data, model_path)
    logging.info(f"Saved model to {model_path}")
    
    # 6. Feature Importances
    importances = best_model.feature_importances_
    indices = np.argsort(importances)[::-1]
    logging.info("Top Feature Importances:")
    for f in range(min(5, len(features))):
        logging.info(f"  {f+1}. {features[indices[f]]} ({importances[indices[f]]:.4f})")
        
    # 7. Test Prediction Example (Agency=1, Plastic=approx enc, 3kg, Tue(3), 10am, 5km)
    try:
        sample_ag = le_agency.transform([1])[0] if 1 in le_agency.classes_ else 0
        sample_cat = le_cat.transform(['Plastic'])[0] if 'Plastic' in le_cat.classes_ else 0
        sample_feat = np.array([[sample_ag, sample_cat, 3.0, 3, 10, 5.0]])
        pred = best_model.predict(sample_feat)[0]
        logging.info(f"Sample prediction (Agency=1, Plastic, 3kg, Tue 10am, 5km) -> ETA: {pred:.1f} hours")
    except Exception as e:
        pass

if __name__ == '__main__':
    main()
