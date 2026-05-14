# Notun Alo Environmental Impact Intelligence System - Implementation Document

Project path: `C:\xampp\htdocs\notun_alo`
Implementation date: 2026-05-13
Local environment: Windows, XAMPP, PHP, MySQL, Python Flask

## 1. Goal

The system converts recycling activity into understandable sustainability impact:

- CO2 emissions prevented
- Water saved
- Energy saved
- Equivalent car kilometers avoided
- Equivalent phone charges powered
- Equivalent drinking-water bottles saved
- E-waste high-impact storytelling and leaderboard boost
- Platform-wide sustainability reporting
- Three-month impact forecasting

The deterministic scientific calculations are separated from forecasting. CO2, water, and energy calculations never use ML.

## 2. Final Feasibility Status

The implementation is feasible and running locally.

Verified working:

- Flask API starts on `http://127.0.0.1:5003`
- MySQL impact tables are created/repaired automatically
- CSV factors load correctly: 28 rows
- Dashboard PHP files compile with no syntax errors
- API endpoints return HTTP 200
- Forecast model exists and loads
- Python test suite passes
- E-waste high-impact values are correctly stored after schema repair

Important issue found and fixed during feasibility testing:

- Existing `emission_factors.energy_kwh_per_kg` column was too small: `DECIMAL(6,4)`.
- This capped mobile phone e-waste energy from `210` to `99.9999`.
- Fixed by widening numeric columns to `DECIMAL(12,4)` / `DECIMAL(10,4)` and reloading/upserting CSV factors from the API schema repair path.
- Confirmed mobile phones now store `210.0000 kWh/kg`.

## 3. Directory Structure Implemented

```text
C:\xampp\htdocs\notun_alo
|
|-- ai-service
|   |-- data
|   |   |-- environmental_factors.csv
|   |-- logs
|   |-- tests
|   |   |-- test_environmental_system.py
|   |-- placeholders
|   |   |-- waste_classifier.py
|   |   |-- nasa_validator.py
|   |-- models
|   |   |-- mobilenetv3_placeholder.h5
|   |-- .env.example
|   |-- requirements.txt
|   |-- environmental_engine.py
|   |-- validate_environmental_data.py
|   |-- train_forecast_model.py
|   |-- forecast_engine.py
|   |-- leaderboard_engine.py
|   |-- impact_api.py
|   |-- impact_model.pkl
|   |-- emission_factors.sql
|   |-- impact_queries.sql
|   |-- dashboard_chart.js
|   |-- README.md
|
|-- includes
|   |-- impact_card.php
|   |-- admin_impact.php
|
|-- dashboard.php
|-- user_request_pickup.php
|-- agency.php
|-- admin.php
|-- emission_factors.sql
|-- README.md
```

## 4. Data Source

Source CSV copied and normalized into:

```text
C:\xampp\htdocs\notun_alo\ai-service\data\environmental_factors.csv
```

Columns:

```text
category
subcategory
co2_base_kg_per_kg
co2_sa_adjusted
water_liters_per_kg
energy_kwh_per_kg
source
notes
```

The original CSV header had a split `sourc` / `e` problem. It was cleaned into a valid `source` column.

## 5. Scientific Calculation Rules

Bangladesh / South Asia calculations use:

```text
co2_sa_adjusted
```

Formulas:

```text
CO2 saved = weight_kg * co2_sa_adjusted
Water saved = weight_kg * water_liters_per_kg
Energy saved = weight_kg * energy_kwh_per_kg
```

Emotional equivalents:

```text
1 km average car trip = 0.21 kg CO2
1 phone charge = 0.012 kWh
1 drinking water bottle = 0.5 liters
```

Example verified from MySQL for `2.5 kg` mobile phones:

```text
CO2 saved: 93.50 kg
Water saved: 2,275.00 L
Energy saved: 525.00 kWh
Car km avoided: 445 km
Phone charges: 43,750
```

## 6. E-waste Logic

The platform always uses this message:

```text
Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling.
```

When category is `E-waste`:

- Dashboard shows `High Impact Recycling`
- E-waste appears highlighted in admin impact tables
- Leaderboard applies `1.5x` final score multiplier
- E-waste Hero badge is awarded to the highest e-waste final score, including ties

## 7. Files Implemented

### `ai-service/environmental_engine.py`

Purpose:

