-- Migrazione del modulo Contratti fornitori.
-- Da integrare in update/2_12.sql prima della revisione finale.

INSERT INTO `zz_modules`
(`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`, `attachments_directory`)
VALUES
(
    'Contratti fornitori',
    'contratti_fornitori',
    'SELECT |select| FROM `ac_contratti_fornitori` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `ac_contratti_fornitori`.`id_fornitore` INNER JOIN `ac_stati_contratti_fornitori` ON `ac_stati_contratti_fornitori`.`id` = `ac_contratti_fornitori`.`id_stato` LEFT JOIN `ac_categorie_contratti_fornitori` ON `ac_categorie_contratti_fornitori`.`id` = `ac_contratti_fornitori`.`id_categoria` WHERE 1=1 HAVING 2=2',
    '',
    'fa fa-file-text-o',
    '2.12',
    '2.12',
    30,
    (SELECT `id` FROM `zz_modules` AS `parent_module` WHERE `name` = 'Acquisti'),
    1,
    1,
    1,
    0,
    'contratti_fornitori'
);

SET @id_modulo_cf := LAST_INSERT_ID();

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`)
SELECT `id`, @id_modulo_cf,
    CASE WHEN `id` = 2 THEN 'Supplier contracts' ELSE 'Contratti fornitori' END
FROM `zz_langs`;

CREATE TABLE `ac_stati_contratti_fornitori` (
    `id` int NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `colore` varchar(20) NOT NULL DEFAULT '#6c757d',
    `ordine` int NOT NULL DEFAULT 0,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ac_categorie_contratti_fornitori` (
    `id` int NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ac_contratti_fornitori` (
    `id` int NOT NULL AUTO_INCREMENT,
    `numero` varchar(50) NOT NULL,
    `id_segment` int NOT NULL,
    `id_fornitore` int NOT NULL,
    `id_referente` int DEFAULT NULL,
    `idagente` int DEFAULT NULL,
    `id_stato` int NOT NULL,
    `id_categoria` int DEFAULT NULL,
    `nome` varchar(255) NOT NULL,
    `numero_fornitore` varchar(100) DEFAULT NULL,
    `data_stipula` date DEFAULT NULL,
    `data_inizio` date NOT NULL,
    `validita` int DEFAULT NULL,
    `tipo_validita` varchar(20) DEFAULT NULL,
    `data_scadenza` date DEFAULT NULL,
    `giorni_preavviso` int NOT NULL DEFAULT 0,
    `data_limite_disdetta` date DEFAULT NULL,
    `rinnovo_automatico` tinyint(1) NOT NULL DEFAULT 0,
    `mesi_rinnovo` int NOT NULL DEFAULT 0,
    `condizioni_rinnovo` varchar(255) DEFAULT NULL,
    `importo` decimal(15,2) NOT NULL DEFAULT 0.00,
    `periodicita` varchar(30) DEFAULT NULL,
    `note_economiche` varchar(255) DEFAULT NULL,
    `note` text NOT NULL,
    `id_contratto_precedente` int DEFAULT NULL,
    `id_contratto_successivo` int DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `numero_segmento` (`id_segment`, `numero`),
    KEY `id_fornitore` (`id_fornitore`),
    KEY `id_stato` (`id_stato`),
    KEY `id_categoria` (`id_categoria`),
    KEY `data_scadenza` (`data_scadenza`),
    KEY `data_limite_disdetta` (`data_limite_disdetta`),
    KEY `rinnovo_automatico` (`rinnovo_automatico`),
    KEY `id_contratto_precedente` (`id_contratto_precedente`),
    KEY `id_contratto_successivo` (`id_contratto_successivo`),
    CONSTRAINT `ac_contratti_fornitori_ibfk_1` FOREIGN KEY (`id_segment`) REFERENCES `zz_segments` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `ac_contratti_fornitori_ibfk_2` FOREIGN KEY (`id_fornitore`) REFERENCES `an_anagrafiche` (`idanagrafica`) ON DELETE RESTRICT,
    CONSTRAINT `ac_contratti_fornitori_ibfk_3` FOREIGN KEY (`id_referente`) REFERENCES `an_referenti` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ac_contratti_fornitori_ibfk_4` FOREIGN KEY (`idagente`) REFERENCES `an_anagrafiche` (`idanagrafica`) ON DELETE SET NULL,
    CONSTRAINT `ac_contratti_fornitori_ibfk_5` FOREIGN KEY (`id_stato`) REFERENCES `ac_stati_contratti_fornitori` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `ac_contratti_fornitori_ibfk_6` FOREIGN KEY (`id_categoria`) REFERENCES `ac_categorie_contratti_fornitori` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ac_contratti_fornitori_ibfk_7` FOREIGN KEY (`id_contratto_precedente`) REFERENCES `ac_contratti_fornitori` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ac_contratti_fornitori_ibfk_8` FOREIGN KEY (`id_contratto_successivo`) REFERENCES `ac_contratti_fornitori` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ac_stati_contratti_fornitori` (`nome`, `colore`, `ordine`, `enabled`) VALUES
('Bozza', '#6c757d', 10, 1),
('Attivo', '#28a745', 20, 1),
('In scadenza', '#f39c12', 30, 0),
('Disdetto', '#dc3545', 40, 1),
('Terminato', '#343a40', 50, 1);

INSERT INTO `ac_categorie_contratti_fornitori` (`nome`, `enabled`) VALUES
('Telefonia', 1), ('Cloud', 1), ('Software', 1), ('Assicurazioni', 1),
('Leasing', 1), ('Energia', 1), ('Consulenza', 1), ('Manutenzione', 1),
('Noleggio', 1), ('Licenze', 1), ('Altro', 1);

INSERT INTO `zz_segments`
(`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `for_fe`, `is_sezionale`, `created_at`, `updated_at`, `is_fiscale`)
VALUES
(@id_modulo_cf, 'Contratti fornitori', '1=1', 'WHR', '####/yy', '', '', 1, 0, 0, 0, 0, 1, NOW(), NOW(), 0);

SET @id_segment_cf := LAST_INSERT_ID();

INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`)
SELECT `id`, @id_modulo_cf FROM `zz_groups` WHERE `nome` = 'Amministratori';

INSERT INTO `zz_group_segment` (`id_gruppo`, `id_segment`)
SELECT `id`, @id_segment_cf FROM `zz_groups` WHERE `nome` = 'Amministratori';

INSERT INTO `zz_views`
(`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `visible`, `summable`, `avg`, `default`)
VALUES
(@id_modulo_cf, 'Numero', '`ac_contratti_fornitori`.`numero`', 1, 1, 0, 0, 0, 1, 0, 0, 1),
(@id_modulo_cf, 'Fornitore', '`an_anagrafiche`.`ragione_sociale`', 2, 1, 0, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Contratto', '`ac_contratti_fornitori`.`nome`', 3, 1, 0, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Categoria', '`ac_categorie_contratti_fornitori`.`nome`', 4, 1, 0, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Stato', 'CASE WHEN `ac_stati_contratti_fornitori`.`nome` = ''Attivo'' AND `ac_contratti_fornitori`.`data_scadenza` < CURDATE() THEN ''<span class="badge badge-danger">Scaduto</span>'' WHEN `ac_stati_contratti_fornitori`.`nome` = ''Attivo'' AND `ac_contratti_fornitori`.`data_scadenza` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY) THEN ''<span class="badge badge-warning">In scadenza</span>'' ELSE CONCAT(''<span class="badge" style="background-color:'', `ac_stati_contratti_fornitori`.`colore`, ''">'', `ac_stati_contratti_fornitori`.`nome`, ''</span>'') END', 5, 1, 0, 0, 1, 1, 0, 0, 0),
(@id_modulo_cf, 'Data inizio', '`ac_contratti_fornitori`.`data_inizio`', 6, 1, 1, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Data scadenza', '`ac_contratti_fornitori`.`data_scadenza`', 7, 1, 1, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Termine disdetta', '`ac_contratti_fornitori`.`data_limite_disdetta`', 8, 1, 1, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Rinnovo automatico', 'IF(`ac_contratti_fornitori`.`rinnovo_automatico` = 1, ''Sì'', ''No'')', 9, 1, 0, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'Importo', '`ac_contratti_fornitori`.`importo`', 10, 1, 0, 1, 0, 1, 1, 0, 0),
(@id_modulo_cf, 'Note operative', 'LEFT(`ac_contratti_fornitori`.`note`, 100)', 11, 1, 0, 0, 0, 1, 0, 0, 0),
(@id_modulo_cf, 'id', '`ac_contratti_fornitori`.`id`', 99, 0, 0, 0, 0, 0, 0, 0, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT `zz_langs`.`id`, `zz_views`.`id`, `zz_views`.`name`
FROM `zz_views`
CROSS JOIN `zz_langs`
WHERE `zz_views`.`id_module` = @id_modulo_cf;

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT `zz_groups`.`id`, `zz_views`.`id`
FROM `zz_groups`
CROSS JOIN `zz_views`
WHERE `zz_groups`.`nome` = 'Amministratori' AND `zz_views`.`id_module` = @id_modulo_cf;

INSERT INTO `zz_widgets`
(`name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `enabled`, `order`, `help`)
VALUES
('CF - Disdette entro 30 giorni', 'stats', @id_modulo_cf, 'controller_top', 'col-md-3', 'SELECT COUNT(*) AS dato FROM `ac_contratti_fornitori` c INNER JOIN `ac_stati_contratti_fornitori` s ON s.`id` = c.`id_stato` WHERE s.`nome` = ''Attivo'' AND c.`data_limite_disdetta` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)', 'yellow', 'fa fa-bell', '', '', 'link', '', 1, 1, 'Contratti attivi con termine utile di disdetta entro 30 giorni.'),
('CF - In scadenza entro 60 giorni', 'stats', @id_modulo_cf, 'controller_top', 'col-md-3', 'SELECT COUNT(*) AS dato FROM `ac_contratti_fornitori` c INNER JOIN `ac_stati_contratti_fornitori` s ON s.`id` = c.`id_stato` WHERE s.`nome` = ''Attivo'' AND c.`data_scadenza` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)', 'orange', 'fa fa-calendar-times-o', '', '', 'link', '', 1, 2, 'Contratti ancora attivi con scadenza entro 60 giorni.'),
('CF - Scaduti ancora attivi', 'stats', @id_modulo_cf, 'controller_top', 'col-md-3', 'SELECT COUNT(*) AS dato FROM `ac_contratti_fornitori` c INNER JOIN `ac_stati_contratti_fornitori` s ON s.`id` = c.`id_stato` WHERE s.`nome` = ''Attivo'' AND c.`data_scadenza` < CURDATE()', 'red', 'fa fa-exclamation-triangle', '', '', 'link', '', 1, 3, 'Contratti ancora Attivi con data di scadenza già superata.'),
('CF - Rinnovi automatici entro 60 giorni', 'stats', @id_modulo_cf, 'controller_top', 'col-md-3', 'SELECT COUNT(*) AS dato FROM `ac_contratti_fornitori` c INNER JOIN `ac_stati_contratti_fornitori` s ON s.`id` = c.`id_stato` WHERE s.`nome` = ''Attivo'' AND c.`rinnovo_automatico` = 1 AND c.`data_scadenza` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)', 'green', 'fa fa-repeat', '', '', 'link', '', 1, 4, 'Contratti con rinnovo automatico e scadenza entro 60 giorni.');

INSERT INTO `zz_widgets_lang` (`id_lang`, `id_record`, `title`, `text`)
SELECT `zz_langs`.`id`, `zz_widgets`.`id`,
    REPLACE(`zz_widgets`.`name`, 'CF - ', ''),
    REPLACE(`zz_widgets`.`name`, 'CF - ', '')
FROM `zz_widgets`
CROSS JOIN `zz_langs`
WHERE `zz_widgets`.`id_module` = @id_modulo_cf;
