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