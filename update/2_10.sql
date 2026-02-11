-- Aggiunta vista per le newsletter
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Opt-in newsletter', 'IF(an_anagrafiche.enable_newsletter=1,\'SI\',\'NO\')', 20, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Opt-in newsletter'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Opt-in newsletter');

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
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Log risposte API', 'Solo errori', 'list[Solo errori,debug]', 1, 'API');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Log risposte API', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Log API responses', '');

-- Aggiunta modulo log operazioni
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Log operazioni', 'log_operazioni', 'SELECT |select| FROM zz_operations LEFT JOIN zz_users ON zz_operations.id_utente = zz_users.id LEFT JOIN an_anagrafiche ON zz_users.idanagrafica = an_anagrafiche.idanagrafica LEFT JOIN zz_modules_lang ON zz_operations.id_module = zz_modules_lang.id_record AND zz_modules_lang.id_lang = 1 LEFT JOIN zz_plugins_lang ON zz_operations.id_plugin = zz_plugins_lang.id_record AND zz_plugins_lang.id_lang = 1 WHERE 1=1|date_period(zz_operations.created_at)| HAVING 2=2 ORDER BY zz_operations.created_at DESC', '', 'fa fa-database', '2.9.2', '2.9.2', 1, (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Stato dei servizi'), 1, 1);

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_modules`), 'Log operazioni'),
(2, (SELECT MAX(`id`) FROM `zz_modules`), 'Operations log');

-- Aggiunta vista per il modulo log operazioni
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`, `html_format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'id', 'zz_operations.id', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Modulo/Plugin', 'CONCAT(zz_modules_lang.title, IF(zz_operations.id_plugin IS NOT NULL, CONCAT(" - ", zz_plugins_lang.title), ""))', 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Record', 'IF(zz_operations.id_record IS NOT NULL, CONCAT(\'<span class="badge badge-secondary">\', zz_operations.id_record, \'</span>\'), \'\')', 2, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Operazione', 'REPLACE(CONCAT(UCASE(LEFT(zz_operations.op, 1)), LCASE(SUBSTRING(zz_operations.op, 2))), \'_\', \' \')', 3, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Utente', 'CONCAT(IF(zz_operations.id_api IS NULL, \'<i class="fa fa-user"></i> \', \'<i class="fa fa-plug"></i> \'), an_anagrafiche.ragione_sociale)', 4, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Data e ora', 'zz_operations.created_at', 5, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Livello', 'zz_operations.level', 6, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Messaggio', 'zz_operations.message', 7, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), 'Context', 'zz_operations.context', 8, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Log operazioni'), '_bg_', 'IF(zz_operations.level = \'error\', \'#ec5353\', \'\')', 9, 0, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`)-9 FROM `zz_views`), 'id'),
(2, (SELECT MAX(`id`)-9 FROM `zz_views`), 'id'),
(1, (SELECT MAX(`id`)-8 FROM `zz_views`), 'Modulo/Plugin'),
(2, (SELECT MAX(`id`)-8 FROM `zz_views`), 'Module/Plugin'),
(1, (SELECT MAX(`id`)-7 FROM `zz_views`), 'Record'),
(2, (SELECT MAX(`id`)-7 FROM `zz_views`), 'Record'),
(1, (SELECT MAX(`id`)-6 FROM `zz_views`), 'Operazione'),
(2, (SELECT MAX(`id`)-6 FROM `zz_views`), 'Operation'),
(1, (SELECT MAX(`id`)-5 FROM `zz_views`), 'Utente'),
(2, (SELECT MAX(`id`)-5 FROM `zz_views`), 'User'),
(1, (SELECT MAX(`id`)-4 FROM `zz_views`), 'Data e ora'),
(2, (SELECT MAX(`id`)-4 FROM `zz_views`), 'Date and time'),
(1, (SELECT MAX(`id`)-3 FROM `zz_views`), 'Livello'),
(2, (SELECT MAX(`id`)-3 FROM `zz_views`), 'Level'),
(1, (SELECT MAX(`id`)-2 FROM `zz_views`), 'Messaggio'),
(2, (SELECT MAX(`id`)-2 FROM `zz_views`), 'Message'),
(1, (SELECT MAX(`id`)-1 FROM `zz_views`), 'Context'),
(2, (SELECT MAX(`id`)-1 FROM `zz_views`), 'Context'),
(1, (SELECT MAX(`id`) FROM `zz_views`), '_bg_'),
(2, (SELECT MAX(`id`) FROM `zz_views`), '_bg_');

