-- Correzione riferimento per colonna email inviata nei moduli
-- Preventivi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `co_preventivi`
    LEFT JOIN `an_anagrafiche`
        ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_statipreventivi`
        ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT JOIN `co_statipreventivi_lang`
        ON (
            `co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record`
            AND co_statipreventivi_lang.id_lang = |lang|
        )
    LEFT JOIN (
        SELECT
            `idpreventivo`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM
            `co_righe_preventivi`
        GROUP BY
            `idpreventivo`
    ) AS righe
        ON `co_preventivi`.`id` = `righe`.`idpreventivo`
    LEFT JOIN (
        SELECT
            `an_anagrafiche`.`idanagrafica`,
            `an_anagrafiche`.`ragione_sociale` AS nome
        FROM
            `an_anagrafiche`
    ) AS agente
        ON `agente`.`idanagrafica` = `co_preventivi`.`idagente`
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`,
            `co_righe_documenti`.`original_document_id` AS `idpreventivo`
        FROM
            `co_documenti`
            INNER JOIN `co_righe_documenti`
                ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
        WHERE
            `original_document_type` = \'ModulesPreventiviPreventivo\'
        GROUP BY
            `idpreventivo`, `original_document_id`
    ) AS `fattura`
        ON `fattura`.`idpreventivo` = `co_preventivi`.`id`
    LEFT JOIN (
        SELECT
            COUNT(em_emails.id) AS emails,
            em_emails.id_record
        FROM
            em_emails
            INNER JOIN zz_operations
                ON zz_operations.id_email = em_emails.id
        WHERE
            id_module IN (
                SELECT `id`
                FROM `zz_modules`
                WHERE `name` = \'Preventivi\'
            )
            AND `zz_operations`.`op` = \'send-email\'
        GROUP BY
            em_emails.id_record
    ) AS `email`
        ON `email`.`id_record` = `co_preventivi`.`id`
WHERE
    1 = 1
    |segment(`co_preventivi`.`id_segment`)|
    |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = NULL)|
    AND `default_revision` = 1
GROUP BY
    `co_preventivi`.`id`,
    `fattura`.`info`
HAVING
    2 = 2
ORDER BY
    `co_preventivi`.`data_bozza` DESC,
    `numero` ASC' WHERE `zz_modules`.`name` = 'Preventivi';