- Loads emission factors once using cache
- Resolves category/subcategory factors
- Applies fallback to `General_[category]` behavior through category averages
- Calculates deterministic impact JSON

Main functions:

```text
calculate_co2_saved()
calculate_water_saved()
calculate_energy_saved()
calculate_environmental_impact()
resolve_factor()
load_emission_factors()
```

### `ai-service/validate_environmental_data.py`

Purpose:

- Validates CSV schema
- Detects duplicate category/subcategory rows
- Detects missing values
- Prints unique categories
- Calculates category averages
- Prints top 5 highest-impact subcategories
- Prints South Asia adjustment summary
- Prints e-waste comparison summary

Verified output:

```text
Loaded rows: 28
Schema OK
Duplicate category/subcategory rows: 0
Mobile phone recycling is 28.8x higher impact than mixed plastic recycling.
```

### `ai-service/emission_factors.sql`

Purpose:

- Creates `emission_factors`
- Repairs old narrow numeric columns
- Adds pickup `subcategory` column if missing
- Inserts all dataset rows
- Creates `category_averages` view
- Adds indexes

Important repair included:

```sql
ALTER TABLE emission_factors MODIFY energy_kwh_per_kg DECIMAL(12,4) NOT NULL;
```

### `ai-service/impact_queries.sql`

Includes SQL for:

- User impact summary
- Monthly CO2 trend
- Platform impact summary
- Top recyclers leaderboard
- E-waste analytics

All queries use `COALESCE()` and `category_averages` fallback logic.

### `ai-service/train_forecast_model.py`

Purpose:

- Creates 6000 synthetic training rows
- Uses category popularity weighting
- Adds seasonal variation
- Adds Gaussian noise
- Adds slight upward growth trend
- Trains `GradientBoostingRegressor`
- Saves `impact_model.pkl`

Model is used only for future trend forecasting.

Verified training metrics:

```text
MAE: 1.532
RMSE: 2.909
R2: 0.973
```

### `ai-service/forecast_engine.py`

Purpose:

- Loads `impact_model.pkl`
- Produces 3-month forecasts
- Handles cold-start users
- Detects trend labels:
  - Stable
  - Growing
  - Declining
- Adds confidence labels:
  - low
  - medium
  - high

### `ai-service/leaderboard_engine.py`

Purpose:

- Queries MySQL directly
- Calculates user eco scores
- Applies consistency score
- Applies e-waste multiplier
- Returns leaderboard JSON

Consistency scoring:

```text
0-1 pickups: 0.1
2-4 pickups: 0.5
5-7 pickups: 0.8
8+ pickups: 1.0
```

Eco score:

```text
Base Score = (CO2 * 0.5) + (Water * 0.2) + (Energy * 0.2) + (Consistency * 0.1)
Final Score = Base Score * 1.5 if top category is E-waste
```

### `ai-service/impact_api.py`

Purpose:

- Flask API on port `5003`
- CORS enabled
- JSON responses
- Rotating file logging in `ai-service/logs`
- Environment variable support
- MySQL schema auto-repair
- Robust error handling

Endpoints:

```text
GET /health
GET /impact?user_id=1
GET /forecast?user_id=1
GET /platform-stats
GET /leaderboard
```

Verified endpoints returned HTTP 200.

### `includes/impact_card.php`

Purpose:

- User dashboard impact cards
- CO2 card
- Water card
- Energy card
- Forecast section
- Emotional impact message
- E-waste high-impact badge display

Uses:

```javascript
fetch("http://localhost:5003/impact?user_id=1")
fetch("http://localhost:5003/forecast?user_id=1")
```

In the real dashboard it uses the current logged-in user id.

### `includes/admin_impact.php`

Purpose:

- Admin platform sustainability report
- Total CO2 saved
- Total water saved
- Total energy saved
- Category distribution
- E-waste highlighting

### `ai-service/dashboard_chart.js`

Purpose:

- Chart.js stacked bar chart
- Monthly CO2 by category
- Category colors
- Responsive layout

### `user_request_pickup.php`

Feasibility patch:

- Added all environmental categories
- Added subcategory dropdown
- Added e-waste option
- Added points preview for expanded categories
- Auto-adds `pickups.subcategory` if missing

### `dashboard.php`

Feasibility patch:

- Uses `includes/impact_card.php`
- Loads `ai-service/dashboard_chart.js`
- Repairs `pickups.subcategory` if missing
- Legacy pickup POST handler now supports all expanded categories and subcategories
- Monthly chart query now uses safe subcategory matching with category-average fallback

