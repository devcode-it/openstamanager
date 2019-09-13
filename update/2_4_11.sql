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

INSERT INTO `em_emails` (`id`, `id_module`, `id_smtp`, `name`, `icon`, `subject`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 1, 'Reset password', 'fa fa-envelope', 'Richiesta di reset password', '', '', '', '<p>Gentile {username},</p>\r\n<p>a seguito della richiesta di reimpostazione della password del Suo account è pregato di inserire la nuova password che desidera utilizzare al seguente link:</p>\r\n<p class="text-center"><a href="{reset_link}">{reset_link}</a></p>\r\n<p>&nbsp;</p><p>Se non sei il responsabile della richiesta in questione, contatta l''amministratore il prima possibile per richiedere un cambio di username.</p>\r\n<p>&nbsp;</p>\r\n<p>Distinti saluti</p>\r\n', '0');

-- Relazione tra le righe dei documenti
ALTER TABLE `co_righe_documenti` ADD `original_id` int(11), ADD `original_type` varchar(255);
ALTER TABLE `or_righe_ordini` ADD `original_id` int(11), ADD `original_type` varchar(255);
ALTER TABLE `dt_righe_ddt` ADD `original_id` int(11), ADD `original_type` varchar(255);

ALTER TABLE `co_righe_contratti` ADD `abilita_serial` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `co_righe_preventivi` ADD `abilita_serial` tinyint(1) NOT NULL DEFAULT '0';

--
-- Attenzione: da testare per il corretto aggiornamento dei dati.
--

--
-- Fatture
--
-- Collegamento Articoli
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ddt\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0;

-- Collegamento Sconti
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ddt\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0;

-- Collegamento Descrizioni
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ddt\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` AND `co_righe_documenti`.`descrizione` = `co_righe_contratti`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_contratti`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Contratti\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

UPDATE `co_righe_documenti` INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` AND `co_righe_documenti`.`descrizione` = `co_righe_preventivi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `co_righe_preventivi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Preventivi\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0;

-- Collegamento Righe
UPDATE `co_righe_documenti` INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`idordine` = `or_righe_ordini`.`idordine` AND `co_righe_documenti`.`descrizione` = `or_righe_ordini`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `or_righe_ordini`.`idarticolo` SET `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ordini\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL;

UPDATE `co_righe_documenti` INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`idddt` = `dt_righe_ddt`.`idddt` AND `co_righe_documenti`.`descrizione` = `dt_righe_ddt`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `dt_righe_ddt`.`idarticolo` SET `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Ddt\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL;

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
(NULL, 'v1', 'create', 'articolo_intervento', 'Modules\\Interventi\\API\\v1\\Articoli', '1');

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

-- Fix query Scadenzario
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_scadenziario`
   LEFT JOIN `co_documenti`  ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
   LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
   LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
   LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
   LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
