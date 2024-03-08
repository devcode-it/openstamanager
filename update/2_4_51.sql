-- Aggiunto modulo Stati fatture
INSERT INTO `zz_modules` (`name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES ('Stati fatture', 'Stati fatture','stati_fattura', 'SELECT |select| FROM `co_statidocumento` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.50', '2.4.50', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1');

-- Aggiunta viste Stati fatture
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'Icona', 'icona', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'Descrizione', 'descrizione', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati fatture'), 'color_Colore', 'colore', 1, 0, 0, 1, 0);

-- Flag verificato in Movimenti
ALTER TABLE `co_movimenti` ADD `verified_at` TIMESTAMP NULL AFTER `totale_reddito`, ADD `verified_by` INT NOT NULL AFTER `verified_at`; 

-- Aggiunta vista Allegati in Attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Allegati', 'IF(zz_files.name != \'\', GROUP_CONCAT(\' \', zz_files.name), \'No\')', '30', '1', '0', '0', '0', '', '', '0', '0', '0');

-- Aggiunta impostazione Crea contratto rinnovabile di default alla creazione di un contratto
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Crea contratto rinnovabile di default", '0', 'boolean', 1, 'Contratti', 2, 'Attivando questa impostazione i nuovi contratti creati saranno impostati automaticamente come Rinnovabili.');

-- Aggiunta impostazione Giorni di preavviso di default alla creazione di un contratto
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Giorni di preavviso di default", '2', 'decimal', 1, 'Contratti', 3, 'Inserire il numero di giorni di preavviso da impostare automaticamente alla creazione di un contratto.');

-- Checklist plugin Impianti
ALTER TABLE `zz_checks` ADD `id_module_from` INT NOT NULL AFTER `id`, ADD `id_record_from` INT NOT NULL AFTER `id_module_from`; 
UPDATE `zz_modules` SET `use_checklists` = '1' WHERE `zz_modules`.`name` = 'Categorie impianti'; 

-- Aggiunto modulo Gestione task
INSERT INTO `zz_modules` (`name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES ('Gestione task', 'Gestione task','gestione_task', 'SELECT |select| FROM `zz_tasks` WHERE 1=1 HAVING 2=2', '', 'fa fa-calendar', '2.4.51', '2.4.51', '5', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '1');

-- Aggiunta viste Gestione task
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Nome', 'name', 1, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Expression', 'expression', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Prossima esecuzione', 'next_execution_at', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Precedente esecuzione', 'last_executed_at', 4, 1, 0, 0, 1);

-- Fix che evita che venga allegato il riepilogo interventi con tutti gli interventi del tecnico
DELETE FROM `em_print_template` WHERE `em_print_template`.`id_print` IN (SELECT `id` FROM `zz_prints` WHERE `zz_prints`.`is_record` = 0);

-- Il widget Notifiche interne è ora Note interne
UPDATE `zz_widgets` SET `text` = 'Note interne' WHERE `zz_widgets`.`name` = "Note interne";
