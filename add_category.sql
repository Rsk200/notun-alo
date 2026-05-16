ALTER TABLE notun_alo.products ADD COLUMN category VARCHAR(100) DEFAULT 'General';
UPDATE notun_alo.products SET category = 'Stationery' WHERE name LIKE '%Notebook%' OR name LIKE '%Pen%';
UPDATE notun_alo.products SET category = 'Accessories' WHERE name LIKE '%Bag%';
UPDATE notun_alo.products SET category = 'Home Decor' WHERE name LIKE '%Pot%' OR name LIKE '%Coaster%' OR name LIKE '%vass%';
