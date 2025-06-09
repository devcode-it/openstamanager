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

-- Aggiunta colonne marche e Modello nella vista Articoli (nascoste di default)
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Articoli';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Marche', '(SELECT `name` FROM `zz_marche` WHERE `zz_marche`.`id` = `mg_articoli`.`id_marca`)', 15, 1, 0, 0, 0, '', '', 0, 0, 0, 0),
(@id_module, 'Modello', '`mg_articoli`.`id_modello`', 16, 1, 0, 0, 0, '', '', 0, 0, 0, 0);

SELECT @id:= MAX(`id`) FROM `zz_views`;
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, @id-1, 'Marche'),
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

-- Allineamento vista Fatture di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.|lang|)
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`nome`, ' - ', `iban`) AS `descrizione` FROM `co_banche`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN (SELECT `iddocumento`, GROUP_CONCAT(`co_pianodeiconti3`.`descrizione`) AS `descrizione` FROM `co_righe_documenti` INNER JOIN `co_pianodeiconti3` ON `co_pianodeiconti3`.`id` = `co_righe_documenti`.`idconto` GROUP BY iddocumento) AS `conti` ON `conti`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`idanagrafica` AS `anagrafica`, `d`.`id_segment`, YEAR(`d`.`data`) AS `anno` FROM `co_documenti` AS `d`
    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `d`.`id_segment`, `numero_documento`, `d`.`idanagrafica`, YEAR(`d`.`data`)) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`idanagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment` AND `d`.`anno` = YEAR(`co_documenti`.`data`))
WHERE
    1=1
AND
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)| |date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    `co_documenti`.`id`, `d`.`conteggio`
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = 'Fatture di acquisto';

RENAME TABLE `mg_categorie` TO `zz_categorie`;
RENAME TABLE `mg_categorie_lang` TO `zz_categorie_lang`;

ALTER TABLE `zz_categorie` ADD `is_articolo` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `zz_categorie` ADD `is_impianto` TINYINT(1) NOT NULL DEFAULT 0;

UPDATE `zz_categorie` SET `is_articolo` = 1 WHERE `id` IN (SELECT DISTINCT `id_categoria` FROM `mg_articoli` WHERE `id_categoria` IS NOT NULL AND `id_categoria` > 0)
OR `id` IN (SELECT DISTINCT `id_sottocategoria` FROM `mg_articoli` WHERE `id_sottocategoria` IS NOT NULL AND `id_sottocategoria` > 0);

INSERT INTO `zz_categorie` (`colore`, `parent`, `is_articolo`, `is_impianto`)
SELECT `colore`, `parent`, 0, 1 FROM `my_impianti_categorie`;

INSERT INTO `zz_categorie_lang` (`id_lang`, `id_record`, `title`)
SELECT `mic_lang`.`id_lang`, `zz_cat`.`id`, `mic_lang`.`title`
FROM `my_impianti_categorie_lang` `mic_lang`
JOIN `my_impianti_categorie` `mic` ON `mic_lang`.`id_record` = `mic`.`id`
JOIN `zz_categorie` `zz_cat` ON `zz_cat`.`is_impianto` = 1 AND `zz_cat`.`colore` = `mic`.`colore`;

UPDATE `my_impianti` `imp`
JOIN `my_impianti_categorie` `mic` ON `imp`.`id_categoria` = `mic`.`id`
JOIN `zz_categorie` `zz_cat` ON `zz_cat`.`is_impianto` = 1 AND `zz_cat`.`colore` = `mic`.`colore`
SET `imp`.`id_categoria` = `zz_cat`.`id`;

UPDATE `my_impianti` `imp`
JOIN `my_impianti_categorie` `mic` ON `imp`.`id_sottocategoria` = `mic`.`id`
JOIN `zz_categorie` `zz_cat` ON `zz_cat`.`is_impianto` = 1 AND `zz_cat`.`colore` = `mic`.`colore`
SET `imp`.`id_sottocategoria` = `zz_cat`.`id`;

DROP TABLE IF EXISTS `my_impianti_categorie_lang`;
ALTER TABLE `my_impianti` DROP FOREIGN KEY `my_impianti_ibfk_1`;
DROP TABLE IF EXISTS `my_impianti_categorie`;

