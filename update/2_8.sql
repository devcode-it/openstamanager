-- Servizio verifica iban con ibanapi.com
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES (NULL, 'Endpoint ibanapi.com', 'https://api.ibanapi.com', 'string', '1', 'API', NULL, '0');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES (NULL, 'Api key ibanapi.com', '', 'string', '1', 'API', NULL, '0');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`) VALUES (1, (SELECT id FROM zz_settings WHERE `nome`='Endpoint ibanapi.com'), 'Endpoint ibanapi.com');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`) VALUES (1, (SELECT id FROM zz_settings WHERE `nome`='Api key ibanapi.com'), 'Api key ibanapi.com');

-- Aggiunta impostazione per OpenRouter API Key
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'OpenRouter API Key', '', 'string', 1, 'API', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'OpenRouter API Key'),
'OpenRouter API Key',
'API Key per l''integrazione con OpenRouter AI. Ottieni la tua chiave da https://openrouter.ai/keys');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'OpenRouter API Key'),
'OpenRouter API Key',
'API Key for OpenRouter AI integration. Get your key from https://openrouter.ai/keys');

-- Aggiunta impostazione per Modello AI predefinito OpenRouter
-- Define the list of free models
SET @free_models = 'mistralai/mistral-7b-instruct,google/gemini-pro-1.5,anthropic/claude-3-haiku-20240307,openai/gpt-3.5-turbo'; -- Add/remove models as needed

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Modello AI predefinito per OpenRouter', 'openai/gpt-3.5-turbo', CONCAT('list[', @free_models, ']'), 1, 'API', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Modello AI predefinito per OpenRouter'),
'Modello AI predefinito (OpenRouter)',
'Modello gratuito da utilizzare per impostazione predefinita con l''assistente AI di OpenRouter. Seleziona uno dei modelli disponibili.');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Modello AI predefinito per OpenRouter'),
'Default AI Model (OpenRouter)',
'Free model to use by default with the OpenRouter AI assistant. Select one of the available models.');

-- Aggiunta impostazione per il Prompt di sistema Modello AI
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Prompt di sistema per Modello AI', '', 'textarea', 1, 'API', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Prompt di sistema per Modello AI'),
'Prompt di sistema per Modello AI',
'Il messaggio di sistema inviato all''AI per definire il suo ruolo e comportamento. Modificalo per personalizzare le risposte.');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Prompt di sistema per Modello AI'),
'System Prompt for AI Model',
'The system message sent to the AI to define its role and behavior. Modify it to customize responses.');

-- Nuovo modulo "Descrizioni predefinite"
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Descrizioni predefinite', 'descrizioni_predefinite', 'SELECT |select| FROM `zz_default_description` WHERE 1=1 HAVING 2=2', '', 'fa fa-circle-o', '2.8', '2.8', '8', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Tabelle'), '1', '1', '1', '1');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Descrizioni predefinite';
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', @id_module, 'Descrizioni predefinite', 'Descrizioni predefinite'),
('2', @id_module, 'Descrizioni predefinite', 'Descrizioni predefinite');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Descrizioni predefinite';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Descrizione', '`zz_default_description`.`descrizione`', '3', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'Nome', 'zz_default_description.name', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'id', '`zz_default_description`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Descrizioni predefinite';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = @id_module), 'Descrizione'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = @id_module), 'Description'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Nome' AND `id_module` = @id_module), 'Nome'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Nome' AND `id_module` = @id_module), 'Name'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id');

