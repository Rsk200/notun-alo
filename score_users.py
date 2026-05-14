
from pathlib import Path

import joblib
import mysql.connector
import pandas as pd


MYSQL_HOST = "localhost"
MYSQL_DATABASE = "notun_alo"
MYSQL_USER = "root"
MYSQL_PASSWORD = ""
MYSQL_PORT = 3306

BASE_DIR = Path(__file__).resolve().parent

MODEL_PATH = BASE_DIR / "notun_alo_churn_model.pkl"

FEATURE_QUERY_PATH = BASE_DIR / "feature_query.sql"


def risk_label(score: float) -> str:

    if score > 0.70:
        return "high"

    if score >= 0.40:
        return "medium"

    return "low"


def main():

    # =========================================
    # LOAD MODEL
    # =========================================

    bundle = joblib.load(MODEL_PATH)

    pipeline = bundle["pipeline"]

    feature_columns = bundle["feature_columns"]

    # =========================================
    # LOAD SQL QUERY
    # =========================================

    with FEATURE_QUERY_PATH.open(
        "r",
        encoding="utf-8"
    ) as file:

        query = file.read()

    # =========================================
    # DATABASE CONNECTION
    # =========================================

    conn = mysql.connector.connect(
        host=MYSQL_HOST,
        database=MYSQL_DATABASE,
        user=MYSQL_USER,
        password=MYSQL_PASSWORD,
        port=MYSQL_PORT,
    )

    try:

        cursor = conn.cursor(dictionary=True)

        cursor.execute(query)

        df = pd.DataFrame(cursor.fetchall())

        # =========================================
        # HANDLE EMPTY
        # =========================================

        if df.empty:

            print(
                "No active users found for scoring."
            )

            return

        # =========================================
        # HANDLE NULL VALUES
        # =========================================

        if "days_since_last_pickup" in df.columns:

            df["days_since_last_pickup"] = df[
                "days_since_last_pickup"
            ].fillna(999)

        # =========================================
        # CHECK MODEL FEATURES
        # =========================================

        missing = [

            col

            for col in feature_columns

            if col not in df.columns

        ]

        if missing:

            raise ValueError(
                f"Feature query is missing model columns: {missing}"
            )

        # =========================================
        # PREDICT
        # =========================================

        scores = pipeline.predict_proba(
            df[feature_columns]
        )[:, 1]

        output = pd.DataFrame(
            {
                "user_id": df["user_id"].astype(int),

                "churn_score": scores,

                "risk_label": [
                    risk_label(float(score))
                    for score in scores
                ],
            }
        )

        # =========================================
        # CREATE TABLE
        # =========================================

        cursor = conn.cursor()

        cursor.execute(
            """
            CREATE TABLE IF NOT EXISTS user_ml_scores (

                user_id INT NOT NULL PRIMARY KEY,

                churn_score DECIMAL(6,5) NOT NULL,

                risk_label ENUM(
                    'low',
                    'medium',
                    'high'
                ) NOT NULL,

                updated_at TIMESTAMP
                    DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,

                CONSTRAINT fk_user_ml_scores_user
                    FOREIGN KEY (user_id)
                    REFERENCES users(id)
                    ON DELETE CASCADE

            ) ENGINE=InnoDB;
            """
        )

        # =========================================
        # UPSERT
        # =========================================

        upsert_sql = """
            INSERT INTO user_ml_scores
            (
                user_id,
                churn_score,
                risk_label,
                updated_at
            )

            VALUES
            (
                %s,
                %s,
                %s,
                NOW()
            )

            ON DUPLICATE KEY UPDATE

                churn_score = VALUES(churn_score),

                risk_label = VALUES(risk_label),

                updated_at = NOW();
        """

        cursor.executemany(

            upsert_sql,

            [
                (
                    int(row.user_id),

                    round(
                        float(row.churn_score),
                        5
                    ),

                    row.risk_label,
                )

                for row in output.itertuples(
                    index=False
                )
            ],
        )

        conn.commit()

        # =========================================
        # SUCCESS MESSAGE
        # =========================================

        counts = output[
            "risk_label"
        ].value_counts().to_dict()

        print(
            f"Scored {len(output)} users: "
            f"{counts.get('high', 0)} high-risk, "
            f"{counts.get('medium', 0)} medium-risk, "
            f"{counts.get('low', 0)} low-risk users."
        )

    finally:

        conn.close()


if __name__ == "__main__":

    main()