UPDATE `zz_modules` SET `name` = 'Categorie' WHERE `name` = 'Categorie articoli' ;
UPDATE `zz_modules_lang` SET `title` = 'Categorie' WHERE `title` = 'Categorie articoli' ;
DELETE FROM `zz_modules` WHERE `name` ='Categorie impianti';

-- Aggiungi viste per indicare se la categoria è di tipo Articolo o Impianto
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie'), 'Articolo', 'IF(`zz_categorie`.`is_articolo` = 1, "Sì", "No")', 4, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie'), 'Impianto', 'IF(`zz_categorie`.`is_impianto` = 1, "Sì", "No")', 5, 1, 0, 0, 0, NULL, NULL, 1, 0, 0, 0);

-- Aggiungi traduzioni per le nuove viste
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Articolo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie')), 'Articolo'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Articolo' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie')), 'Article'),
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Impianto' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie')), 'Impianto'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Impianto' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie')), 'Equipment');

UPDATE `zz_modules` SET `directory` = 'categorie' WHERE `zz_modules`.`name` = 'Categorie'; 
UPDATE `zz_modules` SET `attachments_directory` = 'categorie' WHERE `zz_modules`.`name` = 'Categorie';

-- Allineamento vista Articoli
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS qta_impegnata, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'entrata' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS qta_ordinata, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'uscita' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1
    GROUP BY `idarticolo`) ordini_fornitore ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN `mg_categorie` ON `mg_articoli`.`id_categoria` = `mg_categorie`.`id`
    LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id` = `mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.|lang|)
    LEFT JOIN `mg_categorie` AS sottocategorie ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `mg_categorie_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT `co_iva`.`percentuale` AS perc, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id`=`zz_settings`.`valore`)AS iva ON `iva`.`nome`= 'Iva predefinita' 
    LEFT JOIN mg_scorte_sedi ON mg_scorte_sedi.id_articolo = mg_articoli.id
    LEFT JOIN (SELECT CASE WHEN MIN(differenza) < 0 THEN -1 WHEN MAX(threshold_qta) > 0 THEN 1 ELSE 0 END AS stato_giacenza, idarticolo FROM (SELECT SUM(mg_movimenti.qta) - COALESCE(mg_scorte_sedi.threshold_qta, 0) AS differenza, COALESCE(mg_scorte_sedi.threshold_qta, 0) as threshold_qta, mg_movimenti.idarticolo FROM mg_movimenti LEFT JOIN mg_scorte_sedi ON mg_scorte_sedi.id_sede = mg_movimenti.idsede AND mg_scorte_sedi.id_articolo = mg_movimenti.idarticolo GROUP BY mg_movimenti.idarticolo, mg_movimenti.idsede) AS subquery GROUP BY idarticolo) AS giacenze ON giacenze.idarticolo = mg_articoli.id
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
GROUP BY
    `mg_articoli`.`id`
HAVING
    2=2
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `name` = 'Articoli';

UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'mg_categorie_lang', 'zz_categorie_lang') WHERE `options` LIKE '%mg_categorie_lang%';
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'mg_categorie', 'zz_categorie') WHERE `options` LIKE '%mg_categorie%';
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'mg_categorie_lang', 'zz_categorie_lang') WHERE `query` LIKE '%mg_categorie_lang%';
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'mg_categorie', 'zz_categorie') WHERE `query` LIKE '%mg_categorie%';
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'my_impianti_categorie_lang', 'zz_categorie_lang') WHERE `options` LIKE '%my_impianti_categorie_lang%';
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'my_impianti_categorie', 'zz_categorie') WHERE `options` LIKE '%my_impianti_categorie%';
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'my_impianti_categorie_lang', 'zz_categorie_lang') WHERE `query` LIKE '%my_impianti_categorie_lang%';
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'my_impianti_categorie', 'zz_categorie') WHERE `query` LIKE '%my_impianti_categorie%';

RENAME TABLE `mg_marchi` TO `zz_marche`;

ALTER TABLE `zz_marche` ADD `parent` INT NOT NULL AFTER `link`, ADD `is_articolo` BOOLEAN NOT NULL DEFAULT 1, ADD `is_impianto` BOOLEAN NOT NULL DEFAULT 0; 

