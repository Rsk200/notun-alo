-- ============================================================
-- migrations_ai.sql  — AI Smart Assignment System
-- Notun Alo Recycling Platform
-- Run once against the `notun_alo` database.
-- MariaDB / MySQL compatible. All ALTER TABLE statements are
-- idempotent (IF NOT EXISTS) so re-running is safe.
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- A1. Extend users table for agency geo + capacity fields
-- ────────────────────────────────────────────────────────────
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `lat`          DECIMAL(10,8)  NULL          COMMENT 'Agency latitude (WGS-84)',
    ADD COLUMN IF NOT EXISTS `lng`          DECIMAL(11,8)  NULL          COMMENT 'Agency longitude (WGS-84)',
    ADD COLUMN IF NOT EXISTS `is_available` TINYINT(1)     NOT NULL DEFAULT 1    COMMENT '1=accepting pickups, 0=offline',
    ADD COLUMN IF NOT EXISTS `max_capacity` INT            NOT NULL DEFAULT 10   COMMENT 'Max simultaneous pickups',
    ADD COLUMN IF NOT EXISTS `specialty`    VARCHAR(100)   NULL          COMMENT 'Comma-separated categories, e.g. Plastic,Metal';

-- Seed realistic Dhaka coordinates for existing agencies
UPDATE `users` SET lat = 23.7104,  lng = 90.4074,  specialty = 'Paper,Plastic',   max_capacity = 8  WHERE id = 2  AND lat IS NULL;
UPDATE `users` SET lat = 23.7461,  lng = 90.3742,  specialty = 'Paper,Metal',     max_capacity = 10 WHERE id = 5  AND lat IS NULL;
UPDATE `users` SET lat = 23.7925,  lng = 90.4078,  specialty = 'Plastic,E-waste', max_capacity = 6  WHERE id = 6  AND lat IS NULL;

