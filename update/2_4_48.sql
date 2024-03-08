-- Aggiunta tipologia reply to
ALTER TABLE `em_templates` ADD `tipo_reply_to` VARCHAR(255) NOT NULL AFTER `subject`; 
UPDATE `em_templates` SET `tipo_reply_to` = 'email_fissa' WHERE `reply_to` != '';

-- Fix help setting
UPDATE `zz_settings` SET `help` = "Modifica automaticamente la data di emissione di una fattura al momento della sua emissione, impostando data successiva a quella dell'ultima fattura emessa" WHERE `zz_settings`.`nome` = 'Data emissione fattura automatica';

-- Aggiunto template solleciti raggrupati per anagrafica
INSERT INTO `em_templates` (`id`, `id_module`, `name`, `icon`, `subject`, `tipo_reply_to`, `reply_to`, `cc`, `bcc`, `body`, `read_notify`, `predefined`, `note_aggiuntive`, `deleted_at`, `id_account`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Sollecito di pagamento raggruppato per anagrafica', 'fa fa-envelope', 'Sollecito di pagamento fatture', '', '', '', '', '<html>\r\n<head>\r\n <title></title>\r\n <link href=\"https://svc.webspellchecker.net/spellcheck31/wscbundle/css/wsc.css\" rel=\"stylesheet\" type=\"text/css\" />\r\n</head>\r\n<body data-wsc-instance=\"true\">\r\n<p>Spett.le {ragione_sociale},</p>\r\n\r\n<p>da un riscontro contabile, ci risulta che le seguenti fatture a Voi intestate, siano scadute nelle seguenti date:</p>\r\n\r\n<p>{scadenze_fatture_scadute}</p>\r\n\r\n<p>La sollecitiamo pertanto di provvedere quanto prima a regolarizzare la sua situazione contabile.</p>\r\n\r\n<p>Se ha gi&agrave; provveduto al pagamento, ritenga nulla la presente.</p>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p>La ringraziamo e le porgiamo i nostri saluti.</p>\r\n</body>\r\n</html>\r\n', '0', '0', '', NULL, '1', NULL);

-- Aggiunta impostazione intervallo di tempo attività in minuti
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Numero di minuti di avanzamento delle sessioni delle attività', '1', 'string', '1', 'Attività', NULL, 'Specifica di quanti minuti aumentare o diminuire le sessioni di lavoro delle attività'); 

-- Aggiunta impostazione titolo checklist
ALTER TABLE `zz_checks` ADD `is_titolo` BOOLEAN NOT NULL AFTER `id_parent`;
ALTER TABLE `zz_checklist_items` ADD `is_titolo` BOOLEAN NOT NULL AFTER `id_parent`;

-- Aggiunto modulo Stati ordini
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stati degli ordini', 'Stati degli ordini','stati_ordine', 'SELECT |select| FROM `or_statiordine` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2', '', 'fa fa-angle-right', '2.4.48', '2.4.48', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati degli ordini'), 'Fatturabile', 'IF(is_fatturabile, ''S&igrave;'', ''No'')', 6, 1, 0, 0 ,1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati degli ordini'), 'Completato', 'IF(completato, ''S&igrave;'', ''No'')', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati degli ordini'), 'Impegnato', 'IF(impegnato, ''S&igrave;'', ''No'')', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati degli ordini'), 'Icona', 'icona', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati degli ordini'), 'Descrizione', 'descrizione', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati degli ordini'), 'id', 'id', 1, 0, 0, 1, 0);

-- Aggiunto flag can_delete in stati ordini
ALTER TABLE `or_statiordine` ADD `deleted_at` DATETIME NULL;
ALTER TABLE `or_statiordine` ADD `can_delete` BOOLEAN NOT NULL DEFAULT TRUE;
UPDATE `or_statiordine` SET `can_delete` = '0';

-- Aggiunta risorsa api stati ordine
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'retrieve', 'stati_ordine', 'Modules\\StatiOrdine\\API\\v1\\StatiOrdini', '1');

-- Impostazione firme con tavoletta grafica
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES 
(NULL, 'Sistema di firma', 'Base', 'list[Base,Tavoletta Wacom]', '1', 'Generali', '2', 'Seleziona il sistema di firma per i documenti.'),
(NULL, 'Licenza Wacom SDK', 'eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJiMzNhYWUyODA0NmM0MmE5OTgyY2E1NTdkZjhmY2YxOCIsImV4cCI6MTcwODE3ODg3NCwiaWF0IjoxNjgxNzI2MjY3LCJzZWF0cyI6MCwicmlnaHRzIjpbIkpTX0NPUkUiLCJTSUdfU0RLX0NPUkUiLCJTSUdfU0RLX0lTTyIsIlNJR19TREtfRU5DUllQVElPTiJdLCJkZXZpY2VzIjpbIldBQ09NX0FOWSJdLCJ0eXBlIjoiZXZhbCIsImxpY19uYW1lIjoiTGljZW5zZSBmb3IgSmF2YXNjcmlwdCBEZW1vIiwid2Fjb21faWQiOiJiMzNhYWUyODA0NmM0MmE5OTgyY2E1NTdkZjhmY2YxOCIsImxpY191aWQiOiI1MDU3Mzk4My05MDgzLTRjZjUtOGM5Yy0yNTMwNjc0ZWFmMmIiLCJhcHBzX3dpbmRvd3MiOltdLCJhcHBzX2lvcyI6W10sImFwcHNfYW5kcm9pZCI6W10sIm1hY2hpbmVfaWRzIjpbXX0.inGHe6CM3FexchESeu5mlPccOzrbTeZ_goMxRPppmHEqMcKz8DplC0IHOhjPvfV5c1giHsj_OctuomKWAsqt_lqo9ml4QAmnRiBYhE_itq1UdemIYusaHxapU3CL1Nr3ed9g-2Wq9Dducl-d7jCMNVnvmYM7fexIQAEV6soXBnJbdY4YXCznFftcA2jj2YI6Nz6f9ya-C9R1skQ0xuCEkkESWZ9NC_CSbu4I9h_Uv_X-0voY89H046F7wM1t61JGYMfIYwaDcJGazgD8_71OsRfOf5cUKDYsE0c-gH8vUUbbIMRO6i6zICwgEj2FAki9Rf23blPmvk8ijhBU0ZE_Hg', 'string', '1', 'Tavoletta Wacom', '1', NULL),
(NULL, 'Sfondo firma tavoletta Wacom', '#d3d3d3', 'string', '1', 'Tavoletta Wacom', '2', NULL),
(NULL, 'Luminosità firma Wacom', '15', 'integer', '1', 'Tavoletta Wacom', '3', NULL),
(NULL, 'Contrasto firma Wacom', '3', 'integer', '1', 'Tavoletta Wacom', '4', NULL);