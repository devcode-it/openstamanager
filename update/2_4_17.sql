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

-- Fix link del plugin "Ddt del cliente"
UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Numero, Data, Descrizione, Qtà", "query": "SELECT dt_ddt.id, IF(dt_tipiddt.dir = \'entrata\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di vendita\'), (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di acquisto\')) AS _link_module_, dt_ddt.id AS _link_record_, IF(dt_ddt.numero_esterno = \'\', dt_ddt.numero, dt_ddt.numero_esterno) AS Numero, DATE_FORMAT(dt_ddt.data, \'%d/%m/%Y\') AS Data, dt_righe_ddt.descrizione AS `Descrizione`, REPLACE(REPLACE(REPLACE(FORMAT(dt_righe_ddt.qta, 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\') AS `Qtà` FROM dt_ddt LEFT JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt JOIN dt_tipiddt ON dt_ddt.idtipoddt = dt_tipiddt.id WHERE dt_ddt.idanagrafica=|id_parent| GROUP BY dt_ddt.id HAVING 2=2 ORDER BY dt_ddt.id DESC"} ]}' WHERE `zz_plugins`.`name` = 'Ddt del cliente';
