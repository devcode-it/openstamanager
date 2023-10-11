ALTER TABLE `co_staticontratti` ADD `colore` VARCHAR(7) NOT NULL AFTER `icona`; 
ALTER TABLE `co_statidocumento` ADD `colore` VARCHAR(7) NOT NULL AFTER `icona`; 
ALTER TABLE `dt_statiddt` ADD `colore` VARCHAR(7) NOT NULL AFTER `icona`; 
ALTER TABLE `or_statiordine` ADD `colore` VARCHAR(7) NOT NULL AFTER `icona`; 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Stati dei contratti'), 'color_Colore', 'colore', '7', '0', '0', '1', '0', '', '', '1', '0', '0'); 
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Stati degli ordini'), 'color_Colore', 'colore', '7', '0', '0', '1', '0', '', '', '1', '0', '0'); 
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Ordini cliente'), '_bg_', 'or_statiordine.colore', '14', '0', '0', '1', '0', '', '', '0', '0', '0'); 
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Ordini fornitore'), '_bg_', 'or_statiordine.colore', '14', '0', '0', '1', '0', '', '', '0', '0', '0'); 
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Contratti'), '_bg_', 'co_staticontratti.colore', '14', '0', '0', '1', '0', '', '', '0', '0', '0'); 
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Ddt di vendita'), '_bg_', 'dt_statiddt.colore', '14', '0', '0', '1', '0', '', '', '0', '0', '0'); 
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Ddt di acquisto'), '_bg_', 'dt_statiddt.colore', '14', '0', '0', '1', '0', '', '', '0', '0', '0'); 

UPDATE `co_staticontratti` SET `colore` = '#ffef99' WHERE `co_staticontratti`.`descrizione` = 'Bozza'; 
UPDATE `co_staticontratti` SET `colore` = '#99e6ff' WHERE `co_staticontratti`.`descrizione` = 'In attesa di conferma'; 
UPDATE `co_staticontratti` SET `colore` = '#99ffff' WHERE `co_staticontratti`.`descrizione` = 'Accettato'; 
UPDATE `co_staticontratti` SET `colore` = '#e5e4e2' WHERE `co_staticontratti`.`descrizione` = 'Rifiutato'; 
UPDATE `co_staticontratti` SET `colore` = '#fdd9a0' WHERE `co_staticontratti`.`descrizione` = 'In lavorazione'; 
UPDATE `co_staticontratti` SET `colore` = '#98fb98' WHERE `co_staticontratti`.`descrizione` = 'Fatturato'; 
UPDATE `co_staticontratti` SET `colore` = '#88de69' WHERE `co_staticontratti`.`descrizione` = 'Pagato'; 
UPDATE `co_staticontratti` SET `colore` = '#5dd333' WHERE `co_staticontratti`.`descrizione` = 'Concluso'; 
UPDATE `co_staticontratti` SET `colore` = '#b4ff99' WHERE `co_staticontratti`.`descrizione` = 'Parzialmente fatturato'; 

UPDATE `co_statidocumento` SET `colore` = '#88de69' WHERE `co_statidocumento`.`descrizione` = 'Pagato'; 
UPDATE `co_statidocumento` SET `colore` = '#ffef99' WHERE `co_statidocumento`.`descrizione` = 'Bozza'; 
UPDATE `co_statidocumento` SET `colore` = '#99ffff' WHERE `co_statidocumento`.`descrizione` = 'Emessa'; 
UPDATE `co_statidocumento` SET `colore` = '#e5e4e2' WHERE `co_statidocumento`.`descrizione` = 'Annullata';
UPDATE `co_statidocumento` SET `colore` = '#98fb98' WHERE `co_statidocumento`.`descrizione` = 'Parzialmente pagato';

UPDATE `dt_statiddt` SET `colore` = '#ffef99' WHERE `dt_statiddt`.`descrizione` = 'Bozza'; 
UPDATE `dt_statiddt` SET `colore` = '#99ffff' WHERE `dt_statiddt`.`descrizione` = 'Evaso'; 
UPDATE `dt_statiddt` SET `colore` = '#98fb98' WHERE `dt_statiddt`.`descrizione` = 'Fatturato'; 
UPDATE `dt_statiddt` SET `colore` = '#b4ff99' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente fatturato'; 
UPDATE `dt_statiddt` SET `colore` = '#FFDF80' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente evaso'; 

UPDATE `or_statiordine` SET `colore` = '#ffef99' WHERE `or_statiordine`.`descrizione` = 'Bozza'; 
UPDATE `or_statiordine` SET `colore` = '#99ffff' WHERE `or_statiordine`.`descrizione` = 'Evaso'; 
UPDATE `or_statiordine` SET `colore` = '#FFDF80' WHERE `or_statiordine`.`descrizione` = 'Parzialmente evaso'; 
UPDATE `or_statiordine` SET `colore` = '#b4ff99' WHERE `or_statiordine`.`descrizione` = 'Parzialmente fatturato'; 
UPDATE `or_statiordine` SET `colore` = '#98fb98' WHERE `or_statiordine`.`descrizione` = 'Fatturato'; 
UPDATE `or_statiordine` SET `colore` = '#f9f5be' WHERE `or_statiordine`.`descrizione` = 'In attesa di conferma'; 
UPDATE `or_statiordine` SET `colore` = '#99E6FF' WHERE `or_statiordine`.`descrizione` = 'Accettato'; 
UPDATE `or_statiordine` SET `colore` = '#e5e4e2' WHERE `or_statiordine`.`descrizione` = 'Annullato';

