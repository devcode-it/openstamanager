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

-- Aggiunta impostazione per il calcolo delle provvigioni agenti
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES (NULL, 'Calcola provvigione agenti su', 'Ricavo', 'list[Ricavo,Utile]', '1', 'Generali', '5', '0');
INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '1', (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Calcola provvigione agenti su'), 'Calcola provvigione agenti su', NULL), (NULL, '2', (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Calcola provvigione agenti su'), 'Calcola provvigione agenti su', NULL);

-- Aggiunta campi per la gestione dei cespiti
ALTER TABLE `co_righe_documenti` ADD `codice_cespite` VARCHAR(255) NULL , ADD `codice_interno_cespite` VARCHAR(255) NULL , ADD `is_smaltito` BOOLEAN NOT NULL DEFAULT 0; 

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `is_user_setting`) VALUES (NULL, 'Formato codice cespite', '#/YYYY', 'string', '1', 'Cespiti', NULL, NULL, NULL, '0');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Formato codice cespite', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Asset code format', '');

UPDATE `zz_modules` SET `name` = 'Cespiti', `options` = 'SELECT |select| FROM `co_righe_documenti` LEFT JOIN `co_righe_ammortamenti` ON `co_righe_ammortamenti`.`id_riga` = `co_righe_documenti`.`id` INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE 1=1 AND `is_cespite` = 1 GROUP BY co_righe_documenti.id HAVING 2=2' WHERE `name` = 'Ammortamenti / Cespiti';
UPDATE `zz_modules_lang` SET `title` = 'Cespiti' WHERE `id_record` = (select `id` from `zz_modules` where `name` = 'Cespiti');
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Ammortamenti', 'ammortamenti', 'SELECT |select| FROM `co_righe_documenti` RIGHT JOIN `co_righe_ammortamenti` ON `co_righe_ammortamenti`.`id_riga` = `co_righe_documenti`.`id` INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE 1=1 AND `is_cespite` = 1 HAVING 2=2', '', 'fa fa-circle-o', '2.10', '2.10', '1', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Cespiti'), '1', '1', '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Ammortamenti', 'Ammortamenti'),
('2', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Ammortamenti', 'Ammortamenti');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Descrizione', '`co_righe_documenti`.`descrizione`', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Importo', '`co_righe_documenti`.`subtotale`', '3', '1', '0', '1', '0', NULL, NULL, '1', '1', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Fattura', 'CONCAT("Fattura ", `co_documenti`.`numero_esterno`, " del ", YEAR(`co_documenti`.`data`))', '4', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Anno', '`co_righe_ammortamenti`.`anno`', '5', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'id', '`co_righe_documenti`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Stato', 'IF(co_righe_documenti.is_smaltito=1,\'Smaltito\',IF(co_righe_documenti.is_smaltito=1,\'Smaltito\',IF(anno>YEAR(NOW()),\'Ammortizzato\',\'In ammortamento\')))', '6', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Descrizione'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Description'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Importo'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Amount'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Fattura'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Invoice'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Anno' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Anno'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Anno' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Year'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'id'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'id'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Stato'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Status');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Cespiti'), 'Stato', 'IF(anno IS NULL,\'\',IF(MAX(anno)>=YEAR(NOW()),\'In ammortamento\',\'Ammortizzato\'))', '6', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Cespiti')), 'Stato'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Cespiti')), 'Status');

-- Aggiunta conto per erario Iva
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Conto per erario Iva', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione` = 'Erario c/to iva'), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei conti', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Conto per erario Iva', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Account to use for VAT collection', '');

-- Aggiunta campo idmastrino alla tabella co_stampecontabili
ALTER TABLE `co_stampecontabili` ADD `idmastrino` INT NULL;

-- Descrizione aggiuntiva personalizzata in fatturazione
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Descrizione aggiuntiva personalizzata in fatturazione', '', 'textarea', '1', 'Fatturazione', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Descrizione aggiuntiva personalizzata in fatturazione', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Additional custom description in invoices', '');

