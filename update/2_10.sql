-- Aggiunta vista per le newsletter
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Opt-in newsletter', 'IF(an_anagrafiche.enable_newsletter=1,\'SI\',\'NO\')', '20', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1');

SELECT @id_record := `id` FROM `zz_views` WHERE `id_module` = @id_module AND `name` = 'Opt-in newsletter';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, @id_record, 'Opt-in newsletter'),
(2, @id_record, 'Opt-in newsletter');

-- Aggiornamento flag Rientrabile
UPDATE `dt_causalet` SET `is_rientrabile` = '1' WHERE `dt_causalet`.`name` = 'Conto lavorazione';
UPDATE `dt_causalet` SET `is_rientrabile` = '1' WHERE `dt_causalet`.`name` = 'Conto visione';

-- Aggiunta campi in zz_operations per mappatura log API
ALTER TABLE `zz_operations` 
    ADD `level` VARCHAR(255) NULL DEFAULT NULL, 
    ADD `id_api` INT NULL DEFAULT NULL,
    ADD `context` LONGTEXT NULL DEFAULT NULL,
    ADD `message` LONGTEXT NULL DEFAULT NULL,
    ADD `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`);

ALTER TABLE `zz_operations` ADD INDEX(`id_api`);

-- Aggiunta impostazione per log risposta API solo errori/intero
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Log risposte API', 'Solo errori', 'list[Solo errori,debug]', '1', 'API');

SELECT @id := MAX(`id`) FROM `zz_settings`;
INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES 
(NULL, '1', @id, 'Log risposte API', ''),
(NULL, '2', @id, 'Log API responses', '');

-- Aggiunta modulo log operazioni
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Log operazioni', 'log_operazioni', 'SELECT |select| FROM zz_operations LEFT JOIN zz_users ON zz_operations.id_utente = zz_users.id LEFT JOIN an_anagrafiche ON zz_users.idanagrafica = an_anagrafiche.idanagrafica LEFT JOIN zz_modules_lang ON zz_operations.id_module = zz_modules_lang.id_record AND zz_modules_lang.id_lang = 1 LEFT JOIN zz_plugins_lang ON zz_operations.id_plugin = zz_plugins_lang.id_record AND zz_plugins_lang.id_lang = 1 WHERE 1=1|date_period(zz_operations.created_at)| HAVING 2=2 ORDER BY zz_operations.created_at DESC', '', 'fa fa-database', '2.9.2', '2.9.2', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Stato dei servizi'), '1', '1');

SELECT @id_module := MAX(`id`) FROM `zz_modules`;
INSERT INTO `zz_modules_lang` (`id`, `id_lang`, `id_record`, `title`, `meta_title`) VALUES 
(NULL, '1', @id_module, 'Log operazioni', 'Log operazioni'),
(NULL, '2', @id_module, 'Operations log', 'Operations log');

