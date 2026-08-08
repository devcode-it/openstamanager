-- Impostazione per selezionare i gruppi abilitati alla modifica CC e CCN in fase di invio mail
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Gruppi abilitati alla modifica CC e CCN', (SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `zz_groups`), 'query=SELECT `zz_groups`.`id`, `zz_groups_lang`.`title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) ORDER BY `zz_groups`.`id`', 1, 'Mail', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Gruppi abilitati alla modifica CC e CCN', 'Seleziona i gruppi di utenti che possono modificare i campi CC e CCN durante l''invio di email.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Groups enabled to edit CC and BCC', 'Select the user groups that can edit CC and BCC fields when sending emails.');

-- Rimozione impostazione "Logo stampe"
DELETE FROM `zz_settings` WHERE `nome` = 'Logo stampe';

-- Spostamento impostazioni in sezione "Personalizzazioni grafiche"
UPDATE `zz_settings` SET `sezione` = 'Personalizzazioni grafiche' WHERE `nome` = 'Filigrana stampe';
UPDATE `zz_settings` SET `sezione` = 'Personalizzazioni grafiche' WHERE `nome` = 'CSS Personalizzato';

-- Impostazione "Filigrana stampe" trasformata in campo di caricamento file (tipo media, editabile)
UPDATE `zz_settings` SET `tipo` = 'media', `editable` = 1 WHERE `nome` = 'Filigrana stampe';

-- Aggiornamento traduzioni dell'impostazione "Filigrana stampe"
UPDATE `zz_settings_lang` SET `help` = 'Carica un\'immagine da utilizzare come filigrana per le stampe dell\'azienda.' WHERE `id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Filigrana stampe') AND `id_lang` = 1;
UPDATE `zz_settings_lang` SET `help` = 'Upload an image to use as a watermark for company prints.' WHERE `id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Filigrana stampe') AND `id_lang` = 2;

-- Impostazione "Login" per caricare il logo personalizzato nella schermata di login
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Login logo', '', 'media', 1, 'Personalizzazioni grafiche', 5, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Login logo'), 'Login', 'Carica un\'immagine da visualizzare nella schermata di login. Dimensioni consigliate: 489x91 px. Se non viene caricato nessun file, viene utilizzato il logo predefinito.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Login logo'), 'Login', 'Upload an image to display on the login screen. Recommended dimensions: 489x91 px. If no file is uploaded, the default logo is used.');

-- Impostazioni per i loghi del menu laterale
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Logo menu', '', 'media', 1, 'Personalizzazioni grafiche', 6, 0),
('Logo menu quadrato / favicon', '', 'media', 1, 'Personalizzazioni grafiche', 7, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu'), 'Logo menu', 'Carica un\'immagine da visualizzare nel menu laterale quando è esteso. Dimensioni consigliate: 489x91 px. Se non viene caricato nessun file, viene utilizzato il logo predefinito.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu'), 'Extended menu logo', 'Upload an image to display in the sidebar when expanded. Recommended dimensions: 489x91 px. If no file is uploaded, the default logo is used.'),
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu quadrato / favicon'), 'Logo menu quadrato / favicon', 'Carica un\'immagine da visualizzare nel menu laterale quando è compresso (favicon/quadrato). Dimensioni consigliate: 1041x1024 px. Se non viene caricato nessun file, viene utilizzato il logo predefinito.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu quadrato / favicon'), 'Collapsed menu logo', 'Upload an image to display in the sidebar when collapsed (favicon/square). Recommended dimensions: 1041x1024 px. If no file is uploaded, the default logo is used.');

-- Gestione traduzione nome gruppo nella vista del modulo Utenti e permessi
UPDATE `zz_modules` SET `options` = 'SELECT\r\n |select|\r\nFROM\r\n `zz_groups`\r\n LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record` AND `zz_groups_lang`.|lang|)\r\n LEFT JOIN (SELECT `zz_users`.`id_gruppo`, COUNT(`zz_users`.`id`) AS num FROM `zz_users` GROUP BY `id_gruppo`) AS utenti ON `zz_groups`.`id` = `utenti`.`id_gruppo`\r\n LEFT JOIN (SELECT `zz_users`.`id_gruppo`, COUNT(`zz_users`.`id`) AS num FROM `zz_users` WHERE `zz_users`.`enabled` = 1 GROUP BY `id_gruppo`) AS utenti_abilitati ON `zz_groups`.`id` = `utenti_abilitati`.`id_gruppo`\r\n LEFT JOIN (SELECT `zz_users`.`id_gruppo`, COUNT(`zz_tokens`.`id`) AS num FROM `zz_users` INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente` WHERE `zz_tokens`.`enabled` = 1 GROUP BY `id_gruppo`) AS api_abilitate ON `zz_groups`.`id` = `api_abilitate`.`id_gruppo`\r\n LEFT JOIN (SELECT `zz_modules_lang`.`title`, `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)) AS `module` ON `module`.`id` = `zz_groups`.`id_module_start`\r\nWHERE\r\n 1=1\r\nHAVING\r\n 2=2\r\nORDER BY\r\n `id`, `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi';

UPDATE `zz_views` SET `query` = '`zz_groups_lang`.`title`' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name`='Utenti e permessi') AND `zz_views`.`name`='Gruppo';

