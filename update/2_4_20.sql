UPDATE `zz_modules` SET `name` = 'Piani di sconto/magg.' WHERE `name` = 'Listini';

-- Creazione modulo Listini
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Listini', 'Listini', 'listini', 'SELECT |select|
FROM mg_prezzi_articoli
    INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = mg_prezzi_articoli.id_anagrafica
    INNER JOIN mg_articoli ON mg_articoli.id = mg_prezzi_articoli.id_articolo
WHERE 1=1 AND mg_articoli.deleted_at IS NULL AND an_anagrafiche.deleted_at IS NULL
ORDER BY an_anagrafiche.ragione_sociale', '', 'fa fa-file-text-o', '2.4', '2.4', '1', NULL, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Listini' AND `t2`.`name` = 'Magazzino') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'id', 'mg_prezzi_articoli.id', 1, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Minimo', 'mg_prezzi_articoli.minimo', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Massimo', 'mg_prezzi_articoli.massimo', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Prezzo unitario', 'mg_prezzi_articoli.prezzo_unitario', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Sconto percentuale', 'mg_prezzi_articoli.sconto_percentuale', 7, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Articolo', 'CONCAT(mg_articoli.codice, '' - '', mg_articoli.descrizione)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Ragione sociale', 'an_anagrafiche.ragione_sociale', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), '_link_module_', '(SELECT id FROM zz_modules WHERE name = ''Articoli'')', 1, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), '_link_record_', 'mg_articoli.id', 1, 1, 0, 1, 0);

-- Aggiunta impstazione per alert occupazione tecnici
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Alert occupazione tecnici', '1', 'boolean', '1', 'Attività');

-- Aggiunta supporto riferimento_amministrazione per Anagrafiche
ALTER TABLE `an_anagrafiche` ADD `riferimento_amministrazione` VARCHAR(255) AFTER `codicerea`;

-- Fix dimensioni campi descrittivi
ALTER TABLE `co_contratti` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `co_contratti` CHANGE `esclusioni` `esclusioni` TEXT NULL;
ALTER TABLE `co_documenti` CHANGE `note` `note` TEXT NULL;
ALTER TABLE `co_documenti` CHANGE `note_aggiuntive` `note_aggiuntive` TEXT NULL;
ALTER TABLE `co_movimenti` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `co_preventivi` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `co_preventivi` CHANGE `esclusioni` `esclusioni` TEXT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `in_interventi` CHANGE `richiesta` `richiesta` TEXT NULL;
ALTER TABLE `in_interventi` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `in_interventi` CHANGE `informazioniaggiuntive` `informazioniaggiuntive` TEXT NULL;
ALTER TABLE `mg_articoli` CHANGE `contenuto` `contenuto` TEXT NULL;
ALTER TABLE `my_impianto_componenti` CHANGE `contenuto` `contenuto` TEXT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `descrizione` `descrizione` TEXT NULL;
ALTER TABLE `zz_modules` CHANGE `options` `options` TEXT NULL;
ALTER TABLE `zz_modules` CHANGE `options2` `options2` TEXT NULL;
ALTER TABLE `zz_widgets` CHANGE `query` `query` TEXT NULL;
ALTER TABLE `zz_widgets` CHANGE `text` `text` TEXT NULL;

ALTER TABLE `zz_views` CHANGE `format` `format` TINYINT(1) NOT NULL DEFAULT '0';

-- Aggiunti segmenti nel modulo listini
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Tutti', '1=1', 'WHR', '####', '', 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Fornitori', 'mg_prezzi_articoli.dir=\"uscita\"', 'WHR', '####', '', 0, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Clienti', 'mg_prezzi_articoli.dir=\"entrata\"', 'WHR', '####', '', 0, 0, 0, 0);

-- Aggiunto formattabile nel modulo listini ai campi numerici
UPDATE `zz_views` SET `format` = '1' WHERE `name` = 'Prezzo unitario';
UPDATE `zz_views` SET `format` = '1' WHERE `name` = 'Sconto percentuale';
UPDATE `zz_views` SET `format` = '1' WHERE `name` = 'Minimo';
UPDATE `zz_views` SET `format` = '1' WHERE `name` = 'Massimo';


