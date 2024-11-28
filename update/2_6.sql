-- Aggiunta Marchio articolo
ALTER TABLE `mg_articoli` ADD `id_marchio` INT NULL DEFAULT NULL;

CREATE TABLE `mg_marchi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `link` varchar(255) NOT NULL,
    `deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`)) ENGINE = InnoDB; 

INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Marchi', 'marchi', 'SELECT |select| FROM `mg_marchi` WHERE 1=1 HAVING 2=2 ORDER BY `mg_marchi`.`name`', '', 'fa fa-angle-right', '2.6', '2.6', '7', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Tabelle'), '1', '1', '1', '1');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Marchi';
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES 
('1', @id_module, 'Marchi'),
('2', @id_module, 'Marchi');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Marchi';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'id', 'mg_marchi.id', '0', '0', '0', '0', '0', '', '', '0', '0', '0'),
(@id_module, 'Nome', 'mg_marchi.name', '1', '0', '0', '0', '0', '', '', '1', '0', '0'),
(@id_module, 'Link', 'mg_marchi.link', '2', '0', '0', '0', '0', '', '', '1', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Marchi';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Nome' AND `id_module` = @id_module), 'Nome'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Nome' AND `id_module` = @id_module), 'Name'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Link' AND `id_module` = @id_module), 'Link'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Link' AND `id_module` = @id_module), 'Link');

-- Aggiunta modulo Stati dei DDT
ALTER TABLE `dt_statiddt` ADD `deleted_at` timestamp NULL DEFAULT NULL;

INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Stati DDT', 'stati_ddt', 'SELECT |select| FROM `dt_statiddt` LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.|lang|) WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2', '', 'fa fa-circle-o', '2.6', '2.6', '7', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Tabelle'), '1', '1', '1', '1');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Stati DDT';
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES 
('1', @id_module, 'Stati dei DDT'),
('2', @id_module, 'Stati dei DDT');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Stati DDT';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES 
(@id_module, 'Fatturabile', 'IF(is_fatturabile, \'S&igrave;\', \'No\')', '6', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
(@id_module, 'Completato', 'IF(completato, \'S&igrave;\', \'No\')', '5', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
(@id_module, 'Icona', 'icona', '3', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
(@id_module, 'Descrizione', '`dt_statiddt_lang`.`title`', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '0'), 
(@id_module, 'id', '`dt_statiddt`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1'), 
(@id_module, 'color_Colore', 'colore', '7', '0', '0', '1', '0', '', '', '1', '0', '0', '0'); 

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Stati DDT';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fatturabile' AND `id_module` = @id_module), 'Fatturabile'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Fatturabile' AND `id_module` = @id_module), 'To be billed'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Completato' AND `id_module` = @id_module), 'Completato'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Completato' AND `id_module` = @id_module), 'Completed'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Icona' AND `id_module` = @id_module), 'Icona'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Icona' AND `id_module` = @id_module), 'Icon'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = @id_module), 'Descrizione'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = @id_module), 'Description'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id'), 
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'color_Colore' AND `id_module` = @id_module), 'color_Colore'), 
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'color_Colore' AND `id_module` = @id_module), 'color_Color');

CREATE TABLE IF NOT EXISTS `co_mandati_sepa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_banca` int(11) NOT NULL,
  `id_mandato` varchar(255) NOT NULL,
  `data_firma_mandato` DATE NOT NULL,
  `singola_disposizione` TINYINT(1) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_banca`) REFERENCES `co_banche`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Aggiunta del plugin
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Banche';
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES
('Mandati SEPA', @id_module, @id_module, 'tab', '', 1, 1, 0, '2.6.*', '', NULL, 'custom', 'mandati_sepa', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, LAST_INSERT_ID(), 'Mandati SEPA'),
  (2, LAST_INSERT_ID(), 'Mandati SEPA');

-- Allineamento campi telefono e email in viste
UPDATE `zz_views` SET `name` = 'tel_Telefono' WHERE `zz_views`.`name` = 'Telefono'; 
UPDATE `zz_views` SET `name` = 'tel_Cellulare' WHERE `zz_views`.`name` = 'Cellulare'; 
UPDATE `zz_views` SET `name` = 'emailto_Email' WHERE `zz_views`.`name` = 'Email'; 
UPDATE `zz_views_lang` SET `title` = 'tel_Telefono' WHERE `zz_views_lang`.`id_record` = (SELECT `id` FROM `zz_views` WHERE `name` = 'tel_Telefono'); 
UPDATE `zz_views_lang` SET `title` = 'tel_Cellulare' WHERE `zz_views_lang`.`id_record` = (SELECT `id` FROM `zz_views` WHERE `name` = 'tel_Cellulare');
UPDATE `zz_views_lang` SET `title` = 'emailto_Email' WHERE `zz_views_lang`.`id_record` = (SELECT `id` FROM `zz_views` WHERE `name` = 'emailto_Email');

