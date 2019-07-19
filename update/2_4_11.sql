UPDATE `zz_prints` SET `filename` = 'Preventivo num. {numero} del {data}' WHERE `name` = 'Preventivo (senza totali)';

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

-- Collegamento Articoli
--
-- Attenzione: da testare per il corretto aggiornamento dei dati.
--
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
(NULL, 'v1', 'retrieve', 'anagrafiche', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'create', 'anagrafica', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'update', 'anagrafica', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
(NULL, 'v1', 'delete', 'anagrafica', 'Modules\\Anagrafiche\\API\\v1\\Anagrafiche', '1'),
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
(NULL, 'v1', 'create', 'sessione_intervento', 'Modules\\Interventi\\API\\v1\\Sessioni', '1'),
(NULL, 'v1', 'update', 'sessione_intervento', 'Modules\\Interventi\\API\\v1\\Sessioni', '1'),
(NULL, 'v1', 'retrieve', 'articoli_intervento', 'Modules\\Interventi\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'create', 'articolo_intervento', 'Modules\\Interventi\\API\\v1\\Articoli', '1');

