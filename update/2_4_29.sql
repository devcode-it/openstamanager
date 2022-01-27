-- Aggiunto condizioni fornitura in contratti
ALTER TABLE `co_contratti` ADD `condizioni_fornitura` TEXT NOT NULL AFTER `informazioniaggiuntive`; 

UPDATE `zz_settings` SET `nome` = 'Condizioni generali di fornitura preventivi' WHERE `zz_settings`.`nome` = 'Condizioni generali di fornitura';
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Condizioni generali di fornitura contratti', '', 'ckeditor', '1', 'Contratti', NULL, NULL);

-- Rimossa visualizzazione stampe disabilitate
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `zz_prints` WHERE 1=1 AND enabled=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Stampe'; 

-- Filtro che esclude gli articoli eliminati dal widget degli articoli in esaurimento
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM mg_articoli WHERE qta < threshold_qta AND attivo=1 AND deleted_at IS NULL' WHERE `zz_widgets`.`name` = 'Articoli in esaurimento'; 

-- Fix problema iva di vendita preselezionata
ALTER TABLE `mg_articoli` CHANGE `idiva_vendita` `idiva_vendita` INT(11) NULL DEFAULT NULL;
UPDATE `mg_articoli` SET `idiva_vendita`=NULL WHERE `idiva_vendita`=0;

-- Impostazione per la modifica di altri tecnici nell'app
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Abilita la modifica di altri tecnici', '1', 'boolean', 1, 'Applicazione', 4, '');

-- Fix widget preventivi in lavorazione
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE idstato=(SELECT id FROM co_statipreventivi WHERE descrizione=\"In lavorazione\") AND default_revision=1' WHERE `zz_widgets`.`name` ='Preventivi in lavorazione';

-- Rimosso controllo is_pianificabile widget contratti in scadenza
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND co_contratti.id NOT IN (SELECT idcontratto_prev FROM co_contratti contratti) HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Aggiornamento ritenuta contributi in contributi previdenziali
UPDATE `zz_settings` SET `nome` = 'Ritenuta previdenziale predefinita' WHERE `nome` = 'Ritenuta contributi';
UPDATE `zz_modules` SET `name` = 'Ritenute previdenziali', `title` = 'Ritenute previdenziali' WHERE `name` = 'Ritenute contributi';

-- Aggiornamento rivalse in casse previdenziali
UPDATE `zz_settings` SET `nome` = 'Cassa previdenziale predefinita' WHERE `nome` = 'Percentuale rivalsa';
UPDATE `zz_modules` SET `name` = 'Casse previdenziali', `title` = 'Casse previdenziali' WHERE `name` = 'Rivalse';

-- Aggiornamento impostazione predefinita ritenuta d'acconto
UPDATE `zz_settings` SET `nome` = 'Ritenuta d''acconto predefinita' WHERE `nome` = 'Percentuale ritenuta d''acconto';

-- Fix vista tecnici assegnati
UPDATE `zz_views` SET `query` = 'GROUP_CONCAT(DISTINCT(SELECT DISTINCT(ragione_sociale) FROM an_anagrafiche WHERE idanagrafica = in_interventi_tecnici_assegnati.id_tecnico) SEPARATOR \', \')' WHERE `zz_views`.`name` = 'Tecnici assegnati';

-- Fix options Mansioni referenti
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM an_mansioni WHERE 1=1 HAVING 2=2 ORDER BY `nome`' WHERE `zz_modules`.`name` = 'Mansioni referenti'; 

-- Nuova stampa Ordine cliente (senza codici)
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Ordini cliente'), '1', 'Ordine cliente (senza codici)', 'Ordine cliente (senza codici)', 'Ordine cliente num. {numero} del {data}', 'ordini', 'idordine', '{\"pricing\": true, \"last-page-footer\": true, \"hide_codice\": true}', 'fa fa-print', '', '', '0', '0', '1', '1');

-- Aggiunta vista Email inviata in moduli ordini
UPDATE `zz_modules` SET `options` = 'SELECT |select|\r\nFROM `or_ordini`\r\n LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\r\n LEFT JOIN (\r\n SELECT `idordine`,\r\n SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`,\r\n SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\r\n SUM(`subtotale` - `sconto` + `iva`) AS `totale`\r\n FROM `or_righe_ordini`\r\n GROUP BY `idordine`\r\n ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\r\n LEFT JOIN (\r\n SELECT `idordine`,\r\n MIN(`data_evasione`) AS `data_evasione`\r\n FROM `or_righe_ordini`\r\n WHERE (`qta` - `qta_evasa`)>0\r\n GROUP BY `idordine`\r\n ) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\r\n LEFT JOIN (\r\n SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \", \") AS info, co_righe_documenti.idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento GROUP BY idordine\r\n) AS fattura ON fattura.idordine = or_ordini.id\r\nLEFT JOIN (\r\n SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\r\n FROM `zz_operations`\r\n INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\r\n INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\r\n INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`\r\n WHERE `zz_modules`.`name` = \'Ordini fornitore\' AND `zz_operations`.`op` = \'send-email\'\r\n GROUP BY `zz_operations`.`id_record`\r\n ) AS `email` ON `email`.`id_record` = `or_ordini`.`id`\r\nWHERE 1=1 AND `dir` = \'uscita\' |date_period(`data`)|\r\nHAVING 2=2\r\nORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';

UPDATE `zz_modules` SET `options` = 'SELECT |select|\r\nFROM `or_ordini`\r\n LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\r\n LEFT JOIN (\r\n SELECT `idordine`,\r\n SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`,\r\n SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\r\n SUM(`subtotale` - `sconto` + `iva`) AS `totale`\r\n FROM `or_righe_ordini`\r\n GROUP BY `idordine`\r\n ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\r\n LEFT JOIN (\r\n SELECT `idordine`,\r\n MIN(`data_evasione`) AS `data_evasione`\r\n FROM `or_righe_ordini`\r\n WHERE (`qta` - `qta_evasa`)>0\r\n GROUP BY `idordine`\r\n ) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\r\n LEFT JOIN (\r\n SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \", \") AS info, co_righe_documenti.idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento GROUP BY idordine\r\n) AS fattura ON fattura.idordine = or_ordini.id\r\nLEFT JOIN (\r\n SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\r\n FROM `zz_operations`\r\n INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\r\n INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\r\n INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`\r\n WHERE `zz_modules`.`name` = \'Ordini cliente\' AND `zz_operations`.`op` = \'send-email\'\r\n GROUP BY `zz_operations`.`id_record`\r\n ) AS `email` ON `email`.`id_record` = `or_ordini`.`id`\r\nWHERE 1=1 AND `dir` = \'entrata\' |date_period(`data`)|\r\nHAVING 2=2\r\nORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'icon_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 12, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'icon_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 12, 1, 0, 0, '', '', 0, 0, 0);

-- Fix massimale dichiarazione d'intento
ALTER TABLE `co_dichiarazioni_intento` CHANGE `massimale` `massimale` DECIMAL(15,6) NOT NULL; 