CREATE TABLE `in_tipiintervento_tipologie` (`id` INT NOT NULL AUTO_INCREMENT , `idtipointervento` INT NOT NULL , `tipo` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`));

-- Impostazione per consentire l'inserimento di allegati in attività completate
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Permetti l\'inserimento di allegati in attività completate', '0', 'boolean', 1, 'Attività');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Permetti l\'inserimento di allegati in attività completate', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Allow attachment insertion in completed activities', '');

-- Risorsa api per la verifica modifica record
INSERT INTO `zz_api_resources` (`version`, `type`, `resource`, `class`, `enabled`) VALUES
('app-v1', 'retrieve', 'verifica-aggiornamenti', 'API\\App\\v1\\VerificaAggiornamenti', 1);

-- Tabella per la gestione dei token per l'invio di notifiche all'app
CREATE TABLE `zz_app_tokens` (`id` INT NOT NULL AUTO_INCREMENT , `token` VARCHAR(500) NOT NULL , `id_user` INT NOT NULL , `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP , `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)) ENGINE = InnoDB;

-- Aggiunta colonne specifiche per FCM
ALTER TABLE `zz_app_tokens`
ADD COLUMN `platform` VARCHAR(50) NULL AFTER `token`,
ADD COLUMN `device_info` TEXT NULL AFTER `platform`;

-- Aggiunta indice per migliorare le performance
ALTER TABLE `zz_app_tokens` ADD INDEX `idx_user_fcm` (`id_user`, `token`);

-- Registrazione della risorsa API per la gestione dei token FCM
INSERT INTO `zz_api_resources` (`version`, `type`, `resource`, `class`, `enabled`) VALUES
('app-v1', 'create', 'fcm-tokens', 'API\\App\\v1\\FcmTokens', 1),
('app-v1', 'update', 'fcm-tokens', 'API\\App\\v1\\FcmTokens', 1);

-- Tabella per la gestione dei gruppi in tipi di intervento
CREATE TABLE `in_tipiintervento_groups` (`id` INT NOT NULL AUTO_INCREMENT , `idtipointervento` INT NOT NULL , `id_gruppo` INT NOT NULL , PRIMARY KEY (`id`));

-- Registrazione della risorsa API per la gestione delle notifiche
INSERT INTO `zz_api_resources` (`version`, `type`, `resource`, `class`, `enabled`) VALUES
('app-v1', 'retrieve', 'gestione-notifiche', 'API\\App\\v1\\GestioneNotifiche', 1);

-- Aggiunta impostazione per il calcolo delle provvigioni agenti
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
('Calcola provvigione agenti su', 'Ricavo', 'list[Ricavo,Utile]', 1, 'Generali', 5);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Calcola provvigione agenti su', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Calculate agent commission on', '');

-- Aggiunta campi per la gestione dei cespiti
ALTER TABLE `co_righe_documenti` ADD `codice_cespite` VARCHAR(255) NULL , ADD `codice_interno_cespite` VARCHAR(255) NULL , ADD `is_smaltito` BOOLEAN NOT NULL DEFAULT 0; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Formato codice cespite', '#/YYYY', 'string', 1, 'Cespiti');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Formato codice cespite', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Asset code format', '');

UPDATE `zz_modules` SET `name` = 'Cespiti', `options` = 'SELECT |select| FROM `co_righe_documenti` LEFT JOIN `co_righe_ammortamenti` ON `co_righe_ammortamenti`.`id_riga` = `co_righe_documenti`.`id` INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE 1=1 AND `is_cespite` = 1 GROUP BY co_righe_documenti.id HAVING 2=2' WHERE `name` = 'Ammortamenti / Cespiti';
UPDATE `zz_modules_lang` SET `title` = 'Cespiti' WHERE `id_record` = (select `id` from `zz_modules` where `name` = 'Cespiti');
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Ammortamenti', 'ammortamenti', 'SELECT |select| FROM `co_righe_documenti` RIGHT JOIN `co_righe_ammortamenti` ON `co_righe_ammortamenti`.`id_riga` = `co_righe_documenti`.`id` INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE 1=1 AND `is_cespite` = 1 HAVING 2=2', '', 'fa fa-circle-o', '2.10', '2.10', 1, (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Cespiti'), 1, 1);

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_modules`), 'Ammortamenti'),
(2, (SELECT MAX(`id`) FROM `zz_modules`), 'Depreciations');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`, `format`, `summable`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'id', '`co_righe_documenti`.`id`', 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Descrizione', '`co_righe_documenti`.`descrizione`', 2, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Importo', '`co_righe_documenti`.`subtotale`', 3, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Fattura', 'CONCAT("Fattura ", `co_documenti`.`numero_esterno`, " del ", YEAR(`co_documenti`.`data`))', 4, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Anno', '`co_righe_ammortamenti`.`anno`', 5, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti'), 'Stato', 'IF(co_righe_documenti.is_smaltito=1,\'Smaltito\',IF(co_righe_documenti.is_smaltito=1,\'Smaltito\',IF(anno>YEAR(NOW()),\'Ammortizzato\',\'In ammortamento\')))', 6, 1, 0, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'id'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'id'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Descrizione'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Description'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Importo'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Amount'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Fattura'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Invoice'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Anno' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Anno'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Anno' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Year'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Stato'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti')), 'Status');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Cespiti'), 'Stato', 'IF(anno IS NULL,\'\',IF(MAX(anno)>=YEAR(NOW()),\'In ammortamento\',\'Ammortizzato\'))', 6, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Cespiti')), 'Stato'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Cespiti')), 'Status');

-- Aggiunta conto per erario Iva
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Conto per erario Iva', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione` = 'Erario c/to iva'), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', 1, 'Piano dei conti');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Conto per erario Iva', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Account to use for VAT collection', '');