ALTER TABLE `mg_articoli` CHANGE `id_marchio` `id_marca` INT NULL DEFAULT NULL; 
ALTER TABLE `mg_articoli` CHANGE `modello` `id_modello` INT NULL DEFAULT NULL; 

UPDATE `zz_marche` SET `is_articolo` = 1 WHERE `id` IN (SELECT DISTINCT `id_marca` FROM `mg_articoli` WHERE `id_marca` IS NOT NULL AND `id_marca` > 0) OR `id` IN (SELECT DISTINCT `id_modello` FROM `mg_articoli` WHERE `id_modello` IS NOT NULL AND `id_modello` > 0);

INSERT INTO `zz_marche` (`name`,`parent`, `is_articolo`, `is_impianto`) SELECT `title`, `parent`, 0, 1 FROM `my_impianti_marche` INNER JOIN `my_impianti_marche_lang` ON `my_impianti_marche`.`id` = `my_impianti_marche_lang`.`id_record` AND `my_impianti_marche_lang`.`id_lang` = 1;

UPDATE `zz_modules` SET `name` = 'Marche', `directory` = 'marche' WHERE `zz_modules`.`name` = 'Marchi'; 
UPDATE `zz_modules_lang` SET `title` = 'Marche' WHERE `zz_modules_lang`.`title` = 'Marchi'; 

UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'mg_marchi', 'zz_marche') WHERE `options` LIKE '%mg_marchi%';

UPDATE `zz_views` SET `query` = REPLACE(`query`, 'mg_marchi', 'zz_marche') WHERE `query` LIKE '%mg_marchi%';

UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'my_impianti_marche_lang', 'zz_marche') WHERE `options` LIKE '%my_impianti_marche_lang%';
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'my_impianti_marche', 'zz_marche') WHERE `options` LIKE '%my_impianti_marche%';

UPDATE `zz_views` SET `query` = REPLACE(`query`, 'my_impianti_marche_lang', 'zz_marche') WHERE `query` LIKE '%my_impianti_marche_lang%';
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'my_impianti_marche', 'zz_marche') WHERE `query` LIKE '%my_impianti_marche%';

DELETE FROM `zz_modules` WHERE `name` = 'Marche impianti';

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `zz_marche` WHERE 1=1 AND parent = 0 HAVING 2=2 ORDER BY `zz_marche`.`name`' WHERE `zz_modules`.`name` = 'Marche'; 

ALTER TABLE `my_impianti_marche_lang` DROP FOREIGN KEY `my_impianti_marche_lang_ibfk_1`;
ALTER TABLE `my_impianti_marche_lang` DROP FOREIGN KEY `my_impianti_marche_lang_ibfk_2`;
DROP TABLE `my_impianti_marche`;
DROP TABLE `my_impianti_marche_lang`;

-- Allineamento vista Impianti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `my_impianti`
    LEFT JOIN `an_anagrafiche` AS clienti ON `clienti`.`idanagrafica` = `my_impianti`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS tecnici ON `tecnici`.`idanagrafica` = `my_impianti`.`idtecnico` 
    LEFT JOIN `zz_categorie` ON `zz_categorie`.`id` = `my_impianti`.`id_categoria`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` as sub ON sub.`id` = `my_impianti`.`id_sottocategoria`
    LEFT JOIN `zz_categorie_lang` as sub_lang ON (sub.`id` = sub_lang.`id_record` AND sub_lang.|lang|)
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi) AS sede ON sede.id = my_impianti.idsede
    LEFT JOIN `zz_marche` as marca ON `marca`.`id` = `my_impianti`.`id_marca`
    LEFT JOIN `zz_marche` as modello ON `modello`.`id` = `my_impianti`.`id_modello`
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `matricola`" WHERE `name` = 'Impianti';

UPDATE `zz_views` SET `query` = 'marca.name' WHERE `name` = 'Marca' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti');
UPDATE `zz_views` SET `query` = 'modello.name' WHERE `name` = 'Modello' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti');


-- Allineamento vista Preventivi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `co_preventivi`
    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND co_statipreventivi_lang.|lang|)
    LEFT JOIN (SELECT `idpreventivo`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_preventivi` GROUP BY `idpreventivo`) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
    LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`)AS agente ON `agente`.`idanagrafica`=`co_preventivi`.`idagente`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idpreventivo` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\Preventivi\Preventivo' GROUP BY `idpreventivo`, `original_document_id`) AS `fattura` ON `fattura`.`idpreventivo` = `co_preventivi`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_preventivi`.`id`
