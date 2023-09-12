-- Aggiornamento colonna Riferimenti in Ordini cliente
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
	`or_ordini`
    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`
    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idordine` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\\\Ordini\\\\Ordine' GROUP BY `idordine`, `original_document_id`) AS `fattura` ON `fattura`.`idordine` = `or_ordini`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'DDT ', `dt_ddt`.`id` SEPARATOR ', ') AS `info`, `dt_righe_ddt`.`original_document_id` AS `idddt` FROM `dt_ddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id`=`dt_righe_ddt`.`idddt` WHERE `original_document_type`='Modules\\\\Ordini\\\\Ordine' GROUP BY `idddt`, `original_document_id`) AS `ddt` ON `ddt`.`idddt`=`or_ordini`.`id`
    LEFT JOIN (SELECT COUNT(id) as emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN(SELECT id FROM zz_modules WHERE name = 'Ordini cliente') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1 |segment(`or_ordini`.`id_segment`)| AND `dir` = 'entrata'  |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY 
	`data` DESC, 
    CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Ordini cliente';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'CONCAT(IF(fattura.info != "", fattura.info,""), IF(ddt.info != "", ddt.info,""))' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'Rif. fattura';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`name` = 'Riferimenti' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`query` = 'CONCAT(IF(fattura.info != "", fattura.info,""), IF(ddt.info != "", ddt.info,""))';

-- Aggiungo colonna riferimento in Ddt in entrata
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN(SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt` 
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\\\\DDT\\\\DDT' GROUP BY `idddt`, `original_document_id`) AS `fattura` ON `fattura`.`idddt` = `dt_ddt`.`id`
WHERE
    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = 'uscita' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.created_at DESC" WHERE `name` = 'Ddt di acquisto';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Riferimenti', 'IF(fattura.info != "", fattura.info,"")', 15, 1, 0, 0 ,0);

UPDATE `zz_api_resources` SET `resource` = 'checklist-cleanup' WHERE `zz_api_resources`.`resource` = 'checklists-cleanup'; 

UPDATE `zz_widgets` SET `query` = 'SELECT\n    COUNT(dati.id) AS dato\nFROM\n    (\n    SELECT\n        co_contratti.id,\n        (\n            (\n            SELECT\n                SUM(co_righe_contratti.qta)\n            FROM\n                co_righe_contratti\n            WHERE\n                co_righe_contratti.um = \'ore\' AND co_righe_contratti.idcontratto = co_contratti.id\n        ) - IFNULL(\n            (\n            SELECT\n                SUM(in_interventi_tecnici.ore)\n            FROM\n                in_interventi_tecnici\n            INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id\n            WHERE\n                in_interventi.id_contratto = co_contratti.id AND in_interventi.idstatointervento IN(\n                SELECT\n                    in_statiintervento.idstatointervento\n                FROM\n                    in_statiintervento\n                WHERE\n                    in_statiintervento.is_completato = 1\n            )\n        ),\n        0\n        )\n        ) AS ore_rimanenti,\n        DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti,\n        data_conclusione,\n        ore_preavviso_rinnovo,\n        giorni_preavviso_rinnovo,\n        (\n        SELECT\n            ragione_sociale\n        FROM\n            an_anagrafiche\n        WHERE\n            idanagrafica = co_contratti.idanagrafica\n    ) AS ragione_sociale\nFROM\n    co_contratti\n        INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato\nWHERE\n    rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND co_contratti.id NOT IN(\n    SELECT\n        idcontratto_prev\n    FROM\n        co_contratti contratti\n) AND co_staticontratti.descrizione != \"Concluso\"\nHAVING\n    (\n        ore_rimanenti <= ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) <= ABS(giorni_preavviso_rinnovo))\n    ORDER BY\n        giorni_rimanenti ASC,\n        ore_rimanenti ASC\n    ) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

UPDATE `zz_settings` SET `tipo` = 'query=SELECT codice AS id, CONCAT(codice, \' - \', descrizione)as descrizione FROM fe_regime_fiscale;' WHERE `zz_settings`.`nome` = "Regime Fiscale";