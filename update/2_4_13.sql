-- Aggiornamento vista contratti con totale imponibile
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `co_contratti`
    INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT OUTER JOIN (
        SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale`
        FROM `co_righe_contratti`
        GROUP BY `idcontratto`
    ) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT OUTER JOIN (
        SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '''', CONCAT('' - '', nome), '''')) SEPARATOR ''<br>'') AS descrizione, my_impianti_contratti.idcontratto
        FROM my_impianti
            INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto
        GROUP BY my_impianti_contratti.idcontratto
    ) AS impianti ON impianti.idcontratto = co_contratti.id
WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')|
HAVING 2=2
ORDER BY `co_contratti`.`id` DESC' WHERE `name` = 'Contratti';


-- Aggiornamento vista preventivi con totale imponibile
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `co_preventivi`
    INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT OUTER JOIN (
        SELECT `idpreventivo`, SUM(`subtotale` - `sconto`) AS `totale`
        FROM `co_righe_preventivi`
        GROUP BY `idpreventivo`
    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')|
HAVING 2=2
ORDER BY `co_preventivi`.`id` DESC' WHERE `name` = 'Preventivi';


-- Aggiornamento Ddt di acquisto con totale imponibile
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt`
    INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT OUTER JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT OUTER JOIN (
        SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale`
        FROM `dt_righe_ddt`
        GROUP BY `idddt`
    ) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `name` = 'Ddt di acquisto';


-- Aggiornamento Ddt di vendita con totale imponibile
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `dt_ddt`
    INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT OUTER JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT OUTER JOIN (
        SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale`
        FROM `dt_righe_ddt`
        GROUP BY `idddt`
    ) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `name` = 'Ddt di vendita';


-- Aggiornamento Ordini cliente con totale imponibile
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `or_ordini`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT OUTER JOIN (
        SELECT `idordine`, SUM(`subtotale` - `sconto`) AS `totale`
        FROM `or_righe_ordini`
        GROUP BY `idordine`
    ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Ordini cliente';


-- Aggiornamento Ordini fornitore con totale imponibile
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `or_ordini`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT OUTER JOIN (
        SELECT `idordine`, SUM(`subtotale` - `sconto`) AS `totale`
        FROM `or_righe_ordini`
        GROUP BY `idordine`
    ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Ordini fornitore';

-- Fix data registrazione e data competenza non settate
UPDATE `co_documenti` SET `data_registrazione` = `data` WHERE `data_registrazione` IS NULL;
UPDATE `co_documenti` SET `data_competenza` = `data_registrazione` WHERE `data_competenza` IS NULL;

-- Data ora trasporto per ddt
ALTER TABLE `dt_ddt` ADD `data_ora_trasporto` DATETIME NULL DEFAULT NULL AFTER `data`;

--  Corretto widget contratti in scadenza
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato,
       ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.completato = 1)), 0) ) AS ore_rimanenti, 
       data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo
       FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL 
       HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo))' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Impostazione "Filigrana stampe"
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Filigrana stampe', '', 'string', '0', 'Generali');

-- Per elenco coda di invio aggiungo colonna Modulo (legata al template) e Destinatario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Modulo', '(SELECT zz_modules.title FROM zz_modules WHERE zz_modules.id = em_templates.id_module)', 3, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Destinatari', '(SELECT GROUP_CONCAT(address SEPARATOR "<br>") FROM em_email_receiver WHERE em_email_receiver.id_email = em_emails.id)', 1, 1, 0, 1, 1, 0);

-- Modulo Relazioni
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Relazioni', 'Relazioni', 'relazioni_anagrafiche', 'SELECT |select|
FROM `an_relazioni`
WHERE 1=1 
HAVING 2=2
ORDER BY `an_relazioni`.`created_at` DESC', '', 'fa fa-angle-right ', '2.4.13', '2.*', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Anagrafiche'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Relazioni'), 'id', 'an_relazioni.id', 1, 0, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Relazioni'), 'Descrizione', 'an_relazioni.descrizione', 2, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Relazioni'), 'Colore', 'an_relazioni.colore', 3, 1, 0, 1, 1, 0);

-- Ripristino modulo pianificazione fatturazione
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES
(NULL, 'Pianificazione fatturazione', 'Pianificazione fatturazione', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'tab', 'contratti.fatturaordiniservizio.php', 1, 0, 0, '', '', NULL, NULL, '', '');

