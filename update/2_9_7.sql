-- Ripristino campi firma
ALTER TABLE `in_interventi` ADD `firma_data` DATETIME NULL , ADD `firma_nome` VARCHAR(255) NOT NULL;

UPDATE `zz_views` SET `query` = '`sottocategorie_lang`.`title`' WHERE `zz_views`.`name` = 'Sottocategoria' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti');