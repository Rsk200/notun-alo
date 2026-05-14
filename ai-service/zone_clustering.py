import os
import sys
import logging
import pandas as pd
import mysql.connector
from sklearn.cluster import KMeans
import matplotlib.pyplot as plt

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(message)s')

def get_db_connection():
    return mysql.connector.connect(
        host=os.environ.get('DB_HOST', 'localhost'),
        user=os.environ.get('DB_USER', 'root'),
        password=os.environ.get('DB_PASS', ''),
        database=os.environ.get('DB_NAME', 'notun_alo')
    )

def main():
    logging.info("Starting Geographic Zone Clustering...")
    conn = get_db_connection()
    
    # 1. Extract Pickup Coordinates
    query = """
        SELECT p.agency_id, u.lat, u.lng 
        FROM pickups p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'completed' AND u.lat IS NOT NULL AND u.lng IS NOT NULL
    """
    df = pd.read_sql(query, conn)
    
    if len(df) < 30:
        logging.error(f"Need 30+ pickups for clustering. Found only {len(df)}.")
        sys.exit(0)
        
    coords = df[['lat', 'lng']].values
    
    # 2. KMeans with k=4 (Dhaka quadrants)
    k = 4
    kmeans = KMeans(n_clusters=k, random_state=42, n_init=10)
    df['zone_id'] = kmeans.fit_predict(coords)
    
    # Optional: Save Elbow plot
    try:
        inertias = []
        K_range = range(2, 9)
        for i in K_range:
            km = KMeans(n_clusters=i, random_state=42, n_init=10).fit(coords)
            inertias.append(km.inertia_)
        plt.figure()
        plt.plot(K_range, inertias, 'bx-')
        plt.xlabel('k')
        plt.ylabel('Inertia')
        plt.title('Elbow Method for Optimal k')
        os.makedirs('models', exist_ok=True)
        plt.savefig('models/elbow_plot.png')
        logging.info("Saved Elbow plot to models/elbow_plot.png")
    except Exception as e:
        logging.warning(f"Could not plot elbow: {e}")
        
    # 3. Assign agency to zone based on their most frequent cluster
    # Or centroid of their completed pickups
    cursor = conn.cursor()
    cursor.execute("TRUNCATE TABLE agency_zones")
    
    agency_zones = df.groupby('agency_id')['zone_id'].agg(lambda x: x.value_counts().index[0]).reset_index()
    
    insert_data = []
    zone_labels = {0: 'North', 1: 'South', 2: 'East', 3: 'West'} # Simple heuristic labels
    
    for _, row in agency_zones.iterrows():
        ag_id = int(row['agency_id'])
        z_id = int(row['zone_id'])
        insert_data.append((ag_id, z_id, zone_labels.get(z_id, f"Zone_{z_id}")))
        
    cursor.executemany("""
        INSERT INTO agency_zones (agency_id, zone_id, zone_label)
        VALUES (%s, %s, %s)
    """, insert_data)
    
    conn.commit()
    cursor.close()
    conn.close()
    
    logging.info(f"Assigned {len(insert_data)} agencies to {k} zones. DB updated successfully.")

if __name__ == '__main__':
    main()
