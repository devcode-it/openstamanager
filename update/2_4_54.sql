-- Aggiunto colore in per gruppi utenti
ALTER TABLE `zz_groups` ADD `theme` VARCHAR(25) NULL AFTER `id_module_start`;

-- Aggiunta colonna modulo iniziale in utenti e permessi
UPDATE `zz_modules` SET `options` = 'SELECT\n |select|\nFROM \n `zz_groups` \n LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`\n LEFT JOIN (SELECT `zz_modules`.`title`, `zz_modules`.`id` FROM `zz_modules`) AS `module` ON `module`.`id`=`zz_groups`.`id_module_start`\nWHERE \n 1=1\nHAVING \n 2=2 \nORDER BY \n `id`, \n `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi'; 

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

-- Allineamento query Fatture di vendita
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento` FROM `co_movimenti`  WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(id) as `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN(SELECT `id` FROM `zz_modules` WHERE name = 'Fatture di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
	LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
	LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN( SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata') AND `numero_esterno` != '' GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento` HAVING COUNT(`numero_esterno`) > 1 |date_period(`co_documenti`.`data`)| ) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1=1 AND `dir` = 'entrata' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Fatture di vendita';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '(righe.totale_imponibile + righe.iva + `co_documenti`.`rivalsainps`) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Totale documento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '(righe.totale_imponibile + IF( co_documenti.split_payment = 0, round(righe.iva, 2), 0) + `co_documenti`.`rivalsainps` - `co_documenti`.`ritenutaacconto` - `co_documenti`.`sconto_finale` - IF(`co_documenti`.`id_ritenuta_contributi` != 0, (( `righe`.`totale_imponibile` * `co_ritenuta_contributi`.`percentuale_imponibile` / 100) / 100 * `co_ritenuta_contributi`.`percentuale`), 0)) *(1 - `co_documenti`.`sconto_finale_percentuale` / 100 ) * IF( co_tipidocumento.reversed, -1, 1)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Netto a pagare';