-- Aggiunta campo modello
ALTER TABLE `mg_articoli` ADD `modello` VARCHAR(255) NULL AFTER `id_marchio`; 

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL,	'v1',	'retrieve',	'pagamenti',	'Modules\\Pagamenti\\API\\v1\\Pagamenti',	1);

-- Aggiunto spedizione porto e vettore in ordini
ALTER TABLE `or_ordini` ADD `idspedizione` TINYINT NULL AFTER `codice_commessa`, ADD `idporto` TINYINT NULL AFTER `idspedizione`, ADD `idvettore` INT NULL AFTER `idporto`;

-- Aggiunta del plugin Importazione FE
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita';
INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES
('Importazione FE', @id_module, @id_module, 'tab_main', '', 1, 1, 0, '2.6.*', '2.0', NULL, 'custom', 'importFE_ZIP', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`)
VALUES
  (1, LAST_INSERT_ID(), 'Importazione FE'),
  (2, LAST_INSERT_ID(), 'Importazione FE');

-- Aggiunta impostazione per metodo di importazione XML fatture
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES ('Metodo di importazione XML fatture di vendita', 'Automatico', 'list[Automatico,Manuale]', '1', 'Fatturazione', NULL);
INSERT INTO `zz_settings_lang` (`id_record`, `id_lang`, `title`) VALUES
  (LAST_INSERT_ID(), 1, 'Metodo di importazione XML fatture di vendita'),
  (LAST_INSERT_ID(), 2, 'Metodo di importazione XML fatture di vendita');

-- Gestione sottoscorta per sede
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(mg_articoli.id) AS dato, scorte_sedi.id_sede, IFNULL(movimenti.tot, 0), IFNULL(scorte_sedi.threshold_qta, 0)\nFROM `mg_articoli` LEFT JOIN ( SELECT sedi.id AS id_sede, mg_scorte_sedi.id_articolo, IFNULL(threshold_qta, 0) AS threshold_qta FROM ( SELECT \'0\' AS id UNION SELECT id FROM an_sedi ) sedi LEFT JOIN `mg_scorte_sedi` ON sedi.id = mg_scorte_sedi.id_sede GROUP BY sedi.id, mg_scorte_sedi.id_articolo, IFNULL(threshold_qta, 0) ) scorte_sedi ON ( scorte_sedi.id_articolo = mg_articoli.id OR scorte_sedi.id_articolo IS NULL ) LEFT JOIN( SELECT IFNULL(SUM(qta), 0) AS tot, idarticolo, idsede FROM mg_movimenti GROUP BY idarticolo, idsede ) movimenti ON movimenti.idsede = scorte_sedi.id_sede AND movimenti.idarticolo = mg_articoli.id\nWHERE `attivo` = 1 AND `deleted_at` IS NULL AND IFNULL(movimenti.tot,0)<IFNULL(scorte_sedi.threshold_qta,0)' WHERE `zz_widgets`.`name` = 'Articoli in esaurimento';

CREATE TABLE `mg_scorte_sedi` ( 
  `id` INT NOT NULL AUTO_INCREMENT, 
  `id_articolo` INT NOT NULL, 
  `id_sede` INT NOT NULL,
  `threshold_qta` DECIMAL(15,6) NOT NULL, 
PRIMARY KEY (`id`));

-- Aggiunta modulo categorie contratti
CREATE TABLE `co_categorie_contratti` (
  `id` int NOT NULL,
  `colore` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent` int DEFAULT NULL
);

CREATE TABLE `co_categorie_contratti_lang` (
  `id` int NOT NULL,
  `id_lang` int NOT NULL,
  `id_record` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
);

