-- Escluso Art.74 ter D.P.R. 633/72
INSERT INTO `co_iva` (`descrizione`, `percentuale`, `indetraibile`, `esente`, `codice_natura_fe`, `codice`, `default`) VALUES
("Escluso Art.74 ter D.P.R. 633/72", 0, 0, 1, "N4", NULL, 1);

-- Aggiungo vista "Conto" per Fatture di acquisto (opzionale)
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name` = 'Fatture di acquisto') , 'Conto', '(SELECT GROUP_CONCAT(DISTINCT(co_pianodeiconti3.descrizione)) FROM co_righe_documenti INNER JOIN co_pianodeiconti3 ON co_pianodeiconti3.id = co_righe_documenti.idconto WHERE co_righe_documenti.iddocumento = co_documenti.id)', 10, 1, 0, 0, '', '', 0, 0, 1);

-- Stato FE (Notifica esito)
INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES ('NE', 'Notifica esito', 'fa fa-check text-warning');

-- Aggiunta data di registrazione, utile per le fatture di acquisto
ALTER TABLE `co_documenti` ADD `data_registrazione` DATE NULL AFTER `data`, ADD `data_competenza` DATE NULL AFTER `data`;

-- Importo marca da bollo a 2 (https://www.fiscoetasse.com/approfondimenti/12090-applicazione-della-marca-da-bollo-sulle-fatture.html)
UPDATE `zz_settings` SET `valore` = '2' WHERE `zz_settings`.`nome` = 'Importo marca da bollo';

-- Stampa preventivo (senza totali)
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE name='Preventivi'), 1, 'Preventivo (senza totali)', 'Preventivo (senza totali)', 'preventivi', 'idpreventivo', '{"pricing":true, "hide_total":true}', 'fa fa-print', '', '', 0, 0, 1, 1);

-- Dimensione dei file caricati
ALTER TABLE `zz_files` ADD `size` INT(11) NULL AFTER `category`;

-- Elimino data_evasione da co_righe_preventivi
ALTER TABLE `co_righe_preventivi` DROP `data_evasione`;

-- Allineo qta evase per le righe dei preventivi inseriti in una fattura
UPDATE `co_righe_preventivi` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` SET  `co_righe_preventivi`.`qta_evasa` = `co_righe_documenti`.`qta`;

-- Allineo qta evase per le righe dei contratti inseriti in una fattura
UPDATE `co_righe_contratti` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` SET `co_righe_contratti`.`qta_evasa` = `co_righe_documenti`.`qta`;

-- Standardizzazione stati preventivi e contratti
ALTER TABLE `co_staticontratti` ADD `is_completato` BOOLEAN NOT NULL DEFAULT FALSE AFTER `pianificabile`;

ALTER TABLE `co_statipreventivi` CHANGE `completato` `is_completato` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `co_statipreventivi` CHANGE `fatturabile` `is_fatturabile` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `co_statipreventivi` ADD `is_pianificabile` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_fatturabile`;
ALTER TABLE `co_statipreventivi` DROP `annullato`;

ALTER TABLE `co_staticontratti` CHANGE `pianificabile` `is_pianificabile` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `co_staticontratti` CHANGE `fatturabile` `is_fatturabile` BOOLEAN NOT NULL DEFAULT FALSE;

-- Fix degli stati predefiniti preventivi e contratti
UPDATE `co_staticontratti` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Bozza';
UPDATE `co_staticontratti` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'In attesa di conferma';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Accettato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Rifiutato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 1 WHERE `descrizione` = 'In lavorazione';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 0 WHERE `descrizione` = 'Fatturato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 0 WHERE `descrizione` = 'Pagato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Concluso';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 1 WHERE `descrizione` = 'Parzialmente fatturato';

