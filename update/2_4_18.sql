-- Miglioramento della cache interna
CREATE TABLE IF NOT EXISTS `zz_tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `class` TEXT NOT NULL,
    `expression` VARCHAR(255) NOT NULL,
    `next_execution_at` timestamp NULL,
    `last_executed_at` timestamp NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `zz_tasks_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_task` int(11),
    `level` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `context` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_task`) REFERENCES `zz_tasks`(`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_cache` (`id`, `name`, `content`, `valid_time`, `expire_at`) VALUES
(NULL, 'Ultima esecuzione del cron', '', '1 month', NULL),
(NULL, 'ID del cron', '', '1 month', NULL),
(NULL, 'Cron in esecuzione', '', '1 month', NULL),
(NULL, 'Disabilita cron', '', '1 month', NULL);

INSERT INTO `zz_tasks` (`id`, `name`, `class`, `expression`, `last_executed_at`) VALUES
(NULL, 'Backup automatico', 'Modules\\Backups\\BackupTask', '0 1 * * *', NULL),
(NULL, 'Importazione automatica Ricevute FE', 'Plugins\\ReceiptFE\\ReceiptTask', '0 */24 * * *', NULL);

DELETE FROM `zz_hooks` WHERE `class` = 'Modules\\Backups\\BackupHook';

-- Modifica dei Listini in Piani di sconto/magg.
UPDATE `zz_modules` SET `title` = 'Piani di sconto/magg.' WHERE `name` = 'Listini';

-- Aggiunto supporto ai prezzi per Articoli specifici per Anagrafica e range di quantità
CREATE TABLE IF NOT EXISTS `mg_prezzi_articoli` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_articolo` int(11) NOT NULL,
    `id_anagrafica` int(11),
    `minimo` DECIMAL(15,6),
    `massimo` DECIMAL(15,6),
    `prezzo_unitario` DECIMAL(15,6) NOT NULL,
    `prezzo_unitario_ivato` DECIMAL(15,6) NOT NULL,
    `dir` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`),
    FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`)
) ENGINE=InnoDB;

UPDATE `zz_plugins` SET `directory` = 'dettagli_articolo', `name`= 'Dettagli articolo', `title`= 'Dettagli' WHERE `name` = 'Fornitori Articolo';

-- Modulo Giacenze sedi
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Giacenze sedi', 'Giacenze sedi', 'giacenze_sedi', 'SELECT |select| FROM `mg_articoli`
    LEFT OUTER JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT OUTER JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT OUTER JOIN (
        SELECT SUM(qta - qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini
            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id
        WHERE idstatoordine IN (SELECT id FROM or_statiordine WHERE completato = 0)
        GROUP BY idarticolo
    ) ordini ON ordini.idarticolo = mg_articoli.id
WHERE 1=1 AND `mg_articoli`.`deleted_at` IS NULL HAVING 2=2 AND `Q.tà` > 0 ORDER BY `descrizione`', '', 'fa fa-angle-right', '2.4.18', '2.4.18', '5', (SELECT id FROM zz_modules t WHERE t.name = 'Magazzino'), '1', '1', '1', '0');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), '_link_module_', (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Articoli'), '1', '1', '0', '0', NULL, NULL, '0', '0', '1'),
(NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'id', 'mg_articoli.id', '1', '1', '0', '0', NULL, NULL, '0', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Codice', 'mg_articoli.codice', '2', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Categoria', '(SELECT `nome` FROM `mg_categorie` WHERE `id` = `id_categoria`)', '4', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Sottocategoria', '(SELECT `nome` FROM `mg_categorie` WHERE `id` = `id_sottocategoria`)', '5', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Q.tà', '(SELECT SUM(IF(mg_movimenti.idsede_azienda = |giacenze_sedi_idsede|, mg_movimenti.qta, IF(mg_movimenti.idsede_controparte = |giacenze_sedi_idsede|, -mg_movimenti.qta, 0))) FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_azienda WHERE mg_movimenti.idarticolo=mg_articoli.id AND idsede_azienda=|giacenze_sedi_idsede| GROUP BY idsede_azienda)', '9', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Descrizione', 'mg_articoli.descrizione', '1', '1', '0', '0', '', '', '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Fornitore', '(SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica` = `id_fornitore`)', '6', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Prezzo di acquisto', 'prezzo_acquisto', '6', '1', '0', '1', NULL, NULL, '1', '1', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Prezzo di vendita', 'prezzo_vendita', '6', '1', '0', '1', NULL, NULL, '1', '1', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Prezzo vendita ivato', 'IF( co_iva.percentuale IS NOT NULL, (mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * co_iva.percentuale / 100), mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita*(SELECT co_iva.percentuale FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore AND nome=''Iva predefinita'')/100 )', '8', '1', '0', '1', '', '', '0', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Barcode', 'mg_articoli.barcode', '2', '1', '0', '0', '', '', '1', '0', '1');