-- Sostituito icona Listini con ">"
UPDATE `zz_modules` SET `icon` = 'fa fa-angle-right' WHERE `zz_modules`.`name` = 'Listini';

-- Modificato nome plugin dettagli in Prezzi specifici
UPDATE `zz_plugins` SET `name` = 'Prezzi specifici articolo', `title` = 'Prezzi specifici' WHERE `zz_plugins`.`name` = 'Dettagli articolo';

-- Impostazione soft quota
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Soft quota', '', 'integer', '0', 'Generali', NULL, 'Soft quota in GB');

-- Relativo hook per il calcolo dello spazio utilizzato
INSERT INTO `zz_hooks` (`id`, `name`, `class`,  `enabled`, `id_module`, `processing_at`, `processing_token`) VALUES (NULL, 'Spazio', 'Modules\\StatoServizi\\SpaceHook', '1', (SELECT `id` FROM `zz_modules` WHERE `name`='Stato dei servizi'), NULL, NULL);

INSERT INTO `zz_cache` (`id`, `name`, `content`, `valid_time`, `expire_at`) VALUES
(NULL, 'Spazio utilizzato', '', '60 minute', NOW());

-- Introduzione hook per informazioni su Services
INSERT INTO `zz_hooks` (`id`, `name`, `class`,  `enabled`, `id_module`, `processing_at`, `processing_token`) VALUES (NULL, 'Informazioni su Services', 'Modules\\StatoServizi\\ServicesHook', '1', (SELECT `id` FROM `zz_modules` WHERE `name`='Stato dei servizi'), NULL, NULL);

INSERT INTO `zz_cache` (`id`, `name`, `content`, `valid_time`, `expire_at`) VALUES
(NULL, 'Informazioni su Services', '', '7 days', NOW()),
(NULL, 'Informazioni su spazio FE', '', '7 days', NOW());

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Tecnici assegnati', 'GROUP_CONCAT((SELECT DISTINCT(ragione_sociale) FROM an_anagrafiche WHERE idanagrafica = in_interventi_tecnici_assegnati.id_tecnico) SEPARATOR '', '')', 14, 1, 0, 1, 1);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`default` = 1 WHERE (`zz_views`.`name` = 'Tecnici' OR `zz_views`.`name` = 'Rif. fattura') AND `zz_modules`.`name` = 'Interventi';
-- Modifica directory Piani di sconto/magg.
UPDATE `zz_modules` SET `directory` = 'piano_sconto' WHERE `zz_modules`.`name` = 'Piani di sconto/magg.';

-- Aggiunto flag rinnovo automatico in contratti
ALTER TABLE `co_contratti` ADD `rinnovo_automatico` TINYINT(1) NOT NULL DEFAULT '0' AFTER `rinnovabile`;

-- Aggiunto segmento per attività NON completate
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Non completate', 'in_interventi.idstatointervento NOT IN(SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE is_completato=1)', 'WHR', '####', '', '0', '0', '0', '0');

-- Aggiunto segmenti Ri.Ba. Clienti/Fornitori su Scadenzario
DELETE FROM `zz_segments` WHERE name='Scadenzario Ri.Ba.';

INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenzario Ri.Ba. Clienti', 'co_pagamenti.riba=1 AND co_tipidocumento.dir=\"entrata\"', 'WHR', '####', '', 0, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenzario Ri.Ba. Fornitori', 'co_pagamenti.riba=1 AND co_tipidocumento.dir=\"uscita\"', 'WHR', '####', '', 0, 0, 0, 0);

-- Aggiunta impostazione per disabilitare articoli con quantità <= 0
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita', '0', 'boolean', '1', 'Generali',  '20', NULL);