-- Aggiunta campo idmastrino alla tabella co_stampecontabili
ALTER TABLE `co_stampecontabili` ADD `idmastrino` INT NULL;

-- Descrizione aggiuntiva personalizzata in fatturazione
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Descrizione aggiuntiva personalizzata in fatturazione', '', 'textarea', 1, 'Fatturazione');

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
INSERT INTO `zz_api_resources` (`version`, `type`, `resource`, `class`, `enabled`) VALUES
('app-v1', 'retrieve', 'movimento-manuale', 'API\\App\\v1\\MovimentiManuali', 1);

-- #1628 Colonna sede destinazione in preventivi
UPDATE `zz_modules` SET `options` = 'SELECT\r\n |select|\r\nFROM\r\n `co_preventivi`\r\n LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`\r\n LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND co_statipreventivi_lang.id_lang = |lang|)\r\n LEFT JOIN (SELECT `idpreventivo`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_preventivi` GROUP BY `idpreventivo`) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`\r\n LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`) AS agente ON `agente`.`idanagrafica` = `co_preventivi`.`idagente`\r\n LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`, `co_righe_documenti`.`original_document_id` AS `idpreventivo` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = \'ModulesPreventiviPreventivo\' GROUP BY `idpreventivo`, `original_document_id`) AS `fattura` ON `fattura`.`idpreventivo` = `co_preventivi`.`id`\r\n LEFT JOIN (SELECT COUNT(em_emails.id) AS emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Preventivi\') AND `zz_operations`.`op` = \'send-email\' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_preventivi`.`id`\r\n LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, \'<br />\', IF(`an_sedi`.`telefono` != \'\', CONCAT(`an_sedi`.`telefono`, \'<br />\'), \'\'), IF(`an_sedi`.`cellulare` != \'\', CONCAT(`an_sedi`.`cellulare`, \'<br />\'), \'\'), `an_sedi`.`citta`, IF(`an_sedi`.`indirizzo` != \'\', CONCAT(\' - \', `an_sedi`.`indirizzo`), \'\')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `co_preventivi`.`idsede_destinazione`\r\nWHERE\r\n 1=1\r\n |segment(`co_preventivi`.`id_segment`)|\r\n |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = NULL)|\r\n AND `default_revision` = 1\r\nGROUP BY\r\n `co_preventivi`.`id`,\r\n `fattura`.`info`\r\nHAVING\r\n 2=2\r\nORDER BY\r\n `co_preventivi`.`data_bozza` DESC, `numero` ASC', `options2` = '' WHERE `zz_modules`.`name` = 'Preventivi';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`, `html_format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Sede destinazione', 'IF(co_preventivi.idsede_destinazione > 0, sede_destinazione.info, CONCAT(\'\', IF(an_anagrafiche.telefono!=\'\',CONCAT(an_anagrafiche.telefono,\'<br>\'),\'\'),IF(an_anagrafiche.cellulare!=\'\',CONCAT(an_anagrafiche.cellulare,\'<br>\'),\'\'),IF(an_anagrafiche.citta!=\'\',an_anagrafiche.citta,\'\'),IF(an_anagrafiche.indirizzo!=\'\',CONCAT(\' - \',an_anagrafiche.indirizzo),\'\')))', 5, 0, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Sede destinazione'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Destination address');

-- #1706 - Pre-selezione barcode automatico
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Genera barcode automaticamente', '0', 'boolean', 1, 'Magazzino');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Genera barcode automaticamente', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Generate barcode automatically', '');

-- #1533 - Gestione eliminazione utenti
ALTER TABLE `zz_users` ADD `deleted_at` DATE NULL DEFAULT NULL;

-- Creazione tabella per registri viaggio automezzi
CREATE TABLE IF NOT EXISTS `an_automezzi_viaggi` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idtecnico` INT NOT NULL,
    `idsede` INT NOT NULL,
    `data_inizio` DATETIME NULL,
    `data_fine` DATETIME NULL,
    `km_inizio` INT NOT NULL,
    `km_fine` INT NOT NULL,
    `destinazione` VARCHAR(255) NOT NULL,
    `motivazione` VARCHAR(255) DEFAULT NULL,
    `firma_data` DATETIME DEFAULT NULL,
    `firma_nome` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX(`idsede`)
);

-- Aggiunta foreign key
ALTER TABLE `an_automezzi_viaggi` ADD CONSTRAINT `an_automezzi_viaggi_ibfk_1` FOREIGN KEY (`idsede`) REFERENCES `an_sedi`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- Creazione tabella per rifornimenti automezzi
CREATE TABLE IF NOT EXISTS `an_automezzi_rifornimenti` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idviaggio` INT NOT NULL,
    `data` DATETIME NOT NULL,
    `luogo` VARCHAR(255) NOT NULL,
    `id_carburante` INT NULL,
    `quantita` DECIMAL(15,2) NOT NULL,
    `costo` DECIMAL(15,2) NOT NULL,
    `id_gestore` INT NULL,
    `codice_carta` VARCHAR(100) DEFAULT NULL,
    `km` INT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX(`idviaggio`)
);

