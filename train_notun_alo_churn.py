import os
from pathlib import Path

os.environ.setdefault("MPLCONFIGDIR", str(Path.cwd() / ".matplotlib"))
Path(os.environ["MPLCONFIGDIR"]).mkdir(parents=True, exist_ok=True)

import joblib
import matplotlib.pyplot as plt
import pandas as pd
import seaborn as sns
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import (
    accuracy_score,
    classification_report,
    confusion_matrix,
    f1_score,
    precision_score,
    recall_score,
    roc_auc_score,
)
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler
from xgboost import XGBClassifier


DATA_PATH = Path(r"C:\xampp1\htdocs\notun_alo\E Commerce Dataset.xlsx")
OUTPUT_DIR = Path.cwd()
TARGET = "Churn"
ID_COLUMNS = ["CustomerID"]
RANDOM_STATE = 42


def log(section, text=""):
    print(f"\n{'=' * 80}\n{section}\n{'=' * 80}")
    if text:
        print(text)


def clean_payment_mode(value):
    if pd.isna(value):
        return value
    mapping = {
        "CC": "Credit Card",
        "COD": "Cash on Delivery",
    }
    return mapping.get(str(value).strip(), str(value).strip())


def clean_login_device(value):
    if pd.isna(value):
        return value
    value = str(value).strip()
    if value == "Phone":
        return "Mobile Phone"
    return value


def load_dataset():
    df = pd.read_excel(DATA_PATH, sheet_name="E Comm")
    df.columns = [str(col).strip() for col in df.columns]
    if TARGET not in df.columns:
        raise ValueError(f"Expected target column '{TARGET}' was not found.")

    if "PreferredPaymentMode" in df.columns:
        df["PreferredPaymentMode"] = df["PreferredPaymentMode"].apply(clean_payment_mode)
    if "PreferredLoginDevice" in df.columns:
        df["PreferredLoginDevice"] = df["PreferredLoginDevice"].apply(clean_login_device)
    if "PreferedOrderCat" in df.columns:
        df["PreferedOrderCat"] = df["PreferedOrderCat"].replace({"Mobile": "Mobile Phone"})
    return df


def make_preprocessor(X):
    numeric_features = X.select_dtypes(include=["number"]).columns.tolist()
    categorical_features = [col for col in X.columns if col not in numeric_features]

    numeric_pipeline = Pipeline(
        steps=[
            ("imputer", SimpleImputer(strategy="median")),
            ("scaler", StandardScaler()),
        ]
    )
    categorical_pipeline = Pipeline(
        steps=[
            ("imputer", SimpleImputer(strategy="most_frequent")),
            ("onehot", OneHotEncoder(handle_unknown="ignore", sparse_output=False)),
        ]
    )

    preprocessor = ColumnTransformer(
        transformers=[
            ("num", numeric_pipeline, numeric_features),
            ("cat", categorical_pipeline, categorical_features),
        ],
        remainder="drop",
        verbose_feature_names_out=False,
    )
    return preprocessor, numeric_features, categorical_features


def print_step_1(df):
    log("STEP 1 - EXPLORE & UNDERSTAND THE DATASET")
    print(f"Shape: {df.shape[0]} rows, {df.shape[1]} columns\n")
    print("Column names and dtypes:")
    print(df.dtypes.to_string())
    print("\nMissing values per column:")
    print(df.isna().sum().to_string())
    print("\nClass balance:")
    print(df[TARGET].value_counts().rename(index={0: "Not churned (0)", 1: "Churned (1)"}).to_string())
    print("\nFirst 5 rows:")
    print(df.head().to_string(index=False))


def print_step_2_and_plot(df):
    log("STEP 2 - CLEAN & PREPROCESS")
    numeric_cols = df.select_dtypes(include=["number"]).columns.tolist()
    numeric_no_target = [col for col in numeric_cols if col != TARGET]
    categorical_cols = [col for col in df.columns if col not in numeric_cols]

    cleaned = df.copy()
    for col in numeric_no_target:
        cleaned[col] = cleaned[col].fillna(cleaned[col].median())
    for col in categorical_cols:
        mode = cleaned[col].mode(dropna=True)
        cleaned[col] = cleaned[col].fillna(mode.iloc[0] if not mode.empty else "Unknown")

    print(f"Numeric columns filled with median: {numeric_no_target}")
    print(f"Categorical columns filled with mode: {categorical_cols}")

    corr = cleaned[numeric_cols].corr(numeric_only=True)[TARGET].drop(TARGET).sort_values(key=lambda s: s.abs(), ascending=False)
    top5 = corr.head(5)

    plt.figure(figsize=(10, 8))
    sns.heatmap(
        cleaned[numeric_cols].corr(numeric_only=True),
        annot=True,
        fmt=".2f",
        cmap="coolwarm",
        center=0,
        linewidths=0.4,
    )
    plt.title("Numeric Feature Correlation Heatmap")
    plt.tight_layout()
    heatmap_path = OUTPUT_DIR / "churn_correlation_heatmap.png"
    plt.savefig(heatmap_path, dpi=180)
    plt.close()

    print(f"\nCorrelation heatmap saved to: {heatmap_path}")
    print("\nTop 5 numeric features most correlated with Churn:")
    print(top5.to_string())
    return cleaned, top5


