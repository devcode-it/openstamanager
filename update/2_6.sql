-- Aggiunta Marchio articolo
ALTER TABLE `mg_articoli` ADD `id_marchio` INT NULL DEFAULT NULL;

CREATE TABLE `mg_marchi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `link` varchar(255) NOT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`)) ENGINE = InnoDB; 

INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Marchi', 'marchi', 'SELECT |select| FROM `mg_marchi` WHERE 1=1 HAVING 2=2 ORDER BY `mg_marchi`.`name`', '', 'fa fa-angle-right', '2.5.6', '2.5.6', '7', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Tabelle'), '1', '1', '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES ('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi'), 'Marchi');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi'), 'id', 'mg_marchi.id', '0', '0', '0', '0', '0', '', '', '0', '0', '0'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi'), 'Nome', 'mg_marchi.name', '1', '0', '0', '0', '0', '', '', '1', '0', '0'),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi'), 'Link', 'mg_marchi.link', '2', '0', '0', '0', '0', '', '', '1', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi')), 'id'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Nome' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi')), 'Nome'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Link' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Marchi')), 'Link');

-- Aggiunta modulo Stati dei DDT
ALTER TABLE `dt_statiddt` ADD `deleted_at` timestamp NULL DEFAULT NULL;

INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Stati DDT', 'stati_ddt', 'SELECT |select| FROM `dt_statiddt` LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.|lang|) WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2', '', 'fa fa-circle-o', '2.5.6', '2.5.6', '7', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Tabelle'), '1', '1', '1', '1');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES ('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'Stati dei DDT');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'Fatturabile', 'IF(is_fatturabile, \'S&igrave;\', \'No\')', '6', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'Completato', 'IF(completato, \'S&igrave;\', \'No\')', '5', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'Icona', 'icona', '3', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'Descrizione', '`dt_statiddt_lang`.`title`', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'id', '`dt_statiddt`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'), 
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT'), 'color_Colore', 'colore', '7', '0', '0', '1', '0', '', '', '1', '0', '0', '0'); 

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'color_Colore' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'color_Color'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'color_Colore' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'color_Colore'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'id'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'id'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Description'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Descrizione'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Icona' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Icon'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Icona' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Icona'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Completato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Completed'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Completato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Completato'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fatturabile' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Invoiceable'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fatturabile' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati DDT')), 'Fatturabile');