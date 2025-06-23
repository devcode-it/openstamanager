-- Plugin barcode
-- Creazione tabella
CREATE TABLE IF NOT EXISTS `mg_articoli_barcode` (
	`id` int(4) NOT NULL AUTO_INCREMENT,
	`idarticolo` INT NOT NULL,
	`barcode` varchar(100) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB;

ALTER TABLE `mg_articoli_barcode` ADD CONSTRAINT `mg_articoli_barcode_ibfk_1` FOREIGN KEY (`idarticolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;

-- Creazione del plugin
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Articoli';
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options`, `directory`, `help`) VALUES ('Barcode', @id_module, @id_module, 'tab', '', '1', '0', '0', '2.*', '2.4.23', '{ "main_query": [{"type": "table", "fields": "Barcode", "query": "SELECT mg_articoli_barcode.id, mg_articoli_barcode.barcode AS Barcode FROM mg_articoli_barcode WHERE 1=1 AND mg_articoli_barcode.idarticolo=|id_parent| HAVING 2=2 ORDER BY barcode ASC"}]}', 'barcode_articoli', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, LAST_INSERT_ID(), 'Barcode'),
  (2, LAST_INSERT_ID(), 'Barcode');

INSERT INTO `mg_articoli_barcode` (`idarticolo`, `barcode`) (SELECT `mg_articoli`.`id`, `mg_articoli`.`barcode` FROM `mg_articoli` WHERE `mg_articoli`.`barcode` IS NOT NULL AND `mg_articoli`.`barcode` != '');

-- Aggiorno la query del modulo Articoli
UPDATE `zz_modules` SET `options` = 'SELECT\r\n |select|\r\nFROM\r\n `mg_articoli`\r\n LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)\r\n LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`\r\n LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`\r\n LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_impegnata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = \'entrata\' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`\r\n LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_ordinata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = \'uscita\' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1\r\n GROUP BY `idarticolo`) `ordini_fornitore` ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`\r\n LEFT JOIN `zz_categorie` ON `mg_articoli`.`id_categoria` = `zz_categorie`.`id`\r\n LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)\r\n LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`\r\n LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)\r\n LEFT JOIN (SELECT `co_iva`.`percentuale` AS `perc`, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id`=`zz_settings`.`valore`)AS iva ON `iva`.`nome`= \'Iva predefinita\' \r\n LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`\r\n LEFT JOIN (SELECT CASE WHEN MIN(`differenza`) < 0 THEN -1 WHEN MAX(`threshold_qta`) > 0 THEN 1 ELSE 0 END AS `stato_giacenza`, `idarticolo` FROM (SELECT SUM(`mg_movimenti`.`qta`) - COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `differenza`, COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) as `threshold_qta`, `mg_movimenti`.`idarticolo` FROM `mg_movimenti` LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_sede` = `mg_movimenti`.`idsede` AND `mg_scorte_sedi`.`id_articolo` = `mg_movimenti`.`idarticolo` GROUP BY `mg_movimenti`.`idarticolo`, `mg_movimenti`.`idsede`) AS `subquery` \r\n GROUP BY `idarticolo`) AS `giacenze` ON `giacenze`.`idarticolo` = `mg_articoli`.`id`\r\n LEFT JOIN (SELECT CASE WHEN COUNT(`mg_articoli_barcode`.`barcode`) <= 2 THEN GROUP_CONCAT(`mg_articoli_barcode`.`barcode` SEPARATOR \'<br />\') ELSE CONCAT((SELECT GROUP_CONCAT(`b1`.`barcode` SEPARATOR \'<br />\') FROM (SELECT `barcode` FROM `mg_articoli_barcode` `b2` WHERE `b2`.`idarticolo` = `mg_articoli_barcode`.`idarticolo` ORDER BY `b2`.`barcode` ASC LIMIT 2) `b1`), \'<br />...\') END AS `lista`, `mg_articoli_barcode`.`idarticolo` FROM `mg_articoli_barcode` GROUP BY `idarticolo`) AS `barcode` ON `barcode`.`idarticolo` = `mg_articoli`.`id`\r\nWHERE\r\n 1=1 AND `mg_articoli`.`deleted_at` IS NULL\r\nGROUP BY\r\n `mg_articoli`.`id`\r\nHAVING\r\n 2=2\r\nORDER BY\r\n `mg_articoli_lang`.`title`', `options2` = '' WHERE `zz_modules`.`id` = @id_module;

-- Aggiorno la vista barcode nella scheda articolo
UPDATE `zz_views` SET `query`='`barcode`.`lista`', `html_format`=1 WHERE `name` = 'barcode' AND `id_module` = @id_module;

-- Gestione barcode nelle righe dei documenti
ALTER TABLE `co_righe_contratti` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `co_righe_preventivi` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `or_righe_ordini` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `co_righe_documenti` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `dt_righe_ddt` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `in_righe_interventi` ADD `barcode` VARCHAR(100) NULL DEFAULT NULL;