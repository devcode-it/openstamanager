UPDATE `zz_prints` SET `filename` = 'Preventivo num. {numero} del {data}' WHERE `name` = 'Preventivo (senza totali)';
UPDATE `zz_prints` SET `filename` = 'Fattura num. {numero} del {data}' WHERE `name` = 'Fattura di vendita (senza intestazione)';
UPDATE `zz_prints` SET `filename` = 'Calendario' WHERE `name` = 'Stampa calendario';

DELETE FROM `zz_plugins` WHERE `name` = 'Pianificazione ordini di servizio';

-- Aggiunta campo JSON per le informazioni aggiuntive FE
ALTER TABLE `co_righe_documenti` ADD `dati_aggiuntivi_fe` TEXT;
UPDATE `co_righe_documenti` SET `dati_aggiuntivi_fe` = CONCAT('{"tipo_cessione_prestazione":"', IFNULL(tipo_cessione_prestazione, ""), '","riferimento_amministrazione":"', IFNULL(riferimento_amministrazione, ""), '","data_inizio_periodo":"', IFNULL(data_inizio_periodo, ""), '","data_fine_periodo":"', IFNULL(data_fine_periodo, ""), '"}');
ALTER TABLE `co_righe_documenti` DROP `tipo_cessione_prestazione`, DROP `riferimento_amministrazione`, DROP `data_inizio_periodo`, DROP `data_fine_periodo`;

ALTER TABLE `co_documenti` ADD `dati_aggiuntivi_fe` TEXT;

-- Aggiunta stampe consuntivo costi per Preventivi e Contratti
INSERT INTO `zz_prints` (`id_module`, `name`, `title`, `filename`, `directory`, `options`, `icon`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Consuntivo contratto interno', 'Consuntivo contratto interno', 'Consuntivo interno contratto num. {numero} del {data}', 'contratti_cons', '{"dir":"uscita"}', 'fa fa-print', 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Consuntivo preventivo interno', 'Consuntivo preventivo interno', 'Consuntivo interno preventivo num. {numero} del {data}', 'preventivi_cons', '{"dir":"uscita"}', 'fa fa-print', 1, 1);

-- Reset password per gli utenti
ALTER TABLE `zz_users` ADD `reset_token` VARCHAR(255);