-- Aggiunta risorse API dedicate alle task in cron
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'retrieve', 'cron-logs', 'API\\Common\\Task', '1'),
(NULL, 'v1', 'create', 'cron-restart', 'API\\Common\\Task', '1');

-- Fix visualizzazione modulo Causali movimenti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'CONCAT(UCASE(LEFT(tipo_movimento, 1)), SUBSTRING(tipo_movimento, 2))', `zz_views`.`name` = 'Tipo' WHERE `zz_modules`.`name` = 'Causali movimenti' AND `zz_views`.`name` = 'Movimento di carico';
-- Aggiornamento versione API services
UPDATE `zz_settings` SET `valore` = 'v3' WHERE `nome` = 'OSMCloud Services API Version';

-- Aggiornamento margini stampa barbcode
UPDATE `zz_prints` SET `options` = '{"width": 54, "height": 20, "format": [64, 55], "margins": {"top": 5,"bottom": 0,"left": 0,"right": 0}}' WHERE `zz_prints`.`name` = 'Barcode';
-- Aggiunto collegamento con allegato per impostare la ricevuta principale
ALTER TABLE `co_documenti` ADD `id_ricevuta_principale` INT(11);
UPDATE `co_documenti` SET `co_documenti`.`id_ricevuta_principale` = (
    SELECT `zz_files`.`id` FROM `zz_files` WHERE `zz_files`.`id_module` = (
        SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di vendita'
    ) AND `zz_files`.`id_record` = `co_documenti`.`id`
    AND `zz_files`.`name` LIKE 'Ricevuta%'
    ORDER BY `zz_files`.`created_at`
    LIMIT 1
);

-- Aggiunta riferimenti testuali su descrizione righe per Fatture
UPDATE `co_righe_documenti`
    INNER JOIN `co_righe_contratti` ON `co_righe_documenti`.`original_id` = `co_righe_contratti`.`id`
    INNER JOIN `co_contratti` ON `co_contratti`.`id` = `co_righe_contratti`.`idcontratto`
SET `co_righe_documenti`.`descrizione` = CONCAT(`co_righe_documenti`.`descrizione`, '\nRif. contratto num. ', `co_contratti`.`numero`, ' del ', DATE_FORMAT(`co_contratti`.`data_bozza`, '%d/%m/%Y'))
WHERE `co_righe_documenti`.`original_type` LIKE '%Contratti%';
UPDATE `co_righe_documenti`
    INNER JOIN `co_righe_preventivi` ON `co_righe_documenti`.`original_id` = `co_righe_preventivi`.`id`
    INNER JOIN `co_preventivi` ON `co_preventivi`.`id` = `co_righe_preventivi`.`idpreventivo`
SET `co_righe_documenti`.`descrizione` = CONCAT(`co_righe_documenti`.`descrizione`, '\nRif. preventivo num. ', `co_preventivi`.`numero`, ' del ', DATE_FORMAT(`co_preventivi`.`data_bozza`, '%d/%m/%Y'))
WHERE `co_righe_documenti`.`original_type` LIKE '%Preventivi%';
UPDATE `co_righe_documenti`
    INNER JOIN `or_righe_ordini` ON `co_righe_documenti`.`original_id` = `or_righe_ordini`.`id`
    INNER JOIN `or_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine`
    INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine`
SET `co_righe_documenti`.`descrizione` = CONCAT(`co_righe_documenti`.`descrizione`, '\nRif. ordine num. ', IF(`or_ordini`.`numero_esterno`!='', `or_ordini`.`numero_esterno`, `or_ordini`.`numero`), ' del ', DATE_FORMAT(`or_ordini`.`data`, '%d/%m/%Y'))
WHERE `co_righe_documenti`.`original_type` LIKE '%Ordini%';
UPDATE `co_righe_documenti`
    INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`
    INNER JOIN `dt_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`
    INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt`
SET `co_righe_documenti`.`descrizione` = CONCAT(`co_righe_documenti`.`descrizione`, '\nRif. ', LOWER(`dt_tipiddt`.`descrizione`), ' num. ', `dt_ddt`.`numero`, ' del ', DATE_FORMAT(`dt_ddt`.`data`, '%d/%m/%Y'))
WHERE `co_righe_documenti`.`original_type` LIKE '%DDT%';
UPDATE `co_righe_documenti`
    INNER JOIN `in_righe_interventi` ON `co_righe_documenti`.`original_id` = `in_righe_interventi`.`id`
    INNER JOIN `in_interventi` ON `in_interventi`.`id` = `in_righe_interventi`.`idintervento`
SET `co_righe_documenti`.`descrizione` = CONCAT(`co_righe_documenti`.`descrizione`, '\nRif. attività num. ', `in_interventi`.`codice`, ' del ', DATE_FORMAT(`in_interventi`.`data_richiesta`, '%d/%m/%Y'))
WHERE `co_righe_documenti`.`original_type` LIKE '%Interventi%';

-- Aggiunta riferimenti testuali su descrizione righe per Ordini
UPDATE `or_righe_ordini`
    INNER JOIN `co_righe_contratti` ON `or_righe_ordini`.`original_id` = `co_righe_contratti`.`id`
    INNER JOIN `co_contratti` ON `co_contratti`.`id` = `co_righe_contratti`.`idcontratto`
SET `or_righe_ordini`.`descrizione` = CONCAT(`or_righe_ordini`.`descrizione`, '\nRif. contratto num. ', `co_contratti`.`numero`, ' del ', DATE_FORMAT(`co_contratti`.`data_bozza`, '%d/%m/%Y'))
WHERE `or_righe_ordini`.`original_type` LIKE '%Contratti%';
UPDATE `or_righe_ordini`
    INNER JOIN `co_righe_preventivi` ON `or_righe_ordini`.`original_id` = `co_righe_preventivi`.`id`
    INNER JOIN `co_preventivi` ON `co_preventivi`.`id` = `co_righe_preventivi`.`idpreventivo`
SET `or_righe_ordini`.`descrizione` = CONCAT(`or_righe_ordini`.`descrizione`, '\nRif. preventivo num. ', `co_preventivi`.`numero`, ' del ', DATE_FORMAT(`co_preventivi`.`data_bozza`, '%d/%m/%Y'))
WHERE `or_righe_ordini`.`original_type` LIKE '%Preventivi%';
UPDATE `or_righe_ordini`
    INNER JOIN `dt_righe_ddt` ON `or_righe_ordini`.`original_id` = `dt_righe_ddt`.`id`
    INNER JOIN `dt_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`
    INNER JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt`
SET `or_righe_ordini`.`descrizione` = CONCAT(`or_righe_ordini`.`descrizione`, '\nRif. ', LOWER(`dt_tipiddt`.`descrizione`), ' num. ', `dt_ddt`.`numero`, ' del ', DATE_FORMAT(`dt_ddt`.`data`, '%d/%m/%Y'))
WHERE `or_righe_ordini`.`original_type` LIKE '%DDT%';
UPDATE `or_righe_ordini`
    INNER JOIN `in_righe_interventi` ON `or_righe_ordini`.`original_id` = `in_righe_interventi`.`id`
    INNER JOIN `in_interventi` ON `in_interventi`.`id` = `in_righe_interventi`.`idintervento`
