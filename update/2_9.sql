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

-- Disattivo la vista barcode nella scheda articolo in attesa di modificarla
UPDATE `zz_views` SET `visible`=0 WHERE `name` = 'barcode' AND `id_module` = @id_module;