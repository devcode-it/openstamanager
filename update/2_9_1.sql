-- Aggiunta vista per identificare i task disabilitati
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Gestione task';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, '_bg_', 'IF(`zz_tasks`.`enabled`=1, \'#dff0d8\', \'#f2dede\')', '0', '0', '0', '0', '0', NULL, NULL, '0', '0', '0', '1');

SELECT @id_record := `id` FROM `zz_views` WHERE `id_module` = @id_module AND `name` = '_bg_';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, @id_record, '_bg_'),
(2, @id_record, '_bg_');

-- Nuova gestione Hooks con Task
-- Invio emails
INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`) VALUES
('Hook Email', 'Modules\\Emails\\EmailHookTask', '*/5 * * * *', 1);

SELECT @idtask := MAX(id) FROM zz_tasks;
INSERT INTO `zz_tasks_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', @idtask, 'Hook Email'),
('2', @idtask, 'Email Hook');

-- Verifica aggiornamenti
INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`) VALUES
('Hook Aggiornamenti', 'Modules\\Aggiornamenti\\UpdateHookTask', '0 */24 * * *', 1);

SELECT @idtask := MAX(id) FROM zz_tasks;
INSERT INTO `zz_tasks_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', @idtask, 'Hook Aggiornamenti'),
('2', @idtask, 'Updates Hook');

-- Calcolo spazio disponibile
INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`) VALUES
('Hook Spazio disponibile', 'Modules\\StatoServizi\\SpaceHookTask', '0 */24 * * *', 1);

SELECT @idtask := MAX(id) FROM zz_tasks;
INSERT INTO `zz_tasks_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', @idtask, 'Hook Spazio disponibile'),
('2', @idtask, 'Space Hook');

-- Informazioni su services
INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`) VALUES
('Hook Informazioni su Services', 'Modules\\StatoServizi\\ServicesHookTask', '0 */24 * * *', 1);

SELECT @idtask := MAX(id) FROM zz_tasks;
INSERT INTO `zz_tasks_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', @idtask, 'Hook Informazioni su Services'),
('2', @idtask, 'Services Hook');

-- Invio fatture elettroniche
INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`) VALUES
('Hook Invio Fatture Elettroniche', 'Plugins\\ExportFE\\InvoiceHookTask', '*/30 * * * *', 1);

SELECT @idtask := MAX(id) FROM zz_tasks;
INSERT INTO `zz_tasks_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', @idtask, 'Hook Invio Fatture Elettroniche'),
('2', @idtask, 'Electronic Invoices Hook');

-- Importazione automatica ricevute FE
UPDATE `zz_tasks` SET `expression` = '0 */4 * * *' WHERE `name` = 'Importazione automatica Ricevute FE';

INSERT INTO `zz_tasks` (`name`, `class`, `expression`, `enabled`) VALUES
('Hook Importazione Fatture Elettroniche', 'Plugins\\ImportFE\\InvoiceHookTask', '0 */24 * * *', 1);

SELECT @idtask := MAX(id) FROM zz_tasks;
INSERT INTO `zz_tasks_lang` (`id_lang`, `id_record`, `title`) VALUES
('1', @idtask, 'Hook Importazione Fatture Elettroniche'),
('2', @idtask, 'Electronic Invoices Import Hook');

UPDATE `zz_modules` m1 JOIN `zz_modules` m2 ON m2.name = 'Tabelle' SET m1.parent = m2.id WHERE m1.name = 'Categorie contratti';

UPDATE `zz_modules` m1 JOIN `zz_modules` m2 ON m1.parent = m2.id AND m2.name = 'Tabelle' SET m1.icon = 'fa fa-circle-o';