CREATE TABLE `zz_default_description` (`id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , `descrizione` TEXT NOT NULL , `note` TEXT NOT NULL , PRIMARY KEY (`id`));
CREATE TABLE `zz_default_description_module` (`id` INT NOT NULL AUTO_INCREMENT , `id_description` INT NOT NULL , `id_module` INT NOT NULL , PRIMARY KEY (`id`));

-- Aggiunte colonne Note e _bg_ in Categorie impianti
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Categorie impianti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Note', '`nota`', 3, 1, 0, 0, 0, '', '', 1, 0, 0, 0),
(@id_module, '_bg_', '`colore`', 4, 1, 0, 0, 0, '', '', 0, 0, 0, 0);

SELECT @id:= MAX(`id`) FROM `zz_views`;
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, @id-1, 'Note'),
(2, @id-1, 'Note'),
(1, @id, '_bg_'),
(2, @id, '_bg_');

-- Miglioria plugin Assicurazione crediti
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Fido assicurato, Data inizio, Data fine, Totale, Residuo\", \"query\": \"SELECT id, DATE_FORMAT(data_inizio,\'%d/%m/%Y\') AS \'Data inizio\', DATE_FORMAT(data_fine,\'%d/%m/%Y\') AS \'Data fine\', ROUND(fido_assicurato, 2) AS \'Fido assicurato\', ROUND(totale, 2) AS Totale, ROUND(fido_assicurato - totale, 2) AS Residuo, IF((fido_assicurato - totale) < 0, \'#f4af1b\', \'#4dc347\') AS _bg_ FROM an_assicurazione_crediti WHERE 1=1 AND id_anagrafica = |id_parent| HAVING 2=2 ORDER BY an_assicurazione_crediti.id DESC\"} ]}' WHERE `zz_plugins`.`name` = 'Assicurazione crediti';

ALTER TABLE `my_impianti` ADD `note` VARCHAR(255) NULL AFTER `descrizione`;

-- Aggiunta colonne Marchio e Modello nella vista Articoli (nascoste di default)
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Articoli';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Marchio', '(SELECT `name` FROM `mg_marchi` WHERE `mg_marchi`.`id` = `mg_articoli`.`id_marchio`)', 15, 1, 0, 0, 0, '', '', 0, 0, 0, 0),
(@id_module, 'Modello', '`mg_articoli`.`modello`', 16, 1, 0, 0, 0, '', '', 0, 0, 0, 0);

SELECT @id:= MAX(`id`) FROM `zz_views`;
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, @id-1, 'Marchio'),
(2, @id-1, 'Brand'),
(1, @id, 'Modello'),
(2, @id, 'Model');

INSERT INTO `zz_storage_adapters` (`name`, `class`, `options`, `can_delete`, `is_default`, `is_local`) VALUES
('Backup', '\\Modules\\FileAdapters\\Adapters\\LocalAdapter', '{ \"directory\":\"/files/backups\" }', 1, 0, 1);

ALTER TABLE `zz_settings` CHANGE `is_user_setting` `is_user_setting` TINYINT(1) NOT NULL DEFAULT '0';

-- Aggiunta impostazione per l'adattatore di archiviazione per i backup
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Adattatore archiviazione backup', (SELECT `id` FROM `zz_storage_adapters` WHERE name = 'Backup'), 'query=SELECT `id`, `name` AS descrizione FROM `zz_storage_adapters` WHERE `deleted_at` IS NULL ORDER BY `name`', 1, 'Backup', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Adattatore archiviazione backup'), 'Adattatore archiviazione backup', 'Adattatore di archiviazione da utilizzare per i backup'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Adattatore archiviazione backup'), 'Backup storage adapter', 'Storage adapter to use for backups');

-- Aggiunta impostazione per la password dei backup esterni
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Password di protezione backup', '', 'password', 1, 'Backup', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Password di protezione backup'), 'Password di protezione backup', 'Password da utilizzare per proteggere i backup in formato zip'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Password di protezione backup'), 'Backup protection password', 'Password to use for protecting zip backups');

-- Rinomina campo is_completato in is_bloccato nelle tabelle degli stati
-- Tabella in_statiintervento
ALTER TABLE `in_statiintervento` CHANGE `is_completato` `is_bloccato` TINYINT(1) NOT NULL;

-- Tabella co_statipreventivi
ALTER TABLE `co_statipreventivi` CHANGE `is_completato` `is_bloccato` BOOLEAN NOT NULL DEFAULT FALSE;

-- Tabella co_staticontratti
ALTER TABLE `co_staticontratti` CHANGE `is_completato` `is_bloccato` BOOLEAN NOT NULL DEFAULT FALSE;

-- Aggiornamento segmento "Non completate" in Interventi
UPDATE `zz_segments` SET `clause` = 'in_interventi.idstatointervento NOT IN(SELECT in_statiintervento.id FROM in_statiintervento WHERE is_bloccato=1)' WHERE `zz_segments`.`name` = 'Non completate' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');

-- Aggiornamento widget "Attività da pianificare"
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE id NOT IN (SELECT idintervento FROM in_interventi_tecnici) AND idstatointervento IN (SELECT id FROM in_statiintervento WHERE is_bloccato = 0) ' WHERE `zz_widgets`.`name` = 'Attività da pianificare';

-- Aggiornamento widget "Contratti in scadenza"
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(`dati`.`id`) AS dato FROM (SELECT `co_contratti`.`id`,((SELECT SUM(`co_righe_contratti`.`qta`) FROM `co_righe_contratti` WHERE `co_righe_contratti`.`um` = \"ore\" AND `co_righe_contratti`.`idcontratto` = `co_contratti`.`id`) - IFNULL((SELECT SUM(`in_interventi_tecnici`.`ore`) FROM `in_interventi_tecnici` INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` WHERE `in_interventi`.`id_contratto` = `co_contratti`.`id` AND `in_interventi`.`idstatointervento` IN (SELECT `in_statiintervento`.`id` FROM `in_statiintervento` WHERE `in_statiintervento`.`is_bloccato` = 1)),0)) AS `ore_rimanenti`, DATEDIFF(`data_conclusione`, NOW()) AS giorni_rimanenti, `data_conclusione`, `ore_preavviso_rinnovo`, `giorni_preavviso_rinnovo`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica` = `co_contratti`.`idanagrafica`) AS ragione_sociale FROM `co_contratti` INNER JOIN `co_staticontratti` ON `co_staticontratti`.`id` = `co_contratti`.`idstato` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `rinnovabile` = 1 AND YEAR(`data_conclusione`) > 1970 AND `co_contratti`.`id` NOT IN (SELECT `idcontratto_prev` FROM `co_contratti` contratti) AND `co_staticontratti_lang`.`title` NOT IN (\"Concluso\", \"Rifiutato\", \"Bozza\") HAVING (`ore_rimanenti` <= `ore_preavviso_rinnovo` OR DATEDIFF(`data_conclusione`, NOW()) <= ABS(`giorni_preavviso_rinnovo`)) ORDER BY `giorni_rimanenti` ASC,`ore_rimanenti` ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Aggiornamento impostazione "Stato dell'attività alla chiusura"
UPDATE `zz_settings` SET `tipo` = 'query=SELECT `in_statiintervento`.`id`, `name` AS text FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE is_bloccato = 1' WHERE `zz_settings`.`nome` = "Stato dell'attività alla chiusura";

-- Aggiornamento impostazione "Stato dell'attività dopo la firma"
UPDATE `zz_settings` SET `tipo` = 'query=SELECT `in_statiintervento`.`id`, `name` AS text FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE is_bloccato = 1' WHERE `zz_settings`.`nome` = "Stato dell'attività dopo la firma";

-- Aggiornamento viste per Stati dei preventivi e Stati dei contratti
UPDATE `zz_views` SET `query` = 'IF(is_bloccato, ''Sì'', ''No'')' WHERE `name` = 'Completato' AND `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN ('Stati dei preventivi', 'Stati dei contratti'));

-- Tabella dt_statiddt
ALTER TABLE `dt_statiddt` CHANGE `completato` `is_bloccato` BOOLEAN NOT NULL DEFAULT FALSE;

-- Tabella or_statiordine
ALTER TABLE `or_statiordine` CHANGE `completato` `is_bloccato` BOOLEAN NOT NULL DEFAULT FALSE;

-- Aggiornamento viste per Stati degli ordini e Stati DDT
UPDATE `zz_views` SET `query` = 'IF(is_bloccato, ''Sì'', ''No'')' WHERE `name` = 'Completato' AND `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN ('Stati degli ordini', 'Stati DDT'));

-- Rinomina le colonne "Completato" in "Bloccato" nelle viste
UPDATE `zz_views` SET `name` = 'Bloccato' WHERE `name` = 'Completato' AND `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN ('Stati dei preventivi', 'Stati dei contratti', 'Stati degli ordini', 'Stati DDT'));

-- Aggiornamento delle traduzioni nelle viste
UPDATE `zz_views_lang` SET `title` = 'Bloccato' WHERE `id_record` IN (SELECT `id` FROM `zz_views` WHERE `name` = 'Bloccato');
