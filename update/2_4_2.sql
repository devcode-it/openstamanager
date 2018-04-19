-- Gestione documentale 
 CREATE TABLE IF NOT EXISTS `zz_documenti_categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `zz_documenti` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcategoria` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


INSERT INTO `zz_documenti_categorie` (`id`, `descrizione`, `deleted`) VALUES
(NULL, 'Documenti societ&agrave;', 0),
(NULL, 'Contratti assunzione personale', 0);

-- Innesto modulo gestione documentale
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Gestione documentale', 'Gestione documentale', 'gestione_documentale', '{	"main_query": [	{	"type": "table", "fields": "Categoria, Nome, Data", "query": "SELECT id,(SELECT descrizione FROM zz_documenti_categorie WHERE zz_documenti_categorie.id = idcategoria) AS Categoria, zz_documenti.nome AS Nome, DATE_FORMAT( zz_documenti.`data`, ''%d/%m/%Y'' ) AS `Data` FROM zz_documenti  WHERE  `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 1=1"}	]}', '', 'fa fa-file-text-o', '2.4', '2.4', '1', NULL, '1', '1');

-- Innesto modulo categorie documenti
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Categorie documenti', 'Categorie documenti', 'categorie_documenti', '{	"main_query": [	{	"type": "table", "fields": "Descrizione", "query": "SELECT zz_documenti_categorie.`descrizione`as Descrizione, zz_documenti_categorie.`id`as id FROM zz_documenti_categorie WHERE deleted = 0 HAVING 1=1"}	]}', '', 'fa fa-file-text-o', '2.4', '2.4', '1', (SELECT `id` FROM `zz_modules` m WHERE `name` = 'Gestione documentale'), '1', '1');