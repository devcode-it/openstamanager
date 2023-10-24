UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'CONCAT(IF(fattura.info != "", fattura.info,""), IF(ddt.info != "", ddt.info,""))' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'Rif. fattura';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`name` = 'Riferimenti' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`query` = 'CONCAT(IF(fattura.info != "", fattura.info,""), IF(ddt.info != "", ddt.info,""))';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Riferimenti', 'IF(fattura.info != "", fattura.info,"")', 15, 1, 0, 0 ,0);

UPDATE `zz_api_resources` SET `resource` = 'checklist-cleanup' WHERE `zz_api_resources`.`resource` = 'checklists-cleanup'; 

UPDATE `zz_widgets` SET `query` = 'SELECT\n    COUNT(dati.id) AS dato\nFROM\n    (\n    SELECT\n        co_contratti.id,\n        (\n            (\n            SELECT\n                SUM(co_righe_contratti.qta)\n            FROM\n                co_righe_contratti\n            WHERE\n                co_righe_contratti.um = \'ore\' AND co_righe_contratti.idcontratto = co_contratti.id\n        ) - IFNULL(\n            (\n            SELECT\n                SUM(in_interventi_tecnici.ore)\n            FROM\n                in_interventi_tecnici\n            INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id\n            WHERE\n                in_interventi.id_contratto = co_contratti.id AND in_interventi.idstatointervento IN(\n                SELECT\n                    in_statiintervento.idstatointervento\n                FROM\n                    in_statiintervento\n                WHERE\n                    in_statiintervento.is_completato = 1\n            )\n        ),\n        0\n        )\n        ) AS ore_rimanenti,\n        DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti,\n        data_conclusione,\n        ore_preavviso_rinnovo,\n        giorni_preavviso_rinnovo,\n        (\n        SELECT\n            ragione_sociale\n        FROM\n            an_anagrafiche\n        WHERE\n            idanagrafica = co_contratti.idanagrafica\n    ) AS ragione_sociale\nFROM\n    co_contratti\n        INNER JOIN co_staticontratti ON co_staticontratti.id = co_contratti.idstato\nWHERE\n    rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND co_contratti.id NOT IN(\n    SELECT\n        idcontratto_prev\n    FROM\n        co_contratti contratti\n) AND co_staticontratti.descrizione NOT IN (\"Concluso\", \"Rifiutato\", \"Bozza\")\nHAVING\n    (\n        ore_rimanenti <= ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) <= ABS(giorni_preavviso_rinnovo))\n    ORDER BY\n        giorni_rimanenti ASC,\n        ore_rimanenti ASC\n    ) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

UPDATE `zz_settings` SET `tipo` = 'query=SELECT codice AS id, CONCAT(codice, \' - \', descrizione)as descrizione FROM fe_regime_fiscale;' WHERE `zz_settings`.`nome` = "Regime Fiscale";

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini cliente'), 'Data scadenza', 'data_scadenza_predefinita', 3, 1, 0, 1, 1, 1);

UPDATE `zz_widgets` SET `text` = 'Listini disattivati' WHERE `zz_widgets`.`name` = 'Listini scaduti'; 

INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `help`) VALUES (NULL, 'Preventivi da fatturare', 'stats', '1', 'controller_top', NULL, 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE idstato IN (SELECT id FROM co_statipreventivi WHERE is_fatturabile=1) AND default_revision=1', '#44aae4', 'fa fa-file', '', './modules/preventivi/widgets/preventivi.fatturare.dashboard.php', 'popup', '', 'Preventivi da fatturare', 0, NULL);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), '_bg_', 'IF(threshold_qta!=0, IF(mg_articoli.qta>=threshold_qta, \'#CCFFCC\', \'#FFCCEB\'), \'\')', '14', '0', '0', '0', '0', '', '', '0', '0', '0');

-- Aggiunto titolo righe preventivi
ALTER TABLE `co_righe_preventivi` ADD `is_titolo` BOOLEAN NOT NULL AFTER `confermato`; 

ALTER TABLE `co_documenti` CHANGE `numero_esterno` `numero_esterno` VARCHAR(100) NOT NULL; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Tipo di sconto predefinito', '%', 'list[%,€]', '1', 'Generali', '1', NULL); 

ALTER TABLE `co_tipidocumento` ADD `id_segment` INT NOT NULL AFTER `predefined`; 
UPDATE `co_tipidocumento` SET `id_segment`= (SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_segments`.`predefined` = 1) WHERE `co_tipidocumento`.`dir` = 'entrata';
UPDATE `co_tipidocumento` SET `id_segment`= (SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_segments`.`predefined` = 1) WHERE `co_tipidocumento`.`dir` = 'uscita';
UPDATE `co_tipidocumento` SET `id_segment`=(SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_segments`.`name` = 'Autofatture' AND `zz_modules`.`name` = 'Fatture di vendita') WHERE `co_tipidocumento`.`dir` = 'entrata' AND `co_tipidocumento`.`descrizione` LIKE "%autofattura%";
UPDATE `co_tipidocumento` SET `id_segment`=(SELECT `zz_segments`.`id` FROM `zz_segments` INNER JOIN `zz_modules` ON `zz_segments`.`id_module` = `zz_modules`.`id` WHERE `zz_segments`.`name` = 'Autofatture' AND `zz_modules`.`name` = 'Fatture di acquisto') WHERE `co_tipidocumento`.`dir` = 'uscita' AND `co_tipidocumento`.`descrizione` LIKE "%autofattura%";

-- Fix query Fatture di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`nome`, ' - ', `iban`) AS `descrizione` FROM `co_banche`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN (SELECT `iddocumento`, GROUP_CONCAT(`co_pianodeiconti3`.`descrizione`) AS `descrizione` FROM `co_righe_documenti` INNER JOIN `co_pianodeiconti3` ON `co_pianodeiconti3`.`id` = `co_righe_documenti`.`idconto` GROUP BY iddocumento) AS `conti` ON `conti`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`idanagrafica` AS `anagrafica`, `d`.`id_segment` FROM `co_documenti` AS `d`
    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `d`.`id_segment`, `numero_documento`, `d`.`idanagrafica`) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`idanagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment`)
WHERE 
    1=1 
AND 
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)| |date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    `co_documenti`.`id`, `d`.`conteggio`
HAVING 
    2=2
ORDER BY 
    `co_documenti`.`data` DESC,
    CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = 'Fatture di acquisto';

-- Fix widget Acquisti
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\nWHERE co_tipidocumento.dir=\'uscita\' |segment(`co_documenti`.`id_segment`)| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Acquisti';

-- Fix widget Debiti verso fornitori
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((SELECT ABS(SUM(da_pagare-pagato))), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'uscita\' AND co_documenti.idstatodocumento!=1 |segment(`co_documenti`.`id_segment`)| AND 1=1' WHERE `zz_widgets`.`name` = 'Debiti verso fornitori';

-- Fix widget Fatturato
UPDATE `zz_widgets` SET `query` = 'SELECT\n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(\n (co_righe_documenti.subtotale - co_righe_documenti.sconto) * IF(co_tipidocumento.reversed, -1, 1)\n )\n ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM co_righe_documenti\n INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id\n INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id\n INNER JOIN co_statidocumento ON co_documenti.idstatodocumento = co_statidocumento.id\nWHERE co_statidocumento.descrizione!=\'Bozza\' AND co_tipidocumento.dir=\'entrata\' |segment(`co_documenti`.`id_segment`)| AND data >= \'|period_start|\' AND data <= \'|period_end|\' AND 1=1' WHERE `zz_widgets`.`name` = 'Fatturato';

-- Fix widget Crediti da clienti
UPDATE `zz_widgets` SET `query` = 'SELECT \n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(da_pagare-pagato)), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'entrata\' AND co_documenti.idstatodocumento!=1 |segment(`co_documenti`.`id_segment`)| AND 1=1' WHERE `zz_widgets`.`name` = 'Crediti da clienti';

-- Correzione collegamento scadenze al log email
UPDATE `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` SET `zz_operations`.`id_record` = `em_emails`.`id_record` WHERE `zz_operations`.`id_record` IS NULL AND `zz_modules`.`name` = "Scadenzario" AND `zz_operations`.`op` IN ("send-email", "send-sollecito");