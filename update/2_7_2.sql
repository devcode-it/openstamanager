ALTER TABLE `co_pianodeiconti3` CHANGE `descrizione` `descrizione` VARCHAR(255) NOT NULL; 

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
 