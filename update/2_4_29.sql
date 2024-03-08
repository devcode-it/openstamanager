-- Aggiunto condizioni fornitura in contratti
ALTER TABLE `co_contratti` ADD `condizioni_fornitura` TEXT NOT NULL AFTER `informazioniaggiuntive`; 

UPDATE `zz_settings` SET `nome` = 'Condizioni generali di fornitura preventivi' WHERE `zz_settings`.`nome` = 'Condizioni generali di fornitura';
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Condizioni generali di fornitura contratti', '', 'ckeditor', '1', 'Contratti', NULL, NULL);

-- Filtro che esclude gli articoli eliminati dal widget degli articoli in esaurimento
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM mg_articoli WHERE qta < threshold_qta AND attivo=1 AND deleted_at IS NULL' WHERE `zz_widgets`.`name` = 'Articoli in esaurimento'; 

-- Fix problema iva di vendita preselezionata
ALTER TABLE `mg_articoli` CHANGE `idiva_vendita` `idiva_vendita` INT(11) NULL DEFAULT NULL;
UPDATE `mg_articoli` SET `idiva_vendita`=NULL WHERE `idiva_vendita`=0;

-- Impostazione per la modifica di altri tecnici nell'app
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Abilita la modifica di altri tecnici', '1', 'boolean', 1, 'Applicazione', 4, '');

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

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'icon_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 12, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'icon_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 12, 1, 0, 0, '', '', 0, 0, 0);

-- Fix massimale dichiarazione d'intento
ALTER TABLE `co_dichiarazioni_intento` CHANGE `massimale` `massimale` DECIMAL(15,6) NOT NULL; 