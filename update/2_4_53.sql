-- Aggiunta impostazione per visualizzare riferimento su ogni riga in stampa
UPDATE `zz_settings` SET `zz_settings`.`nome` = 'Visualizza riferimento su ogni riga in stampa', `help` = "Se disabilitato, raggruppa il riferimento ai documenti collegati in un\'unica riga, se abilitato riporta i riferimenti ai documenti in ogni riga." WHERE `zz_settings`.`nome` = "Riferimento dei documenti nelle stampe";

-- Correzioni Automezzi
UPDATE `zz_views` SET `query` = 'IFNULL(an_sedi.nome,an_sedi.nomesede)', `order` = '1' WHERE `zz_views`.`name` = 'Nome' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Automezzi');

-- Correzione vista riferimenti DDT in ordini cliente
UPDATE `zz_modules` SET `options` = '\r\nSELECT\r\n    |select|\r\nFROM\r\n	`or_ordini`\r\n    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\r\n    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\r\n    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\r\n    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`\r\n    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT \'Fattura \',`co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`, `co_righe_documenti`.`original_document_id` AS `idordine` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`=\'Modules\\\\Ordini\\\\Ordine\' GROUP BY original_document_id) AS `fattura` ON `fattura`.`idordine` = `or_ordini`.`id`\r\n    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT \'DDT \', `dt_ddt`.`numero_esterno` SEPARATOR \', \') AS `info`, `dt_righe_ddt`.`original_document_id` AS `idddt` FROM `dt_ddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id`=`dt_righe_ddt`.`idddt` WHERE `original_document_type`=\'Modules\\\\Ordini\\\\Ordine\' GROUP BY original_document_id) AS `ddt` ON `ddt`.`idddt`=`or_ordini`.`id`\r\n    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = \'Ordini cliente\') AND `zz_operations`.`op` = \'send-email\' GROUP BY id_record) AS `email` ON `email`.`id_record` = `or_ordini`.`id`\r\nWHERE\r\n    1=1 |segment(`or_ordini`.`id_segment`)| AND `dir` = \'entrata\'  |date_period(`or_ordini`.`data`)|\r\nHAVING\r\n    2=2\r\nORDER BY \r\n	`data` DESC, \r\n    CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';

-- Correzione campo icon_title_Inviata in vista Fatture di vendita
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'Inviata via email\', \'\')' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_Inviata';

-- Aggiunta colonna email in anagrafiche di default nascosta
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name = 'Anagrafiche'), 'Email', '`an_anagrafiche`.`email`', '17', '1', '0', '0', '0', '', '', '0', '0', '0'); 

-- Modifica valore di default di Cifre decimali per quantità in stampa
UPDATE `zz_settings` SET `valore` = '2' WHERE `zz_settings`.`nome` = 'Cifre decimali per quantità in stampa';

-- Fix campo data in listini
ALTER TABLE `mg_listini_articoli` CHANGE `data_scadenza` `data_scadenza` DATE NULL;

-- Aggiunta importazione listini cliente
INSERT INTO `zz_imports` (`name`, `class`) VALUES ('Listini cliente', 'Modules\\ListiniCliente\\Import\\CSV');

-- Aggiunta impostazione per definire il listino cliente predefinito
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`, `help`) VALUES (NULL, 'Listino cliente predefinito', '', 'query=SELECT id, nome AS descrizione FROM `mg_listini` ORDER BY descrizione ASC', '1', 'Generali', NULL, NULL, 'In fase di creazione anagrafica cliente collega il listino all\'anagrafica stessa');

-- Aggiunta impostazione per importazione serial di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Creazione seriali in import FE", '1', 'boolean', 1, 'Fatturazione Elettronica', '16', "Determina il valore predefinito dell'impostazione Creazione seriali in fase di importazione di una fattura elettronica");
