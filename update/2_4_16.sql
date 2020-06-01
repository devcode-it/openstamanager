UPDATE `zz_views` SET `search` = 1 WHERE `zz_views`.`name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie documenti');
UPDATE `zz_views` SET `search` = 1 WHERE `zz_views`.`name` IN ('Categoria', 'Nome', 'Data') AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale');


-- Aggiunta rivalsa inps e relativa iva per il calcolo del totale ivato del documento
UPDATE `zz_views` SET `query` = 'righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Totale ivato';

UPDATE `zz_views` SET `query` = 'righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Totale ivato';

-- Aggiunta campi righe contratti --
ALTER TABLE `co_righe_contratti` ADD `original_id` INT(11) NULL DEFAULT NULL AFTER `abilita_serial` , ADD `original_type` VARCHAR(255) NULL DEFAULT NULL AFTER `original_id`;