-- Aggiunta campo note nello scadenzario --
ALTER TABLE `co_scadenziario` ADD `note` VARCHAR(255) DEFAULT NULL AFTER `data_pagamento`; 

-- Aggiunta note in vista scadenzario --
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Scadenzario' ), 'Note', 'co_scadenziario.note', '5', '1', '0', '0', '', '', '0', '0', '0');

UPDATE `zz_settings` SET `nome` = 'Ora inizio sul calendario' WHERE `zz_settings`.`nome` = 'Inizio orario lavorativo';
UPDATE `zz_settings` SET `nome` = 'Ora fine sul calendario' WHERE `zz_settings`.`nome` = 'Fine orario lavorativo';
UPDATE `zz_settings` SET `nome` = 'Formato codice attività' WHERE `zz_settings`.`nome` = 'Formato codice intervento';

-- Inizio orario lavorativo
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `help`) VALUES (NULL, 'Inizio orario lavorativo', '08:00:00', 'time', '1', 'Interventi', 'Inizio dell''orario lavorativo standard.');

-- Fine orario lavorativo
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `help`) VALUES (NULL, 'Fine orario lavorativo', '18:00:00', 'time', '1', 'Interventi', 'Fine dell''orario lavorativo standard.');

-- Giorni lavorativi
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,`help`) VALUES (NULL, 'Giorni lavorativi', 'Lunedì,Martedì,Mercoledì,Giovedì,Venerdì', 'multiple[Lunedì,Martedì,Mercoledì,Giovedì,Venerdì,Sabato,Domenica]', '1', 'Interventi', '');

ALTER TABLE `zz_settings` CHANGE `help` `help` TEXT;

UPDATE `zz_settings` SET `help` = '<p>Impostare la maschera senza indicare l''anno per evitare il reset del contatore.</p><ul><li><b>####</b>: Numero progressivo del documento, con zeri non significativi per raggiungere il numero desiderato di caratteri</li><li><b>YYYY</b>: Anno corrente a 4 cifre</li><li><b>yy</b>: Anno corrente a 2 cifre</li></ul>' WHERE `zz_settings`.`nome` = 'Formato codice preventivi';

-- Fix nome hook Aggiornamenti
UPDATE `zz_hooks` SET `name` = 'Aggiornamenti' WHERE `class` = 'Modules\\Aggiornamenti\\UpdateHook';

-- Colonne aggiuntive articoli
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `mg_articoli` LEFT OUTER JOIN an_anagrafiche ON mg_articoli.id_fornitore=an_anagrafiche.idanagrafica LEFT OUTER JOIN co_iva ON mg_articoli.idiva_vendita=co_iva.id LEFT OUTER JOIN (SELECT SUM(qta-qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine=or_ordini.id WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato=0) GROUP BY idarticolo) a ON a.idarticolo=mg_articoli.id WHERE 1=1 AND (`mg_articoli`.`deleted_at`) IS NULL HAVING 2=2 ORDER BY `descrizione`' WHERE `zz_modules`.`name` = 'Articoli';

