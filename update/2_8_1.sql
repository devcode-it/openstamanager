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

UPDATE `zz_prints` SET `available_options` = '{"pricing":"Visualizzare i prezzi", "hide-total": "Nascondere i totali delle righe", "show-only-total": "Visualizzare solo i totali del documento", "hide-header": "Nascondere intestazione", "hide-footer": "Nascondere footer", "last-page-footer": "Visualizzare footer solo su ultima pagina", "hide-item-number": "Nascondere i codici degli articoli"}' WHERE `zz_prints`.`id_module` = (SELECT id FROM `zz_modules` WHERE `name` = 'Preventivi');
ALTER TABLE `zz_prints` DROP `default`;

INSERT INTO `co_pianodeiconti3` (`numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES ('000040', 'Iva transitoria', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione` = 'Conti transitori'), '', '100.00'); 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Conto per Iva transitoria', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione` = 'Iva transitoria'), "query=SELECT `id`, CONCAT_WS(' - ', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC", '1', 'Piano dei conti', NULL, '0');

SELECT @id_record := `id` FROM `zz_settings` WHERE `nome` = 'Conto per Iva transitoria';
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', @id_record, 'Conto per Iva transitoria', ''), 
('2', @id_record, 'Conto per Iva transitoria', '');
