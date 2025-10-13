-- Allineamento vista Contratti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
    LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN (WITH RigheAgg AS (SELECT idintervento,SUM(prezzo_unitario * qta) AS sommacosti_per_intervento FROM in_righe_interventi GROUP BY idintervento), TecniciAgg AS (SELECT idintervento, SUM(prezzo_ore_consuntivo) AS sommasessioni_per_intervento FROM in_interventi_tecnici GROUP BY idintervento) SELECT SUM(COALESCE(RigheAgg.sommacosti_per_intervento, 0)) AS sommacosti, SUM(COALESCE(TecniciAgg.sommasessioni_per_intervento, 0)) AS sommasessioni, i.id_contratto FROM in_interventi i LEFT JOIN RigheAgg ON RigheAgg.idintervento = i.id LEFT JOIN TecniciAgg ON TecniciAgg.idintervento = i.id GROUP BY i.id_contratto) AS spesacontratto ON spesacontratto.id_contratto = co_contratti.id
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br />') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id LEFT JOIN in_tipiintervento ON in_interventi_tecnici.idtipointervento=in_tipiintervento.id WHERE non_conteggiare=0 GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
    LEFT JOIN `co_categorie_contratti` ON `co_contratti`.`id_categoria` = `co_categorie_contratti`.`id`
    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)
    LEFT JOIN `co_categorie_contratti` AS sottocategorie ON `co_contratti`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `co_categorie_contratti_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idcontratto` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\\\Contratti\\\\Contratto' GROUP BY `idcontratto`, `original_document_id`) AS `fattura` ON `fattura`.`idcontratto` = `co_contratti`.`id`
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
GROUP BY
    `co_contratti`.`id`
HAVING
    2=2
ORDER BY
    `co_contratti`.`data_bozza` DESC" WHERE `name` = 'Contratti';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
((SELECT id FROM zz_modules WHERE name = 'Contratti'), 'Rif. fattura', 'fattura.info', 18, 1, 1, 0, 0, '', '', 1, 0, 0, 0);

-- Aggiunta traduzione per la colonna "Rif. fattura"
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE name = 'Contratti') AND `name` = 'Rif. fattura'), 'Rif. fattura'),
('2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE name = 'Contratti') AND `name` = 'Rif. fattura'), 'Invoice Ref.');


-- Plugin barcode
-- Creazione tabella
CREATE TABLE IF NOT EXISTS `mg_articoli_barcode` (
	`id` int(4) NOT NULL AUTO_INCREMENT,
	`idarticolo` INT NOT NULL,
	`barcode` varchar(100) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

ALTER TABLE `mg_articoli_barcode` ADD CONSTRAINT `mg_articoli_barcode_ibfk_1` FOREIGN KEY (`idarticolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Creazione del plugin
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options`, `directory`, `help`) VALUES ('Barcode', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'tab', '', '1', '0', '0', '2.*', '2.4.23', '{ "main_query": [{"type": "table", "fields": "Barcode", "query": "SELECT mg_articoli_barcode.id, mg_articoli_barcode.barcode AS Barcode FROM mg_articoli_barcode WHERE 1=1 AND mg_articoli_barcode.idarticolo=|id_parent| HAVING 2=2 ORDER BY barcode ASC"}]}', 'barcode_articoli', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, LAST_INSERT_ID(), 'Barcode'),
  (2, LAST_INSERT_ID(), 'Barcode');

INSERT INTO `mg_articoli_barcode` (`idarticolo`, `barcode`) (SELECT `mg_articoli`.`id`, `mg_articoli`.`barcode` FROM `mg_articoli` WHERE `mg_articoli`.`barcode` IS NOT NULL AND `mg_articoli`.`barcode` != '');

-- Aggiorno la vista barcode nella scheda articolo
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'barcode_lista', 'barcode.lista', '17', '1', '0', '0', '0', '', '', '0', '0', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'barcode_lista' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')), 'barcode_lista'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'barcode_lista' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')), 'barcode_lista');

UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `query`='CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(`barcode`.`lista`, \'<br />\', 2), \'<br />\', -2), \'<br />...\')', `html_format`=1, `search_inside` = 'barcode_lista' WHERE `zz_views`.`name` = 'barcode' AND `zz_modules`.`name` = 'Articoli';

-- Gestione barcode nelle righe dei documenti
ALTER TABLE `co_righe_contratti` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `co_righe_preventivi` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `or_righe_ordini` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `co_righe_documenti` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `dt_righe_ddt` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `in_righe_interventi` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;