-- Fatture di vendita
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (
        SELECT
            SUM(`totale`) AS `totale`,
            `iddocumento`
        FROM
            `co_movimenti`
        WHERE
            `totale` > 0
            AND `primanota` = 1
        GROUP BY
            `iddocumento`
    ) AS `primanota`
        ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche`
        ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento`
        ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang`
        ON (
            `co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record`
            AND co_tipidocumento_lang.|lang|
        )
    LEFT JOIN (
        SELECT
            `iddocumento`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva`
        FROM
            `co_righe_documenti`
            LEFT JOIN `co_iva`
                ON `co_iva`.`id` = `co_righe_documenti`.`idiva`
        GROUP BY
            `iddocumento`
    ) AS `righe`
        ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (
        SELECT
            `co_banche`.`id`,
            CONCAT(`co_banche`.`nome`, \' - \', `co_banche`.`iban`) AS `descrizione`
        FROM
            `co_banche`
        GROUP BY
            `co_banche`.`id`
    ) AS `banche`
        ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `co_statidocumento`
        ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang`
        ON (
            `co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record`
            AND `co_statidocumento_lang`.|lang|
        )
    LEFT JOIN `fe_stati_documento`
        ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang`
        ON (
            `fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record`
            AND `fe_stati_documento_lang`.|lang|
        )
    LEFT JOIN `co_ritenuta_contributi`
        ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (
        SELECT
            COUNT(`em_emails`.`id`) AS `emails`,
            `em_emails`.`id_record`
        FROM
            `em_emails`
            INNER JOIN `zz_operations`
                ON `zz_operations`.`id_email` = `em_emails`.`id`
        WHERE
            `id_module` IN (
                SELECT `id`
                FROM `zz_modules`
                WHERE `name` = \'Fatture di vendita\'
            )
            AND `zz_operations`.`op` = \'send-email\'
        GROUP BY
            `em_emails`.`id_record`
    ) AS `email`
        ON `email`.`id_record` = `co_documenti`.`id`
    LEFT JOIN `co_pagamenti`
        ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang`
        ON (
            `co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record`
            AND co_pagamenti_lang.|lang|
        )
    LEFT JOIN (
        SELECT
            `numero_esterno`,
            `id_segment`,
            `idtipodocumento`,
            `data`
        FROM
            `co_documenti`
        WHERE
            `co_documenti`.`idtipodocumento` IN (
                SELECT `id`
                FROM `co_tipidocumento`
                WHERE `dir` = \'entrata\'
            )
            AND `numero_esterno` != \'\'
            |date_period(`co_documenti`.`data`)|
        GROUP BY
            `id_segment`, `numero_esterno`, `idtipodocumento`
        HAVING
            COUNT(`numero_esterno`) > 1
    ) AS dup
        ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno`
        AND `dup`.`id_segment` = `co_documenti`.`id_segment`
        AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1 = 1
    AND `dir` = \'entrata\'
    |segment(`co_documenti`.`id_segment`)|
    |date_period(`co_documenti`.`data`)|
HAVING
    2 = 2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di vendita';

-- Interventi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche`
        ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici`
        ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (
        SELECT
            `idintervento`,
            SUM(`prezzo_unitario` * `qta` - `sconto`) AS `ricavo_righe`,
            SUM(`costo_unitario` * `qta`) AS `costo_righe`
        FROM
            `in_righe_interventi`
        GROUP BY
            `idintervento`
    ) AS `righe`
        ON `righe`.`idintervento` = `in_interventi`.`id`
    INNER JOIN `in_statiintervento`
        ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
    LEFT JOIN `in_statiintervento_lang`
        ON (
            `in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id`
            AND `in_statiintervento_lang`.|lang|
        )
    LEFT JOIN `an_referenti`
        ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (
        SELECT
            `an_sedi`.`id`,
            CONCAT(
                `an_sedi`.`nomesede`, \'<br />\',
                IF(`an_sedi`.`telefono` != \'\', CONCAT(`an_sedi`.`telefono`, \'<br />\'), \'\'),
                IF(`an_sedi`.`cellulare` != \'\', CONCAT(`an_sedi`.`cellulare`, \'<br />\'), \'\'),
                `an_sedi`.`citta`,
                IF(`an_sedi`.`indirizzo` != \'\', CONCAT(\' - \', `an_sedi`.`indirizzo`), \'\')
            ) AS `info`
        FROM
            `an_sedi`
    ) AS `sede_destinazione`
        ON `sede_destinazione`.`id` = `in_interventi`.`idsede_destinazione`
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`,
            `co_righe_documenti`.`original_document_id` AS `idintervento`
        FROM
            `co_documenti`
            INNER JOIN `co_righe_documenti`
                ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
        WHERE
            `original_document_type` = \'Modules\\\\Interventi\\\\Intervento\'
        GROUP BY
            `idintervento`, `original_document_id`
    ) AS `fattura`
        ON `fattura`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (
        SELECT
            `in_interventi_tecnici_assegnati`.`id_intervento`,
            GROUP_CONCAT(DISTINCT `ragione_sociale` SEPARATOR \', \') AS `nomi`
        FROM
            `an_anagrafiche`
            INNER JOIN `in_interventi_tecnici_assegnati`
                ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica`
        GROUP BY
            `id_intervento`
    ) AS `tecnici_assegnati`
        ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (
        SELECT
            `in_interventi_tecnici`.`idintervento`,
            GROUP_CONCAT(DISTINCT `ragione_sociale` SEPARATOR \', \') AS `nomi`
        FROM
            `an_anagrafiche`
            INNER JOIN `in_interventi_tecnici`
                ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica`
        GROUP BY
            `idintervento`
    ) AS `tecnici`
        ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (
        SELECT
            COUNT(`em_emails`.`id`) AS emails,
            `em_emails`.`id_record`
        FROM
            `em_emails`
            INNER JOIN `zz_operations`
                ON `zz_operations`.`id_email` = `em_emails`.`id`
        WHERE
            `id_module` IN (
                SELECT `zz_modules`.`id`
                FROM `zz_modules`
                WHERE `name` = \'Interventi\'
            )
            AND `zz_operations`.`op` = \'send-email\'
        GROUP BY
            `em_emails`.`id_record`
    ) AS `email`
        ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != \'\', CONCAT(\' - \', `nome`), \'\')) SEPARATOR \'<br />\') AS `descrizione`,
            `my_impianti_interventi`.`idintervento`
        FROM
            `my_impianti`
            INNER JOIN `my_impianti_interventi`
                ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto`
        GROUP BY
            `my_impianti_interventi`.`idintervento`
    ) AS `impianti`
        ON `impianti`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (
        SELECT
            `co_contratti`.`id`,
            CONCAT(`co_contratti`.`numero`, \' del \', DATE_FORMAT(`data_bozza`, \'%d/%m/%Y\')) AS `info`
        FROM
            `co_contratti`
    ) AS `contratto`
        ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (
        SELECT
            `co_preventivi`.`id`,
            CONCAT(`co_preventivi`.`numero`, \' del \', DATE_FORMAT(`data_bozza`, \'%d/%m/%Y\')) AS `info`
        FROM
            `co_preventivi`
    ) AS `preventivo`
        ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (
        SELECT
            `or_ordini`.`id`,
            CONCAT(`or_ordini`.`numero`, \' del \', DATE_FORMAT(`data`, \'%d/%m/%Y\')) AS `info`
        FROM
            `or_ordini`
    ) AS `ordine`
        ON `ordine`.`id` = `in_interventi`.`id_ordine`
    INNER JOIN `in_tipiintervento`
        ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang`
        ON (
            `in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id`
            AND `in_tipiintervento_lang`.|lang|
        )
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(\' \', `zz_files`.`name`) AS name,
            `zz_files`.`id_record`
        FROM
            `zz_files`
            INNER JOIN `zz_modules`
                ON `zz_files`.`id_module` = `zz_modules`.`id`
            LEFT JOIN `zz_modules_lang`
                ON (
                    `zz_modules_lang`.`id_record` = `zz_modules`.`id`
                    AND `zz_modules_lang`.|lang|
                )
        WHERE
            `zz_modules`.`name` = \'Interventi\'
        GROUP BY
            id_record
    ) AS `files`
        ON `files`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (
        SELECT
            `in_interventi_tags`.`id_intervento`,
            GROUP_CONCAT(DISTINCT `name` SEPARATOR \', \') AS `nomi`
        FROM
            `in_tags`
            INNER JOIN `in_interventi_tags`
                ON `in_interventi_tags`.`id_tag` = `in_tags`.`id`
        GROUP BY
            `in_interventi_tags`.`id_intervento`
    ) AS `tags`
        ON `in_interventi`.`id` = `tags`.`id_intervento`
    LEFT JOIN `an_zone`
        ON `an_anagrafiche`.`idzona` = `an_zone`.`id`
WHERE
    1 = 1
    |segment(`in_interventi`.`id_segment`)|
    |date_period(`orario_inizio`, `data_richiesta`)|
GROUP BY
    `in_interventi`.`id`
HAVING
    2 = 2
ORDER BY
    IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';

-- Ordini cliente
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `or_ordini`
    INNER JOIN `or_tipiordine`
        ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche`
        ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS agente
        ON `or_ordini`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN (
        SELECT
            `idordine`,
            SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM
            `or_righe_ordini`
        GROUP BY
            `idordine`
    ) AS righe
        ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (
        SELECT
            `idordine`,
            MIN(`data_evasione`) AS `data_evasione`
        FROM
            `or_righe_ordini`
        WHERE
            (`qta` - `qta_evasa`) > 0
        GROUP BY
            `idordine`
    ) AS `righe_da_evadere`
        ON `righe`.`idordine` = `righe_da_evadere`.`idordine`
    INNER JOIN `or_statiordine`
        ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN `or_statiordine_lang`
        ON (
            `or_statiordine`.`id` = `or_statiordine_lang`.`id_record`
            AND `or_statiordine_lang`.|lang|
        )
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(DISTINCT \'Fattura \', `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`,
            `co_righe_documenti`.`original_document_id` AS `idordine`
        FROM
            `co_documenti`
            INNER JOIN `co_righe_documenti`
                ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
        WHERE
            `original_document_type` = \'ModulesOrdiniOrdine\'
        GROUP BY
            `original_document_id`
    ) AS `fattura`
        ON `fattura`.`idordine` = `or_ordini`.`id`
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(DISTINCT \'DDT \', `dt_ddt`.`numero_esterno` SEPARATOR \', \') AS `info`,
            `dt_righe_ddt`.`original_document_id` AS `idddt`
        FROM
            `dt_ddt`
            INNER JOIN `dt_righe_ddt`
                ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`
        WHERE
            `original_document_type` = \'ModulesOrdiniOrdine\'
        GROUP BY
            `original_document_id`
    ) AS `ddt`
        ON `ddt`.`idddt` = `or_ordini`.`id`
    LEFT JOIN (
        SELECT
            COUNT(`em_emails`.`id`) AS emails,
            `em_emails`.`id_record`
        FROM
            `em_emails`
            INNER JOIN `zz_operations`
                ON `zz_operations`.`id_email` = `em_emails`.`id`
        WHERE
            `id_module` IN (
                SELECT `id`
                FROM `zz_modules`
                WHERE `name` = \'Ordini cliente\'
            )
            AND `zz_operations`.`op` = \'send-email\'
        GROUP BY
            `em_emails`.`id_record`
    ) AS email
        ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1 = 1
    |segment(`or_ordini`.`id_segment`)|
    AND `dir` = \'entrata\'
    |date_period(`or_ordini`.`data`)|
HAVING
    2 = 2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';

-- Ordini fornitore
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `or_ordini`
    INNER JOIN `or_tipiordine`
        ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche`
        ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN (
        SELECT
            `idordine`,
            SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM
            `or_righe_ordini`
        GROUP BY
            `idordine`
    ) AS righe
        ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (
        SELECT
            `idordine`,
            MIN(`data_evasione`) AS `data_evasione`
        FROM
            `or_righe_ordini`
        WHERE
            (`qta` - `qta_evasa`) > 0
        GROUP BY
            `idordine`
    ) AS `righe_da_evadere`
        ON `righe`.`idordine` = `righe_da_evadere`.`idordine`
    INNER JOIN `or_statiordine`
        ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN `or_statiordine_lang`
        ON (
            `or_statiordine`.`id` = `or_statiordine_lang`.`id_record`
            AND `or_statiordine_lang`.|lang|
        )
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \', \') AS info,
            co_righe_documenti.original_document_id AS idordine
        FROM
            co_documenti
            INNER JOIN co_righe_documenti
                ON co_documenti.id = co_righe_documenti.iddocumento
        WHERE
            original_document_type = \'Modules\\\\Ordini\\\\Ordine\'
        GROUP BY
            idordine, original_document_id
    ) AS fattura
        ON fattura.idordine = or_ordini.id
    LEFT JOIN (
        SELECT
            COUNT(`em_emails`.`id`) AS emails,
            `em_emails`.`id_record`
        FROM
            `em_emails`
            INNER JOIN `zz_operations`
                ON `zz_operations`.`id_email` = `em_emails`.`id`
        WHERE
            `id_module` IN (
                SELECT `id`
                FROM `zz_modules`
                WHERE `name` = \'Ordini fornitore\'
            )
            AND `zz_operations`.`op` = \'send-email\'
        GROUP BY
            `em_emails`.`id_record`
    ) AS email
        ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1 = 1
    |segment(`or_ordini`.`id_segment`)|
    AND `dir` = \'uscita\'
    |date_period(`or_ordini`.`data`)|
HAVING
    2 = 2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';

-- Ddt in uscita
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche`
        ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `dt_tipiddt`
        ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet`
        ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT JOIN `dt_causalet_lang`
        ON (
            `dt_causalet_lang`.`id_record` = `dt_causalet`.`id`
            AND `dt_causalet_lang`.|lang|
        )
    LEFT JOIN `dt_spedizione`
        ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT JOIN `dt_spedizione_lang`
        ON (
            `dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id`
            AND `dt_spedizione_lang`.|lang|
        )
    LEFT JOIN `an_anagrafiche` AS `vettori`
        ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT JOIN `an_sedi` AS `sedi`
        ON `dt_ddt`.`idsede_partenza` = `sedi`.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione`
        ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN (
        SELECT
            `idddt`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM
            `dt_righe_ddt`
        GROUP BY
            `idddt`
    ) AS righe
        ON `dt_ddt`.`id` = `righe`.`idddt`
    LEFT JOIN `dt_statiddt`
        ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
    LEFT JOIN `dt_statiddt_lang`
        ON (
            `dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id`
            AND `dt_statiddt_lang`.|lang|
        )
    LEFT JOIN (
        SELECT
            GROUP_CONCAT(DISTINCT \'Fattura \', `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`,
            `co_righe_documenti`.`original_document_id` AS `idddt`
        FROM
            `co_documenti`
            INNER JOIN `co_righe_documenti`
                ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
        WHERE
            `original_document_type` = \'Modules\\\\DDT\\\\DDT\'
        GROUP BY
            `original_document_id`
    ) AS `fattura`
        ON `fattura`.`idddt` = `dt_ddt`.`id`
    LEFT JOIN (
        SELECT
            COUNT(`em_emails`.`id`) AS emails,
            `em_emails`.`id_record`
        FROM
            `em_emails`
            INNER JOIN `zz_operations`
                ON `zz_operations`.`id_email` = `em_emails`.`id`
        WHERE
            `id_module` IN (
                SELECT `id`
                FROM `zz_modules`
                WHERE `name` = \'Ddt in uscita\'
            )
            AND `zz_operations`.`op` = \'send-email\'
        GROUP BY
            `id_record`
    ) AS `email`
        ON `email`.`id_record` = `dt_ddt`.`id`
WHERE
    1 = 1
    |segment(`dt_ddt`.`id_segment`)|
    AND `dir` = \'entrata\'
    |date_period(`data`)|
HAVING
    2 = 2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.`created_at` DESC' WHERE `zz_modules`.`name` = 'Ddt in uscita';

-- Miglioramento query per modulo Utenti e permessi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `zz_groups`
    LEFT JOIN (
        SELECT
            `zz_users`.`idgruppo`,
            COUNT(`zz_users`.`id`) AS num
        FROM
            `zz_users`
        GROUP BY
            `idgruppo`
    ) AS utenti ON `zz_groups`.`id` = `utenti`.`idgruppo`
    LEFT JOIN (
        SELECT
            `zz_users`.`idgruppo`,
            COUNT(`zz_users`.`id`) AS num
        FROM
            `zz_users`
        WHERE
            `zz_users`.`enabled` = 1
        GROUP BY
            `idgruppo`
    ) AS utenti_abilitati ON `zz_groups`.`id` = `utenti_abilitati`.`idgruppo`
    LEFT JOIN (
        SELECT
            `zz_users`.`idgruppo`,
            COUNT(`zz_tokens`.`id`) AS num
        FROM
            `zz_users`
            INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente`
        WHERE
            `zz_tokens`.`enabled` = 1
        GROUP BY
            `idgruppo`
    ) AS api_abilitate ON `zz_groups`.`id` = `api_abilitate`.`idgruppo`
    LEFT JOIN (
        SELECT
            `zz_modules_lang`.`title`,
            `zz_modules`.`id`
        FROM
            `zz_modules`
            LEFT JOIN `zz_modules_lang` ON (
                `zz_modules_lang`.`id_record` = `zz_modules`.`id`
                AND `zz_modules_lang`.|lang|
            )
    ) AS `module` ON `module`.`id` = `zz_groups`.`id_module_start`
WHERE
    1 = 1
HAVING
    2 = 2
ORDER BY
    `id`,
    `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi';