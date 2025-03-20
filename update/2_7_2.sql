ALTER TABLE `co_pianodeiconti3` CHANGE `descrizione` `descrizione` VARCHAR(255) NOT NULL; 

-- Allineamento vista Contratti, introduzione campo Residuo contratto
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
    LEFT JOIN (SELECT SUM(`prezzo_unitario`) AS somma, `idintervento`, `id_contratto` FROM `in_righe_interventi` LEFT JOIN `in_interventi` ON `in_righe_interventi`.`idintervento` = `in_interventi`.`id` GROUP BY `id_contratto`) AS spesacontratto ON `spesacontratto`.`id_contratto` = `co_contratti`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS descrizione, `my_impianti_contratti`.`idcontratto` FROM `my_impianti` INNER JOIN `my_impianti_contratti` ON `my_impianti`.`id` = `my_impianti_contratti`.`idimpianto` GROUP BY `my_impianti_contratti`.`idcontratto`) AS impianti ON `impianti`.`idcontratto` = `co_contratti`.`id`
    LEFT JOIN (SELECT `um`, SUM(`qta`) AS somma, `idcontratto` FROM `co_righe_contratti` GROUP BY `um`, `idcontratto`) AS `orecontratti` ON `orecontratti`.`um` = 'ore' AND `orecontratti`.`idcontratto` = `co_contratti`.`id`
    LEFT JOIN(SELECT `in_interventi`.`id_contratto`, SUM(ore) AS `sommatecnici` FROM `in_interventi_tecnici` INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_tipiintervento` ON `in_interventi_tecnici`.`idtipointervento`=`in_tipiintervento`.`id` WHERE `non_conteggiare`=0 GROUP BY `in_interventi`.`id_contratto`) AS tecnici ON `tecnici`.`id_contratto` = `co_contratti`.`id`
    LEFT JOIN `co_categorie_contratti` ON `co_contratti`.`id_categoria` = `co_categorie_contratti`.`id`
    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)
    LEFT JOIN `co_categorie_contratti` AS sottocategorie ON `co_contratti`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `co_categorie_contratti_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
HAVING 
    2=2" WHERE `name` = 'Contratti';

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Residuo contratto', "IF((righe.totale_imponibile - spesacontratto.somma) != 0, righe.totale_imponibile - spesacontratto.somma, '')", '20', '1', '0', '0', '0', '', '', '1', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Residuo contratto' AND `id_module` = @id_module), 'Residuo contratto'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Residuo contratto' AND `id_module` = @id_module), 'Contract Residual');


-- Aggiunta colonna Note interne in Preventivi
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Preventivi';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`co_preventivi`.`informazioniaggiuntive`", '18', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Preventivi';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Aggiunta colonna Note interne in Contratti
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`co_contratti`.`informazioniaggiuntive`", '18', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Allineamento vista Articoli
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS qta_impegnata, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'entrata' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS qta_ordinata, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'uscita' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1
    GROUP BY `idarticolo`) ordini_fornitore ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN `mg_categorie` ON `mg_articoli`.`id_categoria` = `mg_categorie`.`id`
    LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id` = `mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.|lang|)
    LEFT JOIN `mg_categorie` AS sottocategorie ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `mg_categorie_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT `co_iva`.`percentuale` AS perc, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id`=`zz_settings`.`valore`)AS iva ON `iva`.`nome`= 'Iva predefinita' 
    LEFT JOIN mg_scorte_sedi ON mg_scorte_sedi.id_articolo = mg_articoli.id
    LEFT JOIN (SELECT CASE WHEN MIN(differenza) < 0 THEN -1 WHEN MAX(threshold_qta) > 0 THEN 1 ELSE 0 END AS stato_giacenza, idarticolo FROM (SELECT SUM(mg_movimenti.qta) - COALESCE(mg_scorte_sedi.threshold_qta, 0) AS differenza, COALESCE(mg_scorte_sedi.threshold_qta, 0) as threshold_qta, mg_movimenti.idarticolo FROM mg_movimenti LEFT JOIN mg_scorte_sedi ON mg_scorte_sedi.id_sede = mg_movimenti.idsede AND mg_scorte_sedi.id_articolo = mg_movimenti.idarticolo GROUP BY mg_movimenti.idarticolo, mg_movimenti.idsede) AS subquery GROUP BY idarticolo) AS giacenze ON giacenze.idarticolo = mg_articoli.id
WHERE
    1=1 AND(`mg_articoli`.`deleted_at`) IS NULL
GROUP BY
    `mg_articoli`.`id`
HAVING
    2=2
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `name` = 'Articoli';

-- Aggiunta colonna _bg_ in Articoli
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Articoli';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, '_bg_', "IF(giacenze.stato_giacenza!=0, IF(giacenze.stato_giacenza>0, '#CCFFCC', '#ec5353'), '')", '16', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Articoli';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = '_bg_' AND `id_module` = @id_module), '_bg_'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = '_bg_' AND `id_module` = @id_module), '_bg_');

UPDATE `zz_views` SET `query` = '`mg_articoli`.`qta`' WHERE `zz_views`.`name` =  'Q.tà' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');
UPDATE `zz_views` SET `query` = '`mg_articoli`.`qta`-IFNULL(a.qta_impegnata, 0)' WHERE `zz_views`.`name` =  'Q.tà disponibile' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');
 