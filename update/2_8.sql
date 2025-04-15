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