-- Aggiunto colore su Tags 
ALTER TABLE `in_tags` ADD `colore` VARCHAR(7) NOT NULL DEFAULT '#FFFFFF' AFTER `name`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags'), 'color_Colore', 'colore', '2', '0', '0', '0', '0', '', '', '1', '0', '0', '1');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES ('1', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags') AND `name` = 'color_Colore'), 'color_Colore'),('2', (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags') AND `name` = 'color_Colore'), 'color_Color');

-- Aggiunte colonne Ore, Stato, Tecnico e Descrizione nel plugin Anagrafiche -> Storico Attività
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [ {  \"type\": \"table\", \"fields\": \"Numero, Data inizio, Data fine, Tipo, Ore, Stato, Tecnico, Descrizione\", \"query\": \"SELECT in_interventi.id, in_interventi.codice AS Numero, DATE_FORMAT(MAX(orario_inizio), \'%d/%m/%Y\') AS \'Data inizio\', DATE_FORMAT(MAX(orario_fine), \'%d/%m/%Y\') AS \'Data fine\', `in_tipi_intervento_lang`.`title`AS \'Tipo\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Interventi\' LIMIT 1) AS _link_module_, in_interventi.id AS _link_record_, FORMAT(`in_interventi_tecnici`.`ore`, 2) AS Ore, `in_stati_intervento`.`name` AS Stato, `an_anagrafiche`.`ragione_sociale` AS Tecnico, `in_interventi`.`descrizione` AS Descrizione FROM in_interventi LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id` INNER JOIN `in_stati_intervento` ON `in_interventi`.`id_stato`=`in_stati_intervento`.`id` INNER JOIN `in_tipi_intervento` ON (`in_interventi`.`id_tipo_intervento` = `in_tipi_intervento`.`id`) LEFT JOIN `in_tipi_intervento_lang` ON (`in_tipi_intervento_lang`.`id_record` = `in_tipi_intervento`.`id` AND `in_tipi_intervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \'Lingua\')) LEFT JOIN `an_anagrafiche` ON `in_interventi_tecnici`.`id_tecnico` = `an_anagrafiche`.`id` WHERE 1=1 AND in_interventi.deleted_at IS NULL AND id_anagrafica = |id_parent| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY in_interventi.id DESC\"}]}' WHERE `zz_plugins`.`name`= 'Storico attività';

-- Soglia oltre la quale i sottoconti di un mastro del Piano dei conti vengono mostrati come tabella con ricerca e impaginazione
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Soglia datatable sottoconti', '500', 'integer', 1, 'Piano dei conti', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Soglia datatable sottoconti'), 'Soglia datatable sottoconti', 'Numero di sottoconti oltre il quale, espandendo un mastro nel Piano dei conti, i sottoconti vengono mostrati in una tabella con ricerca e impaginazione invece dell\'elenco semplice. Default 500.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Soglia datatable sottoconti'), 'Subaccount datatable threshold', 'Number of subaccounts above which, when expanding an account in the Chart of accounts, subaccounts are shown in a searchable, paginated table instead of the plain list. Default 500.');

-- Indice per la selezione paginata dei sottoconti per mastro
ALTER TABLE `co_piano_dei_conti3` ADD INDEX `idx_id_piano_dei_conti2_numero` (`id_piano_dei_conti2`, `numero`);

-- Indici per il join anagrafica del dettaglio sottoconti
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_id_conto_cliente` (`id_conto_cliente`);
ALTER TABLE `an_anagrafiche` ADD INDEX `idx_id_conto_fornitore` (`id_conto_fornitore`);



UPDATE `zz_views`
LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module`
SET `zz_views`.`query` = 'IFNULL((SELECT SUM(`mg_movimenti`.`qta`) FROM `mg_movimenti` WHERE `mg_movimenti`.`id_articolo` = `mg_articoli`.`id`), 0)-IFNULL((SELECT IFNULL(SUM(`mg_movimenti`.`qta`), 0) FROM `mg_movimenti` WHERE `mg_movimenti`.`id_articolo` = `mg_articoli`.`id` AND `mg_movimenti`.`id_sede` = (SELECT NULLIF(`valore`, '''') FROM `zz_settings` WHERE `nome` = ''Magazzino cespiti'')), 0)'
WHERE `zz_views`.`name` = 'Q.tà' AND `zz_modules`.`name` = 'Articoli';

UPDATE `zz_views`
LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module`
SET `zz_views`.`query` = 'IFNULL((SELECT SUM(`mg_movimenti`.`qta`) FROM `mg_movimenti` WHERE `mg_movimenti`.`id_articolo` = `mg_articoli`.`id`), 0)-IFNULL(a.qta_impegnata, 0)-IFNULL((SELECT IFNULL(SUM(`mg_movimenti`.`qta`), 0) FROM `mg_movimenti` WHERE `mg_movimenti`.`id_articolo` = `mg_articoli`.`id` AND `mg_movimenti`.`id_sede` = (SELECT NULLIF(`valore`, '''') FROM `zz_settings` WHERE `nome` = ''Magazzino cespiti'')), 0)'
WHERE `zz_views`.`name` = 'Q.tà disponibile' AND `zz_modules`.`name` = 'Articoli';

-- Rimossa condizione per poter visualizzare anche le Stampe non attive
UPDATE `zz_modules` SET `options` = 'SELECT\r\n    |select| \r\nFROM \r\n    `zz_prints`\r\n    LEFT JOIN `zz_prints_lang` ON (`zz_prints_lang`.`id_record` = `zz_prints`.`id` AND `zz_prints_lang`.|lang|)\r\n    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_prints`.`id_module`\r\n    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)\r\nWHERE \r\n    1=1 \r\nHAVING \r\n    2=2' WHERE `zz_modules`.`name` = 'Stampe';

-- Rimozione dei plugin sostituiti dalla consultazione centralizzata nel plugin "Statistiche"
DELETE FROM `zz_plugins` WHERE `name` = "Ddt del cliente";
DELETE FROM `zz_plugins` WHERE `name` = "Storico attività";
DELETE FROM `zz_plugins` WHERE `name` = "Contratti del cliente";
DELETE FROM `zz_plugins` WHERE `name` = "Impianti del cliente";

-- Aggiunte stampe intervento con note sessione tecnici
INSERT INTO `zz_prints` ( `id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`, `available_options`) VALUES ( (SELECT `id` FROM `zz_modules` WHERE name = 'Interventi'), '1', 'Intervento (con note per sessione)', 'interventi', 'idintervento', '{\"pricing\":true, \"note-sessione\":true}', 'fa fa-print', '', '', '0', '0', '1', NULL), ((SELECT `id` FROM `zz_modules` WHERE name = 'Interventi'), '1', 'Intervento (senza orari)', 'interventi', 'idintervento', '{\"nascondi-orario\":true}', 'fa fa-print', '', '', '0', '0', '1', NULL),((SELECT `id` FROM `zz_modules` WHERE name = 'Interventi'), '1', 'Intervento (senza ore e km)', 'interventi', 'idintervento', '{\"nascondi-ore-km\":true}', 'fa fa-print', '', '', '0', '0', '1', NULL);

INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES ('1', (SELECT `id` FROM `zz_prints` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = 'Intervento (con note per sessione)'), 'Intervento (con note per sessione)', 'Intervento num. {numero} del {data}'), ('1', (SELECT `id` FROM `zz_prints` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = 'Intervento (senza orari)'), 'Intervento (senza orari)', 'Intervento num. {numero} del {data}'), ('1', (SELECT `id` FROM `zz_prints` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = 'Intervento (senza ore e km)'), 'Intervento (senza ore e km)', 'Intervento num. {numero} del {data}');

-- Impostazione per la tipologia anagrafica predefinita in fase di inserimento di una nuova anagrafica
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Tipologia anagrafica predefinita', '', 'list[Azienda,Ente pubblico,Privato]', 1, 'Anagrafiche', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Tipologia anagrafica predefinita'), 'Tipologia anagrafica predefinita', 'Tipologia (Azienda, Ente pubblico o Privato) preselezionata automaticamente nella finestra di aggiunta di una nuova anagrafica. Se non impostata, nessuna tipologia viene preselezionata.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Tipologia anagrafica predefinita'), 'Default entity classification', 'Classification (Company, Public entity or Private) automatically preselected in the new entity creation window. If not set, no classification is preselected.');