### `agency.php`

Feasibility patch:

- Expanded reward points for all new categories
- E-waste receives higher points
- Agency task cards show category and subcategory
- Completed task table shows category and subcategory

## 8. API Response Examples

### `/health`

```json
{
  "status": "ok",
  "service": "notun-alo-impact-api",
  "factors_loaded": 28,
  "constants": {
    "car_kg_co2_per_km": 0.21,
    "phone_charge_kwh": 0.012,
    "water_bottle_liters": 0.5
  }
}
```

### `/impact?user_id=1`

Returns:

```json
{
  "user_id": 1,
  "total_pickups": 0,
  "total_kg_recycled": 0.0,
  "co2_saved_kg": 0.0,
  "water_saved_liters": 0.0,
  "energy_saved_kwh": 0.0,
  "car_trip_equivalent": 0,
  "water_bottle_equivalent": 0,
  "phone_charge_equivalent": 0,
  "high_impact_badge": null,
  "ewaste_message": "Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling."
}
```

### `/leaderboard`

Returns leaderboard rows like:

```json
{
  "rank": 1,
  "user_name": "Impact Demo E-waste",
  "eco_score": 948.29,
  "badge": "Forest Guardian, E-waste Hero",
  "co2_saved": 92.91,
  "top_category": "E-waste"
}
```

## 9. Setup Instructions

Open PowerShell:

```powershell
cd C:\xampp\htdocs\notun_alo\ai-service
python -m pip install -r requirements.txt
copy .env.example .env
```

Start XAMPP:

```text
Apache: Start
MySQL: Start
```

Import SQL in phpMyAdmin:

```text
C:\xampp\htdocs\notun_alo\ai-service\emission_factors.sql
```

Start Flask:

```powershell
cd C:\xampp\htdocs\notun_alo\ai-service
python impact_api.py
```

Open:

```text
http://localhost/notun_alo/dashboard.php
http://localhost/notun_alo/admin.php
http://127.0.0.1:5003/health
```

## 10. Tests Performed

### Python unit tests

Command:

```powershell
python -m pytest tests
```

Result:

```text
6 passed
```

Validated:

- formulas
- fallback logic
- e-waste highlighting
- leaderboard calculations
- forecasting ranges
- SQL file existence and query contents

### PHP syntax checks

Commands:

```powershell
C:\xampp\php\php.exe -l dashboard.php
C:\xampp\php\php.exe -l user_request_pickup.php
C:\xampp\php\php.exe -l agency.php
C:\xampp\php\php.exe -l includes\impact_card.php
C:\xampp\php\php.exe -l includes\admin_impact.php
```

Result:

```text
No syntax errors detected
```

### Flask API checks

Verified:

```text
/health -> 200
/platform-stats -> 200
/leaderboard -> 200
/impact?user_id=1 -> 200
/forecast?user_id=1 -> 200
```

### MySQL factor feasibility check

Verified mobile phone e-waste values after schema repair:

```text
co2_sa_adjusted: 37.4000
water_liters_per_kg: 910.0000
energy_kwh_per_kg: 210.0000
```

## 11. Demo Flow

1. Start XAMPP Apache and MySQL.
2. Start Flask API:

```powershell
cd C:\xampp\htdocs\notun_alo\ai-service
python impact_api.py
```

3. Log in as a user.
4. Open Request Pickup.
5. Select category, for example `E-waste`.
6. Select subcategory, for example `Mobile phones`.
7. Submit pickup.
8. Admin assigns pickup to agency.
9. Agency marks pickup collected.
10. User dashboard shows CO2, water, energy, equivalents, forecast, and e-waste story.
11. Admin dashboard shows platform impact and e-waste highlighted.
12. Leaderboard reflects eco score and e-waste multiplier.

## 12. Known Operational Notes

- Flask must be running for live dashboard fetch cards.
- If Flask is not running, the dashboard still loads but the impact cards show a startup message.
- Existing old pickups without subcategory still work through category-average fallback.
- `git` is not available on this Windows PATH, so git status was not checked.
- The in-app browser blocked direct JSON API navigation, but Python and the PHP app verified the local API successfully.

## 13. Final Result

The system is ready for local hackathon demo use. It is lightweight, Windows/XAMPP-friendly, deterministic for environmental calculations, and uses ML only for future trend forecasting.
