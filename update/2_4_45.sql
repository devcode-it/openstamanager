-- Aggiunto import Preventivi
INSERT INTO `zz_imports` (`id`, `id_module`, `name`, `class`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Preventivi'), 'Preventivi', 'Modules\\Preventivi\\Import\\CSV', NULL);

-- Modifica nomi colonne Totali
UPDATE `zz_views` SET `name` = 'Totale documento' WHERE `name` = 'Totale ivato';
UPDATE `zz_views` SET `name` = 'Imponibile' WHERE `name` = 'Totale';

-- Fix query Preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'icon_Inviata', 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 16, 1, 0, 0, 1, 0);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'icon_title_Inviata', 'IF(emails IS NOT NULL, \'Inviato via email\', \'\')', 17, 1, 0, 0, 0, 0);

-- Fix query vista Attività
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'icon_Inviata';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'Inviata via email\', \'\')' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'icon_title_Inviata';

-- Fix query Ordini cliente
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'icon_Inviata';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`name` = 'icon_Inviato' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'icon_Inviata';

-- Fix query Ordini fornitore
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'icon_Inviata';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`name` = 'icon_Inviato' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'icon_Inviata';

-- Aggiornamento data ultima sessione in rapportino intervento
UPDATE `em_templates` SET `body` = '<p>Gentile Cliente,</p>\n<p>inviamo in allegato il rapportino numero {numero} del {data fine intervento}.</p>\n<p>Distinti saluti</p>', `subject` = 'Invio rapportino numero {numero} del {data fine intervento}' WHERE `em_templates`.`name` = "Rapportino intervento"; 

-- Aggiunta stampa liquidazione provvigioni
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`, `available_options`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 1, 'Provvigioni', 'Provvigioni', 'Provvigioni {ragione_sociale}', 'provvigione', 'idanagrafica', '', 'fa fa-print', '', '', 0, 0, 0, 1, NULL);

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ( "Visualizza solo promemoria assegnati", '0', 'boolean', '1', 'Applicazione', '7', 'Se abilitata permetti ai tecnici la visualizzazione dei soli promemoria in cui risultano come assegnati');
