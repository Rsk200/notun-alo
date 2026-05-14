# Notun Alo Environmental Impact Intelligence System

This project turns recycling pickups into clear environmental impact stories for the Notun Alo platform.

Example: instead of only showing `5 kg plastic recycled`, the dashboard can show CO2 avoided, water saved, energy saved, car-trip equivalents, phone-charge equivalents, and e-waste high-impact badges.

## Local Windows Setup

1. Start XAMPP.
2. Start Apache and MySQL.
3. Confirm the project is here:

```powershell
C:\xampp\htdocs\notun_alo
```

4. Open the AI service folder:

```powershell
cd C:\xampp\htdocs\notun_alo\ai-service
```

5. Install Python requirements:

```powershell
python -m pip install -r requirements.txt
```

6. Copy the environment template:

```powershell
copy .env.example .env
```

Default `.env` values work for a normal XAMPP MySQL setup:

```env
NOTUN_ALO_DB_HOST=localhost
NOTUN_ALO_DB_USER=root
NOTUN_ALO_DB_PASS=
NOTUN_ALO_DB_NAME=notun_alo
IMPACT_API_HOST=127.0.0.1
IMPACT_API_PORT=5003
```

## Database Import

Import this SQL file into the `notun_alo` database using phpMyAdmin or MySQL CLI:

```powershell
C:\xampp\htdocs\notun_alo\ai-service\emission_factors.sql
```

The SQL creates:

- `emission_factors`
- indexes on `category` and `subcategory`
- `category_averages` view
- `pickups.subcategory` if missing

The Flask API also calls schema repair on startup, so the demo is more forgiving.

## Data Validation

Run:

```powershell
cd C:\xampp\htdocs\notun_alo\ai-service
python validate_environmental_data.py
```

This validates schema, duplicates, missing values, category averages, South Asia adjustment summary, top-impact materials, and e-waste comparison.

## Forecast Model

Run:

```powershell
python train_forecast_model.py
```

This creates `impact_model.pkl` using 5000+ synthetic rows with realistic category weighting, seasonality, Gaussian noise, and upward growth.

The model is used only for forecasting future trends. It is not used for deterministic CO2, water, or energy calculations.

## Start Flask API

Run:

```powershell
python impact_api.py
```

API runs at:

```text
http://localhost:5003
```

## API Testing

Open these URLs in a browser:

```text
http://localhost:5003/health
http://localhost:5003/impact?user_id=1
http://localhost:5003/forecast?user_id=1
http://localhost:5003/platform-stats
http://localhost:5003/leaderboard
```

## Dashboard Integration

User dashboard:

```text
http://localhost/notun_alo/dashboard.php
```

Admin dashboard:

```text
http://localhost/notun_alo/admin.php
```

Files added for UI:

- `includes/impact_card.php`
- `includes/admin_impact.php`
- `ai-service/dashboard_chart.js`

## Scientific Formulas

All Bangladesh calculations use `co2_sa_adjusted`.

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

## E-waste Logic

The system always displays:

```text
Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling.
```

If top category is `E-waste`, leaderboard score uses:

```text
Final Score = Base Score * 1.5
```

E-waste dashboard rows show a high-impact badge.

## Leaderboard Formula

Consistency score:

```text
0-1 pickups = 0.1
2-4 pickups = 0.5
5-7 pickups = 0.8
8+ pickups = 1.0
```

Eco score:

```text
Base Score = (CO2 * 0.5) + (Water * 0.2) + (Energy * 0.2) + (Consistency * 0.1)
```

Badges:

- Active Recycler: 5+ pickups in last month
- Eco Warrior: 100+ kg recycled
- Forest Guardian: top 10% final score
- E-waste Hero: highest final score among e-waste users, ties included

## Tests

Run:

```powershell
python -m pytest tests
```

## Troubleshooting

If `ModuleNotFoundError` appears, run:

```powershell
python -m pip install -r requirements.txt
```

If API endpoints fail with a database error:

1. Start XAMPP MySQL.
2. Confirm database name is `notun_alo`.
3. Import `ai-service/emission_factors.sql`.
4. Check `.env` database credentials.

If dashboard impact cards keep loading:

1. Start Flask API with `python impact_api.py`.
2. Open `http://localhost:5003/health`.
3. Reload `dashboard.php`.

## Future AI Placeholders

These are placeholders only:

- `ai-service/placeholders/waste_classifier.py`
- `ai-service/placeholders/nasa_validator.py`
- `ai-service/models/mobilenetv3_placeholder.h5`

They are not used in current deterministic impact calculations.
