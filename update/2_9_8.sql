ALTER TABLE `zz_oauth2` CHANGE `nome` `name` VARCHAR(255) NOT NULL; 

UPDATE `zz_views` SET `query` = 'name' WHERE `name` = 'Nome' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Accesso con OAuth');