UPDATE `co_statipreventivi` SET `colore` = '#ffef99' WHERE `co_statipreventivi`.`descrizione` = 'Bozza'; 
UPDATE `co_statipreventivi` SET `colore` = '#99e6ff' WHERE `co_statipreventivi`.`descrizione` = 'In attesa di conferma'; 
UPDATE `co_statipreventivi` SET `colore` = '#99ffff' WHERE `co_statipreventivi`.`descrizione` = 'Accettato'; 
UPDATE `co_statipreventivi` SET `colore` = '#e5e4e2' WHERE `co_statipreventivi`.`descrizione` = 'Rifiutato';  
UPDATE `co_statipreventivi` SET `colore` = '#fdd9a0' WHERE `co_statipreventivi`.`descrizione` = 'In lavorazione'; 
UPDATE `co_statipreventivi` SET `colore` = '#d8bfd8' WHERE `co_statipreventivi`.`descrizione` = 'Concluso'; 
UPDATE `co_statipreventivi` SET `colore` = '#88de69' WHERE `co_statipreventivi`.`descrizione` = 'Pagato'; 
UPDATE `co_statipreventivi` SET `colore` = '#98fb98' WHERE `co_statipreventivi`.`descrizione` = 'Fatturato'; 
UPDATE `co_statipreventivi` SET `colore` = '#b4ff99' WHERE `co_statipreventivi`.`descrizione` = 'Parzialmente fatturato'; 

UPDATE `in_statiintervento` SET `colore` = '#ffef99' WHERE `in_statiintervento`.`descrizione` = 'Da programmare' AND `colore` = '#2deded'; 
UPDATE `in_statiintervento` SET `colore` = '#98fb98' WHERE `in_statiintervento`.`descrizione` = 'Fatturato' AND `colore` = '#55ff55'; 
UPDATE `in_statiintervento` SET `colore` = '#d8bfd8' WHERE `in_statiintervento`.`descrizione` = 'Completato' AND `colore` = '#a3ff82';
UPDATE `in_statiintervento` SET `colore` = '#99e6ff' WHERE `in_statiintervento`.`descrizione` = 'Programmato' AND `colore` = '#ffc400';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`d`.`conteggio`>1, \'red\', co_statidocumento.colore)' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = '_bg_'; 
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`dup`.`numero_esterno` IS NOT NULL, \'red\', co_statidocumento.colore)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = '_bg_'; 

-- Allineamento query Fatture di vendita
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento` FROM `co_movimenti`  WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS descrizione FROM `co_banche` GROUP BY `co_banche`.`id`) AS banche ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Fatture di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
	LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
	LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN( SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata') AND `numero_esterno` != '' GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento` HAVING COUNT(`numero_esterno`) > 1 |date_period(`co_documenti`.`data`)| ) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1=1 AND `dir` = 'entrata' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Fatture di vendita';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Tipi documento'), 'Sezionale', 'zz_segments.name', '8', '1', '0', '0', '0', '', '', '1', '0', '0'); 

UPDATE `zz_prints` SET `predefined` = '0' WHERE `zz_prints`.`name` IN ('Stampa calendario settimanale', "Intervento & checklist", "Intervento & checklist (senza costi)", "Barcode bulk");

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_tipidocumento` LEFT JOIN zz_segments ON co_tipidocumento.id_segment = zz_segments.id WHERE 1=1 AND deleted_at IS NULL HAVING 2=2' WHERE `zz_modules`.`name` = 'Tipi documento'; 

-- Impostazione per login amministratore da app
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Permetti l\'accesso agli amministratori", '1', 'boolean', 1, 'Applicazione', 4, '');

UPDATE `co_pianodeiconti2` SET `dir` = 'entrata/uscita' WHERE `co_pianodeiconti2`.`descrizione` = 'Immobilizzazioni'; 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Scadenzario'), 'Esportazione distinta', 'presentazioni_exported_at', '20', '1', '0', '1', '0', '', '', '1', '0', '0'); 

-- Fix query vista Ordini cliente
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
	`or_ordini`
    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`
    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idordine` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\\\Ordini\\\\Ordine' GROUP BY original_document_id) AS `fattura` ON `fattura`.`idordine` = `or_ordini`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'DDT ', `dt_ddt`.`id` SEPARATOR ', ') AS `info`, `dt_righe_ddt`.`original_document_id` AS `idddt` FROM `dt_ddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id`=`dt_righe_ddt`.`idddt` WHERE `original_document_type`='Modules\\\\Ordini\\\\Ordine' GROUP BY original_document_id) AS `ddt` ON `ddt`.`idddt`=`or_ordini`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Ordini cliente') AND `zz_operations`.`op` = 'send-email' GROUP BY id_record) AS `email` ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1 |segment(`or_ordini`.`id_segment`)| AND `dir` = 'entrata'  |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY 
	`data` DESC, 
    CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Ordini cliente';

-- Fix query vista Ddt in entrata
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN(SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt` 
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\\\DDT\\\\DDT' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`idddt` = `dt_ddt`.`id`
WHERE
    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = 'uscita' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.created_at DESC" WHERE `name` = 'Ddt di acquisto';

-- Aggiungo colonna riferimento in Ddt in uscita
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN (SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\\\DDT\\\\DDT' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`idddt` = `dt_ddt`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Ddt di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY id_record) AS `email` ON `email`.`id_record` = `dt_ddt`.`id`
WHERE
    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = 'entrata' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.created_at DESC" WHERE `name` = 'Ddt di vendita';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Riferimenti', 'IF(fattura.info != "", fattura.info,"")', 16, 1, 0, 0 ,0);
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'icon_Inviato';
