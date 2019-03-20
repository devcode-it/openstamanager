-- Escluso Art.74 ter D.P.R. 633/72
INSERT INTO `co_iva` (`descrizione`, `percentuale`, `indetraibile`, `esente`, `codice_natura_fe`, `codice`, `default`) VALUES
("Escluso Art.74 ter D.P.R. 633/72", 0, 0, 1, "N4", NULL, 1);

-- Aggiungo vista "Conto" per Fatture di acquisto (opzionale)
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name` = 'Fatture di acquisto') , 'Conto', '(SELECT GROUP_CONCAT(DISTINCT(co_pianodeiconti3.descrizione)) FROM co_righe_documenti INNER JOIN co_pianodeiconti3 ON co_pianodeiconti3.id = co_righe_documenti.idconto WHERE co_righe_documenti.iddocumento = co_documenti.id)', 10, 1, 0, 0, '', '', 0, 0, 1);

-- Stato FE (Notifica esito)
INSERT INTO `fe_stati_documento` (`codice`, `descrizione`, `icon`) VALUES ('NE', 'Notifica esito', 'fa fa-check text-success');

-- Aggiunta data ricezione, utile per le fatture di acquisto
ALTER TABLE `co_documenti` ADD `data_ricezione` DATE NULL AFTER `data`;

-- Importo marca da bollo a 2 (https://www.fiscoetasse.com/approfondimenti/12090-applicazione-della-marca-da-bollo-sulle-fatture.html)
UPDATE `zz_settings` SET `valore` = '2' WHERE `zz_settings`.`nome` = 'Importo marca da bollo';

-- Stampa preventivo (senza totali)
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE name='Preventivi'), 1, 'Preventivo (senza totali)', 'Preventivo (senza totali)', 'preventivi', 'idpreventivo', '{"pricing":true, "hide_total":true}', 'fa fa-print', '', '', 0, 0, 1, 1);

-- Dimensione dei file caricati
ALTER TABLE `zz_files` ADD `size` INT(11) NULL AFTER `category`;

-- Preventivi e contratti nello stato 'Pagato' non sono più fatturabili 
UPDATE `co_staticontratti` SET `fatturabile` = '0' WHERE `descrizione` = 'Pagato';
UPDATE `co_statipreventivi` SET `fatturabile` = '0' WHERE `descrizione` = 'Pagato';

-- Definisco anche per i contratti lo stato completato (blocco la modifica della scheda)
ALTER TABLE `co_staticontratti` ADD `completato` BOOLEAN NOT NULL DEFAULT FALSE AFTER `pianificabile`;

-- Elimino data_evasione da co_righe_preventivi
ALTER TABLE `co_righe_preventivi` DROP `data_evasione`;

-- Allineo qta evase per le righe dei preventivi inseriti in una fattura
UPDATE `co_righe_preventivi` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idpreventivo` = `co_righe_preventivi`.`idpreventivo` SET  `co_righe_preventivi`.`qta_evasa` = `co_righe_documenti`.`qta`;

-- Allineo qta evase per le righe dei contratti inseriti in una fattura
UPDATE `co_righe_contratti` INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idcontratto` = `co_righe_contratti`.`idcontratto` SET `co_righe_contratti`.`qta_evasa` = `co_righe_documenti`.`qta`;