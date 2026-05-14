SELECT
    u.id AS user_id,

    GREATEST(TIMESTAMPDIFF(MONTH, u.created_at, CURDATE()), 0) AS Tenure,

    'Mobile Phone' AS PreferredLoginDevice,

    CASE
        WHEN LOWER(COALESCE(u.address, '')) REGEXP 'dhaka|chattogram|chittagong' THEN 1
        WHEN LOWER(COALESCE(u.address, '')) REGEXP 'rajshahi|khulna|sylhet|barishal|rangpur|mymensingh|cumilla|comilla|narayanganj|gazipur' THEN 2
        ELSE 3
    END AS CityTier,

    CASE
        WHEN COALESCE(p.old_pending_pickups, 0) > 0 THEN 30
        WHEN COALESCE(p.completed_pickups, 0) = 0 THEN 20
        ELSE 8
    END AS WarehouseToHome,
    'Debit Card' AS PreferredPaymentMode,
    'Male' AS Gender,
    LEAST(5, ROUND(COALESCE(p.pickups_last_30_days, 0) / 2, 0)) AS HourSpendOnApp,
    CASE WHEN COALESCE(p.old_pending_pickups, 0) > 0 THEN 4 ELSE 2 END AS NumberOfDeviceRegistered,
    CASE COALESCE(p.preferred_order_cat, 'Others')
        WHEN 'Paper' THEN 'Laptop & Accessory'
        WHEN 'Plastic' THEN 'Mobile Phone'
        WHEN 'Metal' THEN 'Mobile Phone'
        ELSE 'Others'
    END AS PreferedOrderCat,

    CASE
        WHEN COALESCE(p.old_pending_pickups, 0) > 0 THEN 2
        WHEN COALESCE(p.completed_pickups, 0) = 0 THEN 3
        WHEN COALESCE(p.days_since_last_order, 999) <= 30 THEN 5
        WHEN COALESCE(p.days_since_last_order, 999) <= 90 THEN 4
        ELSE 3
    END AS SatisfactionScore,

    'Single' AS MaritalStatus,
    CASE
        WHEN COALESCE(p.old_pending_pickups, 0) > 0 THEN 8
        WHEN u.address IS NULL OR TRIM(u.address) = '' THEN 0
        ELSE 2
    END AS NumberOfAddress,
    CASE WHEN COALESCE(p.old_pending_pickups, 0) > 0 THEN 1 ELSE 0 END AS Complain,

    CASE
        WHEN COALESCE(p.previous_year_weight, 0) = 0 AND COALESCE(p.current_year_weight, 0) > 0 THEN 100
        WHEN COALESCE(p.previous_year_weight, 0) = 0 THEN 0
        ELSE ROUND(((p.current_year_weight - p.previous_year_weight) / p.previous_year_weight) * 100, 2)
    END AS OrderAmountHikeFromlastYear,

    0 AS CouponUsed,
    COALESCE(p.completed_pickups, 0) AS OrderCount,
    COALESCE(p.days_since_last_order, 999) AS DaySinceLastOrder,
    COALESCE(r.total_points, 0) AS CashbackAmount

FROM users u
LEFT JOIN rewards r
    ON r.user_id = u.id
LEFT JOIN (
    SELECT
        base.user_id,
        COUNT(CASE WHEN base.status = 'completed' THEN 1 END) AS completed_pickups,
        COUNT(CASE WHEN base.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS pickups_last_30_days,
        COUNT(CASE WHEN base.status IN ('pending', 'assigned') AND base.schedule_date < CURDATE() THEN 1 END) AS old_pending_pickups,
        GREATEST(DATEDIFF(CURDATE(), MAX(CASE WHEN base.status = 'completed' THEN base.schedule_date END)), 0) AS days_since_last_order,
        SUM(CASE WHEN base.status = 'completed' AND base.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                 THEN base.estimated_weight ELSE 0 END) AS current_year_weight,
        SUM(CASE WHEN base.status = 'completed'
                   AND base.schedule_date < DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                   AND base.schedule_date >= DATE_SUB(CURDATE(), INTERVAL 730 DAY)
                 THEN base.estimated_weight ELSE 0 END) AS previous_year_weight,
        (
            SELECT p2.category
            FROM pickups p2
            WHERE p2.user_id = base.user_id
            GROUP BY p2.category
            ORDER BY COUNT(*) DESC
            LIMIT 1
        ) AS preferred_order_cat
    FROM pickups base
    GROUP BY base.user_id
) p
    ON p.user_id = u.id
WHERE u.role = 'user';
