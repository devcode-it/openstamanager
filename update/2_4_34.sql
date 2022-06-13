-- Aggiunta campi Ore, Costi, Ricavi su attività, di default disattivati
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_interventi`\r\nINNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\nLEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`\r\nLEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`\r\nLEFT JOIN (SELECT idintervento, SUM(prezzo_unitario*qta-sconto) AS ricavo_righe, SUM(costo_unitario*qta) AS costo_righe FROM `in_righe_interventi` GROUP BY idintervento) AS righe ON righe.`idintervento` = `in_interventi`.`id`\r\nLEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`\r\nLEFT JOIN (\r\n SELECT an_sedi.id, CONCAT(an_sedi.nomesede, \'<br />\',IF(an_sedi.telefono!=\'\',CONCAT(an_sedi.telefono,\'<br />\'),\'\'),IF(an_sedi.cellulare!=\'\',CONCAT(an_sedi.cellulare,\'<br />\'),\'\'),an_sedi.citta,IF(an_sedi.indirizzo!=\'\',CONCAT(\' - \',an_sedi.indirizzo),\'\')) AS info FROM an_sedi\r\n) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione\r\nLEFT JOIN (\r\n SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \", \") AS info, co_righe_documenti.original_document_id AS idintervento FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type=\'Modules\\\\Interventi\\\\Intervento\' GROUP BY idintervento\r\n) AS fattura ON fattura.idintervento = in_interventi.id\r\nLEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\r\n FROM `zz_operations`\r\n INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\r\n INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\r\n INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` \r\n WHERE `zz_modules`.`name` = \'Interventi\' AND `zz_operations`.`op` = \'send-email\' \r\n GROUP BY `zz_operations`.`id_record`) AS email ON email.id_record=in_interventi.id\r\nLEFT JOIN (\r\n SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != \'\', CONCAT(\' - \', nome), \'\')) SEPARATOR \'<br />\') AS descrizione, my_impianti_interventi.idintervento\r\n FROM my_impianti\r\n INNER JOIN my_impianti_interventi ON my_impianti.id = my_impianti_interventi.idimpianto\r\n GROUP BY my_impianti_interventi.idintervento\r\n) AS impianti ON impianti.idintervento = in_interventi.id\r\nLEFT JOIN (\r\n SELECT co_contratti.id, CONCAT(co_contratti.numero, \' del \', DATE_FORMAT(data_bozza, \'%d/%m/%Y\')) AS info FROM co_contratti\r\n) AS contratto ON contratto.id = in_interventi.id_contratto\r\nLEFT JOIN (\r\n SELECT co_preventivi.id, CONCAT(co_preventivi.numero, \' del \', DATE_FORMAT(data_bozza, \'%d/%m/%Y\')) AS info FROM co_preventivi\r\n) AS preventivo ON preventivo.id = in_interventi.id_preventivo\r\nLEFT JOIN (\r\n SELECT or_ordini.id, CONCAT(or_ordini.numero, \' del \', DATE_FORMAT(data, \'%d/%m/%Y\')) AS info FROM or_ordini\r\n) AS ordine ON ordine.id = in_interventi.id_ordine\r\nWHERE 1=1 |date_period(`orario_inizio`,`data_richiesta`)|\r\nGROUP BY `in_interventi`.`id`\r\nHAVING 2=2\r\nORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`html_format` = 1, `query` = "IF(in_interventi.idsede_destinazione > 0, sede_destinazione.info, CONCAT('', IF(an_anagrafiche.telefono!='',CONCAT(an_anagrafiche.telefono,'<br>'),''),IF(an_anagrafiche.cellulare!='',CONCAT(an_anagrafiche.cellulare,'<br>'),''),IF(an_anagrafiche.citta!='',an_anagrafiche.citta,''),IF(an_anagrafiche.indirizzo!='',CONCAT(' - ',an_anagrafiche.indirizzo),'')))" WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Sede';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Ricavi', 'IFNULL( SUM(prezzo_ore_consuntivo+prezzo_km_consuntivo+prezzo_dirittochiamata), 0 ) + IFNULL( ricavo_righe, 0 )', 21, 1, 0, 1, 0, '', '', 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Costi', 'IFNULL( SUM(prezzo_ore_consuntivo_tecnico+prezzo_km_consuntivo_tecnico+prezzo_dirittochiamata_tecnico), 0 ) + IFNULL( costo_righe, 0 )', 20, 1, 0, 1, 0, '', '', 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Ore', 'SUM(in_interventi_tecnici.ore)', 28, 1, 0, 1, 0, '', '', 0, 1, 1);

-- Aggiunta permessi viste ai gruppi
INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) ( SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` IN('Ore', 'Costi', 'Ricavi') );

-- Nuovo plugin "Provvigioni"
CREATE TABLE IF NOT EXISTS `co_provvigioni` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idagente` int NOT NULL,
  `idarticolo` int NOT NULL,
  `provvigione` decimal(12,6) NOT NULL,
  `tipo_provvigione` enum('UNT','PRC') NOT NULL DEFAULT 'UNT',
  PRIMARY KEY (`id`)
);

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Provvigioni', 'Provvigioni', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'tab', '', '1', '1', '0', '', '', NULL, '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Agente, Provvigione\", \"query\": \"SELECT co_provvigioni.id, an_anagrafiche.ragione_sociale AS `Agente`, CONCAT(FORMAT(co_provvigioni.provvigione,2), \' \', IF(co_provvigioni.tipo_provvigione=\'UNT\', \'€\', \'%\')) AS `Provvigione` FROM co_provvigioni LEFT JOIN an_anagrafiche ON co_provvigioni.idagente=an_anagrafiche.idanagrafica WHERE co_provvigioni.idarticolo=|id_parent| HAVING 2=2 ORDER BY co_provvigioni.id DESC\"} ]}', 'provvigioni', '');

ALTER TABLE `co_righe_contratti` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `co_righe_preventivi` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `co_righe_documenti` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `dt_righe_ddt` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`;

ALTER TABLE `in_righe_interventi` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `or_righe_ordini` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `an_anagrafiche` ADD `provvigione_default` DECIMAL(12,6) NOT NULL AFTER `idtipointervento_default`; 

ALTER TABLE `in_interventi` ADD `idagente` INT NOT NULL AFTER `idreferente`; 

-- Impostazione per controlli su stati FE
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Data inizio controlli su stati FE', '01/01/2019', 'date', '1', 'Fatturazione elettronica', '23', NULL);

-- Aggiunto campo deleted_at in in_fasceorarie
ALTER TABLE `in_fasceorarie` ADD `deleted_at` TIMESTAMP NULL AFTER `include_bank_holidays`; 

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_fasceorarie` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2' WHERE `zz_modules`.`name` = 'Fasce orarie'; 

-- Stampa Barcode bulk
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), '0', 'Barcode bulk', 'Barcode', 'Barcode', 'barcode_bulk', '', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- Stampa scadenza
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 1, 'Scadenza', 'Scadenza', 'Scadenza', 'scadenzario', '', '', 'fa fa-print', '', '', 0, 0, 1, 1);