INSERT INTO `zz_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 1, 'Reset password', 'fa fa-envelope', 'Richiesta di reset password', '', '', '', '<p>Gentile {username},</p>\r\n<p>a seguito della richiesta di reimpostazione della password del Suo account è pregato di inserire la nuova password che desidera utilizzare al seguente link:</p>\r\n<p class="text-center"><a href="{reset_link}">{reset_link}</a></p>\r\n<p>&nbsp;</p><p>Se non sei il responsabile della richiesta in questione, contatta l''amministratore il prima possibile per richiedere un cambio di username.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0');

-- Relazione tra le righe dei documenti
ALTER TABLE `co_righe_documenti` ADD `original_id` int(11), ADD `original_type` varchar(255);
ALTER TABLE `or_righe_ordini` ADD `original_id` int(11), ADD `original_type` varchar(255);
ALTER TABLE `dt_righe_ddt` ADD `original_id` int(11), ADD `original_type` varchar(255);

ALTER TABLE `co_righe_contratti` ADD `abilita_serial` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `co_righe_preventivi` ADD `abilita_serial` tinyint(1) NOT NULL DEFAULT '0';

--
-- Fatture
--
-- Collegamento Articoli
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\DDT\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

-- Collegamento Sconti
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\DDT\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

-- Collegamento Descrizioni
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\DDT\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

-- Collegamento Righe
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\DDT\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL;

--
-- DDT
--
-- Collegamento Articoli
UPDATE `dt_righe_ddt` INNER JOIN `or_righe_ordini` ON `dt_righe_ddt`.`idordine` = `or_righe_ordini`.`idordine` AND `dt_righe_ddt`.`descrizione` = `or_righe_ordini`.`descrizione` AND `dt_righe_ddt`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `dt_righe_ddt`.`original_id` = `or_righe_ordini`.`id`, `dt_righe_ddt`.`original_type` = 'Modules\\Ordini\\Components\\Articolo' WHERE `dt_righe_ddt`.`idarticolo` != 0;

-- Collegamento Sconti
UPDATE `dt_righe_ddt` INNER JOIN `or_righe_ordini` ON `dt_righe_ddt`.`idordine` = `or_righe_ordini`.`idordine` AND `dt_righe_ddt`.`descrizione` = `or_righe_ordini`.`descrizione` AND `dt_righe_ddt`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `dt_righe_ddt`.`original_id` = `or_righe_ordini`.`id`, `dt_righe_ddt`.`original_type` = 'Modules\\Ordini\\Components\\Sconto' WHERE `dt_righe_ddt`.`is_sconto` != 0;

-- Collegamento Descrizioni
UPDATE `dt_righe_ddt` INNER JOIN `or_righe_ordini` ON `dt_righe_ddt`.`idordine` = `or_righe_ordini`.`idordine` AND `dt_righe_ddt`.`descrizione` = `or_righe_ordini`.`descrizione` AND `dt_righe_ddt`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `dt_righe_ddt`.`original_id` = `or_righe_ordini`.`id`, `dt_righe_ddt`.`original_type` = 'Modules\\Ordini\\Components\\Descrizione' WHERE `dt_righe_ddt`.`is_descrizione` != 0;

-- Collegamento Righe
UPDATE `dt_righe_ddt` INNER JOIN `or_righe_ordini` ON `dt_righe_ddt`.`idordine` = `or_righe_ordini`.`idordine` AND `dt_righe_ddt`.`descrizione` = `or_righe_ordini`.`descrizione` AND `dt_righe_ddt`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `dt_righe_ddt`.`original_id` = `or_righe_ordini`.`id`, `dt_righe_ddt`.`original_type` = 'Modules\\Ordini\\Components\\Riga' WHERE `dt_righe_ddt`.`original_id` IS NULL;

--
-- Ordini
--
-- Collegamento Articoli
UPDATE `or_righe_ordini` INNER JOIN `co_righe_preventivi` ON `or_righe_ordini`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `or_righe_ordini`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `or_righe_ordini`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `or_righe_ordini`.`original_id` = `co_righe_preventivi`.`id`, `or_righe_ordini`.`original_type` = 'Modules\\Preventivi\\Components\\Articolo' WHERE `or_righe_ordini`.`idarticolo` != 0;

-- Collegamento Sconti
UPDATE `or_righe_ordini` INNER JOIN `co_righe_preventivi` ON `or_righe_ordini`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `or_righe_ordini`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `or_righe_ordini`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `or_righe_ordini`.`original_id` = `co_righe_preventivi`.`id`, `or_righe_ordini`.`original_type` = 'Modules\\Preventivi\\Components\\Sconto' WHERE `or_righe_ordini`.`is_sconto` != 0;

-- Collegamento Descrizioni
UPDATE `or_righe_ordini` INNER JOIN `co_righe_preventivi` ON `or_righe_ordini`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `or_righe_ordini`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `or_righe_ordini`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `or_righe_ordini`.`original_id` = `co_righe_preventivi`.`id`, `or_righe_ordini`.`original_type` = 'Modules\\Preventivi\\Components\\Descrizione' WHERE `or_righe_ordini`.`is_descrizione` != 0;

-- Collegamento Righe
UPDATE `or_righe_ordini` INNER JOIN `co_righe_preventivi` ON `or_righe_ordini`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `or_righe_ordini`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `or_righe_ordini`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `or_righe_ordini`.`original_id` = `co_righe_preventivi`.`id`, `or_righe_ordini`.`original_type` = 'Modules\\Preventivi\\Components\\Riga' WHERE `or_righe_ordini`.`original_id` IS NULL;

-- Aggiunta foto utente
ALTER TABLE `zz_users` ADD `image_file_id` int(11);
UPDATE `zz_modules` SET `enabled` = 1 WHERE `name` = 'Utenti e permessi';

-- Aggiornamento sistema API
CREATE TABLE IF NOT EXISTS `zz_api_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(15) NOT NULL,
  `type` ENUM('create', 'retrieve', 'update', 'delete'),
  `resource` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DELETE FROM `zz_settings` WHERE `nome` = 'Tabelle escluse per la sincronizzazione API automatica';

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'create', 'allegato', 'Api\\Common\\Allegato', '1'),
(NULL, 'v1', 'retrieve', 'clienti', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'retrieve', 'anagrafiche', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'create', 'anagrafica', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'update', 'anagrafica', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'delete', 'anagrafica', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'retrieve', 'sedi', 'Modules\\Anagrafiche\\API\\v1\\Sedi', '1'),
(NULL, 'v1', 'create', 'movimento_articolo', 'Modules\\Articoli\\API\\v1\\Movimenti', '1'),
(NULL, 'v1', 'retrieve', 'articoli', 'Modules\\Articoli\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'create', 'login', 'Modules\\Utenti\\API\\v1\\Login', '1'),
(NULL, 'v1', 'create', 'logout', 'Modules\\Utenti\\API\\v1\\Logout', '1'),
(NULL, 'v1', 'retrieve', 'folder_size', 'Modules\\StatoServizi\\API\\v1\\FolderSize', '1'),
(NULL, 'v1', 'retrieve', 'tipi_intervento', 'Modules\\TipiIntervento\\API\\v1\\TipiInterventi', '1'),
(NULL, 'v1', 'retrieve', 'stati_intervento', 'Modules\\StatiIntervento\\API\\v1\\StatiInterventi', '1'),
(NULL, 'v1', 'retrieve', 'stati_preventivo', 'Modules\\StatiPreventivo\\API\\v1\\StatiPreventivi', '1'),
(NULL, 'v1', 'retrieve', 'stati_contratto', 'Modules\\StatiContratto\\API\\v1\\StatiContratti', '1'),
(NULL, 'v1', 'retrieve', 'tipi_intervento', 'Modules\\Interventi\\API\\v1\\Interventi', '1'),
(NULL, 'v1', 'retrieve', 'interventi', 'Modules\\Interventi\\API\\v1\\Interventi', '1'),
(NULL, 'v1', 'create', 'intervento', 'Modules\\Interventi\\API\\v1\\Interventi', '1'),
(NULL, 'v1', 'update', 'intervento', 'Modules\\Interventi\\API\\v1\\Interventi', '1'),
(NULL, 'v1', 'update', 'firma_intervento', 'Modules\\Interventi\\API\\v1\\Firma', '1'),
(NULL, 'v1', 'retrieve', 'sync', 'Modules\\Interventi\\API\\v1\\Sync', '1'),
(NULL, 'v1', 'update', 'sync', 'Modules\\Interventi\\API\\v1\\Sync', '1'),
(NULL, 'v1', 'retrieve', 'sessioni_intervento', 'Modules\\Interventi\\API\\v1\\Sessioni', '1'),
(NULL, 'v1', 'create', 'sessione', 'Modules\\Interventi\\API\\v1\\Sessioni', '1'),
(NULL, 'v1', 'delete', 'sessioni_intervento', 'Modules\\Interventi\\API\\v1\\Sessioni', '1'),
(NULL, 'v1', 'retrieve', 'articoli_intervento', 'Modules\\Interventi\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'create', 'articolo_intervento', 'Modules\\Interventi\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'retrieve', 'stampa', 'Api\\Common\\Stampa', '1');

-- Supporto alla personalizzazione dell'API remota OSMCloud
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'OSMCloud Services API Version', 'v2', 'string', 0, 'Fatturazione Elettronica', 11);
DELETE FROM `zz_settings` WHERE `nome` = 'apilayer API key for Email';