-- Aggiunta foreign key per rifornimenti
ALTER TABLE `an_automezzi_rifornimenti` ADD CONSTRAINT `an_automezzi_rifornimenti_ibfk_1` FOREIGN KEY (`idviaggio`) REFERENCES `an_automezzi_viaggi`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- Aggiunta stampa registro viaggio
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 1, 'Registro viaggio', 'registro_viaggio', 'id', '', 'fa fa-print', '', '', 0, 0, 1);

-- Aggiunta traduzione stampa registro viaggio
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES
(1, (SELECT `id` FROM `zz_prints` WHERE `name` = 'Registro viaggio'), 'Registro viaggio', 'Registro viaggio'),
(2, (SELECT `id` FROM `zz_prints` WHERE `name` = 'Registro viaggio'), 'Travel register', 'Travel register');

-- Creazione tabella per manutenzioni/scadenze automezzi
CREATE TABLE IF NOT EXISTS `an_automezzi_scadenze` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idsede` INT NOT NULL,
    `descrizione` VARCHAR(255) NOT NULL,
    `data_inizio` DATE NOT NULL,
    `data_fine` DATE DEFAULT NULL,
    `km` INT DEFAULT NULL,
    `codice` VARCHAR(100) DEFAULT NULL,
    `is_manutenzione` TINYINT(1) NOT NULL DEFAULT 0,
    `is_completato` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX(`idsede`)
);

-- Aggiunta foreign key per scadenze
ALTER TABLE `an_automezzi_scadenze` ADD CONSTRAINT `an_automezzi_scadenze_ibfk_1` FOREIGN KEY (`idsede`) REFERENCES `an_sedi`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options`, `directory`, `help`) VALUES ('Manutenzioni', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'tab', '', '1', '0', '0', '2.*', '2.10', 'custom', 'automezzi_manutenzioni', '');
INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, (SELECT MAX(`id`) FROM `zz_plugins`), 'Manutenzioni'),
  (2, (SELECT MAX(`id`) FROM `zz_plugins`), 'Maintenance');

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options`, `directory`, `help`) VALUES ('Scadenze', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'tab', '', '1', '0', '0', '2.*', '2.10', 'custom', 'automezzi_scadenze', '');
INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, LAST_INSERT_ID(), 'Scadenze'),
  (2, LAST_INSERT_ID(), 'Deadlines');

-- Creazione tabella per tipi di carburante
CREATE TABLE IF NOT EXISTS `an_automezzi_tipi_carburante` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `descrizione` VARCHAR(100) NOT NULL,
    `um` VARCHAR(20) NOT NULL,
    PRIMARY KEY (`id`)
);

-- Inserimento tipi di carburante predefiniti
INSERT INTO `an_automezzi_tipi_carburante` (`descrizione`, `um`) VALUES
('Benzina', 'litri'),
('Diesel', 'litri'),
('GPL', 'litri'),
('Metano', 'kg');

-- Creazione tabella per gestori carburante
CREATE TABLE IF NOT EXISTS `an_automezzi_gestori` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `descrizione` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`)
);