-- Aggiunta scelta minuti di snap in dashboard
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Tempo predefinito di snap attività sul calendario', '00:15:00', 'string', '1', 'Dashboard', '5', 'Va utilizzato il formato di Fullcalendar: hh:mm:ss');

-- Filtro per mostrare preventivi ai clienti
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_groups` WHERE `nome`='Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Preventivi'), 'Mostra preventivi ai clienti coinvolti', 'co_preventivi.idanagrafica=|id_anagrafica|', 'WHR', 1, 0);

-- Nuova tabella per gestire le provenienze
CREATE TABLE IF NOT EXISTS `an_provenienze` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(100) NOT NULL,
  `colore` varchar(7) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


INSERT INTO `an_provenienze` (`id`, `descrizione`, `colore`) VALUES
(NULL, 'Sito web', '#caffb7'),
(NULL, 'Passaparola', '#8fbafd');

-- Aggiunto id_provenienza per scheda anagrafica Cliente
ALTER TABLE `an_anagrafiche` ADD `id_provenienza` AFTER `idrelazione`, INT DEFAULT NULL;

-- Nuovo modulo per gestire le "Provenienze" 
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Provenienze', 'Provenienze clienti', 'provenienze', 'SELECT |select| FROM `an_provenienze` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.34', '2.4.34', '3', (SELECT id FROM zz_modules t WHERE t.name = 'Anagrafiche'), '1', '1', '0', '0'); 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Provenienze'), 'id', 'an_provenienze.id', 1, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Provenienze'), 'descrizione', 'an_provenienze.descrizione', 2, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Provenienze'), 'colore', 'an_provenienze.colore', 3, 1, 0, 1);


-- Aggiunta tabella settore merceologico
CREATE TABLE IF NOT EXISTS `an_settori` ( `id` INT NOT NULL AUTO_INCREMENT , `descrizione` VARCHAR(100) NOT NULL , PRIMARY KEY (`id`));

INSERT INTO `an_settori`(
    `descrizione`
)(
    SELECT DISTINCT `settore` FROM `an_anagrafiche`
);

ALTER TABLE `an_anagrafiche` ADD `id_settore` INT NOT NULL AFTER `settore`;

UPDATE `an_anagrafiche`, `an_settori` SET `id_settore`=`an_settori`.`id` WHERE `an_settori`.`descrizione`=`an_anagrafiche`.`settore`;

ALTER TABLE `an_anagrafiche` DROP `settore`;

-- Nuovo modulo per gestire i "Settori merceologici" 
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Settori', 'Settori merceologici', 'settori_merceologici', 'SELECT |select| FROM `an_settori` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.34', '2.4.34', '4', (SELECT id FROM zz_modules t WHERE t.name = 'Anagrafiche'), '1', '1', '0', '0'); 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Settori'), 'id', 'an_settori.id', 1, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Settori'), 'descrizione', 'an_settori.descrizione', 2, 1, 0, 1);

-- Fix eliminazione fattura collegata a Nota di credito
ALTER TABLE `co_documenti` DROP FOREIGN KEY `co_documenti_ibfk_1`;
ALTER TABLE `co_righe_documenti` DROP FOREIGN KEY `co_righe_documenti_ibfk_1`;