-- Impostazione per raggruppamento righe per articolo e barcode nei DDT
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
('Raggruppa gli articoli con stesso barcode nei DDT', '0', 'boolean', '1', 'Ddt', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome`='Raggruppa gli articoli con stesso barcode nei DDT'), 'Raggruppa gli articoli con stesso barcode nei DDT', ''),
(2, (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome`='Raggruppa gli articoli con stesso barcode nei DDT'), 'Group the items with the same barcode in the delivery notes', '');

-- Data competenza movimenti
ALTER TABLE `co_movimenti` ADD `data_inizio_competenza` DATE NULL AFTER `data`, ADD `data_fine_competenza` DATE NULL AFTER `data_inizio_competenza`;

-- Aggiunta della tabella per gestire i token OTP per l'autenticazione
CREATE TABLE IF NOT EXISTS `zz_otp_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_utente` int(11) NULL,
    `token` varchar(255) NOT NULL,
    `descrizione` varchar(255),
    `tipo_accesso` varchar(255) NOT NULL,
    `valido_dal` DATETIME NULL,
    `valido_al` DATETIME NULL,
    `id_module_target` int(11) NOT NULL,
    `id_record_target` int(11) NOT NULL,
    `permessi` enum('r', 'rw') NULL,
    `email` varchar(255) NOT NULL,
    `enabled` tinyint(1) NOT NULL DEFAULT 0,
    `last_otp` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Aggiunta del modulo per la gestione dei token OTP
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Accesso con Token/OTP', 'otp_tokens', 'SELECT |select| FROM `zz_otp_tokens` WHERE 1=1 HAVING 2=2', '', 'fa fa-link', '2.9', '2.9', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP'), 'Accesso con Token/OTP', 'Accesso con Token/OTP'),
('2', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP'), 'OTP/Token login', 'OTP/Token login');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP'), 'id', '`zz_otp_tokens`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP'), 'Descrizione', '`zz_otp_tokens`.`descrizione`', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP')), 'id'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP')), 'id'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP')), 'Descrizione'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con Token/OTP')), 'Description');

-- Aggiunta del template email per la richiesta del codice OTP
INSERT INTO `em_templates` (`id`, `id_module`, `name`, `icon`, `tipo_reply_to`, `reply_to`, `cc`, `bcc`, `read_notify`, `predefined`, `note_aggiuntive`, `enabled`, `type`, `indirizzi_proposti`, `deleted_at`, `id_account`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'Richiesta codice OTP', 'fa fa-envelope', '', '', '', '', 0, 0, '', 1, 'a', 0, NULL, 1);

INSERT INTO `em_templates_lang` (`id`, `id_lang`, `id_record`, `title`, `subject`, `body`) VALUES
(NULL, 1, (SELECT id FROM `em_templates` WHERE `name` = 'Richiesta codice OTP'), 'Richiesta codice OTP', 'Richiesta codice OTP', "<p>Gentile {username},</p>\n\n<p>di seguito il codice OTP per il login:</p>\n\n<p><strong>{codice_otp}</strong></p>\n\n<p> </p>\n\n<p> </p>\n\n<p>Se non sei il responsabile della richiesta in questione, contatta l\'amministratore il prima possibile.</p>\n\n<p> </p>\n\n<p>Distinti saluti</p>"),
(NULL, 2, (SELECT id FROM `em_templates` WHERE `name` = 'Richiesta codice OTP'), 'OTP code request', 'OTP code request', '<p>Dear {username},</p>\n\n<p>below is the OTP code for login:</p>\n\n<p><strong>{codice_otp}</strong></p>\n\n<p> </p>\n\n<p> </p>\n\n<p>If you are not responsible for the request in question, please contact the administrator as soon as possible.</p>\n\n<p> </p>\n\n<p>Best regards</p>');

-- Aggiunta impostazione per il template email della richiesta del codice OTP
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES (NULL, 'Template email richiesta codice OTP', (SELECT id FROM `em_templates` WHERE `name` = 'Richiesta codice OTP'), 'query=SELECT `em_templates`.`id`, `name` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \"Lingua\"))', '1', 'Generali', '2', '0');

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '1', (SELECT id FROM `zz_settings` WHERE `nome` = 'Template email richiesta codice OTP'), 'Template email richiesta codice OTP', '');

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '2', (SELECT id FROM `zz_settings` WHERE `nome` = 'Template email richiesta codice OTP'), 'OTP code request email template', '');