SET `or_righe_ordini`.`descrizione` = CONCAT(`or_righe_ordini`.`descrizione`, '\nRif. attività num. ', `in_interventi`.`codice`, ' del ', DATE_FORMAT(`in_interventi`.`data_richiesta`, '%d/%m/%Y'))
WHERE `or_righe_ordini`.`original_type` LIKE '%Interventi%';

-- Aggiunta riferimenti testuali su descrizione righe per DDT
UPDATE `dt_righe_ddt`
    INNER JOIN `co_righe_contratti` ON `dt_righe_ddt`.`original_id` = `co_righe_contratti`.`id`
    INNER JOIN `co_contratti` ON `co_contratti`.`id` = `co_righe_contratti`.`idcontratto`
SET `dt_righe_ddt`.`descrizione` = CONCAT(`dt_righe_ddt`.`descrizione`, '\nRif. contratto num. ', `co_contratti`.`numero`, ' del ', DATE_FORMAT(`co_contratti`.`data_bozza`, '%d/%m/%Y'))
WHERE `dt_righe_ddt`.`original_type` LIKE '%Contratti%';
UPDATE `dt_righe_ddt`
    INNER JOIN `co_righe_preventivi` ON `dt_righe_ddt`.`original_id` = `co_righe_preventivi`.`id`
    INNER JOIN `co_preventivi` ON `co_preventivi`.`id` = `co_righe_preventivi`.`idpreventivo`
