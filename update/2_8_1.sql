-- Allineamento vista Giacenze sedi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.|lang|)
    LEFT JOIN (SELECT SUM(`qta` - `qta_evasa`) AS qta_impegnata, `idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` WHERE `idstatoordine` IN(SELECT `id` FROM `or_statiordine` WHERE `is_bloccato` = 0) GROUP BY `idarticolo`) ordini ON `ordini`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT `idarticolo`, `idsede`, SUM(`qta`) AS `qta` FROM `mg_movimenti` WHERE `idsede` = |giacenze_sedi_idsede| GROUP BY `idarticolo`, `idsede`) movimenti ON `mg_articoli`.`id` = `movimenti`.`idarticolo`
    LEFT JOIN `zz_categorie` AS categoria ON `categoria`.`id`= `mg_articoli`.`id_categoria`
    LEFT JOIN `zz_categorie_lang` AS categoria_lang ON (`categoria_lang`.`id_record` = `categoria`.`id` AND `categoria_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS sottocategoria ON `sottocategoria`.`id`=`mg_articoli`.`id_sottocategoria`
    LEFT JOIN `zz_categorie_lang` AS sottocategoria_lang ON (`sottocategoria_lang`.`id_record` = `sottocategoria`.`id` AND `sottocategoria_lang`.|lang|)
	LEFT JOIN (SELECT `co_iva`.`percentuale` AS perc, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id`=`zz_settings`.`valore`)AS iva ON `iva`.`nome`= 'Iva predefinita' 
WHERE 
    1=1 AND `mg_articoli`.`deleted_at` IS NULL 
HAVING
    2=2 AND `qta` > 0
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `zz_modules`.`name` = 'Giacenze sedi';