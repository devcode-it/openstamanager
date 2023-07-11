-- Ottimizzazione query vista Scadenzario
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_banche`.`nome`' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Banca azienda';
