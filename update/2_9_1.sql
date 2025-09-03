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

-- Aggiorno la query del modulo Articoli
UPDATE `zz_modules`
SET `options` = 'SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_impegnata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = ''entrata'' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_ordinata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = ''uscita'' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) `ordini_fornitore` ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN `zz_categorie` ON `mg_articoli`.`id_categoria` = `zz_categorie`.`id`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT `co_iva`.`percentuale` AS `perc`, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id` = `zz_settings`.`valore`) AS iva ON `iva`.`nome` = ''Iva predefinita''
    LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT CASE WHEN MIN(`differenza`) < 0 THEN -1 WHEN MAX(`threshold_qta`) > 0 THEN 1 ELSE 0 END AS `stato_giacenza`, `idarticolo` FROM (SELECT SUM(`mg_movimenti`.`qta`) - COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `differenza`, COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `threshold_qta`, `mg_movimenti`.`idarticolo` FROM `mg_movimenti` LEFT JOIN `mg_scorte_sedi` ON (`mg_scorte_sedi`.`id_sede` = `mg_movimenti`.`idsede` AND `mg_scorte_sedi`.`id_articolo` = `mg_movimenti`.`idarticolo`) GROUP BY `mg_movimenti`.`idarticolo`, `mg_movimenti`.`idsede`) AS `subquery` GROUP BY `idarticolo`) AS `giacenze` ON `giacenze`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT CASE WHEN COUNT(`mg_articoli_barcode`.`barcode`) <= 2 THEN GROUP_CONCAT(`mg_articoli_barcode`.`barcode` SEPARATOR ''<br />'') ELSE CONCAT((SELECT GROUP_CONCAT(`b1`.`barcode` SEPARATOR ''<br />'') FROM (SELECT `barcode` FROM `mg_articoli_barcode` `b2` WHERE `b2`.`idarticolo` = `mg_articoli_barcode`.`idarticolo` ORDER BY `b2`.`barcode` ASC) `b1`)) END AS `lista`, `mg_articoli_barcode`.`idarticolo` FROM `mg_articoli` LEFT JOIN `mg_articoli_barcode` ON `mg_articoli_barcode`.`idarticolo` = `mg_articoli`.`id` GROUP BY `mg_articoli`.`id`) AS `barcode` ON `barcode`.`idarticolo` = `mg_articoli`.`id`
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `mg_articoli_lang`.`title`' WHERE `zz_modules`.`name` = 'Articoli';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`format` = 1 WHERE `zz_modules`.`name` = 'Gestione task' AND `zz_views`.`name` IN ('Prossima esecuzione', 'Precedente esecuzione');

-- Aggiunta hook per il controllo dello stato del cron
INSERT INTO `zz_hooks` (`name`, `class`, `enabled`, `id_module`) VALUES
('Stato Cron', 'Modules\\StatoServizi\\CronStatusHook', 1, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato servizi'));

-- Aggiunta traduzione per l'hook
INSERT INTO `zz_hooks_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_hooks`), 'Stato Cron'),
(2, (SELECT MAX(`id`) FROM `zz_hooks`), 'Cron Status');

-- Gestione sottoscorta per sede
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(DISTINCT mg_articoli.id) AS dato FROM `mg_articoli`INNER JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`LEFT JOIN (SELECT IFNULL(SUM(qta), 0) AS tot, idarticolo, idsede FROM mg_movimenti GROUP BY idarticolo, idsede) movimenti ON movimenti.idsede = mg_scorte_sedi.id_sede AND movimenti.idarticolo = mg_articoli.id WHERE `mg_articoli`.`attivo` = 1 AND `mg_articoli`.`deleted_at` IS NULL AND `mg_scorte_sedi`.`threshold_qta` > 0 AND IFNULL(movimenti.tot, 0) < `mg_scorte_sedi`.`threshold_qta`' WHERE `zz_widgets`.`name` = 'Articoli in esaurimento';
