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