ALTER TABLE `co_categorie_contratti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent`),
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_categorie_contratti_lang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mg_categorie_lang_ibfk_1` (`id_record`),
  ADD KEY `mg_categorie_lang_ibfk_2` (`id_lang`),
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES
('Categorie contratti', 'categorie_contratti', '\r\nSELECT\r\n |select|\r\nFROM \r\n `co_categorie_contratti`\r\n    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)\r\nWHERE \r\n    1=1 AND `parent` IS NULL \r\nHAVING \r\n    2=2', '', 'fa fa-briefcase', '2.5.5', '2.5.5', 1, 40, 1, 1, 0, 0);
INSERT INTO `zz_modules_lang` (`id`, `id_lang`, `id_record`, `title`) VALUES (NULL, '1', (SELECT `id` FROM `zz_modules` WHERE name='Categorie contratti'), 'Categorie contratti');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Categorie contratti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'id', '`co_categorie_contratti`.`id`', 3, 1, 0, 0, 0, NULL, NULL, 0, 0, 0, 0),
(@id_module, 'Nome', '`co_categorie_contratti_lang`.`title`', 2, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 0);

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Categorie contratti';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'id' AND `zz_views`.`id_module` = @id_module), 'id'),
(1, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Nome' AND `zz_views`.`id_module` = @id_module), 'Nome'),
(2, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'id' AND `zz_views`.`id_module` = @id_module), 'id'),
(2, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Nome' AND `zz_views`.`id_module` = @id_module), 'Name');

ALTER TABLE `co_contratti` 
  ADD `id_categoria` INT NULL DEFAULT NULL , 
  ADD `id_sottocategoria` INT NULL DEFAULT NULL ; 

-- Aggiunta colonna IVA in vista Fatture di vendita
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'IVA', '(righe.iva)*IF(co_tipidocumento.reversed, -1, 1)', '16', '1', '0', '1', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'IVA' AND `id_module` = @id_module), 'IVA'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'IVA' AND `id_module` = @id_module), 'IVA');

-- Allineamento vista Attività
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `idintervento`, SUM(`prezzo_unitario`*`qta`-`sconto`) AS `ricavo_righe`, SUM(`costo_unitario`*`qta`) AS `costo_righe` FROM `in_righe_interventi` GROUP BY `idintervento`) AS `righe` ON `righe`.`idintervento` = `in_interventi`.`id`
    INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
    LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.|lang|)
    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />',IF(`an_sedi`.`telefono`!='',CONCAT(`an_sedi`.`telefono`,'<br />'),''),IF(`an_sedi`.`cellulare`!='',CONCAT(`an_sedi`.`cellulare`,'<br />'),''),`an_sedi`.`citta`,IF(`an_sedi`.`indirizzo`!='',CONCAT(' - ',`an_sedi`.`indirizzo`),'')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `in_interventi`.`idsede_destinazione`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idintervento` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = 'Modules\\\\Interventi\\\\Intervento' GROUP BY `idintervento`, `original_document_id`) AS `fattura` ON `fattura`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS `tecnici_assegnati` ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS `tecnici` ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (SELECT COUNT(`id`) as emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `name` = 'Interventi' AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS `descrizione`, `my_impianti_interventi`.`idintervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto` GROUP BY `my_impianti_interventi`.`idintervento`) AS `impianti` ON `impianti`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `co_contratti`.`id`, CONCAT(`co_contratti`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_contratti`) AS `contratto` ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (SELECT `co_preventivi`.`id`, CONCAT(`co_preventivi`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_preventivi`) AS `preventivo` ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (SELECT `or_ordini`.`id`, CONCAT(`or_ordini`.`numero`, ' del ', DATE_FORMAT(`data`, '%d/%m/%Y')) AS `info` FROM `or_ordini`) AS `ordine` ON `ordine`.`id` = `in_interventi`.`id_ordine`
    INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(' ', `zz_files`.`name`) as name, `zz_files`.`id_record` FROM `zz_files` INNER JOIN `zz_modules` ON `zz_files`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) WHERE `zz_modules`.`name` = 'Interventi' GROUP BY id_record) AS files ON `files`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tags`.`id_intervento`, GROUP_CONCAT( DISTINCT `name` SEPARATOR ', ') AS `nomi` FROM `in_tags` INNER JOIN `in_interventi_tags` ON `in_interventi_tags`.`id_tag` = `in_tags`.`id` GROUP BY `in_interventi_tags`.`id_intervento`) AS `tags` ON `in_interventi`.`id` = `tags`.`id_intervento`
WHERE 
    1=1 |segment(`in_interventi`.`id_segment`)| |date_period(`orario_inizio`,`data_richiesta`)|
GROUP BY 
    `in_interventi`.`id`
HAVING 
    2=2
ORDER BY 
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `zz_modules`.`name` = 'Interventi';

-- Allineamento plugin consuntivo
UPDATE `zz_plugins` SET `directory` = 'consuntivo', `script` = '', `options` = 'custom' WHERE `name` = 'Consuntivo';

-- Aggiunta gestione impostazioni per utente
ALTER TABLE `zz_users` ADD `options` TEXT NOT NULL; 
ALTER TABLE `zz_settings` ADD `is_user_setting` BOOLEAN NOT NULL;
UPDATE `zz_settings` SET `is_user_setting` = '1' WHERE `zz_settings`.`nome` = 'Nascondere la barra dei plugin di default';
UPDATE `zz_settings` SET `is_user_setting` = '1' WHERE `zz_settings`.`nome` = 'Nascondere la barra sinistra di default';
UPDATE `zz_settings` SET `is_user_setting` = '1' WHERE `zz_settings`.`nome` = 'Sistema di firma ';
UPDATE `zz_settings` SET `is_user_setting` = '1' WHERE `zz_settings`.`nome` = 'Inizio periodo calendario';
UPDATE `zz_settings` SET `is_user_setting` = '1' WHERE `zz_settings`.`nome` = 'Fine periodo calendario';
