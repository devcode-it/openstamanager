UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'CONCAT(IF(fattura.info != "", fattura.info,""), IF(ddt.info != "", ddt.info,""))' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'Rif. fattura';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`name` = 'Riferimenti' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`query` = 'CONCAT(IF(fattura.info != "", fattura.info,""), IF(ddt.info != "", ddt.info,""))';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Riferimenti', 'IF(fattura.info != "", fattura.info,"")', 15, 1, 0, 0 ,0);

UPDATE `zz_api_resources` SET `resource` = 'checklist-cleanup' WHERE `zz_api_resources`.`resource` = 'checklists-cleanup'; 

UPDATE `zz_settings` SET `tipo` = 'query=SELECT codice AS id, CONCAT(codice, \' - \', descrizione)as descrizione FROM fe_regime_fiscale;' WHERE `zz_settings`.`nome` = "Regime Fiscale";

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini cliente'), 'Data scadenza', 'data_scadenza_predefinita', 3, 1, 0, 1, 1, 1);

UPDATE `zz_widgets` SET `text` = 'Listini disattivati' WHERE `zz_widgets`.`name` = 'Listini scaduti'; 

INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `help`) VALUES (NULL, 'Preventivi da fatturare', 'stats', '1', 'controller_top', NULL, 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE idstato IN (SELECT id FROM co_statipreventivi WHERE is_fatturabile=1) AND default_revision=1', '#44aae4', 'fa fa-file', '', './modules/preventivi/widgets/preventivi.fatturare.dashboard.php', 'popup', '', 'Preventivi da fatturare', 0, NULL);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), '_bg_', 'IF(threshold_qta!=0, IF(mg_articoli.qta>=threshold_qta, \'#CCFFCC\', \'#FFCCEB\'), \'\')', '14', '0', '0', '0', '0', '', '', '0', '0', '0');

-- Aggiunto titolo righe preventivi
ALTER TABLE `co_righe_preventivi` ADD `is_titolo` BOOLEAN NOT NULL AFTER `confermato`; 

ALTER TABLE `co_documenti` CHANGE `numero_esterno` `numero_esterno` VARCHAR(100) NOT NULL; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Tipo di sconto predefinito', '%', 'list[%,€]', '1', 'Generali', '1', NULL); 

ALTER TABLE `co_tipidocumento` ADD `id_segment` INT NOT NULL AFTER `predefined`; 
UPDATE `co_tipidocumento` SET `id_segment`= (SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_segments`.`predefined` = 1) WHERE `co_tipidocumento`.`dir` = 'entrata';
UPDATE `co_tipidocumento` SET `id_segment`= (SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_segments`.`predefined` = 1) WHERE `co_tipidocumento`.`dir` = 'uscita';
UPDATE `co_tipidocumento` SET `id_segment`=(SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_segments`.`name` = 'Autofatture' AND `zz_modules`.`name` = 'Fatture di vendita') WHERE `co_tipidocumento`.`dir` = 'entrata' AND `co_tipidocumento`.`descrizione` LIKE "%autofattura%";
UPDATE `co_tipidocumento` SET `id_segment`=(SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_segments`.`name` = 'Autofatture' AND `zz_modules`.`name` = 'Fatture di acquisto') WHERE `co_tipidocumento`.`dir` = 'uscita' AND `co_tipidocumento`.`descrizione` LIKE "%autofattura%";

-- Fix widget Acquisti
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\nWHERE co_tipidocumento.dir=\'uscita\' |segment(`co_documenti`.`id_segment`)| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Acquisti';

-- Fix widget Debiti verso fornitori
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((SELECT ABS(SUM(da_pagare-pagato))), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'uscita\' AND co_documenti.idstatodocumento!=1 |segment(`co_documenti`.`id_segment`)| AND 1=1' WHERE `zz_widgets`.`name` = 'Debiti verso fornitori';

-- Fix widget Crediti da clienti
UPDATE `zz_widgets` SET `query` = 'SELECT \n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(da_pagare-pagato)), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'entrata\' AND co_documenti.idstatodocumento!=1 |segment(`co_documenti`.`id_segment`)| AND 1=1' WHERE `zz_widgets`.`name` = 'Crediti da clienti';

-- Correzione collegamento scadenze al log email
UPDATE `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` SET `zz_operations`.`id_record` = `em_emails`.`id_record` WHERE `zz_operations`.`id_record` IS NULL AND `zz_modules`.`name` = "Scadenzario" AND `zz_operations`.`op` IN ("send-email", "send-sollecito");