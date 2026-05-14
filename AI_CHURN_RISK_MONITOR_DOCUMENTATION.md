# AI Churn Risk Monitor Documentation

Project: Notun Alo Recycling Platform  
Feature: AI/ML-based user churn prediction  
Prepared for: Beginner-friendly reading and project explanation

---

## 1. What Is the AI Churn Risk Monitor?

The **AI Churn Risk Monitor** is a machine learning feature added to the Notun Alo admin dashboard.

In simple words, it tries to answer this question:

> Which users are likely to stop using Notun Alo?

In business and machine learning, this is called **churn prediction**.

A user is considered at risk of churn when they show behavior such as:

- not scheduling pickups recently
- having old pending or assigned pickups
- having low reward points
- having very few completed pickups
- being a new user with weak platform activity

The admin can use this information to take action before the user becomes inactive. For example, the admin can send bonus points to high-risk users.

---

## 2. Why This Feature Was Added to Notun Alo

Notun Alo is a recycling platform where users:

- schedule waste pickups
- recycle Paper, Plastic, and Metal
- earn reward points
- buy eco-friendly products

For this type of platform, user retention is important. If users stop scheduling pickups, the platform loses recycling activity.

The AI Churn Risk Monitor helps the admin identify risky users early.

Example:

If a user has not completed pickups recently and has a delayed pickup, the system may mark them as **high risk**.

---

## 3. Dataset Used

The machine learning model was trained using the Kaggle **E-Commerce Customer Churn Dataset**.

Dataset file used in this project:

```text
C:\xampp1\htdocs\notun_alo\E Commerce Dataset.xlsx
```

This dataset contains customer behavior information from an e-commerce platform.

Important columns from the dataset include:

| Dataset Column | Meaning |
|---|---|
| `CustomerID` | Unique customer ID |
| `Churn` | Target value. `1` means churned, `0` means not churned |
| `Tenure` | How long the customer has been with the platform |
| `PreferredLoginDevice` | Device used by the customer |
| `CityTier` | City category |
| `WarehouseToHome` | Distance from warehouse to customer home |
| `PreferredPaymentMode` | Customer payment method |
| `HourSpendOnApp` | Time spent on the app |
| `NumberOfDeviceRegistered` | Number of registered devices |
| `PreferedOrderCat` | Preferred order category |
| `SatisfactionScore` | Customer satisfaction score |
| `Complain` | Whether the customer complained |
| `OrderCount` | Number of orders |
| `DaySinceLastOrder` | Days since the last order |
| `CashbackAmount` | Cashback amount received |

Dataset size:

```text
Rows: 5630
Columns: 20
```

Class balance:

```text
Not churned: 4682
Churned: 948
```

This means most customers did not churn, and fewer customers churned. This is called an imbalanced dataset.

---

## 4. Why an E-Commerce Dataset Was Used for a Recycling Platform

Notun Alo does not yet have thousands of real users. A machine learning model needs enough data to learn patterns.

Because Notun Alo has similar behavior patterns to an e-commerce system, the Kaggle dataset was used as a starting point.

Similarities:

| E-Commerce Platform | Notun Alo Platform |
|---|---|
| Customer orders products | User schedules recycling pickups |
| Cashback | Reward points |
| Order count | Completed pickup count |
| Days since last order | Days since last pickup |
| Complaint | Delayed or old pending pickup |
| Customer churn | User becomes inactive |

So the Kaggle dataset was used to train the first version of the model, and then the features were mapped to Notun Alo's database.

---

## 5. Files Created for This Feature

The following files are part of the AI Churn Risk Monitor:

| File | Purpose |
|---|---|
| `train_notun_alo_churn.py` | Trains the machine learning models using the Excel dataset |
| `notun_alo_churn_model.pkl` | Saved trained model |
| `feature_query.sql` | Extracts user behavior features from the Notun Alo MySQL database |
| `score_users.py` | Loads the model, predicts churn risk, and saves results to MySQL |
| `admin_churn_table.php` | Shows high-risk users in the admin dashboard |
| `seed_churn_test_data.php` | Creates demo users and pickup behavior for testing |
| `churn_correlation_heatmap.png` | Correlation heatmap generated during training |
| `feature_importance_top10.png` | Top feature importance chart |

---

## 6. Full Process from Start to Finish

### Step 1: Load and Explore the Dataset

The Excel dataset was loaded using Python and pandas.

The script checked:

- number of rows and columns
- column names
- data types
- missing values
- churn class balance
- first five rows

This helped us understand what data was available before training.

---

### Step 2: Clean the Dataset

The dataset had some missing values.

Cleaning rules:

- Missing numeric values were filled using the median.
- Missing categorical values were filled using the mode.
- Categorical columns were converted using one-hot encoding.

Example:

