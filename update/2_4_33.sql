-- Nuovo modulo "Fasce orarie"
CREATE TABLE IF NOT EXISTS `in_fasceorarie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `giorni` varchar(255) DEFAULT NULL,
  `ora_inizio` time DEFAULT NULL,
  `ora_fine` time DEFAULT NULL,
  `can_delete` BOOLEAN NOT NULL DEFAULT TRUE,
  `include_bank_holidays` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Fasce orarie', 'Fasce orarie', 'fasce_orarie', 'SELECT |select| FROM `in_fasceorarie` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.32', '2.4.32', '1', (SELECT id FROM zz_modules t WHERE t.name = 'Interventi'), '1', '1', '0', '0'); 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'id', 'in_fasceorarie.id', 1, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Nome', 'in_fasceorarie.nome', 2, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Festività', 'IF(in_fasceorarie.include_bank_holidays, ''S&igrave;'', ''No'')', 3, 1, 0, 1);



-- Fascia oraria "Ordinaria"
INSERT INTO `in_fasceorarie` (`id`, `nome`, `giorni`, `ora_inizio`, `ora_fine`, `can_delete`) VALUES (NULL, 'Ordinario', '1,2,3,4,5,6,7', '00:00', '23:59', '0'); 

-- Relazione fasca oraria / tipo intervento
CREATE TABLE IF NOT EXISTS `in_fasceorarie_tipiintervento` (
  `idfasciaoraria` int NOT NULL,
  `idtipointervento` int NOT NULL,
  `costo_orario` decimal(12,6) NOT NULL,
  `costo_km` decimal(12,6) NOT NULL,
  `costo_diritto_chiamata` decimal(12,6) NOT NULL,
  `costo_orario_tecnico` decimal(12,6) NOT NULL,
  `costo_km_tecnico` decimal(12,6) NOT NULL,
  `costo_diritto_chiamata_tecnico` decimal(12,6) NOT NULL,
  PRIMARY KEY (`idfasciaoraria`,`idtipointervento`),
  FOREIGN KEY (`idfasciaoraria`) REFERENCES `in_fasceorarie` (`id`),
  FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento` (`idtipointervento`),
  KEY `idtipointervento` (`idtipointervento`)
) ENGINE=InnoDB;


-- Nuovo modulo "Eventi"
CREATE TABLE IF NOT EXISTS `zz_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `id_nazione` int NOT NULL,
  `id_regione` int DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `is_bank_holiday` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni` (`id`)
) ENGINE=InnoDB;


INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Eventi', 'Eventi', 'eventi', 'SELECT |select| FROM `zz_events` INNER JOIN `an_nazioni` ON `an_nazioni`.id = `zz_events`.id_nazione WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.32', '2.4.32', '1', (SELECT id FROM zz_modules t WHERE t.name = 'Tabelle'), '1', '1', '0', '0');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'id', 'zz_events.id', 1, 0, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Nome', 'zz_events.nome', 2, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Nazione', 'an_nazioni.nome', 3, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Data', 'zz_events.data', 4, 1, 1, 1);

-- Natale
INSERT INTO `zz_events` (`id`, `nome`, `data`, `id_nazione`, `id_regione`, `is_recurring`, `is_bank_holiday`) VALUES (NULL, 'Natale', '2022-12-25', (SELECT id FROM an_nazioni WHERE nome = 'Italia'), NULL, '1', '1'); 

-- Fix ordine colonne Conto dare e Conto avere in Prima nota
UPDATE `zz_views` SET `order` = '8' WHERE `zz_views`.`name` = 'Conto dare';
UPDATE `zz_views` SET `order` = '9' WHERE `zz_views`.`name` = 'Conto avere';
UPDATE `zz_views` SET `order` = '20' WHERE `zz_views`.`name` = '_print_';
