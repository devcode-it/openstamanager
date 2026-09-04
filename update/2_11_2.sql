-- Aggiunto sconto combinato
ALTER TABLE `co_righe_promemoria` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_righe_promemoria` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;