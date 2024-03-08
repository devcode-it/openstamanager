-- Aggiunta colonna Rif. fattura per preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Rif. fattura', 'fattura.info', 9, 1, 0, 0, 1);

-- Modifico impostazione "Lunghezza in pagine del buffer Datatables" per renderla modificabile dall'utente
UPDATE `zz_settings` SET `editable` = '1', `tipo` = 'list[5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100]', `help` = 'Attenzione, a valori più elevati corrispondono performance peggiori' WHERE `zz_settings`.`nome` = 'Lunghezza in pagine del buffer Datatables'; 

-- Impostazioni per decidere eventuali date predefinite per l'inizio o la fine del calendario (impostate al login)
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,  `order`, `help`) VALUES (NULL, 'Inizio periodo calendario', '', 'date', '1', 'Generali', '23', NULL); 
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,  `order`, `help`) VALUES (NULL, 'Fine periodo calendario', '', 'date', '1', 'Generali', '23', NULL); 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'movimenti.qta' WHERE `zz_views`.`name` = 'Q.tà' AND `zz_modules`.`name` = 'Giacenze sedi';
-- Fix widget rate contrattuali
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_fatturazione_contratti WHERE idcontratto IN( SELECT id FROM co_contratti WHERE co_contratti.idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1)) AND co_fatturazione_contratti.iddocumento=0' WHERE `zz_widgets`.`name` = 'Rate contrattuali'; 

-- Divisione delle colonne modulo modelli prima nota
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'co_movimenti_modelli.nome' WHERE `zz_views`.`name` = 'Nome' AND `zz_modules`.`name` = 'Modelli prima nota';
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