-- Aggiunta fattura.info query preventivi
UPDATE
    `zz_modules`
SET
    `options` = 'SELECT |select|\r\nFROM `co_preventivi`\r\n    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\n    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`\r\n    LEFT JOIN (\r\n        SELECT `idpreventivo`,\r\n            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\r\n            SUM(`subtotale` - `sconto` + `iva`) AS `totale`\r\n        FROM `co_righe_preventivi`\r\n        GROUP BY `idpreventivo`\r\n    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`\r\n
LEFT JOIN (SELECT co_righe_documenti.idpreventivo, CONCAT(\'Fatt. \', co_documenti.numero_esterno,\' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento) AS fattura ON fattura.idpreventivo = co_preventivi.id
WHERE 1=1 |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = \'0000-00-00\')| AND default_revision = 1\r\nHAVING 2=2\r\nORDER BY `co_preventivi`.`id` DESC '
WHERE
    `zz_modules`.`name` = 'Preventivi';


-- Aggiunta colonna Rif. fattura per preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Rif. fattura', 'fattura.info', 9, 1, 0, 0, 1);