SET `dt_righe_ddt`.`descrizione` = CONCAT(`dt_righe_ddt`.`descrizione`, '\nRif. preventivo num. ', `co_preventivi`.`numero`, ' del ', DATE_FORMAT(`co_preventivi`.`data_bozza`, '%d/%m/%Y'))
WHERE `dt_righe_ddt`.`original_type` LIKE '%Preventivi%';
UPDATE `dt_righe_ddt`
    INNER JOIN `or_righe_ordini` ON `dt_righe_ddt`.`original_id` = `or_righe_ordini`.`id`
    INNER JOIN `or_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine`
    INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine`
SET `dt_righe_ddt`.`descrizione` = CONCAT(`dt_righe_ddt`.`descrizione`, '\nRif. ordine num. ', IF(`or_ordini`.`numero_esterno`!='', `or_ordini`.`numero_esterno`, `or_ordini`.`numero`), ' del ', DATE_FORMAT(`or_ordini`.`data`, '%d/%m/%Y'))
WHERE `dt_righe_ddt`.`original_type` LIKE '%Ordini%';
UPDATE `dt_righe_ddt`
    INNER JOIN `in_righe_interventi` ON `dt_righe_ddt`.`original_id` = `in_righe_interventi`.`id`
    INNER JOIN `in_interventi` ON `in_interventi`.`id` = `in_righe_interventi`.`idintervento`
SET `dt_righe_ddt`.`descrizione` = CONCAT(`dt_righe_ddt`.`descrizione`, '\nRif. attività num. ', `in_interventi`.`codice`, ' del ', DATE_FORMAT(`in_interventi`.`data_richiesta`, '%d/%m/%Y'))
WHERE `dt_righe_ddt`.`original_type` LIKE '%Interventi%';

-- Aggiunta campi per i riferimenti in Preventivi
ALTER TABLE `co_righe_preventivi` ADD `original_id` int(11), ADD `original_type` varchar(255);

-- Fix query per plugin Impianti del cliente
UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Matricola, Nome, Data, Descrizione", "query": "SELECT id, (SELECT `id` FROM `zz_modules` WHERE `name` = ''Impianti'') AS _link_module_, id AS _link_record_, matricola AS Matricola, nome AS Nome, DATE_FORMAT(data, ''%d/%m/%Y'') AS Data, descrizione AS Descrizione FROM my_impianti WHERE idanagrafica=|id_parent| HAVING 2=2 ORDER BY id DESC"} ]}' WHERE `zz_plugins`.`name` = 'Impianti del cliente';

-- Aggiornamento del modulo Banche per il supporto completo alle Anagrafiche
ALTER TABLE `co_banche` ADD `id_anagrafica` INT(11) NOT NULL, CHANGE `note` `note` TEXT, CHANGE `filiale` `filiale` varchar(255), ADD `predefined` BOOLEAN NOT NULL DEFAULT FALSE, ADD `creditor_id` varchar(255), ADD `codice_sia` varchar(5);
UPDATE `co_banche` SET  `id_anagrafica` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Azienda predefinita');
ALTER TABLE `co_banche` ADD FOREIGN KEY (`id_anagrafica`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE;

-- Collegamento sulla base dei campi aggiuntivi per le Anagrafiche
UPDATE `co_banche` SET `co_banche`.`id_anagrafica` = (SELECT valore FROM zz_settings WHERE nome = 'Azienda predefinita');

-- Aggiornamento ID relativo sulle Anagrafiche
ALTER TABLE `an_anagrafiche` CHANGE `idbanca_acquisti` `idbanca_acquisti` INT(11),
    CHANGE `idbanca_vendite` `idbanca_vendite` INT(11);
UPDATE `an_anagrafiche` SET `idbanca_acquisti` = NULL WHERE `idbanca_vendite` = 0;
UPDATE `an_anagrafiche` SET `idbanca_vendite` = NULL WHERE `idbanca_vendite` = 0;

INSERT INTO `co_banche` (`id_anagrafica`, `nome`, `iban`, `bic`, `filiale`, `predefined`) SELECT idanagrafica, IF(appoggiobancario != '', appoggiobancario, CONCAT('Banca predefinita di ', ragione_sociale)), codiceiban, bic, filiale, 1 FROM an_anagrafiche WHERE codiceiban IS NOT  NULL AND codiceiban != '';

UPDATE `an_anagrafiche` SET `idbanca_acquisti` = (SELECT `id` FROM `co_banche` WHERE `co_banche`.`id_anagrafica` = `an_anagrafiche`.`idanagrafica` LIMIT 1) WHERE `idbanca_acquisti` IS NULL;
UPDATE `an_anagrafiche` SET `idbanca_vendite` = (SELECT `id` FROM `co_banche` WHERE `co_banche`.`id_anagrafica` = `an_anagrafiche`.`idanagrafica` LIMIT 1) WHERE `idbanca_vendite` IS NULL;

-- Aggiornamento tabella principale per Banche
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_banche` INNER JOIN an_anagrafiche ON `an_anagrafiche`.`idanagrafica` = `co_banche`.`id_anagrafica` WHERE 1=1 AND `co_banche`.`deleted_at` IS NULL AND `an_anagrafiche`.`deleted_at` IS NULL HAVING 2=2' WHERE `name` = 'Banche';
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Banche'), 'Anagrafica', 'an_anagrafiche.ragione_sociale', 0, 1, 0, 0, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Banche'), 'Predefinito', 'IF(`co_banche`.`predefined`, ''Si'', ''No'')', 6, 1, 0, 0, 1, 0, 1);

-- Campo id_banca_controparte e id_banca_azienda per i Documenti
ALTER TABLE `co_documenti` ADD `id_banca_controparte` INT(11) AFTER `idpagamento`;
ALTER TABLE `co_preventivi` ADD `id_banca_controparte` INT(11) AFTER `idpagamento`;
ALTER TABLE `co_contratti` ADD `id_banca_controparte` INT(11) AFTER `idpagamento`;
ALTER TABLE `dt_ddt` ADD `id_banca_controparte` INT(11) AFTER `idpagamento`;
ALTER TABLE `or_ordini` ADD `id_banca_controparte` INT(11) NOT NULL AFTER `idpagamento`;

ALTER TABLE `co_documenti` CHANGE `idbanca` `id_banca_azienda` INT(11) AFTER `idpagamento`;
ALTER TABLE `co_preventivi` ADD `id_banca_azienda` INT(11) AFTER `idpagamento`;
ALTER TABLE `co_contratti` ADD `id_banca_azienda` INT(11) AFTER `idpagamento`;
ALTER TABLE `dt_ddt` ADD `id_banca_azienda` INT(11) AFTER `idpagamento`;
ALTER TABLE `or_ordini` ADD `id_banca_azienda` INT(11) AFTER `idpagamento`;

