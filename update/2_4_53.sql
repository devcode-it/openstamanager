-- Aggiunta impostazione per visualizzare riferimento su ogni riga in stampa
UPDATE `zz_settings` SET `zz_settings`.`nome` = 'Visualizza riferimento su ogni riga in stampa', `help` = "Se disabilitato, raggruppa il riferimento ai documenti collegati in un\'unica riga, se abilitato riporta i riferimenti ai documenti in ogni riga." WHERE `zz_settings`.`nome` = "Riferimento dei documenti nelle stampe";

-- Correzioni Automezzi
UPDATE `zz_views` SET `query` = 'IFNULL(an_sedi.nome,an_sedi.nomesede)', `order` = '1' WHERE `zz_views`.`name` = 'Nome' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Automezzi');

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