-- Aggiunta vista per il modulo log operazioni
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'id', 'zz_operations.id', '0', '1', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'),
(@id_module, 'Modulo/Plugin', 'CONCAT(zz_modules_lang.title, IF(zz_operations.id_plugin IS NOT NULL, CONCAT(" - ", zz_plugins_lang.title), ""))', '1', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'Record', 'IF(zz_operations.id_record IS NOT NULL, CONCAT(\'<span class="badge badge-secondary">\', zz_operations.id_record, \'</span>\'), \'\')', '2', '1', '0', '0', '1', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'Operazione', 'REPLACE(CONCAT(UCASE(LEFT(zz_operations.op, 1)), LCASE(SUBSTRING(zz_operations.op, 2))), \'_\', \' \')', '3', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'Utente', 'CONCAT(IF(zz_operations.id_api IS NULL, \'<i class="fa fa-user"></i> \', \'<i class="fa fa-plug"></i> \'), an_anagrafiche.ragione_sociale)', '4', '1', '0', '0', '1', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'Data e ora', 'zz_operations.created_at', '5', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'Livello', 'zz_operations.level', '6', '1', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'),
(@id_module, 'Messaggio', 'zz_operations.message', '7', '1', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'),
(@id_module, 'Context', 'zz_operations.context', '8', '1', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'),
(@id_module, '_bg_', 'IF(zz_operations.level = \'error\', \'#ec5353\', \'\')', '9', '1', '0', '0', '1', NULL, NULL, '0', '0', '0', '1');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES 
(1, (SELECT MAX(`id`)-9 FROM `zz_views`), 'id'),
(1, (SELECT MAX(`id`)-8 FROM `zz_views`), 'Modulo/Plugin'),
(1, (SELECT MAX(`id`)-7 FROM `zz_views`), 'Record'),
(1, (SELECT MAX(`id`)-6 FROM `zz_views`), 'Operazione'),
(1, (SELECT MAX(`id`)-5 FROM `zz_views`), 'Utente'),
(1, (SELECT MAX(`id`)-4 FROM `zz_views`), 'Data e ora'),
(1, (SELECT MAX(`id`)-3 FROM `zz_views`), 'Livello'),
(1, (SELECT MAX(`id`)-2 FROM `zz_views`), 'Messaggio'),
(1, (SELECT MAX(`id`)-1 FROM `zz_views`), 'Context'),
(1, (SELECT MAX(`id`) FROM `zz_views`), '_bg_');

CREATE TABLE `in_tipiintervento_tipologie` (`id` INT NOT NULL AUTO_INCREMENT , `idtipointervento` INT NOT NULL , `tipo` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`));

-- Impostazione per consentire l'inserimento di allegati in attività completate
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Permetti l\'inserimento di allegati in attività completate', '0', 'boolean', '1', 'Attività', NULL, '0');

SELECT @id_setting := MAX(`id`) FROM `zz_settings`;
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
(1, @id_setting, 'Permetti l\'inserimento di allegati in attività completate', ''), 
(2, @id_setting, 'Allow attachment insertion in completed activities', '');

-- Risorsa api per la verifica modifica record
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) 
VALUES (NULL, 'app-v1', 'retrieve', 'verifica-aggiornamenti', 'API\\App\\v1\\VerificaAggiornamenti', '1');

-- Tabella per la gestione dei token per l'invio di notifiche all'app
CREATE TABLE `zz_app_tokens` (`id` INT NOT NULL AUTO_INCREMENT , `token` VARCHAR(500) NOT NULL , `id_user` INT NOT NULL , `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP , `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)) ENGINE = InnoDB;

-- Aggiunta colonne specifiche per FCM
ALTER TABLE `zz_app_tokens` 
ADD COLUMN `platform` VARCHAR(50) NULL AFTER `token`,
ADD COLUMN `device_info` TEXT NULL AFTER `platform`;

-- Aggiunta indice per migliorare le performance
ALTER TABLE `zz_app_tokens` ADD INDEX `idx_user_fcm` (`id_user`, `token`);

-- Registrazione della risorsa API per la gestione dei token FCM
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) 
VALUES (NULL, 'app-v1', 'create', 'fcm-tokens', 'API\\App\\v1\\FcmTokens', '1');

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) 
VALUES (NULL, 'app-v1', 'update', 'fcm-tokens', 'API\\App\\v1\\FcmTokens', '1');

-- Tabella per la gestione dei gruppi in tipi di intervento
CREATE TABLE `in_tipiintervento_groups` (`id` INT NOT NULL AUTO_INCREMENT , `idtipointervento` INT NOT NULL , `id_gruppo` INT NOT NULL , PRIMARY KEY (`id`));

-- Registrazione della risorsa API per la gestione delle notifiche
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) 
VALUES (NULL, 'app-v1', 'retrieve', 'gestione-notifiche', 'API\\App\\v1\\GestioneNotifiche', '1');

-- Aggiunta {tipo} come variabile per l'impostazione "Descrizione personalizzata in fatturazione"
UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variabili utilizzabili: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 1;

UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variables availables: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 2;