-- Aggiunta foreign key per tipo carburante
ALTER TABLE `an_automezzi_rifornimenti`
    ADD CONSTRAINT `an_automezzi_rifornimenti_ibfk_2`
    FOREIGN KEY (`id_carburante`) REFERENCES `an_automezzi_tipi_carburante`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

-- Aggiunta foreign key per gestore
ALTER TABLE `an_automezzi_rifornimenti`
    ADD CONSTRAINT `an_automezzi_rifornimenti_ibfk_3`
    FOREIGN KEY (`id_gestore`) REFERENCES `an_automezzi_gestori`(`id`) ON DELETE SET NULL ON UPDATE NO ACTION;

-- Inserimento modulo Tipi carburante
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`)
VALUES ('Tipi carburante', 'tipi_carburante', 'SELECT |select| FROM `an_automezzi_tipi_carburante` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.5.8', '2.5.8', '3', (SELECT `id` FROM `zz_modules` `m` WHERE `name`='Automezzi'), '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT MAX(id) FROM `zz_modules`), 'Tipi carburante', 'Tipi carburante'),
('2', (SELECT MAX(id) FROM `zz_modules`), 'Fuel types', 'Fuel types');

-- Inserimento viste per modulo Tipi carburante
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name`='Tipi carburante'), 'id', 'an_automezzi_tipi_carburante.id', 1, 1, 0, 0, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name`='Tipi carburante'), 'Descrizione', 'an_automezzi_tipi_carburante.descrizione', 2, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name`='Tipi carburante'), 'Unità di misura', 'an_automezzi_tipi_carburante.um', 3, 1, 0, 0, '', '', 1, 0, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT MAX(id)-2 FROM `zz_views`), 'id'),
('1', (SELECT MAX(id)-1 FROM `zz_views`), 'Descrizione'),
('1', (SELECT MAX(id) FROM `zz_views`), 'Unità di misura'),
('2', (SELECT MAX(id)-2 FROM `zz_views`), 'id'),
('2', (SELECT MAX(id)-1 FROM `zz_views`), 'Description'),
('2', (SELECT MAX(id) FROM `zz_views`), 'Unit of measure');

-- Inserimento modulo Gestori carburante
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`)
VALUES ('Gestori carburante', 'gestori_carburante', 'SELECT |select| FROM `an_automezzi_gestori` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.5.8', '2.5.8', '4', (SELECT `id` FROM `zz_modules` `m` WHERE `name`='Automezzi'), '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT MAX(id) FROM `zz_modules`), 'Gestori carburante', 'Gestori carburante'),
('2', (SELECT MAX(id) FROM `zz_modules`), 'Fuel suppliers', 'Fuel suppliers');

-- Inserimento viste per modulo Gestori carburante
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name`='Gestori carburante'), 'id', 'an_automezzi_gestori.id', 1, 1, 0, 0, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name`='Gestori carburante'), 'Descrizione', 'an_automezzi_gestori.descrizione', 2, 1, 0, 0, '', '', 1, 0, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT MAX(id)-1 FROM `zz_views`), 'id'),
('1', (SELECT MAX(id) FROM `zz_views`), 'Descrizione'),
('2', (SELECT MAX(id)-1 FROM `zz_views`), 'id'),
('2', (SELECT MAX(id) FROM `zz_views`), 'Description');

DROP TABLE IF EXISTS `an_sedi_tecnici`;

-- Aggiunta campo deleted_at per gestire eliminazione sedi
ALTER TABLE `an_sedi` ADD `deleted_at` TIMESTAMP NULL AFTER `enable_newsletter`; 

-- modifica nome campo gg_garanzia in garanzia in mg_articoli e aggiunta campo tipo_garanzia per gestione dell'unità di misura del valore (giorni, mesi, anni)
ALTER TABLE `mg_articoli` 
    CHANGE `gg_garanzia` `garanzia` INT(11) NOT NULL DEFAULT 0,
    ADD `tipo_garanzia` ENUM('days', 'months', 'years')  NOT NULL DEFAULT 'days';

-- Aggiunta sconto combinato
ALTER TABLE `co_righe_contratti` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_righe_contratti` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;
ALTER TABLE `co_righe_documenti` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_righe_documenti` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;
ALTER TABLE `co_righe_preventivi` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_righe_preventivi` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;
ALTER TABLE `dt_righe_ddt` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `dt_righe_ddt` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;
ALTER TABLE `in_righe_interventi` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `in_righe_interventi` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;
ALTER TABLE `or_righe_ordini` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `or_righe_ordini` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;

-- Nascondere totali tabelle
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Nascondere il valore totale dei record delle tabelle', '1', 'boolean', 1, 'Generali');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Nascondere il valore totale dei record delle tabelle', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Hide total value of records in tables', '');

-- Abilita correttore ortografico CKEditor
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Abilita correttore ortografico', '0', 'boolean', 1, 'Generali');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Abilita correttore ortografico', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Enable spell checker', '');

-- Aggiunta impostazione Unità di misura predefinita
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Unità di misura predefinita', '', 'query=SELECT id, valore as descrizione FROM mg_unitamisura', 1, 'Magazzino');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Unità di misura predefinita', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Default unit of measurement', '');

-- Aggiunta impostazione Durata sessione
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Durata sessione', '60', 'integer', 1, 'Generali');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Durata sessione (minuti)', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Session duration (minutes)', '');

-- Fix stampa registro viaggi
UPDATE `zz_prints` SET `predefined` = '1' WHERE `zz_prints`.`name` = 'Registro viaggio'; 
UPDATE `zz_prints_lang` SET `filename` = 'Registro viaggio {nome} {targa}' WHERE `zz_prints_lang`.`title` = 'Registro viaggio';
UPDATE `zz_prints_lang` SET `filename` = 'Travel register {nome} {targa}' WHERE `zz_prints_lang`.`title` = 'Travel register';

-- Aggiunta gestione delle scadenze per il pagamento automatico
ALTER TABLE `co_pagamenti` ADD `registra_pagamento_automatico` BOOLEAN NOT NULL;
INSERT INTO `zz_tasks` (`id`, `name`, `class`, `expression`, `next_execution_at`, `last_executed_at`, `created_at`, `updated_at`, `enabled`) VALUES (NULL, 'Pagamento automatico', 'Modules\\PrimaNota\\PagamentoAutomaticoTask', '0 */24 * * *', NULL, NULL, NULL, NULL, '1');