UPDATE `co_documenti` SET `id_banca_azienda` = NULL WHERE `id_banca_azienda` = 0;

-- Aggiunta unità di misura secondaria per le stampe documenti di vendita
ALTER TABLE `mg_articoli` ADD `um_secondaria` varchar(255), ADD `fattore_um_secondaria` DECIMAL(15, 6);

-- Aggiunta impostazione per impegnare o meno automaticamente le quantità negli ordini clienti
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conferma automaticamente le quantità negli ordini cliente', '1', 'boolean', '1', 'Ordini', NULL, NULL);

ALTER TABLE `or_righe_ordini` ADD `confermato` BOOLEAN NOT NULL AFTER `id_dettaglio_fornitore`;
UPDATE `or_righe_ordini` SET `confermato` = 1;

-- Aggiunta impostazione per impegnare o meno automaticamente le quantità negli ordini fornitori
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conferma automaticamente le quantità negli ordini fornitore', '1', 'boolean', '1', 'Ordini', NULL, NULL);

-- Aggiunte note prima nota
ALTER TABLE `co_movimenti` ADD `note` TEXT AFTER `descrizione`;

-- Aggiunta risorse API dedicate alle Stampe in binary formato
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'retrieve', 'stampa-binary', 'API\\Common\\Stampa', '1');

-- Fix <CausalePagamento> tracciato 1.2.1 FE (che potrà essere utilizzata a partire dal 01/10/2020 e sarà obbligatoria dal 01/01/2021)
INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES ('M2', 'Prestazioni di lavoro autonomo non esercitate abitualmente per le quali sussiste l’obbligo di iscrizione alla Gestione Separata ENPAPI');

INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES ('M1', 'Redditi derivanti dall’assunzione di obblighi di fare, di non fare o permettere');

INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES ('L1', 'Redditi derivanti dall’utilizzazione economica di opere dell’ingegno, di brevetti industriali e di processi, che sono percepiti da soggetti che abbiano acquistato a titolo oneroso i diritti alla loro utilizzazione');

INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES ('O1', 'Redditi derivanti dall’assunzione di obblighi di fare, di non fare o permettere, per le quali non sussiste l’obbligo di iscrizione alla gestione separata (Circ. INPS n. 104/2001)');

INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES ('V1', 'Redditi derivanti da attività commerciali non esercitate abitualmente (ad esempio, provvigioni corrisposte per prestazioni occasionali ad agente o rappresentante di commercio, mediatore, procacciatore d’affari)');

INSERT INTO `fe_causali_pagamento_ritenuta` (`codice`, `descrizione`) VALUES ('V2', 'Redditi derivanti dalle prestazioni non esercitate abitualmente rese dagli incaricati alla vendita diretta a domicilio');

UPDATE `fe_causali_pagamento_ritenuta` SET `codice` = 'ZO' WHERE `fe_causali_pagamento_ritenuta`.`codice` = 'Z';

UPDATE `zz_settings` SET `valore` = 'ZO' WHERE `zz_settings`.`nome` = 'Causale ritenuta d''acconto' AND `zz_settings`.`valore` = 'Z';

-- Disattivazione aliquote IVA con NATURA non più supportata dal tracciato 1.2.1 FE
-- andrà doverosamente specificato il sotto codice (esempio N3.1, N3.2 etc)
UPDATE `co_iva` SET `deleted_at` = now() WHERE `co_iva`.`codice_natura_fe` IN ('N2','N3','N6');

-- Introduzione tabella di supporto per nodo <TipoRitenuta> tracciato 1.2.1 FE
CREATE TABLE IF NOT EXISTS `fe_tipi_ritenuta` (
    `codice` varchar(4) NOT NULL,
    `descrizione` varchar(255) NOT NULL,
    PRIMARY KEY (`codice`)
) ENGINE=InnoDB;