WHERE 1=1 AND
    (`co_scadenziario`.`scadenza` BETWEEN ''|period_start|'' AND ''|period_end|'' OR ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)) AND
    (`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN(''Emessa'',''Parzialmente pagato''))
HAVING 2=2
ORDER BY `scadenza` ASC' WHERE `name` = 'Scadenzario';

-- Aggiunte impostazione Autocomple web form
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Autocompletamento form', 'on', 'list[on,off]', '1', 'Generali', '', NULL);

-- Data concordata per le scadenza
ALTER TABLE `co_scadenziario` ADD `data_concordata` DATE;

UPDATE `zz_views` SET `query` = 'IF(pagato = da_pagare, ''#38CD4E'', IF(data_concordata IS NOT NULL AND data_concordata > NOW(), '' #CC9837'', IF(scadenza < NOW(), ''#CC4D37'', '''')))' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = '_bg_';

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
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Checklists', 'Checklists', 'checklists', 'SELECT |select| FROM `zz_checklists` WHERE 1=1 HAVING 2=2', '', 'fa fa-check-square-o', '2.4.11', '2.4.11', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Checklists'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Checklists'), 'Nome', 'name', 2, 1, 0, 0, 1),
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
UPDATE `em_emails` SET `cc` = 'sdi52@pec.fatturapa.it' WHERE `name` = 'PEC';

-- Rimozione Pianificazione fatturazione
DELETE FROM `zz_plugins` WHERE `name` = 'Pianificazione fatturazione';

-- Aggiunta deleted_at su mg_articoli
ALTER TABLE `mg_articoli` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `mg_articoli` WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2 ORDER BY `descrizione`' WHERE `name` = 'Articoli';

-- Ampliamento hooks
ALTER TABLE `zz_hooks` ADD `processing_at` TIMESTAMP NULL DEFAULT NULL, ADD `processing_token` varchar(255);
INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES (NULL, 'Backup', 'Modules\\Backups\\BackupHook', '1 day', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Backup'));

-- Miglioramento gestione email
ALTER TABLE `em_emails` RENAME TO `em_templates`;
ALTER TABLE `zz_smtps` RENAME TO `em_accounts`;
ALTER TABLE `zz_email_print` RENAME TO `em_print_template`;

UPDATE zz_modules SET options = REPLACE(options, 'em_emails', 'em_templates'), options2 = REPLACE(options2, 'em_emails', 'em_templates');
UPDATE zz_modules SET options = REPLACE(options, 'zz_smtps', 'em_accounts'), options2 = REPLACE(options2, 'zz_smtps', 'em_accounts');
UPDATE zz_views SET query = REPLACE(query, 'em_emails', 'em_templates');
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
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Newsletter', 'Newsletter', 'newsletter', 'SELECT |select| FROM `em_newsletters` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2', '', 'fa fa-newspaper-o ', '2.4.11', '2.4.11', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Gestione email'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'Nome', 'name', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'Template', '(SELECT name FROM em_templates WHERE id = em_newsletters.id_template)', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Newsletter'), 'Completato', 'IF(completed_at IS NULL, ''No'', ''Si'')', 4, 1, 0, 1, 1);

-- Modulo Stato email
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stato email', 'Coda di invio', 'stato_email', 'SELECT |select| FROM `em_emails` WHERE 1=1 AND (`em_emails`.`created_at` BETWEEN ''|period_start|'' AND ''|period_end|'' OR `em_emails`.`sent_at` IS NULL) HAVING 2=2', '', 'fa fa-spinner ', '2.4.11', '2.4.11', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Gestione email'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'id', 'id', 1, 0, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Oggetto', 'subject', 2, 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Contenuto', 'content', 3, 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Template', '(SELECT name FROM em_templates WHERE id = em_emails.id_template)', 3, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Data invio', 'sent_at', 4, 1, 0, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Ultimo tentativo', 'failed_at', 5, 1, 0, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), '_bg_', 'IF(sent_at IS NULL, IF(failed_at IS NULL, ''#CC9837'', ''#CC4D37''), ''#38CD4E'')', 6, 1, 0, 0, 0, 0);

ALTER TABLE `em_templates` CHANGE `id_smtp` `id_account` INT(11) NOT NULL;
ALTER TABLE `em_print_template` CHANGE `id_email` `id_template` INT(11) NOT NULL;
ALTER TABLE `em_accounts` ADD `timeout` INT(11) NOT NULL DEFAULT 1000;
ALTER TABLE `an_anagrafiche` ADD `enable_newsletter` BOOLEAN DEFAULT TRUE;

-- Aggiunta coda di invio per le Fatture Elettroniche
ALTER TABLE `co_documenti` ADD `hook_send` BOOLEAN DEFAULT FALSE;
INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES (NULL, 'Fatture Elettroniche', 'Plugins\\ExportFE\\InvoiceHook', '1 minute', NULL);

INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES
('ERR', 'Trasmissione non riuscita', 'fa fa-close'),
('QUEUE', 'In coda di elaborazione', 'fa fa-spinner');

-- Ottimizzazione Fatture di vendita
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT OUTER JOIN (
        SELECT `iddocumento`, SUM(`subtotale` - `sconto` + `iva` + `rivalsainps` - `ritenutaacconto`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (
        SELECT `numero_esterno`, `id_segment`
        FROM `co_documenti`
        WHERE `co_documenti`.`idtipodocumento` IN(SELECT `id` FROM `co_tipidocumento` WHERE `dir` = ''entrata'') |date_period(`co_documenti`.`data`)| AND `numero_esterno` != ''''
        GROUP BY `id_segment`, `numero_esterno`
        HAVING COUNT(`numero_esterno`) > 1
    ) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment`
    LEFT OUTER JOIN (
        SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`
        FROM `zz_operations`
            INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`
            INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`
            INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`
        WHERE `zz_modules`.`name` = ''Fatture di vendita'' AND `zz_operations`.`op` = ''send-email''
        GROUP BY `zz_operations`.`id_email`, `zz_operations`.`id_record`
    ) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
WHERE 1=1 AND `dir` = ''entrata'' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Fatture di vendita';

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Totale';

-- Ottimizzazione Fatture di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT OUTER JOIN (
        SELECT `iddocumento`, SUM(`subtotale` - `sconto` + `iva` + `rivalsainps` - `ritenutaacconto`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
WHERE 1=1 AND `dir` = ''uscita'' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero_esterno` = '''', `co_documenti`.`numero`, `co_documenti`.`numero_esterno`) AS UNSIGNED) DESC' WHERE `name` = 'Fatture di acquisto';

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Totale';
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Ragione sociale';
UPDATE `zz_views` SET `query` = 'co_statidocumento.icona' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'icon_Stato';
UPDATE `zz_views` SET `query` = 'co_statidocumento.descrizione' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'icon_title_Stato';

-- Ottimizzazione Contratti
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `co_contratti`
    INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT OUTER JOIN (
        SELECT `idcontratto`, SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_contratti`
        GROUP BY `idcontratto`
    ) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT OUTER JOIN (
        SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '''', CONCAT('' - '', nome), '''')) SEPARATOR ''<br>'') AS descrizione, my_impianti_contratti.idcontratto
        FROM my_impianti
            INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto
        GROUP BY my_impianti_contratti.idcontratto
    ) AS impianti ON impianti.idcontratto = co_contratti.id
WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')|
HAVING 2=2
ORDER BY `co_contratti`.`id` DESC' WHERE `name` = 'Contratti';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Totale', 'righe.totale', 5, 1, 1, 1, 1);
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'Cliente';
UPDATE `zz_views` SET `query` = 'co_staticontratti.icona' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'icon_Stato';
UPDATE `zz_views` SET `query` = 'co_staticontratti.descrizione' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'icon_title_Stato';
UPDATE `zz_views` SET `query` = '`co_contratti`.`id`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'id';
UPDATE `zz_views` SET `query` = '`co_contratti`.`nome`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'Nome';
UPDATE `zz_views` SET `query` = 'impianti.descrizione' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'Impianti';

-- Ottimizzazione Preventivi
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `co_preventivi`
    INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT OUTER JOIN (
        SELECT `idpreventivo`, SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_preventivi`
        GROUP BY `idpreventivo`
    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')|
HAVING 2=2
ORDER BY `co_preventivi`.`id` DESC' WHERE `name` = 'Preventivi';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Totale', 'righe.totale', 5, 1, 1, 1, 1);
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `name` = 'Cliente';
UPDATE `zz_views` SET `query` = 'co_statipreventivi.icona' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `name` = 'icon_Stato';
UPDATE `zz_views` SET `query` = 'co_statipreventivi.descrizione' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `name` = 'icon_title_Stato';
UPDATE `zz_views` SET `query` = '`co_preventivi`.`id`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `name` = 'id';
UPDATE `zz_views` SET `query` = '`co_preventivi`.`nome`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `name` = 'Nome';

-- Ottimizzazione Ddt di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt`
    INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT OUTER JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT OUTER JOIN (
        SELECT `idddt`, SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `dt_righe_ddt`
        GROUP BY `idddt`
    ) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `name` = 'Ddt di acquisto';

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto') AND `name` = 'Totale';
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto') AND `name` = 'Ragione sociale';

-- Ottimizzazione Ddt di vendita
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `dt_ddt`
    INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT OUTER JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT OUTER JOIN (
        SELECT `idddt`, SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `dt_righe_ddt`
        GROUP BY `idddt`
    ) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `name` = 'Ddt di vendita';

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita') AND `name` = 'Totale';
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita') AND `name` = 'Ragione sociale';

-- Ottimizzazione Ordini cliente
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `or_ordini`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT OUTER JOIN (
        SELECT `idordine`, SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `or_righe_ordini`
        GROUP BY `idordine`
    ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Ordini cliente';

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente') AND `name` = 'Totale';
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente') AND `name` = 'Ragione sociale';

-- Ottimizzazione Ordini fornitore
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `or_ordini`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT OUTER JOIN (
        SELECT `idordine`, SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `or_righe_ordini`
        GROUP BY `idordine`
    ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Ordini fornitore';

UPDATE `zz_views` SET `query` = 'righe.totale' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore') AND `name` = 'Totale';
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale ' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore') AND `name` = 'Ragione sociale';

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

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Dichiarazioni d''Intento', 'Dichiarazioni d''Intento', (SELECT id FROM zz_modules WHERE name = 'Fatture di vendita'), (SELECT id FROM zz_modules WHERE name='Anagrafiche'), 'tab', '', '1', '1', '0', '', '', NULL, '{ "main_query": [	{	"type": "table", "fields": "Protocollo, Progressivo, Massimale, Totale, Data inizio, Data fine", "query": "SELECT id, numero_protocollo AS Protocollo, numero_progressivo AS Progressivo, DATE_FORMAT(data_inizio,''%d/%m/%Y'') AS ''Data inizio'', DATE_FORMAT(data_inizio,''%d/%m/%Y'') AS ''Data fine'', ROUND(massimale, 2) AS Massimale, ROUND(totale, 2) AS Totale FROM co_dichiarazioni_intento WHERE 1=1 AND deleted_at IS NULL AND id_anagrafica = |id_parent| HAVING 2=2 ORDER BY co_dichiarazioni_intento.id DESC"}	]}', 'dichiarazioni_intento', '');

