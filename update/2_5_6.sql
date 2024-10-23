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

-- Rimozione impostazioni deprecate per sezionale predefinito autofatture
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Sezionale per autofatture di vendita';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Sezionale per autofatture di acquisto';

-- Aggiunta flag is_autofattura, is_nota_debito e is_nota_credito in fe_tipi_documento
ALTER TABLE `fe_tipi_documento` ADD `is_autofattura` INT NOT NULL DEFAULT '0' AFTER `name`, ADD `is_nota_credito` INT NOT NULL DEFAULT '0' AFTER `is_autofattura`, ADD `is_nota_debito` INT NOT NULL DEFAULT '0' AFTER `is_nota_credito`; 
UPDATE `fe_tipi_documento` SET `is_nota_credito` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD04'; 
UPDATE `fe_tipi_documento` SET `is_nota_debito` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD05'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD16'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD17'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD18'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD19'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD20'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD21'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD28';