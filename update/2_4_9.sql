-- Escluso Art.74 ter D.P.R. 633/72
INSERT INTO `co_iva` (`descrizione`, `percentuale`, `indetraibile`, `esente`, `codice_natura_fe`, `codice`, `default`) VALUES
("Escluso Art.74 ter D.P.R. 633/72", 0, 0, 1, "N4", NULL, 1);

-- Aggiungo vista "Conto" per Fatture di acquisto (opzionale)
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name` = 'Fatture di acquisto') , 'Conto', '(SELECT GROUP_CONCAT(DISTINCT(co_pianodeiconti3.descrizione)) FROM co_righe_documenti INNER JOIN co_pianodeiconti3 ON co_pianodeiconti3.id = co_righe_documenti.idconto WHERE co_righe_documenti.iddocumento = co_documenti.id)', 10, 1, 0, 0, '', '', 0, 0, 1);

-- Stato FE (Notifica esito)
INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES ('NE', 'Notifica esito', 'fa fa-check text-warning');

-- Aggiunta data ricezione, utile per le fatture di acquisto
ALTER TABLE `co_documenti` ADD `data_ricezione` DATE NULL AFTER `data`;

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
ALTER TABLE `co_statipreventivi` ADD `is_fatturabile` BOOLEAN NOT NULL DEFAULT FALSE AFTER `completato`;
ALTER TABLE `co_statipreventivi` ADD `is_pianificabile` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_fatturabile`;
ALTER TABLE `co_statipreventivi` DROP `annullato`;

ALTER TABLE `co_statipreventivi` CHANGE `completato` `is_completato` BOOLEAN NOT NULL DEFAULT FALSE;

ALTER TABLE `co_staticontratti` CHANGE `pianificabile` `is_pianificabile` BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE `co_staticontratti` CHANGE `fatturabile` `is_fatturabile` BOOLEAN NOT NULL DEFAULT FALSE;

-- Fix degli stati predefiniti preventivi e contratti
UPDATE `co_staticontratti` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Bozza';
UPDATE `co_staticontratti` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'In attesa di conferma';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Accettato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Rifiutato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 1 WHERE `descrizione` = 'In lavorazione';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Fatturato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Pagato';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Concluso';
UPDATE `co_staticontratti` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Parzialmente fatturato';

UPDATE `co_statipreventivi` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Bozza';
UPDATE `co_statipreventivi` SET `is_completato` = 0, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'In attesa di conferma';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Accettato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Rifiutato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 1, `is_fatturabile` = 1 WHERE `descrizione` = 'In lavorazione';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Fatturato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 0 WHERE `descrizione` = 'Pagato';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Concluso';
UPDATE `co_statipreventivi` SET `is_completato` = 1, `is_pianificabile` = 0, `is_fatturabile` = 1 WHERE `descrizione` = 'Parzialmente fatturato';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato, co_contratti.id, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970 HAVING ISNULL((SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev=co_contratti.id )) ORDER BY giorni_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_ordiniservizio WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE ispianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Ordini di servizio da impostare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_promemoria WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Interventi da pianificare';

-- Fix filtri per data
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE 1=1 AND `dir` = \'uscita\' |segment| |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(IF(numero_esterno=\'\', numero, numero_esterno) AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di acquisto';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id WHERE 1=1 AND dir = ''entrata'' |segment| |date_period(`data`)| HAVING 2=2 ORDER BY data DESC, CAST(numero_esterno AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di vendita';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM (`in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT OUTER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` WHERE 1=1  |date_period(`orario_inizio`,`data_richiesta`)| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_preventivi` WHERE 1=1 AND default_revision=1 |date_period(`data_bozza`)| HAVING 2=2 ORDER BY `id` DESC' WHERE `zz_modules`.`name` = 'Preventivi';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_contratti` WHERE 1=1 |date_period(`data_bozza`)| HAVING 2=2 ORDER BY `id` DESC' WHERE `zz_modules`.`name` = 'Contratti';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_movimenti` INNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`idconto` = `co_pianodeiconti3`.`id` WHERE 1=1 AND `primanota` = 1 |date_period(`co_movimenti`.`data`)| GROUP BY `idmastrino`, `primanota`, `co_movimenti`.`data` HAVING 2=2 ORDER BY `co_movimenti`.`data` DESC' WHERE `zz_modules`.`name` = 'Prima nota';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `or_ordini` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id` WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di vendita';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id` WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)| HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ddt di acquisto';

UPDATE `zz_views` SET `query` = 'co_movimenti.idmastrino' WHERE `name` = 'id' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota');

-- Stato FE (Attestazione di avvenuta trasmissione della fattura con impossibilità di recapito, estensione ricevuta .zip)
INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES ('AT', 'Attestazione trasmissione', 'fa fa-check text-warning');

-- Aggiungo deleted_at per co_statipreventivi e co_staticontratti
ALTER TABLE `co_statipreventivi` ADD `deleted_at` DATETIME NULL AFTER `updated_at`;
ALTER TABLE `co_staticontratti` ADD `deleted_at` DATETIME NULL AFTER `updated_at`;

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

-- Aggiornamento sconti incodizionati
ALTER TABLE `co_documenti` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `co_preventivi` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `co_contratti` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `or_ordini` DROP `sconto_globale`, DROP `tipo_sconto_globale`;
ALTER TABLE `dt_ddt` DROP `sconto_globale`, DROP `tipo_sconto_globale`;

ALTER TABLE `co_righe_documenti` DROP `sconto_globale`;
ALTER TABLE `co_righe_preventivi` DROP `sconto_globale`;
ALTER TABLE `co_righe_contratti` DROP `sconto_globale`;
ALTER TABLE `or_righe_ordini` DROP `sconto_globale`;
ALTER TABLE `dt_righe_ddt` DROP `sconto_globale`;

-- Fix per la tabella in_righe_interventi
ALTER TABLE `in_righe_interventi` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idintervento`, ADD `idarticolo` INT(11) AFTER `idintervento`, ADD FOREIGN KEY (`idarticolo`) REFERENCES `mg_articoli`(`id`);
ALTER TABLE `mg_articoli_interventi` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idintervento`;