-- Modulo Note spese (#1461)
CREATE TABLE `co_note_spese_tipologie` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `codice` VARCHAR(50) NULL,
    `descrizione` VARCHAR(100) NOT NULL,
    `ordine` INT NOT NULL DEFAULT 100,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `can_delete` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `co_note_spese_tipologie_codice_unique` (`codice`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `co_note_spese_tipologie_lang` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `id_lang` INT NOT NULL,
    `id_record` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `co_note_spese_tipologie_lang_unique` (`id_lang`, `id_record`),
    KEY `co_note_spese_tipologie_lang_record_index` (`id_record`),
    CONSTRAINT `co_note_spese_tipologie_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_note_spese_tipologie` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `co_note_spese_tipologie_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `co_note_spese_stati` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `colore` VARCHAR(30) NOT NULL DEFAULT 'secondary',
    `ordine` INT NOT NULL DEFAULT 100,
    `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `co_note_spese_stati_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `co_note_spese_stati_lang` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `id_lang` INT NOT NULL,
    `id_record` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `co_note_spese_stati_lang_unique` (`id_lang`, `id_record`),
    KEY `co_note_spese_stati_lang_record_index` (`id_record`),
    CONSTRAINT `co_note_spese_stati_lang_ibfk_1` FOREIGN KEY (`id_record`) REFERENCES `co_note_spese_stati` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `co_note_spese_stati_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `co_note_spese` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `data` DATE NOT NULL,
    `id_tipologia` INT NOT NULL,
    `id_stato` INT NOT NULL,
    `descrizione` VARCHAR(255) NOT NULL,
    `importo` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `id_anagrafica` INT NULL,
    `id_operatore` INT NULL,
    `controparte` VARCHAR(255) NULL,
    `origine` VARCHAR(50) NOT NULL DEFAULT 'manuale',
    `id_origine` INT NULL,
    `note` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `co_note_spese_data_index` (`data`),
    KEY `co_note_spese_tipologia_index` (`id_tipologia`),
    KEY `co_note_spese_stato_index` (`id_stato`),
    KEY `co_note_spese_anagrafica_index` (`id_anagrafica`),
    KEY `co_note_spese_operatore_index` (`id_operatore`),
    UNIQUE KEY `co_note_spese_origine_unique` (`origine`, `id_origine`),
    CONSTRAINT `co_note_spese_ibfk_1` FOREIGN KEY (`id_tipologia`) REFERENCES `co_note_spese_tipologie` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `co_note_spese_ibfk_2` FOREIGN KEY (`id_stato`) REFERENCES `co_note_spese_stati` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `co_note_spese_ibfk_3` FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `co_note_spese_ibfk_4` FOREIGN KEY (`id_operatore`) REFERENCES `an_anagrafiche` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `co_note_spese_tipologie` (`codice`, `descrizione`, `ordine`, `enabled`, `can_delete`) VALUES
('carburante', 'Carburante', 10, 1, 0),
('pedaggio', 'Pedaggio / Autostrada', 20, 1, 0),
('parcheggio', 'Parcheggio', 30, 1, 0),
('vitto', 'Vitto', 40, 1, 0),
('alloggio', 'Alloggio', 50, 1, 0),
('trasporto', 'Trasporto', 60, 1, 0),
('materiale_consumo', 'Materiale di consumo', 70, 1, 0),
('assicurazioni', 'Assicurazioni', 80, 1, 0),
('affitti', 'Canoni / Affitti', 90, 1, 0),
('contributi_tributi', 'Contributi / Tributi', 100, 1, 0),
('spese_bancarie', 'Spese bancarie / Commissioni', 110, 1, 0),
('personale', 'Personale', 120, 1, 0),
('altro', 'Altro', 1000, 1, 0);

INSERT INTO `co_note_spese_tipologie_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, `id`, `descrizione` FROM `co_note_spese_tipologie`;
INSERT INTO `co_note_spese_tipologie_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, `id`, CASE `codice`
    WHEN 'carburante' THEN 'Fuel'
    WHEN 'pedaggio' THEN 'Toll / motorway'
    WHEN 'parcheggio' THEN 'Parking'
    WHEN 'vitto' THEN 'Meals'
    WHEN 'alloggio' THEN 'Accommodation'
    WHEN 'trasporto' THEN 'Transport'
    WHEN 'materiale_consumo' THEN 'Consumables'
    WHEN 'assicurazioni' THEN 'Insurance'
    WHEN 'affitti' THEN 'Rent / leases'
    WHEN 'contributi_tributi' THEN 'Contributions / taxes'
    WHEN 'spese_bancarie' THEN 'Bank fees / commissions'
    WHEN 'personale' THEN 'Personnel'
    ELSE 'Other'
END FROM `co_note_spese_tipologie`;

INSERT INTO `co_note_spese_stati` (`name`, `colore`, `ordine`, `can_delete`) VALUES
('da_verificare', 'warning', 10, 0),
('confermato', 'success', 20, 0),
('escluso', 'secondary', 30, 0);

INSERT INTO `co_note_spese_stati_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, `id`, CASE `name` WHEN 'da_verificare' THEN 'Da verificare' WHEN 'confermato' THEN 'Confermata' ELSE 'Esclusa' END FROM `co_note_spese_stati`;
INSERT INTO `co_note_spese_stati_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, `id`, CASE `name` WHEN 'da_verificare' THEN 'To review' WHEN 'confermato' THEN 'Confirmed' ELSE 'Excluded' END FROM `co_note_spese_stati`;

INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Note spese', 'note_spese', 'note_spese',
'SELECT |select| FROM `co_note_spese` LEFT JOIN `co_note_spese_tipologie` ON `co_note_spese_tipologie`.`id` = `co_note_spese`.`id_tipologia` LEFT JOIN `co_note_spese_tipologie_lang` ON (`co_note_spese_tipologie_lang`.`id_record` = `co_note_spese_tipologie`.`id` AND `co_note_spese_tipologie_lang`.|lang|) LEFT JOIN `co_note_spese_stati` ON `co_note_spese_stati`.`id` = `co_note_spese`.`id_stato` LEFT JOIN `co_note_spese_stati_lang` ON (`co_note_spese_stati_lang`.`id_record` = `co_note_spese_stati`.`id` AND `co_note_spese_stati_lang`.|lang|) LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `co_note_spese`.`id_anagrafica` LEFT JOIN `an_anagrafiche` AS `an_operatori` ON `an_operatori`.`id` = `co_note_spese`.`id_operatore` WHERE 1=1 |date_period(`co_note_spese`.`data`)| HAVING 2=2 ORDER BY `co_note_spese`.`data` DESC, `co_note_spese`.`id` DESC',
'', 'fa fa-money', '2.12', '2.12', 20, COALESCE((SELECT `parent` FROM `zz_modules` WHERE `name` = 'Prima nota'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contabilità')), 1, 1),
('Tipologie note spese', 'tipologie_note_spese', 'tipologie_note_spese',
'SELECT |select| FROM `co_note_spese_tipologie` LEFT JOIN `co_note_spese_tipologie_lang` ON (`co_note_spese_tipologie_lang`.`id_record` = `co_note_spese_tipologie`.`id` AND `co_note_spese_tipologie_lang`.|lang|) WHERE 1=1 HAVING 2=2 ORDER BY `co_note_spese_tipologie`.`ordine`, COALESCE(`co_note_spese_tipologie_lang`.`title`, `co_note_spese_tipologie`.`descrizione`)',
'', 'fa fa-tags', '2.12', '2.12', 20, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tabelle'), 1, 1);

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
(1, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Note spese', 'Nota spesa - {descrizione}'),
(2, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Expense notes', 'Expense note - {descrizione}'),
(1, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese'), 'Tipologie note spese', 'Tipologia nota spesa - {title}'),
(2, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese'), 'Expense categories', 'Expense category - {title}');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `visible`, `format`, `html_format`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'id', '`co_note_spese`.`id`', 1, 0, 0, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Data', '`co_note_spese`.`data`', 2, 1, 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Tipologia', 'COALESCE(`co_note_spese_tipologie_lang`.`title`, `co_note_spese_tipologie`.`descrizione`)', 3, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Descrizione', '`co_note_spese`.`descrizione`', 4, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Controparte', 'COALESCE(NULLIF(`co_note_spese`.`controparte`, ''''), `an_anagrafiche`.`ragione_sociale`, '''')', 5, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Operatore', 'COALESCE(`an_operatori`.`ragione_sociale`, '''')', 6, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'icon_Stato', 'CASE `co_note_spese_stati`.`name` WHEN ''confermato'' THEN ''fa fa-check-circle fa-lg text-success'' WHEN ''da_verificare'' THEN ''fa fa-exclamation-triangle fa-lg text-warning'' ELSE ''fa fa-ban fa-lg text-secondary'' END', 7, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'icon_title_Stato', 'COALESCE(`co_note_spese_stati_lang`.`title`, `co_note_spese_stati`.`name`)', 8, 0, 0, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Origine', 'CASE `co_note_spese`.`origine` WHEN ''automezzi_rifornimento'' THEN ''Automezzi'' WHEN ''scadenzario_generico'' THEN ''Scadenzario'' WHEN ''excel'' THEN ''Importazione'' ELSE ''Manuale'' END', 9, 1, 0, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'icon_Allegati', 'IF((SELECT COUNT(*) FROM `zz_files` WHERE `zz_files`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = ''Note spese'') AND `zz_files`.`id_plugin` IS NULL AND `zz_files`.`id_record` = `co_note_spese`.`id` AND (`zz_files`.`key` IS NULL OR `zz_files`.`key` = '''')) > 0, ''fa fa-paperclip fa-lg text-success'', ''fa fa-paperclip fa-lg text-warning'')', 10, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'icon_title_Allegati', 'CAST((SELECT COUNT(*) FROM `zz_files` WHERE `zz_files`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = ''Note spese'') AND `zz_files`.`id_plugin` IS NULL AND `zz_files`.`id_record` = `co_note_spese`.`id` AND (`zz_files`.`key` IS NULL OR `zz_files`.`key` = '''')) AS CHAR)', 11, 0, 0, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'Importo', '`co_note_spese`.`importo`', 12, 1, 1, 1, 0, 1, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, `id`, `name` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese');
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, `id`, CASE `name`
    WHEN 'Data' THEN 'Date' WHEN 'Tipologia' THEN 'Category' WHEN 'Descrizione' THEN 'Description'
    WHEN 'Controparte' THEN 'Counterparty' WHEN 'Operatore' THEN 'Operator'
    WHEN 'icon_Stato' THEN 'icon_Status' WHEN 'icon_title_Stato' THEN 'icon_title_Status'
    WHEN 'Origine' THEN 'Source' WHEN 'icon_Allegati' THEN 'icon_Attachments'
    WHEN 'icon_title_Allegati' THEN 'icon_title_Attachments' WHEN 'Importo' THEN 'Amount' ELSE `name` END
FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `visible`, `format`, `html_format`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese'), 'id', '`co_note_spese_tipologie`.`id`', 1, 0, 0, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese'), 'Descrizione', 'COALESCE(`co_note_spese_tipologie_lang`.`title`, `co_note_spese_tipologie`.`descrizione`)', 2, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese'), 'Ordine', '`co_note_spese_tipologie`.`ordine`', 3, 1, 1, 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese'), 'Attiva', 'IF(`co_note_spese_tipologie`.`enabled` = 1, ''SI'', ''NO'')', 4, 1, 1, 0, 0, 0, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, `id`, `name` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese');
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, `id`, CASE `name` WHEN 'Descrizione' THEN 'Description' WHEN 'Ordine' THEN 'Order' WHEN 'Attiva' THEN 'Enabled' ELSE `name` END
FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipologie note spese');

INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 0, 'Nota spese', 'note_spese', '', '', 'fa fa-print', '2.12', '2.12', 0, 0, 1);
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES
(1, (SELECT `id` FROM `zz_prints` WHERE `name` = 'Nota spese'), 'Nota spese', 'Nota spese'),
(2, (SELECT `id` FROM `zz_prints` WHERE `name` = 'Nota spese'), 'Expense notes', 'Expense notes');

INSERT INTO `zz_widgets` (`name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `enabled`, `order`, `help`) VALUES
('Note spese - confermate', 'custom', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'controller_top', 'col-md-4', '', 'success', 'fa fa-check-circle', '', '', 'link', 'modules/note_spese/widgets/indicatori.php', 1, 1, 'Totale e numero delle spese confermate nel periodo selezionato.'),
('Note spese - da verificare', 'custom', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'controller_top', 'col-md-4', '', 'warning', 'fa fa-exclamation-triangle', '', '', 'link', 'modules/note_spese/widgets/indicatori.php', 1, 2, 'Importo e numero delle note spese che richiedono verifica.'),
('Note spese - senza allegati', 'custom', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'), 'controller_top', 'col-md-4', '', 'info', 'fa fa-paperclip', '', '', 'link', 'modules/note_spese/widgets/indicatori.php', 1, 3, 'Spese confermate del periodo che non hanno ancora allegati.');

INSERT INTO `zz_widgets_lang` (`id_lang`, `id_record`, `title`, `text`) VALUES
(1, (SELECT `id` FROM `zz_widgets` WHERE `name` = 'Note spese - confermate'), 'Spese confermate', 'Spese confermate'),
(2, (SELECT `id` FROM `zz_widgets` WHERE `name` = 'Note spese - confermate'), 'Confirmed expenses', 'Confirmed expenses'),
(1, (SELECT `id` FROM `zz_widgets` WHERE `name` = 'Note spese - da verificare'), 'Da verificare', 'Da verificare'),
(2, (SELECT `id` FROM `zz_widgets` WHERE `name` = 'Note spese - da verificare'), 'To review', 'To review'),
(1, (SELECT `id` FROM `zz_widgets` WHERE `name` = 'Note spese - senza allegati'), 'Senza allegati', 'Senza allegati'),
(2, (SELECT `id` FROM `zz_widgets` WHERE `name` = 'Note spese - senza allegati'), 'Without attachments', 'Without attachments');

INSERT INTO `zz_hooks` (`name`, `class`, `enabled`, `id_module`) VALUES
('Note spese da importare', 'Modules\\NoteSpese\\PendingExpensesHook', 1, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Note spese'));
INSERT INTO `zz_hooks_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_hooks` WHERE `name` = 'Note spese da importare'), 'Note spese da importare'),
(2, (SELECT `id` FROM `zz_hooks` WHERE `name` = 'Note spese da importare'), 'Expense notes to import');

INSERT INTO `zz_permissions` (`id_gruppo`, `id_module`, `permessi`)
SELECT g.`id`, m.`id`, 'rw'
FROM `zz_groups` g
CROSS JOIN `zz_modules` m
WHERE g.`nome` = 'Amministratori' AND m.`name` IN ('Note spese', 'Tipologie note spese');

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT g.`id`, v.`id`
FROM `zz_groups` g
INNER JOIN `zz_views` v ON v.`id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN ('Note spese', 'Tipologie note spese'));
