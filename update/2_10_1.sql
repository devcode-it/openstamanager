-- Allineamento vista Articoli
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_impegnata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'entrata' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_ordinata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'uscita' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) `ordini_fornitore` ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN `zz_categorie` ON `mg_articoli`.`id_categoria` = `zz_categorie`.`id`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT `co_iva`.`percentuale` AS `perc`, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id` = `zz_settings`.`valore`) AS iva ON `iva`.`nome` = 'Iva predefinita'
    LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT CASE WHEN MIN(`qta_attuale`) < MIN(`threshold_qta`) AND MIN(`threshold_qta`) > 0 THEN -1 WHEN MIN(`qta_attuale`) >= MIN(`threshold_qta`) AND MIN(`threshold_qta`) > 0 THEN 1 ELSE 0 END AS `stato_giacenza`, `idarticolo` FROM (SELECT COALESCE(SUM(`mg_movimenti`.`qta`), 0) AS `qta_attuale`, COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `threshold_qta`, `mg_movimenti`.`idarticolo` FROM `mg_movimenti` LEFT JOIN `mg_scorte_sedi` ON (`mg_scorte_sedi`.`id_sede` = `mg_movimenti`.`idsede` AND `mg_scorte_sedi`.`id_articolo` = `mg_movimenti`.`idarticolo`) GROUP BY `mg_movimenti`.`idarticolo`, `mg_movimenti`.`idsede`) AS `subquery` WHERE `idarticolo` IS NOT NULL GROUP BY `idarticolo`) AS `giacenze` ON `giacenze`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(`mg_articoli_barcode`.`barcode` SEPARATOR '<br />') AS `lista`, `mg_articoli_barcode`.`idarticolo` FROM `mg_articoli_barcode` GROUP BY `mg_articoli_barcode`.`idarticolo`) AS `barcode` ON `barcode`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN `zz_marche` ON `mg_articoli`.`id_marca` = `zz_marche`.`id`
    LEFT JOIN `zz_marche` as `modello` ON `mg_articoli`.`id_modello` = `modello`.`id`
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `name` = 'Articoli';

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
