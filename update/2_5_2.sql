-- Aggiunta tabella in_tags
CREATE TABLE `in_tags` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL
);

ALTER TABLE `in_tags`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `in_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- Aggiunta modulo Tags
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Tags', 'tags', 'SELECT |select| FROM `in_tags` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.5.2', '2.5.2', '2', (SELECT `id` FROM `zz_modules` as b WHERE `name` = 'Tabelle'), '1', '1', '1', '0');
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES ('1', (SELECT MAX(id) FROM `zz_modules`), 'Tags');

-- Aggiunta viste Tags
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags'), 'id', 'id', 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags'), 'Nome','name', 1);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`)-1 FROM `zz_views` ), 'id'),
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Nome'),
(2, (SELECT MAX(`id`)-1 FROM `zz_views` ), 'id'),
(2, (SELECT MAX(`id`) FROM `zz_views` ), 'Name');

-- Aggiunta tabella in_interventi_tags
CREATE TABLE `in_interventi_tags` (
  `id` int NOT NULL,
  `id_intervento` int NOT NULL,
  `id_tag`int NOT NULL
);

ALTER TABLE `in_interventi_tags`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `in_interventi_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`files`.`name` != \'\', `files`.`name`, \'No\')' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Allegati';

-- Aggiunta colonna Tags in Attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Tags', 'tags.nomi', 10);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Tags'),
(2, (SELECT MAX(`id`) FROM `zz_views` ), 'Tags');

-- Aggiunta flag per calcolo media su viste
ALTER TABLE `zz_views` ADD `avg` BOOLEAN NOT NULL DEFAULT FALSE AFTER `summable`;

-- Aggiunta tabella my_impianti_marche_lang
CREATE TABLE IF NOT EXISTS `my_impianti_marche_lang` (
    `id` int NOT NULL,
    `id_lang` int NOT NULL,
    `id_record` int NOT NULL,
    `title` VARCHAR(255) NOT NULL
);

ALTER TABLE `my_impianti_marche_lang`
    ADD PRIMARY KEY (`id`);

ALTER TABLE `my_impianti_marche_lang`
    MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `my_impianti_marche_lang` ADD CONSTRAINT `my_impianti_marche_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `my_impianti_marche`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `my_impianti_marche_lang` ADD CONSTRAINT `my_impianti_marche_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

ALTER TABLE `my_impianti_marche` DROP `nota`;

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `my_impianti_marche` LEFT JOIN `my_impianti_marche_lang` ON (my_impianti_marche.id = my_impianti_marche_lang.id_record AND my_impianti_marche_lang.|lang|) WHERE 1=1 AND parent = 0 HAVING 2=2' WHERE `zz_modules`.`name` = 'Marche impianti'; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'my_impianti_marche.id' WHERE `zz_modules`.`name` = 'Marche impianti' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'my_impianti_marche_lang.title' WHERE `zz_modules`.`name` = 'Marche impianti' AND `zz_views`.`name` = 'Nome';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(pagato = da_pagare, \'#CCFFCC\', IF(data_concordata IS NOT NULL AND data_concordata != \'0000-00-00\', IF(data_concordata < NOW(), \'#ec5353\', \'#b3d2e3\'), IF(scadenza < NOW(), \'#f08080\', IF(DATEDIFF(co_scadenziario.scadenza,NOW()) < 10, \'#f9f9c6\', \'\'))))' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = '_bg_';

-- Aggiunta colonna Data concordata in Scadenzario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Data concordata', 'IF(data_concordata IS NOT NULL AND data_concordata != \'0000-00-00\', data_concordata, \'\')', 21, 1);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Data concordata'),
(2, (SELECT MAX(`id`) FROM `zz_views` ), 'Agreed date');

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`dup`.`numero_esterno` IS NOT NULL, \'#ec5353\', co_statidocumento.colore)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = '_bg_';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(em_emails.sent_at IS NULL, IF(em_emails.failed_at IS NULL, \'#f9f9c6\', \'#ec5353\'), \'#CCFFCC\')' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = '_bg_';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`d`.`conteggio`>1, \'#ec5353\', co_statidocumento.colore)' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = '_bg_';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(threshold_qta!=0, IF(mg_articoli.qta>=threshold_qta, \'#CCFFCC\', \'#ec5353\'), \'\')' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = '_bg_';

-- Aggiunta colonna Marca in Impianti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Marca', '`marca_lang`.`title`', 11, 0);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Marca'),
(2, (SELECT MAX(`id`) FROM `zz_views` ), 'Brand');

-- Aggiunta colonna Modello in Impianti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Modello', '`modello_lang`.`title`', 12, 0);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Modello'),
(2, (SELECT MAX(`id`) FROM `zz_views` ), 'Model');

-- Opzioni possibili per stampa "Contratto"
UPDATE `zz_prints` SET `available_options` = '{\"pricing\":\"Visualizzare i prezzi\", \"hide-total\": \"Nascondere i totali delle righe\", \"show-only-total\": \"Visualizzare solo i totali del documento\", \"hide-header\": \"Nascondere intestazione\", \"hide-footer\": \"Nascondere footer\", \"last-page-footer\": \"Visualizzare footer solo su ultima pagina\", \"hide-item-number\": \"Nascondere i codici degli articoli\"}' WHERE `zz_prints`.`name` = 'Contratto';

-- Aggiornamento icone per AdminLTE 3
UPDATE `zz_modules` SET `icon` = "fa fa-circle-o" WHERE `icon` = "fa fa-angle-right";

-- Modifica valore colori
ALTER TABLE `zz_widgets` CHANGE `bgcolor` `bgcolor` VARCHAR(100) NULL DEFAULT NULL; 

UPDATE `zz_widgets` SET `bgcolor` = 
  CASE
    WHEN `bgcolor` = "#ff7e00" OR `bgcolor` = "#f2bd00" THEN "warning"
    WHEN `bgcolor` = "#c62f2a" OR `bgcolor` = "#c2464c" OR `bgcolor` = "#a15d2d" THEN "danger"
    WHEN `bgcolor` = "#f4af1b" THEN "warning"
    WHEN `bgcolor` = "#f2bd00" THEN "primary"
    WHEN `bgcolor` = "#37a02d" OR `bgcolor` = "#4ccc4c" OR `bgcolor` = "#4dc347" OR `bgcolor` = "#6dab3c" THEN "success"
    WHEN `bgcolor` = "#2d70a1" OR `bgcolor` = "#44aae4" THEN "maroon"
    WHEN `bgcolor` = "#45a9f1" OR `bgcolor` = "#00c0ef" OR `bgcolor` = "#2deded" THEN "info"
    WHEN `bgcolor` = "#cccccc" THEN "gray"
    ELSE `bgcolor`
  END
WHERE `bgcolor` IN ("#ff7e00", "#f2bd00", "#c62f2a", "#c2464c", "#a15d2d", "#f4af1b", "#f2bd00", "#37a02d", "#4ccc4c", "#4dc347", "#6dab3c", "#2d70a1", "#44aae4", "#45a9f1", "#00c0ef", "#2deded", "#cccccc");

UPDATE `zz_widgets` set `bgcolor` = 'info' WHERE `name` = 'Note interne';
UPDATE `zz_widgets` set `bgcolor` = 'info' WHERE `name` = 'Rate contrattuali';
UPDATE `zz_widgets` set `bgcolor` = 'gray' WHERE `name` = 'Stampa calendario';
UPDATE `zz_widgets` set `bgcolor` = 'gray' WHERE `name` = 'Stampa calendario settimanale';
UPDATE `zz_widgets` set `bgcolor` = 'success' WHERE `name` = 'Attività confermate';
UPDATE `zz_widgets` set `bgcolor` = 'success' WHERE `name` = 'Preventivi in lavorazione';
UPDATE `zz_widgets` set `bgcolor` = 'warning' WHERE `name` = 'Attività da pianificare';
UPDATE `zz_widgets` set `bgcolor` = 'warning' WHERE `name` = 'Attività nello stato da programmare';

-- Opzioni possibili per stampa della Fattura
UPDATE `zz_prints` SET `available_options` = '{\"pricing\":\"Visualizzare i prezzi\", \"hide-total\": \"Nascondere i totali delle righe\", \"show-only-total\": \"Visualizzare solo i totali del documento\", \"hide-header\": \"Nascondere intestazione\", \"hide-footer\": \"Nascondere footer\", \"last-page-footer\": \"Visualizzare footer solo su ultima pagina\", \"rows-per-page\": \"Definire il numero di righe per pagina\"}' WHERE `zz_prints`.`name` = 'Fattura di vendita';
UPDATE `zz_prints` SET `available_options` = '{\"pricing\":\"Visualizzare i prezzi\", \"hide-total\": \"Nascondere i totali delle righe\", \"show-only-total\": \"Visualizzare solo i totali del documento\", \"hide-header\": \"Nascondere intestazione\", \"hide-footer\": \"Nascondere footer\", \"last-page-footer\": \"Visualizzare footer solo su ultima pagina\", \"rows-per-page\": \"Definire il numero di righe per pagina\"}' WHERE `zz_prints`.`name` = 'Fattura di vendita (senza intestazione)';

-- Fix plugin Statistiche vendita
UPDATE `zz_plugins` SET `options` = '{\"main_query\": [{\"type\": \"table\", \"fields\": \"Articolo, Q.tà, Percentuale tot., Totale\", \"query\": \"SELECT (SELECT `id` FROM `zz_modules` WHERE `name` = \'Articoli\') AS _link_module_, mg_articoli.id AS _link_record_, ROUND(SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)),2) AS `Q.tà`, ROUND((SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) * 100 / (SELECT SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir=\'entrata\' )),2) AS \'Percentuale tot.\', ROUND(SUM(IF(reversed=1, -(co_righe_documenti.subtotale - co_righe_documenti.sconto), (co_righe_documenti.subtotale - co_righe_documenti.sconto))),2) AS Totale, mg_articoli.id, CONCAT(mg_articoli.codice,\' - \',mg_articoli_lang.title) AS Articolo FROM co_documenti INNER JOIN co_statidocumento ON co_statidocumento.id = co_documenti.idstatodocumento INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id LEFT JOIN co_statidocumento_lang ON  (co_statidocumento.id = co_statidocumento_lang.id_record AND co_statidocumento_lang.id_lang = 1) INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo LEFT JOIN mg_articoli_lang ON (mg_articoli.id = mg_articoli_lang.id_record AND mg_articoli_lang.id_lang = 1) WHERE 1=1 AND co_tipidocumento.dir=\'entrata\' AND (co_statidocumento_lang.title = \'Pagato\' OR co_statidocumento_lang.title = \'Parzialmente pagato\' OR co_statidocumento_lang.title = \'Emessa\' ) |date_period(`co_documenti`.`data`)| GROUP BY co_righe_documenti.idarticolo HAVING 2=2 ORDER BY SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) DESC\"}]}' WHERE `zz_plugins`.`name` = 'Statistiche vendita';

-- Fix segmento Articoli disponibili
UPDATE `zz_segments` SET `clause` = '1=1 AND `qta` > 0' WHERE `zz_segments`.`name` = 'Disponibili';

-- Ridimensionamento immagini
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES 
('Ridimensiona automaticamente le immagini caricate', '0', 'boolean', '1', 'Generali', NULL),
('Larghezza per ridimensionamento immagini', '600', 'integer', '1', 'Generali', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Ridimensiona automaticamente le immagini caricate'), 'Ridimensiona automaticamente le immagini caricate', ''),
('1', (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Larghezza per ridimensionamento immagini'), 'Larghezza per ridimensionamento immagini', ''),
('2', (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Ridimensiona automaticamente le immagini caricate'), 'Auto resize uploaded images', ''),
('2', (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Larghezza per ridimensionamento immagini'), 'Resizing images width', '');

-- Allineamento icone
UPDATE `zz_modules` SET `icon` = "fa fa-circle-o" WHERE `parent` = (SELECT `id` FROM (SELECT `id` FROM `zz_modules` WHERE `name` = "Gestione email") AS b);
UPDATE `zz_modules` SET `icon` = CONCAT ('nav-icon ', icon)