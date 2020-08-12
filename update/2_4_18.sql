-- Miglioramento della cache interna
CREATE TABLE IF NOT EXISTS `zz_tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `class` TEXT NOT NULL,
    `expression` VARCHAR(255) NOT NULL,
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
