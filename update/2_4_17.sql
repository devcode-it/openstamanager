-- Aggiunta data competenza nel filtro temporale per le fatture di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN (
        SELECT `iddocumento`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
WHERE 1=1 AND `dir` = \'uscita\' |segment(`co_documenti`.`id_segment`)||date_period(custom, \'|period_start|\' <= `co_documenti`.`data` AND \'|period_end|\' >= `co_documenti`.`data`, \'|period_start|\' <= `co_documenti`.`data_competenza` AND \'|period_end|\' >= `co_documenti`.`data_competenza`  )|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero` = \'\', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC' WHERE `name` = 'Fatture di acquisto';


-- Allineo per i movimenti relativi alle fatture di vendita, la data del movimento con la data del documento
UPDATE `co_movimenti` SET `co_movimenti`.`data` = `co_movimenti`.`data_documento` WHERE `iddocumento` IN (SELECT `co_documenti`.`id` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE `co_tipidocumento`.`dir` = 'entrata' );

-- Allineo per le fatture di vendita, la data_competenza con data emissione del documento
UPDATE `co_documenti` SET `co_documenti`.`data_competenza` = `co_documenti`.`data`  WHERE `co_documenti`.`idtipodocumento` IN (SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir` = 'entrata');

-- Elimino data_documento per co_documenti
ALTER TABLE `co_movimenti` DROP `data_documento`;

-- Allineamento idarticolo nelle tabelle delle righe
ALTER TABLE `co_righe_documenti` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_documenti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_preventivi` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_preventivi` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_contratti` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_contratti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `dt_righe_ddt` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `dt_righe_ddt` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `or_righe_ordini` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `or_righe_ordini` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `or_righe_ordini` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `or_righe_ordini` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_promemoria` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_promemoria` SET `idarticolo` = NULL WHERE `idarticolo` = 0;
