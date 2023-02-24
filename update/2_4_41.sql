-- Ottimizzazione query vista Scadenzario
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_banche`.`nome`' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Banca azienda';
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenziario`
    LEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_operations`.`op` = 'send-email' GROUP BY `zz_operations`.`id_record`) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`
WHERE 
    1=1 AND (`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN('Emessa','Parzialmente pagato','Pagato')) 
GROUP BY 
    id
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `name` = 'Scadenzario';

--Fix query Fatture di vendita
UPDATE `zz_modules` SET `options` = "SELECT
	|select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS descrizione FROM `co_banche` GROUP BY `co_banche`.`id`) AS banche ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_operations`.`op` = 'send-email' GROUP BY `zz_operations`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
	LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
	LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN( SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata' |date_period(`co_documenti`.`data`)| ) AND `numero_esterno` != '' GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento`, `data` HAVING COUNT(`numero_esterno`) > 1) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento` AND `dup`.`data` = `co_documenti`.`data`
WHERE
    1=1 AND `dir` = 'entrata' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Fatture di vendita';