UPDATE `co_statipreventivi` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Bozza';
UPDATE `co_statipreventivi` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'In attesa di conferma';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Accettato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Rifiutato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 1 WHERE `descrizione` = 'In lavorazione';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 0 WHERE `descrizione` = 'Fatturato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 0 WHERE `descrizione` = 'Pagato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Concluso';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 1 WHERE `descrizione` = 'Parzialmente fatturato';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato, co_contratti.id, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND rinnovabile=1 AND ( ( (SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.completato = 1)), 0) ) < co_contratti.ore_preavviso_rinnovo OR NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) ) AND YEAR(data_conclusione) > 1970 HAVING ISNULL((SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev=co_contratti.id )) ORDER BY giorni_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_ordiniservizio WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Ordini di servizio da impostare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_promemoria WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Interventi da pianificare';

-- Fix filtri per data
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE 1=1 AND `dir` = ''uscita'' |segment| |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(IF(numero_esterno='''', numero, numero_esterno) AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di acquisto';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id WHERE 1=1 AND dir = ''entrata'' |segment| |date_period(`data`)| HAVING 2=2 ORDER BY data DESC, CAST(numero_esterno AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di vendita';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM (`in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` WHERE 1=1  |date_period(`orario_inizio`,`data_richiesta`)| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_preventivi` WHERE 1=1 AND default_revision=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')| HAVING 2=2 ORDER BY `id` DESC' WHERE `zz_modules`.`name` = 'Preventivi';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_contratti` WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')| HAVING 2=2 ORDER BY `id` DESC' WHERE `zz_modules`.`name` = 'Contratti';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_movimenti` INNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`idconto` = `co_pianodeiconti3`.`id` WHERE 1=1 AND `primanota` = 1 |date_period(`co_movimenti`.`data`)| GROUP BY `idmastrino`, `primanota`, `co_movimenti`.`data` HAVING 2=2 ORDER BY `co_movimenti`.`data` DESC' WHERE `zz_modules`.`name` = 'Prima nota';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id` WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di vendita';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id` WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ddt di acquisto';

UPDATE `zz_views` SET `query` = 'co_movimenti.idmastrino' WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota');

-- Stato FE (Attestazione di avvenuta trasmissione della fattura con impossibilità di recapito, estensione ricevuta .zip)
INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES ('AT', 'Attestazione trasmissione', 'fa fa-check text-warning');

-- Aggiungo deleted_at per co_statipreventivi e co_staticontratti
ALTER TABLE `co_statipreventivi` ADD `deleted_at` DATETIME NULL;
ALTER TABLE `co_staticontratti` ADD `deleted_at` DATETIME NULL;

-- Aggiunto modulo per gestire gli stati dei preventivi
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stati dei preventivi', 'Stati dei preventivi','stati_preventivo', 'SELECT |select| FROM `co_statipreventivi` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2', '', 'fa fa-angle-right', '2.4.9', '2.4.9', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Preventivi'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei preventivi'), 'Fatturabile', 'IF(is_fatturabile, ''S&igrave;'', ''No'')', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei preventivi'), 'Completato', 'IF(is_completato, ''S&igrave;'', ''No'')', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei preventivi'), 'Pianificabile', 'IF(is_pianificabile, ''S&igrave;'', ''No'')', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei preventivi'), 'Icona', 'icona', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei preventivi'), 'Descrizione', 'descrizione', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei preventivi'), 'id', 'id', 1, 0, 0, 1, 0);

-- Aggiunto modulo per gestire gli stati dei contratti
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stati dei contratti', 'Stati dei contratti','stati_contratto', 'SELECT |select| FROM `co_staticontratti` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2', '', 'fa fa-angle-right', '2.4.9', '2.4.9', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Contratti'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei contratti'), 'Fatturabile', 'IF(is_fatturabile, ''S&igrave;'', ''No'')', 6, 1, 0, 0 ,1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei contratti'), 'Completato', 'IF(is_completato, ''S&igrave;'', ''No'')', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei contratti'), 'Pianificabile', 'IF(is_pianificabile, ''S&igrave;'', ''No'')', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei contratti'), 'Icona', 'icona', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei contratti'), 'Descrizione', 'descrizione', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati dei contratti'), 'id', 'id', 1, 0, 0, 1, 0);

-- Aggiornamento sconti incondizionati
ALTER TABLE `co_documenti` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `co_preventivi` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `co_contratti` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `or_ordini` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `dt_ddt` DROP `sconto_globale`, DROP `tipo_sconto_globale`;

ALTER TABLE `co_righe_documenti` ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;
UPDATE `co_righe_documenti` SET `sconto` = `sconto_globale`, `sconto_unitario` = `sconto_globale`, `tipo_sconto` = 'UNT', `is_sconto` = 1 WHERE `sconto_globale` != 0;
ALTER TABLE `co_righe_documenti` DROP `sconto_globale`;

ALTER TABLE `co_righe_preventivi` ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;
UPDATE `co_righe_preventivi` SET `sconto` = `sconto_globale`, `sconto_unitario` = `sconto_globale`, `tipo_sconto` = 'UNT', `is_sconto` = 1 WHERE `sconto_globale` != 0;
ALTER TABLE `co_righe_preventivi` DROP `sconto_globale`;

ALTER TABLE `co_righe_contratti` ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;
UPDATE `co_righe_contratti` SET `sconto` = `sconto_globale`, `sconto_unitario` = `sconto_globale`, `tipo_sconto` = 'UNT', `is_sconto` = 1 WHERE `sconto_globale` != 0;
ALTER TABLE `co_righe_contratti` DROP `sconto_globale`;

ALTER TABLE `or_righe_ordini` ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;
UPDATE `or_righe_ordini` SET `sconto` = `sconto_globale`, `sconto_unitario` = `sconto_globale`, `tipo_sconto` = 'UNT', `is_sconto` = 1 WHERE `sconto_globale` != 0;
ALTER TABLE `or_righe_ordini` DROP `sconto_globale`;

ALTER TABLE `dt_righe_ddt` ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;
UPDATE `dt_righe_ddt` SET `sconto` = `sconto_globale`, `sconto_unitario` = `sconto_globale`, `tipo_sconto` = 'UNT', `is_sconto` = 1 WHERE `sconto_globale` != 0;
ALTER TABLE `dt_righe_ddt` DROP `sconto_globale`;

-- Fix per la tabella in_righe_interventi
ALTER TABLE `in_righe_interventi` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idintervento`, ADD `idarticolo` INT(11) AFTER `idintervento`, ADD FOREIGN KEY (`idarticolo`) REFERENCES `mg_articoli`(`id`), ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;
ALTER TABLE `mg_articoli_interventi` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idintervento`, ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;

-- Rimozione campi inutilizzati co_ritenutaacconto
ALTER TABLE `co_ritenutaacconto` DROP `esente`, DROP `indetraibile`;
UPDATE `co_ritenutaacconto` SET `percentuale_imponibile` = 100;

-- Aggiornamento widget "Debiti verso fornitori"
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT((SELECT ABS(SUM(da_pagare-pagato))), 2), '','', ''#''), ''.'', '',''),''#'', ''.''), ''&euro;'') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=''uscita'' AND co_documenti.idstatodocumento!=1 |segment| AND 1=1' WHERE `zz_widgets`.`name` = 'Debiti verso fornitori';

-- Aggiunta idsede anche preventivi (completamento 2.4.1)
ALTER TABLE `co_preventivi` ADD `idsede` INT NOT NULL AFTER `idanagrafica`;

-- Aggiunta flag riba per tipi di pagamento Ri.Ba.
ALTER TABLE `co_pagamenti` ADD `riba` TINYINT(1) NOT NULL DEFAULT '0' AFTER `codice_modalita_pagamento_fe`;
UPDATE `co_pagamenti` SET `riba` = 1 WHERE `descrizione`  LIKE 'Ri.Ba.%';

-- Creazione nuovo conto per anticipi Ri.Ba.
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `can_delete`, `can_edit`) VALUES (NULL, '000021', 'Banca C/C (conto anticipi)', '1', '', '0', '0');

-- Aggiunta colonna nome per i modelli primanota
ALTER TABLE `co_movimenti_modelli` ADD `nome` VARCHAR(255) NOT NULL AFTER `idmastrino`;

UPDATE `zz_views` SET `name` = 'Nome', `query` = 'co_movimenti_modelli.nome' WHERE `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND `zz_views`.`name`='Causale predefinita';
UPDATE `zz_views` SET `query` = 'co_movimenti_modelli.idmastrino' WHERE `zz_views`.`id_module` = (SELECT id FROM zz_modules WHERE name='Modelli prima nota') AND `zz_views`.`name`='id';

-- Modelli primanota default
INSERT INTO `co_movimenti_modelli` (`id`, `idmastrino`, `nome`, `descrizione`, `idconto`) VALUES
(NULL, 1, 'Anticipo fattura', 'Anticipo fattura num. {numero} del {data}', -1),
(NULL, 1, 'Anticipo fattura', 'Anticipo fattura num. {numero} del {data}', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C (conto anticipi)')),
(NULL, 1, 'Anticipo fattura', 'Anticipo fattura num. {numero} del {data}', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Spese bancarie')),
(NULL, 2, 'Accredito anticipo', 'Accredito anticipo fattura num. {numero} del {data}', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C (conto anticipi)')),
(NULL, 2, 'Accredito anticipo', 'Accredito anticipo fattura num. {numero} del {data}', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C'));

-- Segmenti per modulo scadenzario
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE name='Scadenzario'), 'Scadenzario totale', '1=1', 'WHR', '####', '', 1, 0, 0, 1),
(NULL, (SELECT id FROM zz_modules WHERE name='Scadenzario'), 'Scadenzario clienti', '((SELECT dir FROM co_tipidocumento WHERE co_tipidocumento.id=co_documenti.idtipodocumento)=''entrata'')', 'WHR', '####', '', 0, 0, 0, 0),
(NULL, (SELECT id FROM zz_modules WHERE name='Scadenzario'), 'Scadenzario fornitori', '((SELECT dir FROM co_tipidocumento WHERE co_tipidocumento.id=co_documenti.idtipodocumento)=''uscita'')', 'WHR', '####', '', 0, 0, 0, 0),
(NULL, (SELECT id FROM zz_modules WHERE name='Scadenzario'), 'Scadenzario Ri.Ba.', 'co_pagamenti.riba=1', 'WHR', '####', '', 0, 0, 0, 0);

-- Fix vari
ALTER TABLE `co_righe_documenti` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `co_righe_documenti` SET `um` = NULL WHERE `um` = '';
ALTER TABLE `co_righe_documenti` CHANGE `idritenutaacconto` `idritenutaacconto` INT(11) NULL, CHANGE `idrivalsainps` `idrivalsainps` INT(11) NULL;
UPDATE `co_righe_documenti` SET `idritenutaacconto` = NULL WHERE `idritenutaacconto` = 0;
UPDATE `co_righe_documenti` SET `idrivalsainps` = NULL WHERE `idrivalsainps` = 0;

ALTER TABLE `co_righe_preventivi` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `co_righe_preventivi` SET `um` = NULL WHERE `um` = '';

ALTER TABLE `co_righe_contratti` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `co_righe_contratti` SET `um` = NULL WHERE `um` = '';

ALTER TABLE `or_righe_ordini` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `or_righe_ordini` SET `um` = NULL WHERE `um` = '';

ALTER TABLE `dt_righe_ddt` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `dt_righe_ddt` SET `um` = NULL WHERE `um` = '';

ALTER TABLE `in_righe_interventi` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `in_righe_interventi` SET `um` = NULL WHERE `um` = '';

ALTER TABLE `mg_articoli_interventi` CHANGE `um` `um` VARCHAR(20) NULL;
UPDATE `mg_articoli_interventi` SET `um` = NULL WHERE `um` = '';

-- Supporto a valute differenti
CREATE TABLE IF NOT EXISTS `zz_currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `symbol` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_currencies` (`id`, `name`, `title`, `symbol`) VALUES
(NULL, 'Euro', 'Euro', '&euro;'),
(NULL, 'Sterlina', 'Sterlina', '&pound;');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Valuta', '', 'query=SELECT id AS id, CONCAT(title, '' - '', symbol) AS text FROM zz_currencies', 1, 'Generali', 12);

-- Aggiornamento dicitura Tipo Cassa
UPDATE `zz_settings` SET `nome` = 'Tipo Cassa Previdenziale' WHERE `zz_settings`.`nome` = 'Tipo Cassa';

-- Aggiunta campo "Rif. fattura" nello scadenzario
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name='Scadenzario'), 'Rif. Fattura', 'IF( numero_esterno!="", numero_esterno, numero )', '2', '1', '0', '0', NULL, NULL, '1', '0', '1');

UPDATE `zz_views` SET `order`=`order`+1 WHERE `id_module`=(SELECT `id` FROM `zz_modules` WHERE `name`='Scadenzario') AND `order` > 1 AND `name` != 'Rif. Fattura';

-- Aggiornamento widget "Crediti da clienti"
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT((SELECT ABS(SUM(da_pagare-pagato))), 2), '','', ''#''), ''.'', '',''),''#'', ''.''), ''&euro;'') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=''entrata'' AND co_documenti.idstatodocumento!=1 |segment| AND 1=1' WHERE `zz_widgets`.`name` = 'Crediti da clienti';

UPDATE `fe_stati_documento` SET `icon`='fa fa-paper-plane-o text-info' WHERE `codice`='MC';
UPDATE `fe_stati_documento` SET `icon`='fa fa-inbox text-success' WHERE `codice`='RC';
UPDATE `fe_stati_documento` SET `icon`='fa fa-file-code-o text-info' WHERE `codice`='GEN';

-- Impostazioni per i riferimenti ai documenti
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Riferimento dei documenti nelle stampe', '1', 'boolean', 1, 'Generali', 13);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Riferimento dei documenti in Fattura Elettronica', '1', 'boolean', 1, 'Generali', 14);

-- Supporto alla personalizzazione dell'API remota OSMCloud
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'OSMCloud Services API URL', 'https://services.osmcloud.it/api/', 'string', 0, 'Fatturazione Elettronica', 11);

-- Miglioramento gestione marca da bollo
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Addebita marca da bollo al cliente', 1, 'boolean', 1, 'Fatturazione', 13),
(NULL, 'Iva da applicare su marca da bollo', (SELECT id FROM `co_iva` WHERE `deleted_at` IS NULL AND `descrizione` = 'Escluso art. 15' ORDER BY descrizione ASC), 'query=SELECT id, id, IF(codice_natura_fe IS NULL, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)), CONCAT( IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)), " (", codice_natura_fe, ")" )) AS descrizione FROM `co_iva` WHERE `deleted_at` IS NULL ORDER BY descrizione ASC', 1, 'Fatturazione', 14),
(NULL, 'Descrizione addebito bollo', 'Marca da bollo', 'string', 1, 'Fatturazione', 15),
(NULL, 'Conto predefinito per la marca da bollo', (SELECT id FROM co_pianodeiconti3 WHERE idpianodeiconti2=(SELECT id FROM co_pianodeiconti2 WHERE descrizione='Ricavi') AND descrizione = 'Rimborso spese marche da bollo'), 'query=SELECT id, descrizione FROM co_pianodeiconti3 WHERE idpianodeiconti2=(SELECT id FROM co_pianodeiconti2 WHERE descrizione=''Ricavi'')', 1, 'Fatturazione', 16);

ALTER TABLE `zz_settings` CHANGE `help` `help` varchar(255);
UPDATE `zz_settings` SET `help` = NULL WHERE `help` = '';

ALTER TABLE `co_documenti` CHANGE `bollo` `bollo` decimal(12,4), CHANGE `data_stato_fe` `data_stato_fe` TIMESTAMP NULL, ADD `addebita_bollo` BOOLEAN NOT NULL DEFAULT TRUE, ADD `id_riga_bollo` int(11), ADD FOREIGN KEY (`id_riga_bollo`) REFERENCES `co_righe_documenti`(`id`) ON DELETE SET NULL;
UPDATE `co_documenti` SET `bollo` = NULL WHERE `idstatodocumento` = (SELECT `id` FROM `co_statidocumento` WHERE `descrizione` = 'Bozza');
UPDATE `co_documenti` SET `data_registrazione` = NULL WHERE `data_registrazione` = '0000-00-00';
UPDATE `co_documenti` SET `data_registrazione` = `data` WHERE `data_registrazione` IS NULL AND idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir = 'uscita');
UPDATE `co_documenti` SET `data_competenza` = `data_registrazione`;
UPDATE `co_documenti` SET `data_stato_fe` = NULL WHERE `data_stato_fe` = '0000-00-00 00:00:00';

-- Rimozione tasto di stampa scadenzario totale da dentro la scadenza
UPDATE `zz_prints` SET `is_record` = 0 WHERE `name` = 'Scadenzario';

-- Aggiunta descrizione nello scadenzario, per scadenze generiche
ALTER TABLE co_scadenziario ADD `descrizione` TEXT NOT NULL AFTER `tipo`;
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Scadenzario'), 'Descrizione scadenza', 'co_scadenziario.descrizione', 1, 1, 0, 0, '', '', 1, 0, 1);

-- Aggiunta segmento per scadenze generiche e F24
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Scadenzario'), 'Scadenzario generico', 'co_scadenziario.tipo="generico"', 'WHR', '####', '', 0, 0, 0, 0), (NULL, (SELECT id FROM zz_modules WHERE `name`='Scadenzario'), 'Scadenzario F24', 'co_scadenziario.tipo="f24"', 'WHR', '####', '', 0, 0, 0, 0);

-- Aggiornamento modulo Scadenzario per adattamento a scadenze multiple
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM (`co_scadenziario` LEFT JOIN (((`co_documenti` LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`) LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`) ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`) LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id` WHERE 1=1 HAVING 2=2 AND IF(`co_statidocumento`.`descrizione` IS NOT NULL, `co_statidocumento`.`descrizione` IN(''Emessa'',''Parzialmente pagato''), 3=3) ORDER BY `scadenza` ASC' WHERE `zz_modules`.`name` = 'Scadenzario';

UPDATE `zz_segments` SET `clause` = '((SELECT dir FROM co_tipidocumento WHERE co_tipidocumento.id=co_documenti.idtipodocumento)=''entrata'') AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `zz_segments`.`name` = 'Scadenzario clienti';
UPDATE `zz_segments` SET `clause` = '((SELECT dir FROM co_tipidocumento WHERE co_tipidocumento.id=co_documenti.idtipodocumento)=''uscita'') AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `zz_segments`.`name` = 'Scadenzario fornitori';
UPDATE `zz_segments` SET `clause` = 'ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `zz_segments`.`name` = 'Scadenzario totale';

-- Aggiornamento cambio colore scadenze
UPDATE `zz_views` SET `query` = 'IF( pagato=da_pagare, ''#38CD4E'', IF(scadenza < NOW(), ''#cc4d37'', '''') )' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = '_bg_';

-- Aggiunta campo con totale da pagare
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Scadenzario'), 'Saldo', 'da_pagare-pagato', 13, 1, 1, 1, '', '', 1, 1, 1);

-- Aggiunta vista ore rimanenti nei contratti
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`="Contratti"), 'Ore rimanenti', '( (SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=''ore'' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id ), 0) )', '5', '1', '0', '1', '', '', '0', '0', '0');

-- Inserimento stato intervento da programmare
INSERT INTO `in_statiintervento` (`idstatointervento`, `descrizione`, `colore`, `can_delete`, `completato`, `created_at`, `notifica`, `id_email`, `destinatari`) VALUES ('DAP', 'Da programmare', '#2deded', '1', '0', NULL, '0', NULL, NULL);

-- Widget attività in programmazione
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES (NULL, 'Attività in programmazione', 'stats', '1', 'controller_top', 'col-md-12', 'SELECT COUNT(id) AS dato FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.descrizione=\'Da programmare\') ORDER BY in_interventi.data_richiesta ASC', '#2deded', 'fa fa-hourglass-half', '', './modules/interventi/widgets/interventi_da_pianificare.php', 'popup', '', 'Attività in programmazione', '1', '8', NULL);

-- Widget attività ancora da programmare
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES (NULL, 'Attività confermate', 'stats', '1', 'controller_top', 'col-md-12', 'SELECT COUNT(id) AS dato FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.descrizione=\'In programmazione\') ORDER BY in_interventi.data_richiesta ASC', '#f2bd00', 'fa fa-hourglass-half', '', './modules/interventi/widgets/interventi_confermati.php', 'popup', '', 'Attività confermate', '1', '8', NULL);

-- Aggiunta ore rimanenti nel contratto per preavviso rinnovo
ALTER TABLE `co_contratti` ADD `ore_preavviso_rinnovo` INT(11) NULL AFTER `giorni_preavviso_rinnovo`;

-- Moduli Stato dei servizi
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stato dei servizi', 'Stato dei servizi', 'stato_servizi', 'custom', '', 'fa fa-clock-o', '2.4.9', '2.4.9', '1', NULL, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Stato dei servizi' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;

-- Widget spazio utilizzato
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES (NULL, 'Spazio utilizzato', 'chart', (SELECT id FROM zz_modules WHERE name = 'Stato dei servizi'), 'controller_right', 'col-md-12', NULL, '#4ccc4c', 'fa fa-hdd-o', '', '', NULL, './modules/stato_servizi/widgets/spazio_utilizzato.php', 'Spazio utilizzato', '1', '1', NULL);

-- Aggiunta vista con data conclusione e flag rinnovabile nei contratti
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Rinnovabile', 'IF(`co_contratti`.`rinnovabile`=1, \'SI\', \'NO\')', '6', '1', '0', '1', '', '', '0', '0', '0'), (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Data conclusione', 'IF(data_conclusione=0, \'\', data_conclusione)', '7', '1', '0', '1', '', '', '0', '0', '0');

-- Aggiunta visualizzazione importo totale negli ordini
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM ((`or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`) INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `or_righe_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine` WHERE 1=1 AND `dir` = \'entrata\' |date_period(`data`)| GROUP BY or_ordini.id HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM ((`or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`) INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `or_righe_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine` WHERE 1=1 AND `dir` = \'uscita\' |date_period(`data`)| GROUP BY or_ordini.id HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Totale', 'SUM(`subtotale` - `sconto`)', '5', '1', '0', '1', '', '', '1', '1', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Totale', 'SUM(`subtotale` - `sconto`)', '5', '1', '0', '1', '', '', '1', '1', '0');

UPDATE `zz_views` SET `query` = '`an_anagrafiche`.`ragione_sociale`' WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ordini cliente', 'Ordini fornitore')) AND `name` = 'Ragione sociale';

-- Riordinamento campi degli ordini cliente e fornitore
UPDATE `zz_views` SET `order` = 3 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ordini cliente', 'Ordini fornitore')) AND `name` = 'Data';
UPDATE `zz_views` SET `order` = 4 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ordini cliente', 'Ordini fornitore')) AND `name` = 'Ragione sociale';
UPDATE `zz_views` SET `order` = 6 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ordini cliente', 'Ordini fornitore')) AND `name` = 'icon_Stato';
UPDATE `zz_views` SET `order` = 7 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ordini cliente', 'Ordini fornitore')) AND `name` = 'icon_title_Stato';

-- Aggiunta visualizzazione nuovi campi utili nei ddt
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM ((((((`dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`) LEFT OUTER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`) LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`) LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`) LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`) LEFT OUTER JOIN `an_anagrafiche` AS `destinatari` ON `dt_ddt`.`idanagrafica` = `destinatari`.`idanagrafica`) LEFT OUTER JOIN `an_sedi` ON `dt_ddt`.`idsede` = `an_sedi`.`id` WHERE 1=1 AND `dir` = \'entrata\' |date_period(`data`)| GROUP BY dt_ddt.id HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di vendita';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM ((((((`dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`) LEFT OUTER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`) LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`) LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`) LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`) LEFT OUTER JOIN `an_anagrafiche` AS `destinatari` ON `dt_ddt`.`idanagrafica` = `destinatari`.`idanagrafica`) LEFT OUTER JOIN `an_sedi` ON `dt_ddt`.`idsede` = `an_sedi`.`id` WHERE 1=1 AND `dir` = \'uscita\' |date_period(`data`)| GROUP BY dt_ddt.id HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di acquisto';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Sede', 'IF(`dt_ddt`.`idsede`=0, \'Sede legale\', `an_sedi`.`nomesede`)', '5', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Causale', '`dt_causalet`.`descrizione`', '6', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Tipo spedizione', '`dt_spedizione`.`descrizione`', '7', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Vettore', '`vettori`.`ragione_sociale`', '8', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Totale', 'SUM(`subtotale` - `sconto`)', '9', '1', '0', '1', '', '', '1', '1', '0');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Sede', 'IF(`dt_ddt`.`idsede`=0, \'Sede legale\', `an_sedi`.`nomesede`)', '5', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Causale', '`dt_causalet`.`descrizione`', '6', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Tipo spedizione', '`dt_spedizione`.`descrizione`', '7', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Vettore', '`vettori`.`ragione_sociale`', '8', '1', '0', '1', '', '', '1', '0', '0');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Totale', 'SUM(`subtotale` - `sconto`)', '9', '1', '0', '1', '', '', '1', '1', '0');

UPDATE `zz_views` SET `query` = '`destinatari`.`ragione_sociale`' WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ddt di vendita', 'Ddt di acquisto')) AND `name` = 'Ragione sociale';

-- Riordinamento campi dei ddt in ingresso e uscita
UPDATE `zz_views` SET `order` = 10 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ddt di vendita', 'Ddt di acquisto')) AND `name` = 'icon_Stato';
UPDATE `zz_views` SET `order` = 11 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ddt di vendita', 'Ddt di acquisto')) AND `name` = 'icon_title_Stato';
UPDATE `zz_views` SET `order` = 12 WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN('Ddt di vendita', 'Ddt di acquisto')) AND `name` = 'dir';

-- Aggiornamento widget "Fatturato" (iva esclusa)
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT((SELECT SUM(subtotale-sconto-co_righe_documenti.ritenutaacconto)), 2), '','', ''#''), ''.'', '',''), ''#'', ''.''), ''&euro;'') AS dato FROM (co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=''entrata'' |segment| AND data >= ''|period_start|'' AND data <= ''|period_end|'' AND 1=1', `help` = 'Fatturato IVA esclusa.' WHERE `zz_widgets`.`name` = 'Fatturato';

-- Aggiornamento widget "Acquisti" (iva esclusa)
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT((SELECT SUM(subtotale-sconto-co_righe_documenti.ritenutaacconto)), 2), '','', ''#''), ''.'', '',''), ''#'', ''.''), ''&euro;'') AS dato FROM (co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=''uscita'' |segment| AND data >= ''|period_start|'' AND data <= ''|period_end|'' AND 1=1', `help` = 'Fatturato IVA esclusa.' WHERE `zz_widgets`.`name` = 'Acquisti';

-- Sistema Hook
CREATE TABLE IF NOT EXISTS `zz_hooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zz_hook_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hook_id` int(11) NOT NULL,
  `results` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`hook_id`) REFERENCES `zz_hooks`(`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`) VALUES
(NULL, 'Ricevute', 'Plugins\\ReceiptFE\\ReceiptHook', '1 day'),
(NULL, 'Fatture', 'Plugins\\ImportFE\\InvoiceHook', '1 day');

-- Aggiunte variabili di sistema per stampa riepilogo interventi
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'richiesta', 'richiesta', '14', '1', '0', '0', '', '', '0', '0', '1');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'descrizione', 'descrizione', '15', '1', '0', '0', '', '', '0', '0', '1');
