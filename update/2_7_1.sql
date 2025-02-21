-- Aggiornamento nome colonna Fornitore in Fornitore predefinito
UPDATE `zz_views` SET `name` = 'Fornitore predefinito' WHERE `zz_views`.`name` = 'Fornitore' AND  `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'); 
UPDATE `zz_views_lang` SET `zz_views_lang`.`title` = 'Fornitore predefinito' WHERE `zz_views_lang`.`id_record` = (SELECT `id` FROM `zz_views` WHERE `name` = 'Fornitore predefinito' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')) AND `zz_views_lang`.`id_lang` = 1; 