UPDATE `zz_views` SET `query` = 'mg_articoli.codice' WHERE `zz_views`.`name` = 'Codice' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');
UPDATE `zz_views` SET `query` = 'mg_articoli.id' WHERE `zz_views`.`name` = 'id' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');
UPDATE `zz_views` SET `query` = 'mg_articoli.descrizione' WHERE `zz_views`.`name` = 'Descrizione' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Prezzo vendita ivato', 'IF( co_iva.percentuale IS NOT NULL, (mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * co_iva.percentuale / 100), mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita*(SELECT co_iva.percentuale FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore AND nome=\'Iva predefinita\')/100 )', 8, 1, 0, 1, '', '', 0, 0, 1),
(NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Q.tà impegnata', 'IFNULL(a.qta_impegnata, 0)', 10, 1, 0, 1, '', '', 0, 0, 1),
(NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Q.tà disponibile', 'qta-IFNULL(a.qta_impegnata, 0)', 11, 1, 0, 1, '', '', 0, 0, 1);

UPDATE `zz_views` SET `order` = '9' WHERE `zz_views`.`name` = 'Q.tà' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');

UPDATE `zz_views` SET `visible` = '1' WHERE `zz_views`.`name` = 'Fornitore' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');
UPDATE `zz_views` SET `visible` = '1' WHERE `zz_views`.`name` = 'Prezzo di acquisto' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');
UPDATE `zz_views` SET `visible` = '1' WHERE `zz_views`.`name` = 'Prezzo di vendita' AND `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Articoli');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Barcode', 'mg_articoli.barcode', '2', '1', '0', '0', '', '', '1', '0', '1');

-- Aggiunto help per maschera codice attività
UPDATE `zz_settings` SET `help` = '<p>Impostare la maschera senza indicare l''anno per evitare il reset del contatore.</p><ul><li><b>####</b>: Numero progressivo dell''attività, con zeri non significativi per raggiungere il numero desiderato di caratteri</li><li><b>YYYY</b>: Anno corrente a 4 cifre</li><li><b>yy</b>: Anno corrente a 2 cifre</li></ul>' WHERE `zz_settings`.`nome` = 'Formato codice attività';

-- Rimosso stato completato dallo stato ordine Parzialmente evaso 
UPDATE `or_statiordine` SET `completato` = '0' WHERE `or_statiordine`.`descrizione` = 'Parzialmente evaso';

-- Fatture di vendita perfezionato campo totale
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT OUTER JOIN (
        SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `imponibile`,
        SUM(`subtotale` - `sconto` + `iva` ) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (
        SELECT `numero_esterno`, `id_segment`
        FROM `co_documenti`
        WHERE `co_documenti`.`idtipodocumento` IN(SELECT `id` FROM `co_tipidocumento` WHERE `dir` = ''entrata'') |date_period(`co_documenti`.`data`)| AND `numero_esterno` != ''''
        GROUP BY `id_segment`, `numero_esterno`
        HAVING COUNT(`numero_esterno`) > 1
    ) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment`
    LEFT OUTER JOIN (
        SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`
        FROM `zz_operations`
            INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`
            INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`
            INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`
        WHERE `zz_modules`.`name` = ''Fatture di vendita'' AND `zz_operations`.`op` = ''send-email''
        GROUP BY `zz_operations`.`id_record`
    ) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
WHERE 1=1 AND `dir` = ''entrata'' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Fatture di vendita';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Fatture di vendita'), 'Imponibile', 'righe.imponibile', '5', '1', '0', '1', '', '', '1', '1', '1');

UPDATE `zz_views` SET `order` = '6' WHERE `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Fatture di vendita') AND `zz_views`.`name` = 'Totale';

-- Fatture di acquisto perfezionato campo totale
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT OUTER JOIN (
        SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `imponibile`,
        SUM(`subtotale` - `sconto`  + `iva`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
WHERE 1=1 AND `dir` = ''uscita'' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero_esterno` = '''', `co_documenti`.`numero`, `co_documenti`.`numero_esterno`) AS UNSIGNED) DESC' WHERE `name` = 'Fatture di acquisto';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Fatture di acquisto'), 'Imponibile', 'righe.imponibile', '5', '1', '0', '1', '', '', '1', '1', '1');

UPDATE `zz_views` SET `order` = '6' WHERE `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE `name`='Fatture di acquisto') AND `zz_views`.`name` = 'Totale';

-- Widget Fatturato fatture di vendita
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT((SELECT SUM(subtotale-sconto)), 2), '','', ''#''), ''.'', '',''), ''#'', ''.''), ''&euro;'') AS dato FROM (co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=''entrata'' |segment| AND data >= ''|period_start|'' AND data <= ''|period_end|'' AND 1=1' WHERE `zz_widgets`.`name` = 'Fatturato';

-- Widget Acquisti fatture di acquisto
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT((SELECT SUM(subtotale-sconto)), 2), '','', ''#''), ''.'', '',''), ''#'', ''.''), ''&euro;'') AS dato FROM (co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=''uscita'' |segment| AND data >= ''|period_start|'' AND data <= ''|period_end|'' AND 1=1' WHERE `zz_widgets`.`name` = 'Acquisti';

UPDATE `zz_widgets` SET `help` = 'Crediti iva inclusa accumulati con i clienti durante tutti gli anni di attività.' WHERE `zz_widgets`.`name` = 'Crediti da clienti';
UPDATE `zz_widgets` SET `help` = 'Debiti iva inclusa accumulati con i fornitori durante tutti gli anni di attività.' WHERE `zz_widgets`.`name` = 'Debiti verso fornitori';

-- Fix relazione su modulo liste
ALTER TABLE `em_list_anagrafica` DROP FOREIGN KEY `em_list_anagrafica_ibfk_1`; ALTER TABLE `em_list_anagrafica` ADD CONSTRAINT `em_list_anagrafica_ibfk_1` FOREIGN KEY (`id_list`) REFERENCES `em_lists`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