WHERE 
    1=1 |segment(`co_preventivi`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)| AND `default_revision` = 1
GROUP BY 
    `co_preventivi`.`id`, `fattura`.`info`
HAVING 
    2=2
ORDER BY 
    `co_preventivi`.`data_bozza` DESC, numero ASC" WHERE `zz_modules`.`name` = 'Preventivi';

-- Allineamento vista Contratti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
    LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN (WITH RigheAgg AS (SELECT idintervento,SUM(prezzo_unitario * qta) AS sommacosti_per_intervento FROM in_righe_interventi GROUP BY idintervento), TecniciAgg AS (SELECT idintervento, SUM(prezzo_ore_consuntivo) AS sommasessioni_per_intervento FROM in_interventi_tecnici GROUP BY idintervento) SELECT SUM(COALESCE(RigheAgg.sommacosti_per_intervento, 0)) AS sommacosti, SUM(COALESCE(TecniciAgg.sommasessioni_per_intervento, 0)) AS sommasessioni, i.id_contratto FROM in_interventi i LEFT JOIN RigheAgg ON RigheAgg.idintervento = i.id LEFT JOIN TecniciAgg ON TecniciAgg.idintervento = i.id GROUP BY i.id_contratto) AS spesacontratto ON spesacontratto.id_contratto = co_contratti.id
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br />') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id LEFT JOIN in_tipiintervento ON in_interventi_tecnici.idtipointervento=in_tipiintervento.id WHERE non_conteggiare=0 GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
    LEFT JOIN `co_categorie_contratti` ON `co_contratti`.`id_categoria` = `co_categorie_contratti`.`id`
    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)
    LEFT JOIN `co_categorie_contratti` AS sottocategorie ON `co_contratti`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `co_categorie_contratti_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
HAVING 
    2=2
ORDER BY 
    `co_contratti`.`data_bozza` DESC" WHERE `name` = 'Contratti';

-- Nuovo modulo "Categorie file"
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Categorie file', 'categorie_files', 'SELECT |select| FROM `zz_files_categories` WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2', '', 'fa fa-circle-o', '2.8', '2.8', '8', (SELECT `id` FROM `zz_modules` AS `t` WHERE `name` = 'Tabelle'), '1', '1', '1', '1');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Categorie file';
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', @id_module, 'Categorie file', 'Categorie file'),
('2', @id_module, 'Categorie file', 'Categorie file');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Categorie file';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Descrizione', 'zz_files_categories.name', '2', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1'),
(@id_module, 'id', '`zz_files_categories`.`id`', '1', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Categorie file';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = @id_module), 'Descrizione'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'Descrizione' AND `id_module` = @id_module), 'Description'),
('1', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id'),
('2', (SELECT `id` FROM `zz_views` WHERE `name` = 'id' AND `id_module` = @id_module), 'id');

CREATE TABLE `zz_files_categories` (`id` INT NOT NULL AUTO_INCREMENT , `name` VARCHAR(255) NOT NULL , `deleted_at` TIMESTAMP NULL , PRIMARY KEY (`id`));
ALTER TABLE `zz_files` ADD `id_category` INT NULL AFTER `category`;

CREATE TABLE `em_files_categories_template` (`id` INT NOT NULL AUTO_INCREMENT , `id_category` INT NOT NULL , `id_template` INT NOT NULL , PRIMARY KEY (`id`));

-- Api seriali
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'app-v1', 'retrieve', 'seriali-cleanup', 'API\\App\\v1\\Seriali', 1),
(NULL, 'app-v1', 'retrieve', 'seriali', 'API\\App\\v1\\Seriali', 1),
(NULL, 'app-v1', 'retrieve', 'seriale', 'API\\App\\v1\\Seriali', 1);