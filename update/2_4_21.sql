-- Aggiunta fattura.info query preventivi
UPDATE
    `zz_modules`
SET
    `options` = 'SELECT |select|\r\nFROM `co_preventivi`\r\n    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`\r\n    LEFT JOIN (\r\n        SELECT `idpreventivo`,\r\n            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\r\n            SUM(`subtotale` - `sconto` + `iva`) AS `totale`\r\n        FROM `co_righe_preventivi`\r\n        GROUP BY `idpreventivo`\r\n    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`\r\n
LEFT JOIN (SELECT co_righe_documenti.idpreventivo, CONCAT(\'Fatt. \', co_documenti.numero_esterno,\' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento) AS fattura ON fattura.idpreventivo = co_preventivi.id
WHERE 1=1 |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = \'0000-00-00\')| AND default_revision = 1\r\nGROUP BY `co_preventivi`.`id`\r\nHAVING 2=2\r\nORDER BY `co_preventivi`.`id` DESC '
WHERE
    `zz_modules`.`name` = 'Preventivi';

-- Aggiunta colonna Rif. fattura per preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Rif. fattura', 'fattura.info', 9, 1, 0, 0, 1);

-- Modifico impostazione "Lunghezza in pagine del buffer Datatables" per renderla modificabile dall'utente
UPDATE `zz_settings` SET `editable` = '1', `tipo` = 'list[5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100]', `help` = 'Attenzione, a valori più elevati corrispondono performance peggiori' WHERE `zz_settings`.`nome` = 'Lunghezza in pagine del buffer Datatables'; 

-- Impostazioni per decidere eventuali date predefinite per l'inizio o la fine del calendario (impostate al login)
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,  `order`, `help`) VALUES (NULL, 'Inizio periodo calendario', '', 'date', '1', 'Generali', '23', NULL); 
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,  `order`, `help`) VALUES (NULL, 'Fine periodo calendario', '', 'date', '1', 'Generali', '23', NULL); 

-- Ottimizzazione calcolo quantità su modulo "Giacenze sedi"
UPDATE `zz_modules` SET `options`='SELECT |select| FROM `mg_articoli`
    LEFT OUTER JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT OUTER JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT OUTER JOIN (
        SELECT SUM(qta - qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini
            INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id
        WHERE idstatoordine IN (SELECT id FROM or_statiordine WHERE completato = 0)
        GROUP BY idarticolo
    ) ordini ON ordini.idarticolo = mg_articoli.id
    LEFT OUTER JOIN (SELECT `idarticolo`, `idsede_azienda`, SUM(`qta`) AS `qta` FROM `mg_movimenti` WHERE `idsede_azienda` = |giacenze_sedi_idsede| GROUP BY `idarticolo`, `idsede_azienda`) movimenti ON `mg_articoli`.`id` = `movimenti`.`idarticolo`
WHERE 1=1 AND `mg_articoli`.`deleted_at` IS NULL HAVING 2=2 AND `Q.tà` > 0 ORDER BY `descrizione`' WHERE `name` = 'Giacenze sedi';
UPDATE `zz_views` SET `query`='movimenti.qta', `format`=1 WHERE `id_module`=(SELECT `id` FROM `zz_modules` WHERE `name`='Giacenze sedi') AND `name`='Q.tà';

-- Fix widget rate contrattuali
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_fatturazione_contratti WHERE idcontratto IN( SELECT id FROM co_contratti WHERE co_contratti.idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1)) AND co_fatturazione_contratti.iddocumento=0' WHERE `zz_widgets`.`name` = 'Rate contrattuali'; 

-- Divisione delle colonne modulo modelli prima nota
UPDATE `zz_views` SET `query` = 'co_movimenti_modelli.nome' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name`='Modelli prima nota') AND `name` LIKE 'Nome';
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Modelli prima nota'), 'Causale', 'co_movimenti_modelli.descrizione', '2', '1', '0', '0', NULL, NULL, '1', '0', '1');

-- Aggiunto flag peso e volume manuale in fatture e ddt
ALTER TABLE `dt_ddt` ADD `peso_manuale` TINYINT(1) NOT NULL AFTER `volume`;
ALTER TABLE `dt_ddt` ADD `volume_manuale` TINYINT(1) NOT NULL AFTER `peso_manuale`;
ALTER TABLE `co_documenti` ADD `peso_manuale` TINYINT(1) NOT NULL AFTER `volume`;
ALTER TABLE `co_documenti` ADD `volume_manuale` TINYINT(1) NOT NULL AFTER `peso_manuale`;

-- Fix fornitore predefinito articoli
INSERT INTO `mg_prezzi_articoli`(
    `id_articolo`,
    `id_anagrafica`,
    `prezzo_unitario`,
    `prezzo_unitario_ivato`,
    `dir`,
    `sconto_percentuale`
)(
    SELECT
        `mg_articoli`.`id`,
        `mg_articoli`.`id_fornitore`,
        `mg_articoli`.`prezzo_acquisto`,
        `mg_articoli`.`prezzo_acquisto` + `mg_articoli`.`prezzo_acquisto` * IFNULL(
            `co_iva`.`percentuale`,
            `iva_default`.`percentuale`
        ) / 100,
        'uscita',
        0
    FROM
        `mg_articoli`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN(
        SELECT
            `valore` AS `idiva`
        FROM
            `zz_settings`
        WHERE
            `nome` = 'Iva predefinita'
    ) AS `impostazioni`
ON
    1 = 1
LEFT JOIN `co_iva` AS `iva_default`
ON
    `iva_default`.`id` = `impostazioni`.`idiva`
WHERE
    `id_fornitore` NOT IN(
    SELECT
        `id_anagrafica`
    FROM
        `mg_prezzi_articoli` AS `prezzi_specifica`
    WHERE
        `id_articolo` = `mg_articoli`.`id`
) AND `id_fornitore` IN (SELECT `idanagrafica` FROM `an_anagrafiche`)
);