`PreferredPaymentMode` is text, so the model cannot directly understand it. One-hot encoding converts it into machine-readable columns.

---

### Step 3: Analyze Important Features

A correlation heatmap was created to understand which numeric features are related to churn.

Top correlated churn signals:

| Feature | Meaning |
|---|---|
| `Tenure` | Shorter-tenure users are more likely to churn |
| `Complain` | Complaints increase churn risk |
| `DaySinceLastOrder` | User activity recency matters |
| `CashbackAmount` | Reward value affects retention |
| `NumberOfDeviceRegistered` | Device behavior can indicate risk |

The heatmap image is saved as:

```text
churn_correlation_heatmap.png
```

---

### Step 4: Train Three Machine Learning Models

Three models were trained:

1. Logistic Regression
2. Random Forest Classifier
3. XGBoost Classifier

Each model was evaluated using:

- Accuracy
- Precision
- Recall
- F1-score
- ROC-AUC score
- Confusion matrix

ROC-AUC was used to choose the best model because churn prediction is a risk-scoring problem.

---

### Step 5: Model Results

The model comparison was:

| Model | Accuracy | Precision | Recall | F1-score | ROC-AUC |
|---|---:|---:|---:|---:|---:|
| Logistic Regression | 0.7922 | 0.4399 | 0.8474 | 0.5791 | 0.8851 |
| Random Forest | 0.9796 | 0.9941 | 0.8842 | 0.9359 | 0.9990 |
| XGBoost | 0.9174 | 0.6932 | 0.9158 | 0.7891 | 0.9740 |

Best model:

```text
Random Forest Classifier
```

Reason:

It achieved the highest ROC-AUC score.

The trained model was saved as:

```text
notun_alo_churn_model.pkl
```

---

## 7. How Kaggle Features Were Mapped to Notun Alo

The Kaggle dataset does not have the exact same columns as Notun Alo. So we created equivalent features from the Notun Alo database.

| Kaggle Column | Notun Alo Equivalent |
|---|---|
| `Tenure` | Months since `users.created_at` |
| `PreferredLoginDevice` | Default value: `Mobile Phone` |
| `CityTier` | Estimated from `users.address` |
| `WarehouseToHome` | Proxy based on pickup problems |
| `PreferredPaymentMode` | Default value: `Debit Card` |
| `Gender` | Default value for model compatibility |
| `HourSpendOnApp` | Proxy from pickups created in the last 30 days |
| `NumberOfDeviceRegistered` | Proxy based on user risk behavior |
| `PreferedOrderCat` | Mapped from most frequent pickup category |
| `SatisfactionScore` | Proxy from completed pickups and delayed pickups |
| `MaritalStatus` | Default value for model compatibility |
| `NumberOfAddress` | Proxy from address and risk behavior |
| `Complain` | `1` if user has old pending or assigned pickups |
| `OrderAmountHikeFromlastYear` | Yearly growth of completed pickup weight |
| `CouponUsed` | Default `0` because coupon table does not exist yet |
| `OrderCount` | Number of completed pickups |
| `DaySinceLastOrder` | Days since last completed pickup |
| `CashbackAmount` | `rewards.total_points` |

These features are generated by:

```text
feature_query.sql
```

---

## 8. How the Scoring Process Works

The scoring process is handled by:

```text
score_users.py
```

This script does the following:

1. Connects to the MySQL database.
2. Runs `feature_query.sql`.
3. Gets all active users where `role = 'user'`.
4. Loads the saved model `notun_alo_churn_model.pkl`.
5. Predicts a churn probability for each user.
6. Converts the probability into a risk label.
7. Saves the result into `user_ml_scores`.

Risk label rules:

| Churn Score | Risk Label |
|---|---|
| Greater than `0.70` | `high` |
| `0.40` to `0.70` | `medium` |
| Less than `0.40` | `low` |

Example:

```text
0.79 = 79% churn risk = high
0.52 = 52% churn risk = medium
0.22 = 22% churn risk = low
```

---

## 9. Database Table Used for ML Scores

The model writes results into this table:

```text
user_ml_scores
```

Table columns:

| Column | Meaning |
|---|---|
| `user_id` | User ID from the `users` table |
| `churn_score` | Predicted churn probability |
| `risk_label` | `high`, `medium`, or `low` |
| `updated_at` | Last scoring time |

This table is created automatically by `score_users.py` or `admin_churn_table.php` if it does not already exist.

---

## 10. How the Admin Dashboard Shows the Result

The admin dashboard includes:

```text
admin_churn_table.php
```

This file displays the **AI Churn Risk Monitor** section.

The table shows:

| Column | Meaning |
|---|---|
| Name | User name |
| Email | User email |
| Days Since Pickup | Days since last completed pickup |
| Churn Score | Risk score as a percentage |
| Action | Button to send bonus points |