def print_step_3_mapping():
    log("STEP 3 - FEATURE MAPPING")
    rows = [
        ("Tenure", "TIMESTAMPDIFF(MONTH, users.created_at, NOW())"),
        ("PreferredLoginDevice", "Not stored yet; default 'Mobile Phone' or add device tracking from login/session logs"),
        ("CityTier", "CASE from users.address: Dhaka/Chattogram as 1, major cities as 2, others as 3"),
        ("WarehouseToHome", "Not stored yet; set 0 or add geocoded distance from nearest pickup hub"),
        ("PreferredPaymentMode", "Not stored yet; default from order/payment table when added, else 'Cash on Delivery'"),
        ("Gender", "Not stored; use 'Unknown' or add optional profile field"),
        ("HourSpendOnApp", "Not stored; proxy with recent activity count or add app analytics table"),
        ("NumberOfDeviceRegistered", "Not stored; default 1 or count distinct login devices when added"),
        ("PreferedOrderCat", "Most frequent pickups.category or most purchased eco-product category"),
        ("SatisfactionScore", "Proxy score from completed pickups, complaints, and pickup recency"),
        ("MaritalStatus", "Not relevant/stored; use 'Unknown'"),
        ("NumberOfAddress", "1 if users.address exists, else 0; better: count saved addresses if table added"),
        ("Complain", "1 if user has delayed/pending/complaint ticket; currently proxy from old pending pickups"),
        ("OrderAmountHikeFromlastYear", "Year-over-year growth in completed pickup weight or order points"),
        ("CouponUsed", "Count bonus/reward campaigns used; currently default 0 unless a coupons table is added"),
        ("OrderCount", "COUNT(pickups.id) WHERE pickups.status='completed'"),
        ("DaySinceLastOrder", "DATEDIFF(CURDATE(), MAX(pickups.schedule_date))"),
        ("CashbackAmount", "COALESCE(rewards.total_points, 0)"),
    ]
    print(f"{'Kaggle Column':<32} | Notun Alo Equivalent")
    print(f"{'-' * 32}-|-{'-' * 74}")
    for kaggle, equivalent in rows:
        print(f"{kaggle:<32} | {equivalent}")


def evaluate_models(df):
    log("STEP 4 - TRAIN 3 MODELS")
    feature_columns = [col for col in df.columns if col not in ID_COLUMNS + [TARGET]]
    X = df[feature_columns]
    y = df[TARGET].astype(int)

    X_train, X_test, y_train, y_test = train_test_split(
        X,
        y,
        test_size=0.2,
        random_state=RANDOM_STATE,
        stratify=y,
    )

    _, numeric_features, categorical_features = make_preprocessor(X)
    neg, pos = y_train.value_counts().sort_index().tolist()
    scale_pos_weight = neg / pos

    models = {
        "Logistic Regression": LogisticRegression(
            class_weight="balanced",
            max_iter=2000,
            solver="liblinear",
            random_state=RANDOM_STATE,
        ),
        "Random Forest": RandomForestClassifier(
            n_estimators=100,
            class_weight="balanced",
            random_state=RANDOM_STATE,
            n_jobs=1,
        ),
        "XGBoost": XGBClassifier(
            n_estimators=250,
            max_depth=4,
            learning_rate=0.05,
            subsample=0.9,
            colsample_bytree=0.9,
            eval_metric="logloss",
            scale_pos_weight=scale_pos_weight,
            random_state=RANDOM_STATE,
            n_jobs=1,
        ),
    }

    results = []
    fitted = {}
    for name, model in models.items():
        preprocessor, _, _ = make_preprocessor(X)
        pipeline = Pipeline(
            steps=[
                ("preprocessor", preprocessor),
                ("model", model),
            ]
        )
        pipeline.fit(X_train, y_train)
        y_pred = pipeline.predict(X_test)
        y_proba = pipeline.predict_proba(X_test)[:, 1]
        roc_auc = roc_auc_score(y_test, y_proba)

        print(f"\n{name}")
        print("-" * len(name))
        print(classification_report(y_test, y_pred, digits=4))
        print(f"ROC-AUC: {roc_auc:.4f}")
        print("Confusion matrix:")
        print(confusion_matrix(y_test, y_pred))

        results.append(
            {
                "model": name,
                "accuracy": accuracy_score(y_test, y_pred),
                "precision": precision_score(y_test, y_pred, zero_division=0),
                "recall": recall_score(y_test, y_pred, zero_division=0),
                "f1": f1_score(y_test, y_pred, zero_division=0),
                "roc_auc": roc_auc,
            }
        )
        fitted[name] = pipeline

    return pd.DataFrame(results).sort_values("roc_auc", ascending=False), fitted, feature_columns, numeric_features, categorical_features