-- Creazione tabella per i flag dei moduli
CREATE TABLE IF NOT EXISTS `zz_modules_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11) NOT NULL,
  `name` enum('use_checklists', 'use_notes', 'enable_otp') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_module_flag` (`id_module`, `name`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Migrazione dei flag esistenti use_notes dalla tabella zz_modules
INSERT INTO `zz_modules_flags` (`id_module`, `name`)
SELECT `id`, 'use_notes'
FROM `zz_modules`
WHERE `use_notes` = 1;

-- Migrazione dei flag esistenti use_checklists dalla tabella zz_modules
INSERT INTO `zz_modules_flags` (`id_module`, `name`)
SELECT `id`, 'use_checklists'
FROM `zz_modules`
WHERE `use_checklists` = 1;

-- Aggiunta flag enable_otp per i moduli specificati
INSERT INTO `zz_modules_flags` (`id_module`, `name`)
SELECT `id`, 'enable_otp'
FROM `zz_modules`
WHERE `name` IN ('Anagrafiche', 'Impianti', 'Gestione documentale');

-- Rimozione dei campi obsoleti dalla tabella zz_modules
ALTER TABLE `zz_modules` DROP COLUMN `use_notes`;
ALTER TABLE `zz_modules` DROP COLUMN `use_checklists`;

-- Aggiunta colonna session_token per gestione sessione singola
ALTER TABLE `zz_users` ADD `session_token` VARCHAR(64) NULL DEFAULT NULL AFTER `password`;

-- Aggiunta risorsa API per la gestione dei movimenti manuali
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`, `created_at`, `updated_at`) VALUES (NULL, 'app-v1', 'retrieve', 'movimento-manuale', 'API\\App\\v1\\MovimentiManuali', '1', NULL, NULL)

-- #1628 Colonna sede destinazione in preventivi
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Preventivi';

UPDATE `zz_modules` SET `options` = 'SELECT\r\n |select|\r\nFROM\r\n `co_preventivi`\r\n LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`\r\n LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND co_statipreventivi_lang.id_lang = |lang|)\r\n LEFT JOIN (SELECT `idpreventivo`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_preventivi` GROUP BY `idpreventivo`) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`\r\n LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`) AS agente ON `agente`.`idanagrafica` = `co_preventivi`.`idagente`\r\n LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`, `co_righe_documenti`.`original_document_id` AS `idpreventivo` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = \'ModulesPreventiviPreventivo\' GROUP BY `idpreventivo`, `original_document_id`) AS `fattura` ON `fattura`.`idpreventivo` = `co_preventivi`.`id`\r\n LEFT JOIN (SELECT COUNT(em_emails.id) AS emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Preventivi\') AND `zz_operations`.`op` = \'send-email\' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_preventivi`.`id`\r\n LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, \'<br />\', IF(`an_sedi`.`telefono` != \'\', CONCAT(`an_sedi`.`telefono`, \'<br />\'), \'\'), IF(`an_sedi`.`cellulare` != \'\', CONCAT(`an_sedi`.`cellulare`, \'<br />\'), \'\'), `an_sedi`.`citta`, IF(`an_sedi`.`indirizzo` != \'\', CONCAT(\' - \', `an_sedi`.`indirizzo`), \'\')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `co_preventivi`.`idsede_destinazione`\r\nWHERE\r\n 1=1\r\n |segment(`co_preventivi`.`id_segment`)|\r\n |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = NULL)|\r\n AND `default_revision` = 1\r\nGROUP BY\r\n `co_preventivi`.`id`,\r\n `fattura`.`info`\r\nHAVING\r\n 2=2\r\nORDER BY\r\n `co_preventivi`.`data_bozza` DESC, `numero` ASC', `options2` = '' WHERE `zz_modules`.`id` = @id_module;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Opt-in newsletter', 'IF(co_preventivi.idsede_destinazione > 0, sede_destinazione.info, CONCAT(\'\', IF(an_anagrafiche.telefono!=\'\',CONCAT(an_anagrafiche.telefono,\'<br>\'),\'\'),IF(an_anagrafiche.cellulare!=\'\',CONCAT(an_anagrafiche.cellulare,\'<br>\'),\'\'),IF(an_anagrafiche.citta!=\'\',an_anagrafiche.citta,\'\'),IF(an_anagrafiche.indirizzo!=\'\',CONCAT(\' - \',an_anagrafiche.indirizzo),\'\')))', '5', '1', '0', '0', '1', NULL, NULL, '0', '0', '0', '1');