Only high-risk users are shown in the table.

The churn score badge colors are:

| Color | Meaning |
|---|---|
| Red | High risk |
| Yellow | Medium risk |
| Green | Low risk |

Currently, the dashboard focuses on high-risk users because those users need the admin's attention first.

---

## 11. Admin Action: Send 50 Bonus Points

Each high-risk row has a button:

```text
Send 50 Bonus Points
```

When the admin clicks it, this SQL logic runs:

```sql
INSERT INTO rewards(user_id, total_points)
VALUES(?, 50)
ON DUPLICATE KEY UPDATE total_points = total_points + 50
```

Meaning:

- If the user does not have a reward record, create one with 50 points.
- If the user already has rewards, add 50 more points.

This is a retention action. It gives users a reason to come back.

---

## 12. Demo Data Created for Testing

To make the feature visible on the website, 30 demo users were created using:

```text
seed_churn_test_data.php
```

Demo admin:

```text
Email: aiadmin@notunalo.test
Password: admin1234
```

Demo users:

```text
Emails: churn001@notunalo.test to churn030@notunalo.test
Password: test1234
```

These users were given different behavior patterns:

- Some users have old delayed pickups.
- Some users have low reward points.
- Some users have recent completed pickups.
- Some users have many completed pickups.

This creates high, medium, and low risk examples for testing.

Current test result:

```text
Scored 33 users: 7 high-risk, 8 medium-risk, 18 low-risk users.
```

Example high-risk users:

```text
churn007@notunalo.test - 79%
churn004@notunalo.test - 79%
churn010@notunalo.test - 79%
churn001@notunalo.test - 76%
```

---

## 13. How to Run the Full Feature

Open PowerShell or terminal in:

```text
C:\xampp1\htdocs\notun_alo
```

### Step 1: Start XAMPP

Start:

- Apache
- MySQL

### Step 2: Create demo data

Run:

```powershell
C:\xampp1\php\php.exe seed_churn_test_data.php
```

### Step 3: Run churn scoring

Run:

```powershell
python score_users.py
```

Expected example output:

```text
Scored 33 users: 7 high-risk, 8 medium-risk, 18 low-risk users.
```

### Step 4: Open the admin dashboard

Go to:

```text
http://localhost/notun_alo/admin.php
```

Login:

```text
Email: aiadmin@notunalo.test
Password: admin1234
```

You should see:

- AI Churn Risk Monitor
- total scored users
- high-risk users
- churn score percentage
- Send 50 Bonus Points button

---

## 14. How to Update Scores Later

Whenever users schedule pickups, complete pickups, or receive reward points, run:

```powershell
python score_users.py
```

Then refresh:

```text
http://localhost/notun_alo/admin.php
```

The churn scores will update.

---

## 15. Important Limitations

This is the first version of the churn system.

Important points:

1. The model was trained on a Kaggle e-commerce dataset, not real Notun Alo historical data.
2. Some dataset columns had to be mapped using proxy values.
3. The model is useful for demonstration and early risk ranking.
4. It should not be treated as a perfect final prediction system.
5. Accuracy may be lower when used on real Notun Alo users.

Because Notun Alo currently has fewer real users than the Kaggle dataset, the result should be interpreted as:

> This user may need attention.

Not:

> This user will definitely leave.

---

## 16. How to Improve the Model in the Future

After Notun Alo has 3 months of real user data, the model should be retrained using actual platform behavior.

Recommended future data to collect:

- last login date
- number of login sessions
- pickup cancellation count
- pickup completion delay
- reward redemption history
- product purchase history
- complaint tickets
- user feedback rating
- notification click history

These features would make the churn model more accurate for Notun Alo.

Future improvement:

Instead of using a Kaggle e-commerce dataset, train the model using only Notun Alo's own real user history.

---

## 17. Simple Explanation for Presentation

You can explain the feature like this:

> We built an AI-based churn prediction system for Notun Alo. Since our platform does not yet have enough real historical data, we trained a machine learning model using a Kaggle e-commerce customer churn dataset. Then we mapped the dataset features to our recycling platform features, such as completed pickups, days since last pickup, delayed pickups, and reward points. The trained model predicts a churn score for each user and stores the result in MySQL. The admin dashboard shows high-risk users and allows the admin to send bonus points to encourage them to return.

---

## 18. Final Summary

- The AI Churn Risk Monitor predicts which users may become inactive.
- It uses a Random Forest machine learning model.
- The model was trained on the Kaggle E-Commerce Customer Churn Dataset.
- Notun Alo database features are extracted using `feature_query.sql`.
- Scores are generated using `score_users.py`.
- Results are saved in `user_ml_scores`.
- The admin dashboard shows high-risk users.
- Admins can send 50 bonus points to retain risky users.
- Demo data was created so the feature can be tested immediately.

