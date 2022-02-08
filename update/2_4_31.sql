-- Aggiunta dicitura fissa nei segmenti fiscali
ALTER TABLE `zz_segments` ADD `dicitura_fissa` TEXT NOT NULL AFTER `note`; 