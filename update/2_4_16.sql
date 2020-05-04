-- Periodi di validità (Contratti e preventivi)
ALTER TABLE `co_contratti` ADD COLUMN `validita_periodo` ENUM('d','m','y') NOT NULL DEFAULT 'd' AFTER `validita`;
ALTER TABLE `co_preventivi` ADD COLUMN `validita_periodo` ENUM('d','m','y') NOT NULL DEFAULT 'd' AFTER `validita`;
