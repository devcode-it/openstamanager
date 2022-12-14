-- Fix query vista Contratti

UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN ( SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN ( SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br>') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN( SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN( SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
WHERE
    1=1
    |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = '0000-00-00')|
HAVING 
    2=2" WHERE `name` = 'Contratti';