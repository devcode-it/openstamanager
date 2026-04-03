-- Allineamento vista Fatture di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.|lang|)
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`nome`, ' - ', `iban`) AS `descrizione` FROM `co_banche`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN (SELECT `iddocumento`, GROUP_CONCAT(DISTINCT `co_pianodeiconti3`.`descrizione` SEPARATOR ', ') AS `descrizione` FROM `co_righe_documenti` INNER JOIN `co_pianodeiconti3` ON `co_pianodeiconti3`.`id` = `co_righe_documenti`.`idconto` GROUP BY iddocumento) AS `conti` ON `conti`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`idanagrafica` AS `anagrafica`, `d`.`id_segment`, YEAR(`d`.`data`) AS `anno` FROM `co_documenti` AS `d`
    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `d`.`id_segment`, `numero_documento`, `d`.`idanagrafica`, YEAR(`d`.`data`)) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`idanagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment` AND `d`.`anno` = YEAR(`co_documenti`.`data`))
WHERE
    1=1
AND
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)| |date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    `co_documenti`.`id`, `d`.`conteggio`
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = 'Fatture di acquisto';

UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Nome, Indirizzo, Città, CAP, Provincia, Referente", "query": "SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia, GROUP_CONCAT(an_referenti.nome SEPARATOR \', \') AS Referente FROM an_sedi LEFT OUTER JOIN an_referenti ON idsede = an_sedi.id WHERE 1=1 AND an_sedi.idanagrafica=|id_parent| AND deleted_at IS NULL GROUP BY an_sedi.id HAVING 2=2 ORDER BY an_sedi.id DESC"} ]}' WHERE `name` = 'Sedi aggiuntive';