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
(NULL, 'Backup automatico', 'Modules\\Backups\\BackupTask', '0 1 * * *', NULL);

DELETE FROM `zz_hooks` WHERE `class` = 'Modules\\Backups\\BackupHook';

-- Modifica dei Listini in Piani di sconto/rincaro
UPDATE `zz_modules` SET `title` = 'Piani di sconto/rincaro' WHERE `name` = 'Listini';

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
(NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'id', 'mg_articoli.id', '1', '1', '0', '0', NULL, NULL, '0', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Codice', 'mg_articoli.codice', '2', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Categoria', '(SELECT `nome` FROM `mg_categorie` WHERE `id` = `id_categoria`)', '4', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Sottocategoria', '(SELECT `nome` FROM `mg_categorie` WHERE `id` = `id_sottocategoria`)', '5', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Q.tà', '(SELECT SUM(IF(mg_movimenti.idsede_azienda = |giacenze_sedi_idsede|, mg_movimenti.qta, IF(mg_movimenti.idsede_controparte = |giacenze_sedi_idsede|, -mg_movimenti.qta, 0))) FROM mg_movimenti LEFT JOIN an_sedi ON an_sedi.id = mg_movimenti.idsede_azienda WHERE mg_movimenti.idarticolo=mg_articoli.id)', '9', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Descrizione', 'mg_articoli.descrizione', '1', '1', '0', '0', '', '', '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Fornitore', '(SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica` = `id_fornitore`)', '6', '1', '0', '0', NULL, NULL, '1', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Prezzo di acquisto', 'prezzo_acquisto', '6', '1', '0', '1', NULL, NULL, '1', '1', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Prezzo di vendita', 'prezzo_vendita', '6', '1', '0', '1', NULL, NULL, '1', '1', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Prezzo vendita ivato', 'IF( co_iva.percentuale IS NOT NULL, (mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * co_iva.percentuale / 100), mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita*(SELECT co_iva.percentuale FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore AND nome=\'Iva predefinita\')/100 )', '8', '1', '0', '1', '', '', '0', '0', '1'),
 (NULL, (SELECT  `id` FROM `zz_modules` WHERE `name` = 'Giacenze sedi'), 'Barcode', 'mg_articoli.barcode', '2', '1', '0', '0', '', '', '1', '0', '1');

-- Aggiunta risorse API dedicate alle task in cron
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'retrieve', 'cron-logs', 'API\\Common\\Task', '1'),
(NULL, 'v1', 'create', 'cron-restart', 'API\\Common\\Task', '1');

-- Fix visualizzazione modulo Causali movimenti
UPDATE `zz_views` SET `query` = 'CONCAT(UCASE(LEFT(tipo_movimento, 1)), SUBSTRING(tipo_movimento, 2))', `name` = 'Tipo' WHERE `zz_views`.`name` = 'Movimento di carico' AND id_module = (SELECT id FROM zz_modules WHERE name = 'Causali movimenti');

-- Aggiornamento versione API services
UPDATE `zz_settings` SET `valore` = 'v3' WHERE `nome` = 'OSMCloud Services API Version';

-- Aggiornamento margini stampa barbcode
UPDATE `zz_prints` SET `options` = '{"width": 54, "height": 20, "format": [64, 55], "margins": {"top": 5,"bottom": 0,"left": 0,"right": 0}}' WHERE `zz_prints`.`name` = 'Barcode';

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
SET `co_righe_documenti`.`descrizione` = CONCAT(`co_righe_documenti`.`descrizione`, '\nRif. ', LOWER(`or_tipiordine`.`descrizione`), ' num. ', `or_ordini`.`numero`, ' del ', DATE_FORMAT(`or_ordini`.`data`, '%d/%m/%Y'))
WHERE `co_righe_documenti`.`original_type` LIKE '%Ordini%';
UPDATE `co_righe_documenti`
    INNER JOIN `dt_righe_ddt` ON `co_righe_documenti`.`original_id` = `dt_righe_ddt`.`id`
    INNER JOIN `dt_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idordine`
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
    INNER JOIN `dt_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idordine`
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
SET `dt_righe_ddt`.`descrizione` = CONCAT(`dt_righe_ddt`.`descrizione`, '\nRif. ', LOWER(`or_tipiordine`.`descrizione`), ' num. ', `or_ordini`.`numero`, ' del ', DATE_FORMAT(`or_ordini`.`data`, '%d/%m/%Y'))
WHERE `dt_righe_ddt`.`original_type` LIKE '%Ordini%';
UPDATE `dt_righe_ddt`
    INNER JOIN `in_righe_interventi` ON `dt_righe_ddt`.`original_id` = `in_righe_interventi`.`id`
    INNER JOIN `in_interventi` ON `in_interventi`.`id` = `in_righe_interventi`.`idintervento`
SET `dt_righe_ddt`.`descrizione` = CONCAT(`dt_righe_ddt`.`descrizione`, '\nRif. attività num. ', `in_interventi`.`codice`, ' del ', DATE_FORMAT(`in_interventi`.`data_richiesta`, '%d/%m/%Y'))
WHERE `dt_righe_ddt`.`original_type` LIKE '%Interventi%';

-- Aggiunta campi per i riferimenti in Preventivi
ALTER TABLE `co_righe_preventivi` ADD `original_id` int(11), ADD `original_type` varchar(255);

-- Fix qtà impegnata: aggiunto filtro per ricerca solo su ordini cliente e non tutti gli ordini
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `mg_articoli` LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore=an_anagrafiche.idanagrafica LEFT JOIN co_iva ON mg_articoli.idiva_vendita=co_iva.id LEFT JOIN (SELECT SUM(qta-qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine=or_ordini.id INNER JOIN or_tipiordine ON or_ordini.idtipoordine=or_tipiordine.id WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato=0) AND or_tipiordine.dir=''entrata'' GROUP BY idarticolo) a ON a.idarticolo=mg_articoli.id LEFT JOIN mg_categorie ON mg_articoli.id_categoria=mg_categorie.id LEFT JOIN mg_categorie AS sottocategorie ON mg_articoli.id_sottocategoria=sottocategorie.id WHERE 1=1 AND (`mg_articoli`.`deleted_at`) IS NULL HAVING 2=2 ORDER BY `mg_articoli`.`descrizione`' WHERE `zz_modules`.`name` = 'Articoli';

-- Fix query per plugin Impianti del cliente
UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Matricola, Nome, Data, Descrizione", "query": "SELECT id, (SELECT `id` FROM `zz_modules` WHERE `name` = ''Impianti'') AS _link_module_, id AS _link_record_, matricola AS Matricola, nome AS Nome, DATE_FORMAT(data, ''%d/%m/%Y'') AS Data, descrizione AS Descrizione FROM my_impianti WHERE idanagrafica=|id_parent| HAVING 2=2 ORDER BY id DESC"} ]}' WHERE `zz_plugins`.`name` = 'Impianti del cliente';