-- ────────────────────────────────────────────────────────────
-- A2. Agency ratings (one rating per completed pickup)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `agency_ratings` (
    `id`         INT(11)    NOT NULL AUTO_INCREMENT,
    `pickup_id`  INT(11)    NOT NULL,
    `agency_id`  INT(11)    NOT NULL,
    `user_id`    INT(11)    NOT NULL,
    `rating`     TINYINT    NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `created_at` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pickup_rating` (`pickup_id`),          -- one rating per pickup
    KEY `idx_ar_agency` (`agency_id`),
    KEY `idx_ar_user`   (`user_id`),
    CONSTRAINT `fk_ar_pickup` FOREIGN KEY (`pickup_id`) REFERENCES `pickups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ar_agency` FOREIGN KEY (`agency_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ar_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='User ratings for completed pickups (1-5 stars)';

-- ────────────────────────────────────────────────────────────
-- A3. Assignment scores — full ML audit trail per decision
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assignment_scores` (
    `id`                      INT(11)       NOT NULL AUTO_INCREMENT,
    `pickup_id`               INT(11)       NOT NULL,
    `agency_id`               INT(11)       NOT NULL,
    `score_total`             DECIMAL(6,4)  NOT NULL DEFAULT 0.0000,
    `score_load`              DECIMAL(6,4)  NOT NULL DEFAULT 0.0000,
    `score_completion`        DECIMAL(6,4)  NOT NULL DEFAULT 0.0000,
    `score_distance`          DECIMAL(6,4)  NOT NULL DEFAULT 0.0000,
    `score_rating`            DECIMAL(6,4)  NOT NULL DEFAULT 0.0000,
    `score_specialty`         DECIMAL(6,4)  NOT NULL DEFAULT 0.0000,
    `predicted_completion_hrs` DECIMAL(5,2) NULL     COMMENT 'ML ETA in hours, NULL if model not loaded',
    `model_version`           VARCHAR(20)   NOT NULL DEFAULT 'weighted_v1',
    `scored_at`               TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_as_pickup`  (`pickup_id`),
    KEY `idx_as_agency`  (`agency_id`),
    KEY `idx_as_scored`  (`scored_at`),
    CONSTRAINT `fk_as_pickup` FOREIGN KEY (`pickup_id`) REFERENCES `pickups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_as_agency` FOREIGN KEY (`agency_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Per-agency scoring audit for every assignment decision';

-- ────────────────────────────────────────────────────────────
-- A4. Agency geographic zones (k-means output)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `agency_zones` (
    `agency_id`  INT(11)     NOT NULL,
    `zone_id`    INT(11)     NOT NULL,
    `zone_label` VARCHAR(20) NOT NULL DEFAULT 'unknown',
    PRIMARY KEY (`agency_id`),
    CONSTRAINT `fk_az_agency` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='k-means zone assignment for each agency';

-- ────────────────────────────────────────────────────────────
-- A5. Model version registry
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `model_versions` (
    `id`          INT(11)     NOT NULL AUTO_INCREMENT,
    `version_tag` VARCHAR(20) NOT NULL,
    `model_type`  VARCHAR(20) NOT NULL COMMENT 'RF or GB',
    `trained_on`  INT(11)     NOT NULL DEFAULT 0 COMMENT 'Number of training rows',
    `test_mae`    DECIMAL(5,2) NULL,
    `trained_at`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_active`   TINYINT(1)  NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_mv_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Registry of trained ML model versions';

-- ────────────────────────────────────────────────────────────
-- A6. assignment_log — transaction log (referenced by PHP)
--     Must exist before the view uses it.
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `assignment_log` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `pickup_id`     INT(11)      NOT NULL,
    `agency_id`     INT(11)      NOT NULL,
    `method`        VARCHAR(20)  NOT NULL DEFAULT 'ai'   COMMENT 'ai | fallback_sql',
    `score_total`   DECIMAL(6,4) NULL,
    `model_version` VARCHAR(20)  NULL,
    `assigned_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_al_pickup` (`pickup_id`),
    KEY `idx_al_agency` (`agency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Immutable log of every pickup assignment';

-- ────────────────────────────────────────────────────────────
-- A7. Notifications table (used by PHP auto-assign)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT(11)       NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)       NOT NULL,
    `title`      VARCHAR(200)  NOT NULL,
    `message`    TEXT          NOT NULL,
    `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user`  (`user_id`),
    KEY `idx_notif_read`  (`is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='In-app notifications for users, agencies, and admins';

-- ────────────────────────────────────────────────────────────
-- A7.5 Add updated_at to pickups if missing (needed for ETA calc)
-- ────────────────────────────────────────────────────────────
ALTER TABLE `pickups`
    ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP NULL DEFAULT NULL
                                          ON UPDATE CURRENT_TIMESTAMP
    COMMENT 'Last status change timestamp';

-- Backfill: completed pickups without updated_at get schedule_date as proxy
UPDATE `pickups`
SET updated_at = TIMESTAMP(schedule_date, '12:00:00')
WHERE status = 'completed' AND updated_at IS NULL;

-- ────────────────────────────────────────────────────────────
-- A8. agency_stats VIEW — core data source for the AI engine
-- ────────────────────────────────────────────────────────────
DROP VIEW IF EXISTS `agency_stats`;

CREATE VIEW `agency_stats` AS
SELECT
    u.id                                                    AS agency_id,
    u.name                                                  AS agency_name,
    u.lat,
    u.lng,
    u.is_available,
    u.max_capacity,
    u.specialty,

    -- Active pickups currently assigned to this agency
    COUNT(CASE WHEN p.status IN ('assigned', 'in_progress') THEN 1 END)
                                                            AS active_pickups,

    -- Load ratio: fraction of capacity currently in use
    ROUND(
        COUNT(CASE WHEN p.status IN ('assigned', 'in_progress') THEN 1 END)
        / GREATEST(u.max_capacity, 1),
        4
    )                                                       AS load_ratio,

    -- Completion rate: fraction of all pickups ever completed
    ROUND(
        COALESCE(
            COUNT(CASE WHEN p.status = 'completed' THEN 1 END)
            / NULLIF(COUNT(p.id), 0),
            1.0
        ),
        4
    )                                                       AS completion_rate,

    -- Average star rating (default 4.0 if no ratings yet)
    ROUND(COALESCE(AVG(ar.rating), 4.0), 2)                AS avg_rating,

    -- Historical totals
    COUNT(CASE WHEN p.status = 'completed' THEN 1 END)     AS total_completed,

    -- Average completion time in hours for completed pickups
    ROUND(
        AVG(
            CASE WHEN p.status = 'completed'
                 THEN TIMESTAMPDIFF(HOUR, p.created_at, p.updated_at)
            END
        ),
        2
    )                                                       AS avg_completion_hrs

FROM `users` u
LEFT JOIN `pickups`        p  ON p.agency_id = u.id
LEFT JOIN `agency_ratings` ar ON ar.agency_id = u.id
WHERE u.role = 'agency'
GROUP BY
    u.id, u.name, u.lat, u.lng,
    u.is_available, u.max_capacity, u.specialty;

-- ============================================================
-- End of migrations_ai.sql
-- Run: mysql -u root notun_alo < migrations_ai.sql
-- ============================================================
