-- Aggiunto colore in per gruppi utenti
ALTER TABLE `zz_groups` ADD `theme` VARCHAR(25) NULL AFTER `id_module_start`;

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'Modulo iniziale', '`module`.`title`', '5', '1', '0', '0', '0', '', '', '1', '0', '0');

-- Aggiunta colonna tema in utenti e permessi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name = 'Utenti e permessi'), '_bg_', 'zz_groups.theme', '4', '0', '0', '1', '0', '', '', '0', '0', '0');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'Tema', '`zz_groups`.`theme`', '4', '1', '0', '0', '0', '', '', '1', '0', '0'); 

-- Creazione modulo Login
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Accesso con OAuth', 'Accesso con OAuth', 'oauth', 'SELECT |select| FROM zz_oauth2 WHERE 1=1 AND is_login=1 HAVING 2=2', '', 'fa fa-angle-right', '1.0', '2.4.54', '100', (SELECT `t`.`id` FROM `zz_modules` AS `t` WHERE `t`.`name` = 'Tabelle'), '1', '1', '0', '0');

ALTER TABLE `zz_oauth2` ADD `nome` VARCHAR(255) NOT NULL AFTER `id`;
ALTER TABLE `zz_oauth2` ADD `is_login` BOOLEAN NOT NULL AFTER `after_configuration`;
ALTER TABLE `zz_oauth2` ADD `enabled` BOOLEAN NOT NULL DEFAULT TRUE AFTER `is_login`;

-- Viste modulo Oauth
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Accesso con OAuth'), 'id', 'id', 0, 1, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Accesso con OAuth'), 'Nome', 'nome', 1, 1, 0, 0, '', '', 1, 0, 0);

INSERT INTO `zz_oauth2` (`nome`, `class`, `client_id`, `client_secret`, `config`, `state`, `access_token`, `refresh_token`, `after_configuration`, `is_login`, `enabled`) VALUES
('Microsoft', 'Modules\\Emails\\OAuth2\\MicrosoftLogin', '', '', '{\"tenant_id\":\"consumers\"}', '', NULL, NULL, '', 1, 0);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '(righe.totale_imponibile + righe.iva + `co_documenti`.`rivalsainps`) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Totale documento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '(righe.totale_imponibile + IF( co_documenti.split_payment = 0, round(righe.iva, 2), 0) + `co_documenti`.`rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale` - IF(`co_documenti`.`id_ritenuta_contributi` != 0, (( `righe`.`totale_imponibile` * `co_ritenuta_contributi`.`percentuale_imponibile` / 100) / 100 * `co_ritenuta_contributi`.`percentuale`), 0)) *(1 - `co_documenti`.`sconto_finale_percentuale` / 100 ) * IF( co_tipidocumento.reversed, -1, 1)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Netto a pagare';


ALTER TABLE `co_promemoria` ADD `data_scadenza` DATETIME NULL AFTER `data_richiesta`;
ALTER TABLE `co_promemoria` ADD `idtecnici` VARCHAR(255) NOT NULL AFTER `idimpianti`;

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Secondi timeout tavoletta Wacom", '15', 'text', 1, 'Tavoletta Wacom', '6', 'Definisce il numero di secondi prima del timeout della tavoletta Wacom');