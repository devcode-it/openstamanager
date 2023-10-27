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

-- Aggiunta colonna Allegati in Interventi
UPDATE `zz_modules` SET `options` = "
SELECT
	|select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `idintervento`, SUM(`prezzo_unitario`*`qta`-`sconto`) AS `ricavo_righe`, SUM(`costo_unitario`*`qta`) AS `costo_righe` FROM `in_righe_interventi` GROUP BY `idintervento`) AS `righe` ON `righe`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`
    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />',IF(`an_sedi`.`telefono`!='',CONCAT(`an_sedi`.`telefono`,'<br />'),''),IF(`an_sedi`.`cellulare`!='',CONCAT(`an_sedi`.`cellulare`,'<br />'),''),`an_sedi`.`citta`,IF(`an_sedi`.`indirizzo`!='',CONCAT(' - ',`an_sedi`.`indirizzo`),'')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `in_interventi`.`idsede_destinazione`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idintervento` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = 'Modules\\\\Interventi\\\\Intervento' GROUP BY `idintervento`, `original_document_id`) AS `fattura` ON `fattura`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS `tecnici_assegnati` ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS `tecnici` ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Interventi') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS `descrizione`, `my_impianti_interventi`.`idintervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto` GROUP BY `my_impianti_interventi`.`idintervento`) AS `impianti` ON `impianti`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `co_contratti`.`id`, CONCAT(`co_contratti`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_contratti`) AS `contratto` ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (SELECT `co_preventivi`.`id`, CONCAT(`co_preventivi`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_preventivi`) AS `preventivo` ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (SELECT `or_ordini`.`id`, CONCAT(`or_ordini`.`numero`, ' del ', DATE_FORMAT(`data`, '%d/%m/%Y')) AS `info` FROM `or_ordini`) AS `ordine` ON `ordine`.`id` = `in_interventi`.`id_ordine`
    LEFT JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`
    LEFT JOIN `zz_files` ON (zz_files.id_record = `in_interventi`.`id` AND zz_files.id_module = (SELECT id FROM zz_modules WHERE name = 'Interventi'))
WHERE 
    1=1 |segment(`in_interventi`.`id_segment`)| |date_period(`orario_inizio`,`data_richiesta`)|
GROUP BY 
    `in_interventi`.`id`
HAVING 
    2=2
ORDER BY 
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `name` = 'Interventi';

-- Aggiunta vista Allegati in Attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Allegati', 'IF(zz_files.name != \'\', GROUP_CONCAT(\' \', zz_files.name), \'No\')', '30', '1', '0', '0', '0', '', '', '0', '0', '0');

-- Aggiunta impostazione Crea contratto rinnovabile di default alla creazione di un contratto
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Crea contratto rinnovabile di default", '0', 'boolean', 1, 'Contratti', 2, 'Attivando questa impostazione i nuovi contratti creati saranno impostati automaticamente come Rinnovabili.');

-- Aggiunta impostazione Giorni di preavviso di default alla creazione di un contratto
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Giorni di preavviso di default", '2', 'decimal', 1, 'Contratti', 3, 'Inserire il numero di giorni di preavviso da impostare automaticamente alla creazione di un contratto.');

-- Aggiunto modulo Gestione task
INSERT INTO `zz_modules` (`name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES ('Gestione task', 'Gestione task','gestione_task', 'SELECT |select| FROM `zz_tasks` WHERE 1=1 HAVING 2=2', '', 'fa fa-calendar', '2.4.51', '2.4.51', '5', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '1');

-- Aggiunta viste Gestione task
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'id', 'id', 1, 0, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Nome', 'name', 1, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Expression', 'expression', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Prossima esecuzione', 'next_execution_at', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione task'), 'Precedente esecuzione', 'last_executed_at', 4, 1, 0, 0, 1);

DELETE FROM `em_print_template` INNER JOIN `zz_prints` ON `em_print_template`.`id_print` = `zz_prints`.`id` WHERE `zz_prints`.`is_record` = 0;