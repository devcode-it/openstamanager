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

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Tipi documento'), 'Sezionale', 'zz_segments.name', '8', '1', '0', '0', '0', '', '', '1', '0', '0'); 

UPDATE `zz_prints` SET `predefined` = '0' WHERE `zz_prints`.`name` IN ('Stampa calendario settimanale', "Intervento & checklist", "Intervento & checklist (senza costi)", "Barcode bulk");

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_tipidocumento` LEFT JOIN zz_segments ON co_tipidocumento.id_segment = zz_segments.id WHERE 1=1 AND deleted_at IS NULL HAVING 2=2' WHERE `zz_modules`.`name` = 'Tipi documento'; 

-- Impostazione per login amministratore da app
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Permetti l\'accesso agli amministratori", '1', 'boolean', 1, 'Applicazione', 4, '');

UPDATE `co_pianodeiconti2` SET `dir` = 'entrata/uscita' WHERE `co_pianodeiconti2`.`descrizione` = 'Immobilizzazioni'; 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT id FROM zz_modules WHERE name = 'Scadenzario'), 'Esportazione distinta', 'presentazioni_exported_at', '20', '1', '0', '1', '0', '', '', '1', '0', '0'); 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Riferimenti', 'IF(fattura.info != "", fattura.info,"")', 16, 1, 0, 0 ,0);
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'icon_Inviato';
