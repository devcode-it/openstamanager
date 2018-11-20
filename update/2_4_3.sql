-- Fix nome colonna 'Cliente' ddt vendita e acquisto
UPDATE `zz_views` SET `name` = 'Ragione sociale' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita') AND name = 'Cliente';
UPDATE `zz_views` SET `name` = 'Ragione sociale' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto') AND name = 'Cliente';