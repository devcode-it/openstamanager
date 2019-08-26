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
UPDATE `zz_emails` SET `cc` = 'sdi52@pec.fatturapa.it' WHERE `name` = 'PEC';

-- Correzione query per visualizzazione fattura inviata o meno nella vista principale
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN (SELECT `numero_esterno`, `id_segment` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN(SELECT `id` FROM `co_tipidocumento` WHERE `dir` = ''entrata'') AND `co_documenti`.`data` >= ''|period_start|'' AND `co_documenti`.`data` <= ''|period_end|'' GROUP BY `id_segment`, `numero_esterno` HAVING COUNT(`numero_esterno`) > 1) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment`
    LEFT OUTER JOIN (SELECT `zz_emails`.`name`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `zz_emails` ON `zz_operations`.`id_email` = `zz_emails`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` AND `zz_modules`.`name` = ''Fatture di vendita'' AND `zz_operations`.`op` = ''send-email'' GROUP BY `zz_operations`.`id_module`, `zz_operations`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
WHERE 1=1 AND `dir` = ''entrata'' |segment(co_documenti.id_segment)| AND `co_documenti`.`data` >= ''|period_start|'' AND `co_documenti`.`data` <= ''|period_end|''
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Fatture di vendita';

-- Rimozione Pianificazione fatturazione
DELETE FROM `zz_plugins` WHERE `name` = 'Pianificazione fatturazione';

-- Aggiunta deleted_at su mg_articoli
ALTER TABLE `mg_articoli` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `mg_articoli` WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2 ORDER BY `descrizione`' WHERE `name` = 'Articoli';
