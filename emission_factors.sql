-- emission_factors.sql - Notun Alo Environmental Impact Factors
-- Import this file into the notun_alo MySQL database.

CREATE TABLE IF NOT EXISTS emission_factors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(80) NOT NULL,
    subcategory VARCHAR(140) NOT NULL,
    co2_base_kg_per_kg DECIMAL(10,4) NOT NULL,
    co2_sa_adjusted DECIMAL(10,4) NOT NULL,
    water_liters_per_kg DECIMAL(12,4) NOT NULL,
    energy_kwh_per_kg DECIMAL(12,4) NOT NULL,
    source VARCHAR(180) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_emission_factor (category, subcategory),
    INDEX idx_emission_category (category),
    INDEX idx_emission_subcategory (subcategory)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE emission_factors MODIFY category VARCHAR(80) NOT NULL;
ALTER TABLE emission_factors MODIFY subcategory VARCHAR(140) NOT NULL;
ALTER TABLE emission_factors ADD COLUMN IF NOT EXISTS co2_base_kg_per_kg DECIMAL(10,4) NOT NULL DEFAULT 0 AFTER subcategory;
ALTER TABLE emission_factors MODIFY co2_base_kg_per_kg DECIMAL(10,4) NOT NULL;
ALTER TABLE emission_factors MODIFY co2_sa_adjusted DECIMAL(10,4) NOT NULL;
ALTER TABLE emission_factors MODIFY water_liters_per_kg DECIMAL(12,4) NOT NULL;
ALTER TABLE emission_factors MODIFY energy_kwh_per_kg DECIMAL(12,4) NOT NULL;
ALTER TABLE emission_factors ADD COLUMN IF NOT EXISTS source VARCHAR(180) NOT NULL DEFAULT 'unknown' AFTER energy_kwh_per_kg;
ALTER TABLE emission_factors ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER source;

ALTER TABLE pickups ADD COLUMN IF NOT EXISTS subcategory VARCHAR(140) NULL AFTER category;
CREATE INDEX IF NOT EXISTS idx_pickups_user_status_date ON pickups(user_id, status, schedule_date);
CREATE INDEX IF NOT EXISTS idx_pickups_category_subcategory ON pickups(category, subcategory);

TRUNCATE TABLE emission_factors;

INSERT INTO emission_factors
(category, subcategory, co2_base_kg_per_kg, co2_sa_adjusted, water_liters_per_kg, energy_kwh_per_kg, source, notes)
VALUES
('Paper', 'Mixed paper', 3.15, 2.68, 26.4, 17.0, 'EPA WARM v15', 'General paper recycling'),
('Paper', 'Newspaper', 2.86, 2.43, 22.1, 14.5, 'EPA WARM v15', 'Newsprint grade'),
('Paper', 'Cardboard / OCC', 3.32, 2.82, 28.7, 18.2, 'EPA WARM v15', 'Old corrugated cardboard'),
('Paper', 'Office paper (HGP)', 4.01, 3.41, 33.2, 21.0, 'EPA WARM v15', 'High grade printing paper'),
('Plastic', 'Mixed plastic', 1.53, 1.30, 5.8, 82.0, 'EPA WARM v15', 'General mixed plastic'),
('Plastic', 'PET (#1 bottles)', 2.23, 1.90, 8.1, 84.0, 'EPA WARM/JRC 2021', 'PET beverage bottles'),
('Plastic', 'HDPE (#2 bottles)', 1.82, 1.55, 6.4, 78.0, 'EPA WARM/JRC 2021', 'HDPE containers'),
('Plastic', 'PP (#5)', 1.73, 1.47, 5.9, 76.0, 'JRC 2021', 'Polypropylene'),
('Plastic', 'PVC (#3)', 0.98, 0.83, 4.2, 61.0, 'JRC 2021', 'PVC products'),
('Plastic', 'LDPE (#4 film)', 1.65, 1.40, 5.5, 80.0, 'JRC 2021', 'Plastic bags and film'),
('Metal', 'Mixed metal', 4.20, 3.57, 8.1, 14.0, 'UNEP IRP 2013', 'General scrap metal'),
('Metal', 'Aluminium cans', 9.10, 7.74, 14.3, 42.0, 'UNEP IRP 2013', 'Beverage cans'),
('Metal', 'Steel / Iron', 1.78, 1.51, 5.2, 8.5, 'UNEP IRP 2013', 'Structural steel scrap'),
('Metal', 'Copper wire', 3.84, 3.26, 9.8, 22.0, 'UNEP IRP 2013', 'Electrical copper wire'),
('Glass', 'Mixed glass', 0.30, 0.26, 2.1, 2.8, 'EPA WARM v15', 'Mixed cullet'),
('Glass', 'Clear glass', 0.33, 0.28, 2.3, 3.1, 'EPA WARM v15', 'Flint cullet'),
('Glass', 'Coloured glass', 0.27, 0.23, 1.9, 2.5, 'EPA WARM v15', 'Green/amber cullet'),
('E-waste', 'Mixed WEEE', 3.20, 2.72, 180.0, 38.0, 'Ecoinvent 3.9', 'General electronic waste'),
('E-waste', 'Mobile phones', 44.0, 37.4, 910.0, 210.0, 'Ecoinvent 3.9', 'Smartphones and feature phones'),
('E-waste', 'Laptops / PCs', 28.0, 23.8, 580.0, 140.0, 'Ecoinvent 3.9', 'Computers and laptops'),
('E-waste', 'Circuit boards (PCB)', 18.5, 15.7, 320.0, 95.0, 'Ecoinvent 3.9', 'Printed circuit boards'),
('Organic', 'Food waste (compost)', 0.58, 0.49, 0.0, 0.5, 'EPA WARM v15', 'Food composted not landfilled'),
('Organic', 'Garden / yard waste', 0.21, 0.18, 0.0, 0.3, 'EPA WARM v15', 'Garden waste composted'),
('Textile', 'Mixed clothing', 4.00, 3.40, 35.0, 28.0, 'Ecoinvent 3.9', 'Mixed garment recycling'),
('Textile', 'Cotton', 3.80, 3.23, 38.0, 25.0, 'Ecoinvent 3.9', 'Cotton fibre recycling'),
('Rubber', 'Tyres / rubber', 1.25, 1.06, 3.5, 15.0, 'EPA WARM v15', 'Tyre-derived fuel / crumb rubber'),
('Wood', 'Dimensional lumber', 0.54, 0.46, 1.8, 4.5, 'EPA WARM v15', 'Structural wood recycling'),
('Wood', 'Mixed wood / furniture', 0.43, 0.37, 1.4, 3.8, 'EPA WARM v15', 'Mixed wood waste');

CREATE OR REPLACE VIEW category_averages AS
SELECT
    category,
    ROUND(AVG(co2_sa_adjusted), 4) AS avg_co2,
    ROUND(AVG(water_liters_per_kg), 4) AS avg_water_liters_per_kg,
    ROUND(AVG(energy_kwh_per_kg), 4) AS avg_energy_kwh_per_kg
FROM emission_factors
GROUP BY category;
