-- Aggiornamento nome colonna Fornitore in Fornitore predefinito
UPDATE `zz_views` SET `name` = 'Fornitore predefinito' WHERE `zz_views`.`name` = 'Fornitore' AND  `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'); 
UPDATE `zz_views_lang` SET `zz_views_lang`.`title` = 'Fornitore predefinito' WHERE `zz_views_lang`.`id_record` = (SELECT `id` FROM `zz_views` WHERE `name` = 'Fornitore predefinito' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')) AND `zz_views_lang`.`id_lang` = 1; 

UPDATE `co_scadenziario` INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id` SET `co_scadenziario`.`id_pagamento` = `co_documenti`.`idpagamento`, `co_scadenziario`.`id_banca_azienda` = `co_documenti`.`id_banca_azienda`, `co_scadenziario`.`id_banca_controparte` = `co_documenti`.`id_banca_controparte` WHERE `co_scadenziario`.`id_pagamento` = 0;

-- Permetto valore NULL per campo options in zz_prints
ALTER TABLE `zz_prints` CHANGE `options` `options` TEXT NULL; 

-- Migrazione file settings di stampa su campo options a database
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Bilancio';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Libro giornale';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Inventario cespiti';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Inventario magazzino';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Prima nota';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Scadenzario';

-- Fix per vista duplicata relazione
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_relazioni_lang`.`title`' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'color_title_Relazione';
