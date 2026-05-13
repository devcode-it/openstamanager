UPDATE `zz_views` SET `query` = '`zz_marche`.`name`' WHERE `name` = 'Marche' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');
UPDATE `zz_views` SET `query` = '`modello`.`name`' WHERE `name` = 'Modello' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');

-- Aggiunta chiave esterna listini in anagrafiche
ALTER TABLE `an_anagrafiche` CHANGE `id_listino` `id_listino` INT NULL; 
UPDATE `an_anagrafiche` SET `id_listino` = null WHERE `id_listino` = 0; 
UPDATE `an_anagrafiche` SET `id_listino` = null WHERE `id_listino` NOT IN (SELECT `id` FROM `mg_listini`);
ALTER TABLE `an_anagrafiche` ADD CONSTRAINT `an_anagrafiche_ibfk_4` FOREIGN KEY (`id_listino`) REFERENCES `mg_listini`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT; 

-- Correzione vista modulo Tipi scadenze
UPDATE `zz_views` SET `query` = '`co_tipi_scadenze_lang`.`title`' WHERE `zz_views`.`name` = 'Nome' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi scadenze'); 
DELETE FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi scadenze'); 

-- Fix colonna mancante dt_statiddt
ALTER TABLE `dt_statiddt` ADD `can_delete` INT NOT NULL DEFAULT '1' AFTER `is_fatturabile`; 
UPDATE `dt_statiddt` SET `can_delete` = 0 WHERE `name` IN ('Bozza', 'Evaso', 'Fatturato', 'Parzialmente fatturato', 'Parzialmente evaso');

-- Fix riferimenti plugin
UPDATE `zz_plugins` SET `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = "Fatture di acquisto") WHERE (`zz_plugins`.`name` = "Fatturazione elettronica" AND `idmodule_to` = (SELECT `id` FROM `zz_modules` WHERE `name` = "Fatture di acquisto"));
UPDATE `zz_plugins` SET `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = "Anagrafiche") WHERE (`zz_plugins`.`name` = "Dichiarazioni d'intento");

-- Aggiunta campo per tracciare il numero di tentativi di invio FE
ALTER TABLE `co_documenti` ADD `fe_attempt` INT NOT NULL DEFAULT '0' AFTER `hook_send`;

-- Aggiunta campo per tracciare la data di fallimento definitivo dopo 3 tentativi
ALTER TABLE `co_documenti` ADD `fe_failed_at` TIMESTAMP NULL AFTER `fe_attempt`; 