-- Correzione per visualizzazione campi 'Dare' e 'Avere'
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`summable` = 1 WHERE `zz_views`.`name` IN ('Dare', 'Avere') AND `zz_modules`.`name` = 'Prima nota';
-- Fix query dichiarazione d'intento
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [	{	\"type\": \"table\", \"fields\": \"Protocollo, Progressivo, Massimale, Totale, Data inizio, Data fine\", \"query\": \"SELECT id, numero_protocollo AS Protocollo, numero_progressivo AS Progressivo, DATE_FORMAT(data_inizio,\'%d/%m/%Y\') AS \'Data inizio\', DATE_FORMAT(data_fine,\'%d/%m/%Y\') AS \'Data fine\', ROUND(massimale, 2) AS Massimale, ROUND(totale, 2) AS Totale FROM co_dichiarazioni_intento WHERE 1=1 AND deleted_at IS NULL AND id_anagrafica = |id_parent| HAVING 2=2 ORDER BY co_dichiarazioni_intento.id DESC\"}	]}' WHERE `zz_plugins`.`name` = "Dichiarazioni d\'Intento";

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Sottocategoria', 'sottocategoria.nome', 5, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Categoria', 'categoria.nome', 4, 1, 0, 0, '', '', 1, 0, 0);

-- Aggiornamento natura iva per aliquote eliminate
UPDATE `co_iva` SET `codice_natura_fe` = 'N2.2' WHERE `codice_natura_fe` = 'N2';
UPDATE `co_iva` SET `codice_natura_fe` = 'N3.6' WHERE `codice_natura_fe` = 'N3';
UPDATE `co_iva` SET `codice_natura_fe` = 'N6.9' WHERE `codice_natura_fe` = 'N6';

-- Aumento testo descrizione per righe attività (da 255 caratteri)
ALTER TABLE `in_righe_interventi` CHANGE `descrizione` `descrizione` TEXT NULL;

ALTER TABLE `co_tipidocumento` CHANGE `descrizione` `descrizione` VARCHAR(125) NOT NULL;

-- Aggiunta tipologia documento TD 25
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES
(NULL, "Fattura differita di cui all'art.21, comma 4, terzo periodo lett. b (Dropshipping)", 'entrata', '0', 'TD25'),
(NULL, "Fattura differita di cui all'art.21, comma 4, terzo periodo lett. b (Dropshipping)", 'uscita', '0', 'TD25');

-- Metodi di pagamento speculari per fatture di acquisto
INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES
(NULL, 'Acconto/anticipo su fattura', 'uscita', '0', 'TD02'),
(NULL, 'Integrazione fattura reverse charge interno', 'uscita', '0', 'TD16'),
(NULL, "Integrazione/autofattura per acquisto servizi dall\'estero", 'uscita', '0', 'TD17'),
(NULL, 'Integrazione per acquisto di beni intracomunitari', 'uscita', '0', 'TD18'),
(NULL, 'Integrazione/autofattura per acquisto di beni ex art.17 c.2 DPR 633/72', 'uscita', '0', 'TD19'),
(NULL, 'Autofattura per regolarizzazione e integrazione delle fatture (art.6 c.8 d.lgs. 471/97 o art.46 c.5 D.L. 331/93)', 'uscita', '0', 'TD20'),
(NULL, 'Autofattura per splafonamento', 'uscita', '0', 'TD21'),
(NULL, 'Estrazione beni da deposito IVA', 'uscita', '0', 'TD22'),
(NULL, "Estrazione beni da deposito IVA con versamento dell\'IVA", 'uscita', '0', 'TD23'),
(NULL, 'Cessione di beni ammortizzabili e per passaggi interni (ex art.36 DPR 633/72)', 'uscita', '0', 'TD26'),
(NULL, 'Fattura per autoconsumo o per cessioni gratuite senza rivalsa', 'uscita', '0', 'TD27');

-- Setto 10 tentativi per email create più di una settimana fa che non sono state mai processate e non hanno ricevuto ne invio o fallimento
UPDATE `em_emails` SET `attempt` = '10', `em_emails`.`failed_at` = NOW() WHERE `em_emails`.`attempt` = 0 AND `em_emails`.`failed_at` IS NULL AND `em_emails`.`sent_at` IS NULL AND `em_emails`.`processing_at` IS NULL AND `em_emails`.`created_at` <= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Aggiunto deleted_at per tipi di documento
ALTER TABLE `co_tipidocumento` ADD `deleted_at` DATETIME NULL DEFAULT NULL AFTER `codice_tipo_documento_fe`;
-- Aggiunti campi predefined, enabled, help  per tipi di documento
ALTER TABLE `co_tipidocumento` ADD `predefined` TINYINT NOT NULL DEFAULT '0' AFTER `codice_tipo_documento_fe`;
ALTER TABLE `co_tipidocumento` ADD `enabled` TINYINT NOT NULL DEFAULT '1' AFTER `predefined`;
ALTER TABLE `co_tipidocumento` ADD `help` VARCHAR(255) NULL AFTER `enabled`;
UPDATE `co_tipidocumento` SET `predefined` = '1' WHERE `co_tipidocumento`.`descrizione` = 'Fattura immediata di vendita';
UPDATE `co_tipidocumento` SET `predefined` = '1' WHERE `co_tipidocumento`.`descrizione` = 'Fattura immediata di acquisto';

UPDATE `co_tipidocumento` SET `help` = 'Fattura emessa entro le ore 24 del giorno di effettuazione dell’operazione.' WHERE `co_tipidocumento`.`descrizione` = 'Fattura immediata di acquisto';
UPDATE `co_tipidocumento` SET `help` = 'Fattura emessa entro le ore 24 del giorno di effettuazione dell’operazione.' WHERE `co_tipidocumento`.`descrizione` = 'Fattura immediata di vendita';
UPDATE `co_tipidocumento` SET `help` = "Fattura emessa entro il giorno 15 del mese successivo a quello di effettuazione dell'operazione  (art. 21 comma 4 lett. a) del D.P.R. 633/1972)." WHERE `co_tipidocumento`.`descrizione` = 'Fattura differita di acquisto';
UPDATE `co_tipidocumento` SET `help` = "Fattura emessa entro il giorno 15 del mese successivo a quello di effettuazione dell'operazione  (art. 21 comma 4 lett. a) del D.P.R. 633/1972)." WHERE `co_tipidocumento`.`descrizione` = 'Fattura differita di vendita';

-- Innesto nuovo modulo Tipi documento
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Tipi documento', 'Tipi documento', 'tipi_documento', 'SELECT |select| FROM `co_tipidocumento` WHERE 1=1 AND deleted_at IS NULL  HAVING 2=2', '', 'fa fa-angle-right', '2.4.20', '2.4.20', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1', '0', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'Attivo', 'co_tipidocumento.enabled', 7, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'Predefinito', 'co_tipidocumento.predefined', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'Codice FE', 'co_tipidocumento.codice_tipo_documento_fe', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'Reversed', 'co_tipidocumento.reversed', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'Direzione', 'co_tipidocumento.dir', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'Descrizione', 'co_tipidocumento.descrizione', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi documento'), 'id', 'co_tipidocumento.id', 1, 1, 0, 0, 0);

-- Aggiornate descrizioni aliquote IVA con codice natura 6.()
UPDATE `co_iva` SET `descrizione`='Art. 74 co. 8 del DPR 633/72 - Cessione di rottami e di altri materiali di recupero' WHERE `descrizione`='Inversione contabile - cessione di rottami e altri materiali di recupero';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 5 del DPR 633/72 - Cessione di oro e argento puro' WHERE `descrizione`='Inversione contabile - cessione di oro e argento pure';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 6 lett. a) del DPR 633/72 - Subappalto nel settore edile' WHERE `descrizione`='Inversione contabile - subappalto nel settore edile';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 6 lett. a-bis) del DPR 633/72 - Cessione di fabbricati' WHERE `descrizione`='Inversione contabile - cessione di fabbricati';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 6 lett. b) del DPR 633/72 - Cessione di telefoni cellulari' WHERE `descrizione`='Inversione contabile - cessione di telefoni cellulari';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 6 lett. c) del DPR 633/72 - Cessione di prodotti elettronici' WHERE `descrizione`='Inversione contabile - cessione di prodotti elettronici';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 6 lett. a-ter) del DPR 633/72 - Prestazioni del comparto edile e settori connessi' WHERE `descrizione`='Inversione contabile - prestazioni comparto edile e settori connessi';
UPDATE `co_iva` SET `descrizione`='Art. 17 co. 6 lett. d-bis) del DPR 633/72 - Operazioni del settore energetico' WHERE `descrizione`='Inversione contabile - operazioni settore energetico';

INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 17 co. 6 lett. d-ter) del DPR 633/72 - Operazioni del settore energetico', '0.00', '0.00', '1', NULL, 'N6.8', NULL, NULL, 'I', '1');

-- Anticipazione colonne per script 2_4_20
ALTER TABLE `co_righe_contratti` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `co_righe_preventivi` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `or_righe_ordini` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

ALTER TABLE `dt_righe_ddt` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`;