-- Fatture pro-forma di acquisto
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `is_fiscale`) VALUES (NULL, '15', 'Fatture pro-forma', '1=1', 'WHR', 'PRO-###', '0');

-- Aggiunto codice cig e codice cup per ddt
ALTER TABLE `dt_ddt` ADD `codice_cig` VARCHAR(15), ADD `codice_cup` VARCHAR(15) AFTER `codice_cig`, ADD `id_documento_fe` VARCHAR(20) AFTER `codice_cup`,ADD `num_item` VARCHAR(15) AFTER `id_documento_fe`;

-- Fix quantità per descrizioni
UPDATE `co_righe_documenti` SET `qta` = 1 WHERE `is_descrizione` = 1;
UPDATE `dt_righe_ddt` SET `qta` = 1 WHERE `is_descrizione` = 1;
UPDATE `co_righe_preventivi` SET `qta` = 1 WHERE `is_descrizione` = 1;
UPDATE `co_righe_contratti` SET `qta` = 1 WHERE `is_descrizione` = 1;
UPDATE `or_righe_ordini` SET `qta` = 1 WHERE `is_descrizione` = 1;
UPDATE `mg_articoli_interventi` SET `qta` = 1 WHERE `is_descrizione` = 1;

-- Aggiunta generale di prezzo_unitario_acquisto
ALTER TABLE `dt_righe_ddt` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;
ALTER TABLE `or_righe_ordini` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;
ALTER TABLE `co_righe_contratti` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;

-- Aggiunte impostazione Autocomple web form
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Autocompletamento form', 'off', 'list[on,off]', '1', 'Generali', '', NULL);