-- Sposto tutti i metodi di accesso sotto un'unica sezione
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Gestione accessi', '', 'menu', '', 'fa fa-key', '2.9', '2.9', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione accessi'), 'Gestione accessi', 'Gestione accessi'),
('2', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione accessi'), 'Login management', 'Login management');

UPDATE `zz_modules` LEFT JOIN `zz_modules` AS `parent_module` ON `parent_module`.`name` = 'Gestione accessi' SET `zz_modules`.`parent` = `parent_module`.`id` WHERE `zz_modules`.`name` IN ('Accesso con Token/OTP', 'Utenti e permessi', 'Accesso con OAuth');

INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`, `available_options`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name = 'Accesso con Token/OTP'), '1', 'QR Code', 'qrcode', '', '{\"width\": 54, \"height\": 20, \"format\": [64, 55], \"margins\": {\"top\": 5,\"bottom\": 0,\"left\": 0,\"right\": 0}}', 'fa fa-print', '', '', '0', '1', '1', NULL);

INSERT INTO `zz_prints_lang` (`id`, `id_lang`, `id_record`, `title`, `filename`) VALUES (NULL, '1', (SELECT id FROM zz_prints WHERE name = 'QR Code'), 'QR Code', 'QR Code');

INSERT INTO `zz_prints_lang` (`id`, `id_lang`, `id_record`, `title`, `filename`) VALUES (NULL, '2', (SELECT id FROM zz_prints WHERE name = 'QR Code'), 'QR Code', 'QR Code');

INSERT INTO zz_files_categories (name) VALUES ('Allegati caricati tramite accesso condiviso');

-- Aggiunta campo per immagine nelle check
ALTER TABLE `zz_checks`
  ADD `id_immagine` INT NULL DEFAULT NULL,
  ADD CONSTRAINT `zz_checks_ibfk_6` FOREIGN KEY (`id_immagine`) REFERENCES `zz_files`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- Rimozione record orfani in co_provvigioni
DELETE FROM `co_provvigioni` WHERE `idagente` NOT IN (SELECT `id` FROM `an_anagrafiche`) OR `idarticolo` NOT IN (SELECT `id` FROM `mg_articoli`);

-- Aggiunta foreign key su co_provvigioni
ALTER TABLE `co_provvigioni` ADD CONSTRAINT `co_provvigioni_ibfk_1` FOREIGN KEY (`idagente`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `co_provvigioni` ADD CONSTRAINT `co_provvigioni_ibfk_2` FOREIGN KEY (`idarticolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Modulo per log esecuzione task
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES ('Log eventi', 'log_task', 'SELECT |select|FROM(SELECT zz_tasks_logs.id, name, zz_tasks_logs.level, zz_tasks_logs.message, IF( LEVEL = \'info\', \'#dff0d8\', IF(LEVEL = \'error\', \'#f2dede\', \'#fcf8e3\') ) AS \'_bg_\', IF( CHAR_LENGTH(CONTEXT) > 200, CONCAT( SUBSTRING(CONTEXT, 1, 200), \'<a title=\"\', REPLACE(CONTEXT, \'\">\', \'[...]\'), \'</a>\' ), CONTEXT ) AS \'Contesto\', CONTEXT AS \'contesto_esteso\', zz_tasks_logs.created_at AS \'Data inizio\', zz_tasks_logs.updated_at AS \'Data fine\', CONCAT( TIMESTAMPDIFF( SECOND, zz_tasks_logs.created_at, zz_tasks_logs.updated_at ), \' secondi\' ) AS \'Eseguito in\'FROM `zz_tasks_logs` INNER JOIN `zz_tasks` ON `zz_tasks`.`id`=`zz_tasks_logs`.`id_task` WHERE 1=1 HAVING 2=2 UNION ALL SELECT zz_api_log.id, NAME, zz_api_log.level, zz_api_log.message, IF( LEVEL = \'info\', \'#dff0d8\', IF(LEVEL = \'error\', \'#f2dede\', \'#fcf8e3\') ) AS \'_bg_\', IF( CHAR_LENGTH(CONTEXT) > 200, CONCAT( SUBSTRING(CONTEXT, 1, 200), \'<a title=\"\', REPLACE(CONTEXT, \'\">\',\'[...]\'), \'</a>\' ), CONTEXT ) AS \'Contesto\', CONTEXT AS \'contesto_esteso\', zz_api_log.created_at AS \'Data inizio\', zz_api_log.updated_at AS \'Data fine\', CONCAT( TIMESTAMPDIFF( SECOND, zz_api_log.created_at, zz_api_log.updated_at ), \' secondi\' ) AS \'Eseguito in\'FROM `zz_api_log`WHERE 1=1 HAVING 2=2 ) AS dati ORDER BY `Data inizio` DESC', '', 'fa fa-calendar', '2.5.7.1', '2.5.7.1', '5', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Gestione task '), '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Log eventi'),
('2', (SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Events log');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`, `html_format`) VALUES
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'id', 'id', '1', '0', '0', '0', NULL, NULL, '0', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Nome task', 'name', '2', '1', '0', '0', NULL, 'name', '1', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Livello', 'level', '3', '1', '0', '0', NULL, 'level', '1', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Messaggio', 'message', '4', '1', '0', '0', NULL, 'message', '1', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Contesto', '`Contesto`', '5', '1', '0', '0', NULL, 'contesto_esteso', '1', '0', '0', '1'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'contesto_esteso', 'contesto_esteso', '5', '1', '0', '0', NULL, 'contesto_esteso', '0', '0', '0', '1'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Data inizio', '`Data inizio`', '6', '1', '0', '1', NULL, '`Data inizio`', '1', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Data fine', '`Data fine`', '6', '1', '0', '1', NULL, '`Data fine`', '1', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), '_bg_', '_bg_', '0', '1', '0', '0', NULL, NULL, '0', '0', '0', '0'),
((SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Eseguito in', '`Eseguito in`', '7', '1', '0', '1', NULL, '`Eseguito in`', '0', '0', '0', '0');

INSERT INTO `zz_views_lang` (`id`, `id_lang`, `id_record`, `title`) VALUES
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'id'), 'id'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'id'), 'id'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Nome task'), 'Nome task'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Nome task'), 'Task name'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Livello'), 'Livello'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Livello'), 'Level'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Messaggio'), 'Messaggio'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Messaggio'), 'Message'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Contesto'), 'Contesto'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Contesto'), 'Context'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Data inizio'), 'Data inizio'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Data inizio'), 'Start date'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Data fine'), 'Data fine'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Data fine'), 'End date'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'contesto_esteso'), 'contesto_esteso'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'contesto_esteso'), 'contesto_esteso'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Eseguito in'), 'Eseguito in'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Eseguito in'), 'Executed in'),
(NULL, '1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = '_bg_'), '_bg_'),
(NULL, '2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = '_bg_'), '_bg_');

INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `for_fe`, `is_sezionale`, `is_fiscale`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Tutti', '1=1', 'WHR', '####', '', '', '1', '0', '0', '0', '0', '0', '1'),
(NULL, (SELECT id FROM zz_modules WHERE `name` = 'Log eventi'), 'Errori', '1=1 AND Livello=error', 'WHR', '####', '', '', '0', '0', '0', '0', '0', '0', '0');

INSERT INTO `zz_segments_lang` (`id`, `id_lang`, `id_record`, `title`) VALUES
(NULL, '1', (SELECT `id` FROM `zz_segments` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Tutti'), 'Tutti'),
(NULL, '2', (SELECT `id` FROM `zz_segments` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Tutti'), 'All'),
(NULL, '1', (SELECT `id` FROM `zz_segments` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Errori'), 'Errori'),
(NULL, '2', (SELECT `id` FROM `zz_segments` WHERE `id_module` = (SELECT id FROM zz_modules WHERE `name` = 'Log eventi') AND `name` = 'Errori'), 'Errors');

-- Gestione log API
-- Creazione tabella di appoggio
CREATE TABLE `zz_api_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `message` VARCHAR(255) NULL DEFAULT NULL,
  `context` TEXT NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Gestione ammortamenti
ALTER TABLE `co_righe_documenti` ADD `is_cespite` BOOLEAN NOT NULL;

INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Ammortamenti / Cespiti', 'ammortamenti', 'SELECT |select| FROM `co_righe_documenti` LEFT JOIN `co_righe_ammortamenti` ON `co_righe_ammortamenti`.`id_riga` = `co_righe_documenti`.`id` INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE 1=1 AND `is_cespite` = 1 HAVING 2=2', '', 'fa fa-circle-o', '2.9', '2.9', '8', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Contabilità'), '1', '1', '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'Ammortamenti / Cespiti', 'Ammortamenti / Cespiti'),
('2', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'Ammortamenti / Cespiti', 'Ammortamenti / Cespiti');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'Descrizione', '`co_righe_documenti`.`descrizione`', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'Importo', '`co_righe_documenti`.`subtotale`', '3', '1', '0', '1', '0', NULL, NULL, '1', '1', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'Fattura', 'CONCAT("Fattura ", `co_documenti`.`numero_esterno`, " del ", YEAR(`co_documenti`.`data`))', '4', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'Anni', 'CONCAT(`co_righe_ammortamenti`.`anno`, " ")', '5', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti'), 'id', '`co_righe_documenti`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Descrizione'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Description'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Importo'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Importo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Amount'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Fattura'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fattura' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Invoice'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Anni' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Anni'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Anni' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'Years'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'id'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ammortamenti / Cespiti')), 'id');

CREATE TABLE `co_righe_ammortamenti` (`id` INT NOT NULL AUTO_INCREMENT , `id_riga` INT NOT NULL , `percentuale` INT NOT NULL , `anno` INT NOT NULL , `id_conto` INT NOT NULL , `id_mastrino` INT NOT NULL , PRIMARY KEY (`id`));

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Conto predefinito per i cespiti', (SELECT id FROM `co_pianodeiconti2` WHERE `descrizione` = 'Immobilizzazioni'), 'query=SELECT id, descrizione FROM co_pianodeiconti2 WHERE idpianodeiconti1=(SELECT id FROM co_pianodeiconti1 WHERE descrizione=\'Patrimoniale\')', '1', 'Piano dei conti', NULL, '0');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES ('1', (SELECT id FROM `zz_settings` WHERE `nome` = 'Conto predefinito per i cespiti'), 'Conto predefinito per i cespiti', '');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES ('2', (SELECT id FROM `zz_settings` WHERE `nome` = 'Conto predefinito per i cespiti'), 'Default account for assets', '');

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Conto predefinito per gli ammortamenti', (SELECT id FROM `co_pianodeiconti2` WHERE `descrizione` = 'Fondi ammortamento'), 'query=SELECT id, descrizione FROM co_pianodeiconti2 WHERE idpianodeiconti1=(SELECT id FROM co_pianodeiconti1 WHERE descrizione=\'Patrimoniale\')', '1', 'Piano dei conti', NULL, '0');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES ('1', (SELECT id FROM `zz_settings` WHERE `nome` = 'Conto predefinito per gli ammortamenti'), 'Conto predefinito per gli ammortamenti', '');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES ('2', (SELECT id FROM `zz_settings` WHERE `nome` = 'Conto predefinito per gli ammortamenti'), 'Default account for depreciation', '');

-- Gestione salvataggio allegati email
CREATE TABLE `em_email_attachment` (`id` INT NOT NULL AUTO_INCREMENT , `id_email` INT NOT NULL , `id_file` INT NOT NULL , `name` VARCHAR(255) NULL , `type` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`));

-- Tasto per disattivazione dei task
ALTER TABLE `zz_tasks` ADD `enabled` TINYINT NOT NULL DEFAULT '0';
UPDATE `zz_tasks` SET `enabled` = '1';

-- Aggiunta footer alle email
UPDATE `em_templates_lang`
SET `body` = CONCAT(`body`, '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; font-size: 11px; color: #666;">
    <p style="margin: 0 0 15px 0;">
        <span style="color: #888; font-size: 10px;">Email generata da </span>
        <a href="https://openstamanager.com" style="color: #007bff; text-decoration: none; font-size: 10px;">OpenSTAManager</a>
    </p>

    <p style="margin: 0 0 10px 0; font-size: 10px; line-height: 1.4; text-align: justify;">
        Le informazioni contenute nella presente comunicazione e relativi allegati possono essere riservate e sono comunque destinate esclusivamente alle persone o all\'ente sopra indicati. Se aveste ricevuto questa email per errore, ci scusiamo per l\'accaduto, Vi invitiamo cortesemente a darcene notizia ed a distruggerla. Vi ricordiamo che la diffusione, l\'utilizzo e/o la conservazione dei dati ricevuti per errore costituiscono violazioni alle disposizioni del regolamento UE 2016/679 (GDPR) denominato "Codice in materia di protezione dei dati personali".
    </p>

    <p style="margin: 0; font-size: 10px; color: #4CAF50;">
        <strong>Rispetta l\'ambiente: non stampare questa email a meno che non sia veramente necessario.</strong>
    </p>
</div>')
WHERE `id_lang` = 1;

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `format` = '0' WHERE `zz_views`.`name` IN ('Sede destinazione', 'Vettore', 'Tipo spedizione') AND `zz_modules`.`name` = 'Ddt in entrata';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `format` = '0' WHERE `zz_views`.`name` IN ('Vettore', 'Tipo spedizione') AND `zz_modules`.`name` = 'Ddt in uscita';

UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `search` = 1 WHERE `zz_modules`.`name` = "Marche";