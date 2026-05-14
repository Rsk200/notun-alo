-- impact_queries.sql - reusable Notun Alo sustainability analytics

-- User Impact Summary
SELECT
    u.name AS user_name,
    COUNT(p.id) AS total_pickups,
    ROUND(COALESCE(SUM(p.estimated_weight), 0), 2) AS total_kg_recycled,
    ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 0), 2) AS co2_saved_kg,
    ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 0), 2) AS water_saved_liters,
    ROUND(COALESCE(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 0), 2) AS energy_saved_kwh
FROM users u
LEFT JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE u.id = 1
GROUP BY u.id, u.name;

-- Monthly CO2 Trend
SELECT
    DATE_FORMAT(p.schedule_date, '%Y-%m') AS month,
    p.category,
    ROUND(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 2) AS co2_saved_kg
FROM pickups p
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE p.status = 'completed'
GROUP BY month, p.category
ORDER BY month ASC, co2_saved_kg DESC;

-- Platform Impact Summary
SELECT
    COUNT(DISTINCT p.user_id) AS active_users,
    COUNT(p.id) AS total_pickups,
    ROUND(SUM(p.estimated_weight), 2) AS total_kg_recycled,
    ROUND(SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 2) AS co2_saved_kg,
    ROUND(SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)), 2) AS water_saved_liters,
    ROUND(SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)), 2) AS energy_saved_kwh
FROM pickups p
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE p.status = 'completed';

-- Top Recyclers Leaderboard with eco scores, badges, usernames, and top category
WITH user_category AS (
    SELECT
        u.id AS user_id,
        u.name AS user_name,
        p.category,
        COUNT(p.id) AS pickup_count,
        SUM(p.estimated_weight) AS total_weight,
        SUM(p.estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)) AS co2_saved,
        SUM(p.estimated_weight * COALESCE(ef.water_liters_per_kg, ca.avg_water_liters_per_kg, 0)) AS water_saved,
        SUM(p.estimated_weight * COALESCE(ef.energy_kwh_per_kg, ca.avg_energy_kwh_per_kg, 0)) AS energy_saved,
        SUM(CASE WHEN p.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS last_month_pickups
    FROM users u
    JOIN pickups p ON p.user_id = u.id AND p.status = 'completed'
    LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
    LEFT JOIN category_averages ca ON ca.category = p.category
    WHERE u.role = 'user'
    GROUP BY u.id, u.name, p.category
), ranked_category AS (
    SELECT *, ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY co2_saved DESC) AS category_rank
    FROM user_category
), user_totals AS (
    SELECT
        user_id,
        MAX(user_name) AS user_name,
        SUM(pickup_count) AS pickup_count,
        SUM(total_weight) AS total_weight,
        SUM(co2_saved) AS co2_saved,
        SUM(water_saved) AS water_saved,
        SUM(energy_saved) AS energy_saved,
        SUM(last_month_pickups) AS last_month_pickups,
        MAX(CASE WHEN category_rank = 1 THEN category END) AS top_category
    FROM ranked_category
    GROUP BY user_id
)
SELECT
    user_name,
    ROUND(((co2_saved * 0.5) + (water_saved * 0.2) + (energy_saved * 0.2) +
        (CASE WHEN pickup_count <= 1 THEN 0.1 WHEN pickup_count <= 4 THEN 0.5 WHEN pickup_count <= 7 THEN 0.8 ELSE 1.0 END * 0.1)) *
        CASE WHEN top_category = 'E-waste' THEN 1.5 ELSE 1 END, 2) AS eco_score,
    CASE
        WHEN top_category = 'E-waste' THEN 'E-waste Hero'
        WHEN total_weight >= 100 THEN 'Eco Warrior'
        WHEN last_month_pickups >= 5 THEN 'Active Recycler'
        ELSE 'Recycler'
    END AS badge,
    ROUND(co2_saved, 2) AS co2_saved,
    top_category
FROM user_totals
ORDER BY eco_score DESC;

-- E-waste Analytics
SELECT
    COUNT(*) AS ewaste_pickups,
    ROUND(SUM(estimated_weight), 2) AS ewaste_kg,
    ROUND(SUM(estimated_weight * COALESCE(ef.co2_sa_adjusted, ca.avg_co2, 0)), 2) AS ewaste_co2_saved_kg,
    'Mobile phone recycling has ~29x higher environmental impact than mixed plastic recycling.' AS ewaste_note
FROM pickups p
LEFT JOIN emission_factors ef ON ef.category = p.category AND p.subcategory IS NOT NULL AND p.subcategory <> '' AND ef.subcategory = p.subcategory
LEFT JOIN category_averages ca ON ca.category = p.category
WHERE p.status = 'completed' AND p.category = 'E-waste';