-- Data concordata per le scadenza
ALTER TABLE `co_scadenziario` ADD `data_concordata` DATE;

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'IF(pagato = da_pagare, ''#38CD4E'', IF(data_concordata IS NOT NULL AND data_concordata > NOW(), '' #CC9837'', IF(scadenza < NOW(), ''#CC4D37'', '''')))' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = '_bg_';
-- Sistema di note interne
CREATE TABLE IF NOT EXISTS `zz_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11),
  `id_plugin` int(11),
  `id_record` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `notification_date` DATE,
  `content` TEXT,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_plugin`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sistema di checklists
CREATE TABLE IF NOT EXISTS `zz_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11),
  `id_plugin` int(11),
  `id_record` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `checked_by` int(11) ,
  `checked_at` TIMESTAMP NULL,
  `content` TEXT,
  `id_parent` int(11),
  `order` int(11),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_plugin`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`checked_by`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_parent`) REFERENCES `zz_checks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zz_check_user` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `id_utente` int(11) NOT NULL,
   `id_check` int(11) NOT NULL,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`id_check`) REFERENCES `zz_checks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zz_checklists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255),
  `id_module` int(11),
  `id_plugin` int(11),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_plugin`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zz_checklist_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_checklist` int(11),
  `content` TEXT,
  `id_parent` int(11),
  `order` int(11),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_checklist`) REFERENCES `zz_checklists`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_parent`) REFERENCES `zz_checklist_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Gestione di note e checklists
ALTER TABLE `zz_modules` ADD `use_notes` BOOLEAN DEFAULT FALSE, ADD `use_checklists` BOOLEAN DEFAULT FALSE;
UPDATE `zz_modules` SET `use_notes` = 1 WHERE `name` IN ('Anagrafiche', 'Interventi', 'Preventivi', 'Contratti', 'Fatture di vendita', 'Fatture di acquisto', 'Scadenzario', 'Ordini cliente', 'Ordini fornitore', 'Articoli', 'Ddt di vendita', 'Ddt di acquisto', 'MyImpianti');
UPDATE `zz_modules` SET `use_checklists` = 1 WHERE `name` IN ('Interventi', 'MyImpianti');

-- Modulo per i template delle Checklist
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Checklists', 'Checklists', 'checklists', 'SELECT |select| FROM `zz_checklists` WHERE 1=1 HAVING 2=2', '', 'fa fa-check-square-o', '2.4.11', '2.*', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Checklists'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Checklists'), 'Nome', 'name', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Checklists'), 'Modulo', '(SELECT name FROM zz_modules WHERE id = zz_checklists.id_module)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Checklists'), 'Plugin', '(SELECT name FROM zz_plugins WHERE id = zz_checklists.id_plugin)', 5, 1, 0, 1, 1);

-- Miglioramento gestione header e footer per le stampe
UPDATE `zz_prints` SET `options` = REPLACE(`options`, "hide_header", "hide-header");
UPDATE `zz_prints` SET `options` = REPLACE(`options`, "hide_footer", "hide-footer");
UPDATE `zz_prints` SET `options` = '{"last-page-footer": true}' WHERE `zz_prints`.`name` = 'Fattura di vendita';
UPDATE `zz_prints` SET `options` = '{"hide-header": true, "hide-footer": true, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Fattura di vendita (senza intestazione)';
UPDATE `zz_prints` SET `options` = '{"pricing": true, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Ordine cliente';
UPDATE `zz_prints` SET `options` = '{"pricing": false, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Ordine cliente (senza costi)';
UPDATE `zz_prints` SET `options` = '{"pricing": true, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Preventivo';
UPDATE `zz_prints` SET `options` = '{"pricing": false, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Preventivo (senza costi)';
UPDATE `zz_prints` SET `options` = '{"pricing": true, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Contratto';
UPDATE `zz_prints` SET `options` = '{"pricing": false, "last-page-footer": true}' WHERE `zz_prints`.`name` = 'Contratto (senza costi)';

-- Widget per le notifiche delle note interne
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`) VALUES (NULL, 'Note interne', 'custom', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Dashboard'), 'controller_top', 'col-md-12', NULL, '#4ccc4c', 'fa fa-file-text-o ', '', './modules/dashboard/widgets/notifiche.php', 'popup', './modules/dashboard/widgets/notifiche.php', 'Notifiche interne', '1', '1');

-- Aggiunto collegamento degli allegati al creatore
ALTER TABLE `zz_files` ADD `created_by` INT(11) AFTER `id_record`, ADD FOREIGN KEY (`created_by`) REFERENCES `zz_users`(`id`) ON DELETE SET NULL;

-- Aggiunto riferimento allo Scadenzario nella Prima Nota
ALTER TABLE `co_movimenti` ADD `id_scadenza` INT(11) AFTER `iddocumento`, ADD FOREIGN KEY (`id_scadenza`) REFERENCES `co_scadenziario`(`id`) ON DELETE CASCADE, ADD `is_insoluto` BOOLEAN NOT NULL DEFAULT FALSE AFTER `id_scadenza`;

-- Aggiornamento indirizzo email SDI
UPDATE `zz_emails` SET `cc` = 'sdi52@pec.fatturapa.it' WHERE `name` = 'PEC';

-- Rimozione Pianificazione fatturazione
DELETE FROM `zz_plugins` WHERE `name` = 'Pianificazione fatturazione';

-- Aggiunta deleted_at su mg_articoli
ALTER TABLE `mg_articoli` ADD `deleted_at` timestamp NULL DEFAULT NULL;

-- Ampliamento hooks
ALTER TABLE `zz_hooks` ADD `processing_at` TIMESTAMP NULL DEFAULT NULL, ADD `processing_token` varchar(255);
INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES (NULL, 'Backup', 'Modules\\Backups\\BackupHook', '1 day', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Backup'));

-- Miglioramento gestione email (questo RENAME genera un errore rif. tabella se mysql <= 5.5.55)
RENAME TABLE `zz_emails` TO `em_templates`;
RENAME TABLE `zz_smtps` TO `em_accounts`;
RENAME TABLE `zz_email_print` TO `em_print_template`;

UPDATE zz_modules SET options = REPLACE(options, 'zz_emails', 'em_templates'), options2 = REPLACE(options2, 'zz_emails', 'em_templates');
UPDATE zz_modules SET options = REPLACE(options, 'zz_smtps', 'em_accounts'), options2 = REPLACE(options2, 'zz_smtps', 'em_accounts');
UPDATE zz_views SET query = REPLACE(query, 'zz_emails', 'em_templates');
UPDATE zz_views SET query = REPLACE(query, 'zz_smtps', 'em_accounts');

CREATE TABLE IF NOT EXISTS `em_newsletters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `id_template` int(11) NOT NULL,
  `state` varchar(25) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` TEXT NOT NULL,
  `notes` TEXT,
  `created_by` int(11) NOT NULL,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_template`) REFERENCES `em_templates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `em_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_account` int(11) NOT NULL,
  `id_template` int(11),
  `id_newsletter` int(11),
  `id_record` int(11),
  `subject` varchar(255),
  `content` TEXT,
  `options` TEXT,
  `sent_at` TIMESTAMP NULL DEFAULT NULL,
  `failed_at` TIMESTAMP NULL DEFAULT NULL,
  `processing_at` TIMESTAMP NULL DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_account`) REFERENCES `em_accounts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_template`) REFERENCES `em_templates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_newsletter`) REFERENCES `em_newsletters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `em_email_receiver` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `id_email` int(11) NOT NULL,
   `type` varchar(255) NOT NULL,
   `address` varchar(255) NOT NULL,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `em_email_upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_email` int(11) NOT NULL,
  `id_file` int(11) NOT NULL,
  `name` varchar(255),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_file`) REFERENCES `zz_files`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `em_email_print` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_email` int(11) NOT NULL,
  `id_print` int(11) NOT NULL,
  `name` varchar(255),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_print`) REFERENCES `zz_prints`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `em_newsletter_anagrafica` (
  `id_newsletter` int(11) NOT NULL,
  `id_anagrafica` int(11) NOT NULL,
  `id_email` int(11),
  FOREIGN KEY (`id_newsletter`) REFERENCES `em_newsletters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE,
  FOREIGN KEY (`id_email`) REFERENCES `em_emails`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Hook per la gestione della coda di invio
ALTER TABLE `zz_hooks` CHANGE `id_module` `id_module` INT(11) NULL;
INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES (NULL, 'Email', 'Modules\\Emails\\EmailHook', '1 minute', NULL);

-- Modulo Newsletter
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Newsletter', 'Newsletter', 'newsletter', 'SELECT |select| FROM `em_newsletters` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2', '', 'fa fa-newspaper-o ', '2.4.11', '2.*', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Gestione email'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'Nome', 'name', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'Template', '(SELECT name FROM em_templates WHERE id = em_newsletters.id_template)', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'Completato', 'IF(completed_at IS NULL, ''No'', ''Si'')', 4, 1, 0, 1, 1);

-- Modulo Stato email
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stato email', 'Coda di invio', 'stato_email', 'SELECT |select|
FROM `em_emails`
    LEFT JOIN `em_templates` ON `em_templates`.`id` = `em_emails`.`id_template`
    INNER JOIN `zz_users` ON `zz_users`.`id` = `em_emails`.`created_by`
WHERE 1=1 AND (`em_emails`.`created_at` BETWEEN ''|period_start|'' AND ''|period_end|'' OR `em_emails`.`sent_at` IS NULL)
HAVING 2=2
ORDER BY `em_emails`.`created_at` DESC', '', 'fa fa-spinner ', '2.4.11', '2.*', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Gestione email'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'id', 'em_emails.id', 1, 0, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Oggetto', 'em_emails.subject', 2, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Contenuto', 'em_emails.content', 3, 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Template', 'em_templates.name', 3, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Data invio', 'em_emails.sent_at', 4, 1, 0, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Ultimo tentativo', 'em_emails.failed_at', 5, 1, 0, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Utente', 'zz_users.username', 6, 1, 0, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), '_bg_', 'IF(em_emails.sent_at IS NULL, IF(em_emails.failed_at IS NULL, ''#CC9837'', ''#CC4D37''), ''#38CD4E'')', 6, 1, 0, 0, 0, 0);

ALTER TABLE `em_templates` ADD `id_account` INT(11) NOT NULL;
UPDATE `em_templates` SET `id_account` = `id_smtp`;
ALTER TABLE `em_templates` DROP FOREIGN KEY `em_templates_ibfk_2`, DROP `id_smtp`;
ALTER TABLE `em_templates` ADD FOREIGN KEY (`id_account`) REFERENCES `em_accounts`(`id`) ON DELETE CASCADE;

ALTER TABLE `em_print_template` ADD `id_template` INT(11) NOT NULL;
UPDATE `em_print_template` SET `id_template` = `id_email`;
ALTER TABLE `em_print_template` DROP FOREIGN KEY `em_print_template_ibfk_1`, DROP `id_email`;
ALTER TABLE `em_print_template` ADD FOREIGN KEY (`id_template`) REFERENCES `em_templates`(`id`) ON DELETE CASCADE;

ALTER TABLE `em_accounts` ADD `timeout` INT(11) NOT NULL DEFAULT 1000;
ALTER TABLE `an_anagrafiche` ADD `enable_newsletter` BOOLEAN DEFAULT TRUE;

-- Aggiunta coda di invio per le Fatture Elettroniche
ALTER TABLE `co_documenti` ADD `hook_send` BOOLEAN DEFAULT FALSE;
INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES (NULL, 'Fatture Elettroniche', 'Plugins\\ExportFE\\InvoiceHook', '1 minute', NULL);

INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES
('ERR', 'Trasmissione non riuscita', 'fa fa-close'),
('QUEUE', 'In coda di elaborazione', 'fa fa-spinner');


UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Ragione sociale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_statidocumento.icona' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_statidocumento.descrizione' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'icon_title_Stato';


INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Totale', 'righe.totale', 5, 1, 1, 1, 1);
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'Cliente';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_staticontratti.icona' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_staticontratti.descrizione' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '`co_contratti`.`id`' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '`co_contratti`.`nome`' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'impianti.descrizione' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'Impianti';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Totale', 'righe.totale', 5, 1, 1, 1, 1);
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'Cliente';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_statipreventivi.icona' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_statipreventivi.descrizione' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '`co_preventivi`.`id`' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '`co_preventivi`.`nome`' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Ragione sociale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Ragione sociale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'Ragione sociale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'Ragione sociale';
-- Correzioni per gli ordini
INSERT INTO `or_statiordine` (`id`, `descrizione`, `annullato`, `icona`, `completato`) VALUES
(NULL, 'In attesa di conferma', '0', 'fa fa-clock-o text-warning', '0'),
(NULL, 'Accettato', '0', 'fa fa-thumbs-up text-success', '0');

ALTER TABLE `or_ordini` ADD `data_cliente` DATE NULL DEFAULT NULL, ADD `numero_cliente` varchar(255) NULL DEFAULT NULL;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Numero ordine cliente', 'numero_cliente', 3, 1, 0, 1, 1);

-- Dichiarazioni d'Intento
CREATE TABLE IF NOT EXISTS `co_dichiarazioni_intento` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_anagrafica` int(11) NOT NULL,
    `data` DATE NOT NULL,
    `numero_protocollo` varchar(255) NOT NULL,
    `numero_progressivo` varchar(255) NOT NULL,
    `data_inizio` DATE NOT NULL,
    `data_fine` DATE NOT NULL,
    `data_protocollo` DATE NULL DEFAULT NULL,
    `data_emissione` DATE NULL DEFAULT NULL,
    `massimale` DECIMAL(12, 4) NOT NULL,
    `totale` DECIMAL(12, 4) NOT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE `co_documenti` ADD `id_dichiarazione_intento` int(11), ADD FOREIGN KEY (`id_dichiarazione_intento`) REFERENCES `co_dichiarazioni_intento`(`id`) ON DELETE SET NULL;

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Dichiarazioni d''Intento', 'Dichiarazioni d''Intento', (SELECT id FROM zz_modules WHERE name = 'Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE name='Anagrafiche'), 'tab', '', '1', '1', '0', '', '', NULL, '{ "main_query": [	{	"type": "table", "fields": "Protocollo, Progressivo, Massimale, Totale, Data inizio, Data fine", "query": "SELECT id, numero_protocollo AS Protocollo, numero_progressivo AS Progressivo, DATE_FORMAT(data_inizio,''%d/%m/%Y'') AS ''Data inizio'', DATE_FORMAT(data_inizio,''%d/%m/%Y'') AS ''Data fine'', ROUND(massimale, 2) AS Massimale, ROUND(totale, 2) AS Totale FROM co_dichiarazioni_intento WHERE 1=1 AND deleted_at IS NULL AND id_anagrafica = |id_parent| HAVING 2=2 ORDER BY co_dichiarazioni_intento.id DESC"}	]}', 'dichiarazioni_intento', '');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Iva per lettere d''intento', '', 'query=SELECT id, descrizione FROM `co_iva` WHERE codice_natura_fe = ''N3'' AND deleted_at IS NULL ORDER BY descrizione ASC', 1, 'Fatturazione', 11);

-- Liste per le newsletter
CREATE TABLE IF NOT EXISTS `em_lists` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` TEXT,
    `query` TEXT,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `em_list_anagrafica` (
  `id_list` int(11) NOT NULL,
  `id_anagrafica` int(11) NOT NULL,
  FOREIGN KEY (`id_list`) REFERENCES `em_newsletters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Liste newsletter', 'Liste', 'liste_newsletter', 'SELECT |select| FROM `em_lists` WHERE deleted_at IS NULL AND 1=1 HAVING 2=2', '', 'fa fa-list', '2.4.11', '2.*', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Gestione email'), '1', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Liste newsletter'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Liste newsletter'), 'Nome', 'name', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Liste newsletter'), 'Descrizione', 'description', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Liste newsletter'), 'Dinamica', 'IF(query IS NULL, ''No'', ''Si'')', 4, 1, 0, 1, 1);

UPDATE `zz_prints` SET `is_record` = '0' WHERE `zz_prints`.`name` = 'Inventario magazzino';

-- Gestione permessi per le categorie documentali
ALTER TABLE `zz_documenti` RENAME TO `do_documenti`;
ALTER TABLE `zz_documenti_categorie` RENAME TO `do_categorie`;

CREATE TABLE IF NOT EXISTS `do_permessi` (
    `id_categoria` int(11) NOT NULL,
    `id_gruppo` int(11) NOT NULL,
    FOREIGN KEY (`id_categoria`) REFERENCES `do_categorie`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_gruppo`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie documenti'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie documenti'), 'Descrizione', 'descrizione', 2, 0, 0, 1, 1);


INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale'), 'id', '`do_documenti`.`id`', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale'), 'Categoria', '`do_categorie`.`descrizione`', 2, 0, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale'), 'Nome', '`do_documenti`.`nome`', 3, 0, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale'), 'Data', '`do_documenti`.`data`', 4, 0, 1, 1, 1);

-- Aggiornamento limite sui decimali
ALTER TABLE `co_contratti` CHANGE `budget` `budget` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti` CHANGE `costo_diritto_chiamata` `costo_diritto_chiamata` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti` CHANGE `ore_lavoro` `ore_lavoro` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti` CHANGE `costo_orario` `costo_orario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti` CHANGE `costo_km` `costo_km` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_ore` `costo_ore` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_km` `costo_km` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_dirittochiamata` `costo_dirittochiamata` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_ore_tecnico` `costo_ore_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_km_tecnico` `costo_km_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_dirittochiamata_tecnico` `costo_dirittochiamata_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `co_dichiarazioni_intento` CHANGE `massimale` `massimale` decimal(12, 6) NOT NULL;
ALTER TABLE `co_dichiarazioni_intento` CHANGE `totale` `totale` decimal(12, 6) NOT NULL;
ALTER TABLE `co_documenti` CHANGE `rivalsainps` `rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `co_documenti` CHANGE `iva_rivalsainps` `iva_rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `co_documenti` CHANGE `ritenutaacconto` `ritenutaacconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_documenti` CHANGE `bollo` `bollo` decimal(12, 6);
ALTER TABLE `co_documenti` CHANGE `ritenuta_contributi` `ritenuta_contributi` decimal(12, 6) NOT NULL;
ALTER TABLE `co_movimenti` CHANGE `totale` `totale` decimal(12, 6);
ALTER TABLE `co_preventivi` CHANGE `budget` `budget` decimal(12, 6) NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `costo_diritto_chiamata` `costo_diritto_chiamata` decimal(12, 6) NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `ore_lavoro` `ore_lavoro` decimal(12, 6) NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `costo_orario` `costo_orario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `costo_km` `costo_km` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_articoli` CHANGE `prezzo_acquisto` `prezzo_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_articoli` CHANGE `prezzo_vendita` `prezzo_vendita` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_articoli` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_articoli` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_articoli` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_righe` CHANGE `prezzo_vendita` `prezzo_vendita` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_righe` CHANGE `prezzo_acquisto` `prezzo_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_righe` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_righe` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_promemoria_righe` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `prezzo_unitario_acquisto` `prezzo_unitario_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `subtotale` `subtotale` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `iva_indetraibile` `iva_indetraibile` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `qta_evasa` `qta_evasa` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `iva_indetraibile` `iva_indetraibile` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `prezzo_unitario_acquisto` `prezzo_unitario_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `subtotale` `subtotale` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `ritenutaacconto` `ritenutaacconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `rivalsainps` `rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `qta_evasa` `qta_evasa` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `iva_indetraibile` `iva_indetraibile` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `prezzo_unitario_acquisto` `prezzo_unitario_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `subtotale` `subtotale` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `qta_evasa` `qta_evasa` decimal(12, 6) NOT NULL;
ALTER TABLE `co_scadenziario` CHANGE `da_pagare` `da_pagare` decimal(12, 6);
ALTER TABLE `co_scadenziario` CHANGE `pagato` `pagato` decimal(12, 6);
ALTER TABLE `dt_ddt` CHANGE `rivalsainps` `rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `iva_rivalsainps` `iva_rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `ritenutaacconto` `ritenutaacconto` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `bollo` `bollo` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `iva_indetraibile` `iva_indetraibile` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `prezzo_unitario_acquisto` `prezzo_unitario_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `subtotale` `subtotale` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `qta_evasa` `qta_evasa` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `ore` `ore` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `km` `km` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_ore_unitario` `prezzo_ore_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_km_unitario` `prezzo_km_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_ore_consuntivo` `prezzo_ore_consuntivo` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_km_consuntivo` `prezzo_km_consuntivo` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_dirittochiamata` `prezzo_dirittochiamata` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_ore_unitario_tecnico` `prezzo_ore_unitario_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_km_unitario_tecnico` `prezzo_km_unitario_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_ore_consuntivo_tecnico` `prezzo_ore_consuntivo_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_km_consuntivo_tecnico` `prezzo_km_consuntivo_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `prezzo_dirittochiamata_tecnico` `prezzo_dirittochiamata_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `scontokm` `scontokm` decimal(12, 6) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `scontokm_unitario` `scontokm_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `in_righe_interventi` CHANGE `prezzo_vendita` `prezzo_vendita` decimal(12, 6) NOT NULL;
ALTER TABLE `in_righe_interventi` CHANGE `prezzo_acquisto` `prezzo_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `in_righe_interventi` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `in_righe_interventi` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `in_righe_interventi` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_ore` `costo_ore` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_km` `costo_km` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_dirittochiamata` `costo_dirittochiamata` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_ore_tecnico` `costo_ore_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_km_tecnico` `costo_km_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_dirittochiamata_tecnico` `costo_dirittochiamata_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_orario` `costo_orario` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_km` `costo_km` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_diritto_chiamata` `costo_diritto_chiamata` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_orario_tecnico` `costo_orario_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_km_tecnico` `costo_km_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_diritto_chiamata_tecnico` `costo_diritto_chiamata_tecnico` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `threshold_qta` `threshold_qta` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `prezzo_acquisto` `prezzo_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `prezzo_vendita` `prezzo_vendita` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `peso_lordo` `peso_lordo` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `volume` `volume` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `prezzo_acquisto` `prezzo_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `prezzo_vendita` `prezzo_vendita` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `mg_movimenti` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `or_ordini` CHANGE `rivalsainps` `rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `or_ordini` CHANGE `iva_rivalsainps` `iva_rivalsainps` decimal(12, 6) NOT NULL;
ALTER TABLE `or_ordini` CHANGE `ritenutaacconto` `ritenutaacconto` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `iva` `iva` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `iva_indetraibile` `iva_indetraibile` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `prezzo_unitario_acquisto` `prezzo_unitario_acquisto` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `subtotale` `subtotale` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `sconto` `sconto` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `sconto_unitario` `sconto_unitario` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `qta` `qta` decimal(12, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `qta_evasa` `qta_evasa` decimal(12, 6) NOT NULL;

UPDATE `zz_settings` SET `tipo` = 'list[2,3,4,5]' WHERE `nome` = 'Cifre decimali per importi';
UPDATE `zz_settings` SET `tipo` = 'list[0,1,2,3,4,5]' WHERE `nome` = 'Cifre decimali per quantità';

-- Aggiunta percentuale combinata in listini
ALTER TABLE `mg_listini` ADD `prc_combinato` VARCHAR(255);

-- Aggiunto supporto ai tentativi di invio email
ALTER TABLE `em_emails` ADD `attempt` INT(11) NOT NULL DEFAULT 0;

-- Aggiunta campo barcode per gli articoli
ALTER TABLE `mg_articoli` ADD `barcode` VARCHAR(255);

-- Aggiunta campo fornitore per gli articoli
ALTER TABLE `mg_articoli` ADD `id_fornitore` INT(11) NULL DEFAULT NULL;

-- Aggiunta vista prezzo vendita e prezzo acquisto per gli articoli
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `summable`, `visible`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Fornitore', '(SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica` = `id_fornitore`)', 6, 1, 0, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Prezzo di acquisto', 'prezzo_acquisto', 6, 1, 0, 1, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Prezzo di vendita', 'prezzo_vendita', 6, 1, 0, 1, 1, 0, 1);

-- Aggiunta  flag can_delete per stati preventivi e contratti
ALTER TABLE `co_statipreventivi` ADD `can_delete` BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE `co_staticontratti` ADD `can_delete` BOOLEAN NOT NULL DEFAULT TRUE;

-- Imposto gli stati Bozza, In lavorazione e Concluso non eliminabili per stati preventivi e contratti
UPDATE `co_statipreventivi` SET `can_delete` = '0' WHERE `co_statipreventivi`.`descrizione` = 'Bozza';
UPDATE `co_statipreventivi` SET `can_delete` = '0' WHERE `co_statipreventivi`.`descrizione` = 'In lavorazione';

UPDATE `co_staticontratti` SET `can_delete` = '0' WHERE `co_staticontratti`.`descrizione` = 'Bozza';
UPDATE `co_staticontratti` SET `can_delete` = '0' WHERE `co_staticontratti`.`descrizione` = 'In lavorazione';
UPDATE `co_staticontratti` SET `can_delete` = '0' WHERE `co_staticontratti`.`descrizione` = 'Concluso';
UPDATE `co_staticontratti` SET `can_delete` = '0' WHERE `co_staticontratti`.`descrizione` = 'Fatturato';
UPDATE `co_staticontratti` SET `can_delete` = '0' WHERE `co_staticontratti`.`descrizione` = 'Parzialmente fatturato';

ALTER TABLE `an_sedi` ADD `note` TEXT NULL DEFAULT NULL AFTER `idzona`;

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'codice' WHERE `zz_modules`.`name` = 'Stati di intervento' AND `zz_views`.`name` = 'Codice';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'codice' WHERE `zz_modules`.`name` = 'Tipi di intervento' AND `zz_views`.`name` = 'Codice';
UPDATE `zz_modules` SET `icon` = 'fa fa-angle-right' WHERE `zz_modules`.`name` = 'Categorie documenti';

-- Fix accesso alla stampa dell'Inventario magazzino
UPDATE `zz_prints` SET `is_record` = '0' WHERE `zz_prints`.`name` = 'Inventario magazzino';

UPDATE `in_statiintervento` SET `can_delete` = '0', `in_statiintervento`.`codice` = 'TODO'  WHERE `in_statiintervento`.`codice` = 'DAP';

-- Fix possibili problemi per data_registrazione e data_competenza
UPDATE `co_documenti` SET `data_registrazione` = NULL WHERE `data_registrazione` = '0000-00-00';
UPDATE `co_documenti` SET `data_registrazione` = `data` WHERE `data_registrazione` IS NULL AND idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir = 'uscita');
UPDATE `co_documenti` SET `data_competenza` = `data_registrazione` WHERE `data_competenza` = '0000-00-00' OR `data_competenza` IS NULL;
