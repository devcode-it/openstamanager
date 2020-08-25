-- Miglioramento della cache interna
CREATE TABLE IF NOT EXISTS `zz_tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `class` TEXT NOT NULL,
    `expression` VARCHAR(255) NOT NULL,
    `next_execution_at` timestamp NULL,
    `last_executed_at` timestamp NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_cache` (`id`, `name`, `content`, `valid_time`, `expire_at`) VALUES
(NULL, 'Ultima esecuzione del cron', '', '1 month', NULL),
(NULL, 'Riavvia cron', '', '1 month', NULL),
(NULL, 'Disabilita cron', '', '1 month', NULL);

INSERT INTO `zz_tasks` (`id`, `name`, `class`, `expression`, `last_executed_at`) VALUES
(NULL, 'Backup automatico', 'Modules\\Backups\\BackupTask', '0 0 * * *', NULL);

DELETE FROM `zz_hooks` WHERE `class` = 'Modules\\Backups\\BackupHook';

-- Modifica dei Listini in Piani di sconto/rincaro
UPDATE `zz_modules` SET `title` = 'Piani di sconto/rincaro' WHERE `name` = 'Listini';

-- Aggiunto supporto ai prezzi per Articoli specifici per Anagrafica e range di quantità
CREATE TABLE IF NOT EXISTS `mg_prezzi_articoli` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_articolo` int(11) NOT NULL,
    `id_anagrafica` int(11),
    `minimo` DECIMAL(15,6),
    `massimo` DECIMAL(15,6),
    `prezzo_unitario` DECIMAL(15,6) NOT NULL,
    `prezzo_unitario_ivato` DECIMAL(15,6) NOT NULL,
    `direzione` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`),
    FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`)
) ENGINE=InnoDB;

UPDATE `zz_plugins` SET `directory` = 'dettagli_articolo', `name`= 'Dettagli articolo', `title`= 'Dettagli' WHERE `name` = 'Fornitori Articolo';
