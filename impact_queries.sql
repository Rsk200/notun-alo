-- QUERY A: User Impact Summary
-- Returns one aggregate environmental impact row for the supplied :user_id.
-- It uses exact category/subcategory matches first and falls back to category_averages.
SELECT
  u.name,
  COUNT(p.id)                                          AS total_pickups,
  ROUND(COALESCE(SUM(p.estimated_weight), 0), 2)       AS total_kg_recycled,
  ROUND(COALESCE(SUM(p.estimated_weight *
    COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0), 3)  AS total_co2_saved_kg,
  ROUND(COALESCE(SUM(p.estimated_weight *
    COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 0), 1) AS total_water_saved_liters,
  ROUND(COALESCE(SUM(p.estimated_weight *
    COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 0), 2) AS total_energy_saved_kwh,
  ROUND(COALESCE(SUM(p.estimated_weight *
    COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0)
    / 590 * 100, 2)                                   AS pct_of_bd_annual_footprint,
  ROUND(COALESCE(SUM(p.estimated_weight *
    COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0)
    / 0.21, 0)                                        AS equivalent_car_km_saved
FROM users u
LEFT JOIN pickups p ON u.id = p.user_id AND p.status = 'completed'
LEFT JOIN emission_factors ef
  ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
LEFT JOIN category_averages ca ON p.category = ca.category
WHERE u.id = :user_id
GROUP BY u.id, u.name;

-- QUERY B: Monthly CO2 Trend
-- Returns the user's last 12 months of completed recycling impact grouped by month and category.
-- The category average is used when the submitted subcategory is NULL or unknown.
SELECT
  DATE_FORMAT(p.schedule_date, '%Y-%m')              AS month,
  p.category,
  ROUND(SUM(p.estimated_weight *
    COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 3)    AS co2_saved_kg,
  ROUND(SUM(p.estimated_weight), 2)                  AS kg_collected
FROM pickups p
LEFT JOIN emission_factors ef
  ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
LEFT JOIN category_averages ca ON p.category = ca.category
WHERE p.user_id = :user_id
  AND p.status = 'completed'
  AND p.schedule_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY month, p.category
ORDER BY month ASC, co2_saved_kg DESC;

-- QUERY C: Platform-Wide Admin Dashboard
-- Returns platform totals, active users, total CO2 saved in tonnes, and the highest-impact category.
-- Exact factor matches are preferred and category averages cover old or incomplete pickup records.
SELECT
  COUNT(DISTINCT p.user_id)                          AS total_active_users,
  COUNT(p.id)                                        AS total_pickups,
  ROUND(COALESCE(SUM(p.estimated_weight), 0), 2)     AS total_kg_collected,
  ROUND(COALESCE(SUM(p.estimated_weight *
    COALESCE(ef.co2_sa_adjusted, ca.avg_co2)), 0)
    / 1000, 4)                                       AS total_co2_saved_tonnes,
  (SELECT p2.category
   FROM pickups p2
   LEFT JOIN emission_factors ef2
     ON p2.category = ef2.category AND COALESCE(p2.subcategory, p2.category) = ef2.subcategory
   LEFT JOIN category_averages ca2 ON p2.category = ca2.category
   WHERE p2.status = 'completed'
   GROUP BY p2.category
   ORDER BY SUM(p2.estimated_weight * COALESCE(ef2.co2_sa_adjusted, ca2.avg_co2)) DESC
   LIMIT 1)                                          AS highest_impact_category,
  ROUND(COALESCE(AVG(p.estimated_weight), 0), 2)     AS avg_weight_per_pickup
FROM pickups p
LEFT JOIN emission_factors ef
  ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
LEFT JOIN category_averages ca ON p.category = ca.category
WHERE p.status = 'completed';

-- QUERY D: CO2 Leaderboard (Top 10 with badges)
-- Ranks the top ten users by total CO2 saved and assigns simple retention-friendly badges.
-- The fallback average keeps the leaderboard valid for pickups with NULL/unknown subcategories.
SELECT
  RANK() OVER (ORDER BY total_co2 DESC)              AS rank_position,
  u.name,
  ROUND(total_co2, 2)                                AS total_co2_saved_kg,
  ROUND(total_kg, 2)                                 AS total_kg_recycled,
  CASE
    WHEN total_co2 > 100 THEN 'Forest Guardian'
    WHEN total_co2 > 50  THEN 'Eco Warrior'
    WHEN total_co2 > 10  THEN 'Active Recycler'
    ELSE 'Getting Started'
  END                                                AS badge_label
FROM (
  SELECT p.user_id,
    SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2)) AS total_co2,
    SUM(p.estimated_weight) AS total_kg
  FROM pickups p
  LEFT JOIN emission_factors ef
    ON p.category = ef.category AND COALESCE(p.subcategory, p.category) = ef.subcategory
  LEFT JOIN category_averages ca ON p.category = ca.category
  WHERE p.status = 'completed'
  GROUP BY p.user_id
) ranked
JOIN users u ON u.id = ranked.user_id
ORDER BY total_co2 DESC
LIMIT 10;