-- Creazione tabella per le tariffe delle sedi
CREATE TABLE IF NOT EXISTS `in_tariffe_sedi` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idsede` INT NOT NULL,
    `idtipointervento` INT NOT NULL,
    `costo_ore` DECIMAL(12,4) NOT NULL,
    `costo_km` DECIMAL(12,4) NOT NULL,
    `costo_dirittochiamata` DECIMAL(12,4) NOT NULL,
    PRIMARY KEY (`id`)
);
ALTER TABLE `in_tariffe_sedi` ADD CONSTRAINT `in_tariffe_sedi_ibfk_1` FOREIGN KEY (`idsede`) REFERENCES `an_sedi`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
ALTER TABLE `in_tariffe_sedi` ADD CONSTRAINT `in_tariffe_sedi_ibfk_2` FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

-- Aggiunta impostazioni per esportazione XML LIPE
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES 
(NULL, 'Codice fiscale dichiarante', '', 'string', '1', 'LIPE XML', NULL),
(NULL, 'Codice fiscale intermediario', '', 'string', '1', 'LIPE XML', NULL),
(NULL, 'Identificativo software', '', 'string', '1', 'LIPE XML', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
(1, (SELECT MAX(`id`)-2 FROM `zz_settings`), 'Codice fiscale dichiarante', 'Codice fiscale del dichiarante per la LIPE (Liquidazione IVA Periodica). Se vuoto, viene usato il codice fiscale dell''azienda predefinita.'),
(1, (SELECT MAX(`id`)-1 FROM `zz_settings`), 'Codice fiscale intermediario', 'Codice fiscale dell''intermediario per la LIPE.'),
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Identificativo software', 'Codice identificativo del software utilizzato per la LIPE (es. codice fiscale del produttore del software). Se vuoto, il campo viene omesso nel file XML.'),
(2, (SELECT MAX(`id`)-2 FROM `zz_settings`), 'Tax ID of declarant', 'Tax ID of declarant for LIPE (Periodic VAT Settlement). If empty, the company''s default tax ID is used.'),
(2, (SELECT MAX(`id`)-1 FROM `zz_settings`), 'Tax ID of intermediary', 'Tax ID of intermediary for LIPE.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Software identifier', 'Software identifier code for LIPE (e.g., tax ID of software producer). If empty, the field is omitted from the XML file.');

-- Nuovo plugin Danni automezzi
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options`, `directory`, `help`) VALUES ('Danni', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'tab', '', '1', '0', '0', '2.*', '2.10', 'custom', 'automezzi_danni', '');
INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, (SELECT MAX(`id`) FROM `zz_plugins`), 'Danni'),
  (2, (SELECT MAX(`id`) FROM `zz_plugins`), 'Damages');

-- Creazione tabella per danni automezzi
CREATE TABLE IF NOT EXISTS `an_automezzi_danni` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `idsede` INT NOT NULL,
    `descrizione` VARCHAR(255) NOT NULL,
    `data` DATE NOT NULL,
    `luogo` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX(`idsede`)
);

-- Aggiunta colonne minimo e massimo alla tabella mg_listini_articoli per gestire i prezzi per range
ALTER TABLE `mg_listini_articoli` ADD `minimo` decimal(15,6) DEFAULT NULL;
ALTER TABLE `mg_listini_articoli` ADD `massimo` decimal(15,6) DEFAULT NULL;

-- Aggiunta provider OAuth2 Keycloak
INSERT INTO `zz_oauth2` (`name`, `class`, `client_id`, `client_secret`, `config`, `state`, `access_token`, `refresh_token`, `after_configuration`, `is_login`, `enabled`) VALUES
('Keycloak', 'Modules\\Emails\\OAuth2\\KeycloakLogin', '', '', '{\"auth_server_url\":\"\",\"realm\":\"\"}', '', NULL, NULL, '', 1, 0);