def get_feature_importance(pipeline):
    preprocessor = pipeline.named_steps["preprocessor"]
    model = pipeline.named_steps["model"]
    feature_names = preprocessor.get_feature_names_out()

    if hasattr(model, "feature_importances_"):
        importance = model.feature_importances_
    elif hasattr(model, "coef_"):
        importance = abs(model.coef_[0])
    else:
        raise ValueError("Winning model does not expose feature importance.")

    return (
        pd.DataFrame({"feature": feature_names, "importance": importance})
        .sort_values("importance", ascending=False)
        .head(10)
    )


def save_best_model(summary, fitted, feature_columns, numeric_features, categorical_features):
    log("STEP 5 - PICK THE BEST MODEL")
    print("ROC-AUC comparison:")
    print(summary.to_string(index=False, formatters={
        "accuracy": "{:.4f}".format,
        "precision": "{:.4f}".format,
        "recall": "{:.4f}".format,
        "f1": "{:.4f}".format,
        "roc_auc": "{:.4f}".format,
    }))

    best_name = summary.iloc[0]["model"]
    best_pipeline = fitted[best_name]
    print(f"\nBest model selected automatically: {best_name}")

    top_importance = get_feature_importance(best_pipeline)
    plt.figure(figsize=(10, 6))
    sns.barplot(data=top_importance, y="feature", x="importance", hue="feature", dodge=False, legend=False, palette="viridis")
    plt.title(f"Top 10 Feature Importance - {best_name}")
    plt.xlabel("Importance")
    plt.ylabel("")
    plt.tight_layout()
    importance_path = OUTPUT_DIR / "feature_importance_top10.png"
    plt.savefig(importance_path, dpi=180)
    plt.close()
    print(f"Feature importance plot saved to: {importance_path}")
    print("\nTop 10 features:")
    print(top_importance.to_string(index=False))

    bundle = {
        "pipeline": best_pipeline,
        "model_name": best_name,
        "feature_columns": feature_columns,
        "numeric_features": numeric_features,
        "categorical_features": categorical_features,
        "metrics": summary.to_dict(orient="records"),
        "risk_thresholds": {"high": 0.70, "medium": 0.40},
    }
    model_path = OUTPUT_DIR / "notun_alo_churn_model.pkl"
    joblib.dump(bundle, model_path)
    print(f"\nSaved model bundle to: {model_path}")
    return best_name, top_importance


def main():
    df = load_dataset()
    print_step_1(df)
    cleaned, top5_corr = print_step_2_and_plot(df)
    print_step_3_mapping()
    summary, fitted, feature_columns, numeric_features, categorical_features = evaluate_models(df)
    best_name, top_importance = save_best_model(summary, fitted, feature_columns, numeric_features, categorical_features)

    log("STEP 7 - FINAL SUMMARY")
    print(f"- Winning model: {best_name}, selected by highest ROC-AUC.")
    print("- The top churn signals from numeric correlation are:")
    for feature, value in top5_corr.head(3).items():
        print(f"  - {feature}: correlation {value:.4f}")
    print("- When moving from Kaggle data to under 500 real Notun Alo users, expect unstable accuracy; treat scores as risk ranking, not final truth.")
    print("- With small local data, ROC-AUC around 0.65-0.75 is realistic if behavior fields are captured consistently.")
    print("- After 3 months, retrain using actual pickup frequency, missed pickups, points earned, complaint history, and days since last completed pickup.")
    print("- Improve performance by adding event logs: login device, last activity, bonus point usage, pickup cancellations, and product purchases.")


if __name__ == "__main__":
    main()
