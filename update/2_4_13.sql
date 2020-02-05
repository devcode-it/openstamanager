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

UPDATE `zz_hooks` SET `name` = 'Aggiornamenti' WHERE `class` = 'Modules\Aggiornamenti\UpdateHook';

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


UPDATE `zz_settings` SET `help` = '<p>Impostare la maschera senza indicare l''anno per evitare il reset del contatore.</p><ul><li><b>####</b>: Numero progressivo dell''attività, con zeri non significativi per raggiungere il numero desiderato di caratteri</li><li><b>YYYY</b>: Anno corrente a 4 cifre</li><li><b>yy</b>: Anno corrente a 2 cifre</li></ul>' WHERE `zz_settings`.`nome` = 'Formato codice attività';

UPDATE `or_statiordine` SET `completato` = '0' WHERE `or_statiordine`.`descrizione` ='Parzialmente evaso';