INSERT INTO `fe_tipi_ritenuta` (`codice`, `descrizione`) VALUES
('RT01', 'Ritenuta persone fisiche'),
('RT02', 'Ritenuta persone giuridiche'),
('RT03', 'Contributo INPS'),
('RT04', 'Contributo ENASARCO'),
('RT05', 'Contributo ENPAM'),
('RT06', 'Altro contributo previdenziale');

-- Impostazione percentuale deducibile di default al 100%
ALTER TABLE `co_pianodeiconti3` CHANGE `percentuale_deducibile` `percentuale_deducibile` DECIMAL(5,2) NOT NULL DEFAULT '100';

ALTER TABLE `fe_stati_documento` ADD `is_generabile` BOOLEAN DEFAULT FALSE,
    ADD `is_inviabile` BOOLEAN DEFAULT FALSE,
    ADD `tipo` varchar(255) NOT NULL;

UPDATE `fe_stati_documento` SET `is_generabile` = '1', `is_inviabile` = '1' WHERE `codice` = 'ERVAL';
UPDATE `fe_stati_documento` SET `is_generabile` = '1', `is_inviabile` = '1' WHERE `codice` = 'ERR';
UPDATE `fe_stati_documento` SET `is_generabile` = '1', `is_inviabile` = '1' WHERE `codice` = 'GEN';
UPDATE `fe_stati_documento` SET `is_generabile` = '1' WHERE `codice` = 'NS';
UPDATE `fe_stati_documento` SET `is_generabile` = '1' WHERE `codice` = 'EC02';

UPDATE `fe_stati_documento` SET `tipo` = 'danger';
UPDATE `fe_stati_documento` SET `tipo` = 'warning' WHERE `codice` IN ('ERVAL', 'WAIT', 'NE');
UPDATE `fe_stati_documento` SET `tipo` = 'success' WHERE `codice` IN ('EC01', 'RC');
UPDATE `fe_stati_documento` SET `tipo` = 'info' WHERE `codice` IN ('GEN', 'MC');

-- Aggiunta stampa liquidazione IVA
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES (NULL,(SELECT id FROM zz_modules WHERE name='Stampe contabili'), '1', 'Liquidazione IVA', 'Liquidazione IVA', 'Liquidazione IVA', 'liquidazione_iva', '', '', 'fa fa-print', '', '', '0', '0', '1', '1');

-- Aggiunta impostazione per Liquidazione IVA
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Liquidazione iva', 'Mensile', 'list[Mensile,Trimestrale]', '1', 'Fatturazione', '16', NULL);

-- Aggiornamento causali DDT in caso di assenza
UPDATE `dt_ddt` SET `idcausalet` = (SELECT `id` FROM `dt_causalet` WHERE `predefined` = 1 LIMIT 1) WHERE EXISTS(SELECT `id` FROM `dt_causalet` WHERE `predefined` = 1) AND (idcausalet = 0 OR idcausalet IS NULL);
UPDATE `dt_ddt` SET `idcausalet` = (SELECT `id` FROM `dt_causalet` WHERE `descrizione` = 'Vendita' LIMIT 1) WHERE EXISTS(SELECT `id` FROM `dt_causalet` WHERE `descrizione` = 'Vendita') AND (idcausalet = 0 OR idcausalet IS NULL);
UPDATE `dt_ddt` SET `idcausalet` = (SELECT `id` FROM `dt_causalet`) WHERE EXISTS(SELECT `id` FROM `dt_causalet`) AND (idcausalet = 0 OR idcausalet IS NULL);

-- Aggiornamento del modulo Impostazioni
UPDATE `zz_modules` SET `options` = 'custom' WHERE `name` = 'Impostazioni';

-- Elimino token disabilitati
DELETE FROM `zz_tokens` WHERE `zz_tokens`.`enabled` = 0;

-- Aggiunto colonna sconto per le coppie anagrafica articolo
ALTER TABLE `mg_prezzi_articoli` ADD `sconto_percentuale` DECIMAL(15,6) NOT NULL AFTER `massimo`;

-- Aggiunta impostazione per mostrare o nascondere barra plugin
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Nascondere la barra dei plugin di default', '0', 'boolean', '1', 'Generali',  '2', NULL);
