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
SET `zz_views`.`query` = '`mg_articoli`.`qta`-IFNULL((SELECT IFNULL(SUM(`mg_movimenti`.`qta`), 0) FROM `mg_movimenti` WHERE `mg_movimenti`.`id_articolo` = `mg_articoli`.`id` AND `mg_movimenti`.`id_sede` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = ''Magazzino cespiti'')), 0)'
WHERE `zz_views`.`name` = 'Q.tà' AND `zz_modules`.`name` = 'Articoli';

UPDATE `zz_views`
LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module`
SET `zz_views`.`query` = '`mg_articoli`.`qta`-IFNULL(a.qta_impegnata, 0)-IFNULL((SELECT IFNULL(SUM(`mg_movimenti`.`qta`), 0) FROM `mg_movimenti` WHERE `mg_movimenti`.`id_articolo` = `mg_articoli`.`id` AND `mg_movimenti`.`id_sede` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = ''Magazzino cespiti'')), 0)'
WHERE `zz_views`.`name` = 'Q.tà disponibile' AND `zz_modules`.`name` = 'Articoli';

-- Rimossa condizione per poter visualizzare anche le Stampe non attive
UPDATE `zz_modules` SET `options` = 'SELECT\r\n    |select| \r\nFROM \r\n    `zz_prints`\r\n    LEFT JOIN `zz_prints_lang` ON (`zz_prints_lang`.`id_record` = `zz_prints`.`id` AND `zz_prints_lang`.|lang|)\r\n    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_prints`.`id_module`\r\n    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)\r\nWHERE \r\n    1=1 \r\nHAVING \r\n    2=2' WHERE `zz_modules`.`name` = 'Stampe';

-- Rimozione dei plugin sostituiti dalla consultazione centralizzata nel plugin "Statistiche"
DELETE FROM `zz_plugins` WHERE `name` = "Ddt del cliente";
DELETE FROM `zz_plugins` WHERE `name` = "Storico attività";
DELETE FROM `zz_plugins` WHERE `name` = "Contratti del cliente";
DELETE FROM `zz_plugins` WHERE `name` = "Impianti del cliente";
