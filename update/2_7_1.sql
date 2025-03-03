-- Aggiornamento nome colonna Fornitore in Fornitore predefinito
UPDATE `zz_views` SET `name` = 'Fornitore predefinito' WHERE `zz_views`.`name` = 'Fornitore' AND  `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'); 
UPDATE `zz_views_lang` SET `zz_views_lang`.`title` = 'Fornitore predefinito' WHERE `zz_views_lang`.`id_record` = (SELECT `id` FROM `zz_views` WHERE `name` = 'Fornitore predefinito' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')) AND `zz_views_lang`.`id_lang` = 1; 

UPDATE `co_scadenziario` INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id` SET `co_scadenziario`.`id_pagamento` = `co_documenti`.`idpagamento`, `co_scadenziario`.`id_banca_azienda` = `co_documenti`.`id_banca_azienda`, `co_scadenziario`.`id_banca_controparte` = `co_documenti`.`id_banca_controparte` WHERE `co_scadenziario`.`id_pagamento` = 0;

-- Permetto valore NULL per campo options in zz_prints
ALTER TABLE `zz_prints` CHANGE `options` `options` TEXT NULL; 

-- Migrazione file settings di stampa su campo options a database
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Bilancio';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Libro giornale';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Inventario cespiti';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Inventario magazzino';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Prima nota';
UPDATE `zz_prints` SET `options` = "{\"orientation\": \"L\"}" WHERE `zz_prints`.`name` = 'Scadenzario';

-- Allineamento vista Contratti, ore rimanenti esclude ora le attività con tipo da non conteggiare
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
    LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br>') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN(SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id LEFT JOIN in_tipiintervento ON in_interventi_tecnici.idtipointervento=in_tipiintervento.id WHERE non_conteggiare=0 GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
    LEFT JOIN `co_categorie_contratti` ON `co_contratti`.`id_categoria` = `co_categorie_contratti`.`id`
    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)
    LEFT JOIN `co_categorie_contratti` AS sottocategorie ON `co_contratti`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `co_categorie_contratti_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
HAVING 
    2=2" WHERE `name` = 'Contratti';

-- Fix per vista duplicata relazione
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_relazioni_lang`.`title`' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'color_title_Relazione';
