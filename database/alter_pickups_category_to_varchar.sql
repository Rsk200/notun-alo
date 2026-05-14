-- Run once if pickups.category is still ENUM('Paper','Plastic','Metal')
-- and you use extended categories (Glass, E-waste, etc.) from the app UI.
-- Safe to re-run: if already VARCHAR, adjust or skip manually.
ALTER TABLE `pickups` MODIFY `category` VARCHAR(50) NOT NULL;
