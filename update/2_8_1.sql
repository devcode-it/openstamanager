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

-- Pulizia dei permessi sui segmenti per gruppi che non hanno accesso al modulo (esclusi gli Amministratori)
DELETE `zz_group_segment` FROM `zz_group_segment`
INNER JOIN `zz_segments` ON `zz_group_segment`.`id_segment` = `zz_segments`.`id`
INNER JOIN `zz_groups` ON `zz_group_segment`.`id_gruppo` = `zz_groups`.`id`
LEFT JOIN `zz_permissions` ON `zz_groups`.`id` = `zz_permissions`.`idgruppo`
    AND `zz_segments`.`id_module` = `zz_permissions`.`idmodule`
WHERE `zz_groups`.`nome` != 'Amministratori'
    AND (`zz_permissions`.`id` IS NULL OR `zz_permissions`.`permessi` = '-');

-- Assicura che il gruppo Amministratori abbia accesso a tutti i segmenti
INSERT IGNORE INTO `zz_group_segment` (`id_gruppo`, `id_segment`)
SELECT
    `admin_group`.`id` AS `id_gruppo`,
    `zz_segments`.`id` AS `id_segment`
FROM `zz_segments`
CROSS JOIN (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori') AS `admin_group`
LEFT JOIN `zz_group_segment` AS `existing` ON `existing`.`id_gruppo` = `admin_group`.`id`
    AND `existing`.`id_segment` = `zz_segments`.`id`
WHERE `existing`.`id_gruppo` IS NULL;

-- Assicura che i gruppi con permessi sui moduli abbiano accesso al segmento predefinito
INSERT IGNORE INTO `zz_group_segment` (`id_gruppo`, `id_segment`)
SELECT
    `zz_permissions`.`idgruppo` AS `id_gruppo`,
    `default_segments`.`id` AS `id_segment`
FROM `zz_permissions`
INNER JOIN (
    SELECT `id`, `id_module`
    FROM `zz_segments`
    WHERE `predefined` = 1
) AS `default_segments` ON `zz_permissions`.`idmodule` = `default_segments`.`id_module`
LEFT JOIN `zz_group_segment` AS `existing_access` ON `zz_permissions`.`idgruppo` = `existing_access`.`id_gruppo`
    AND `existing_access`.`id_segment` IN (
        SELECT `id` FROM `zz_segments` WHERE `id_module` = `zz_permissions`.`idmodule`
    )
WHERE `zz_permissions`.`permessi` IN ('r', 'rw')
    AND `existing_access`.`id_gruppo` IS NULL;

 -- Pulizia dei permessi sulle viste per gruppi che non hanno accesso al modulo (esclusi gli Amministratori)
DELETE `zz_group_view` FROM `zz_group_view`
INNER JOIN `zz_views` ON `zz_group_view`.`id_vista` = `zz_views`.`id`
INNER JOIN `zz_groups` ON `zz_group_view`.`id_gruppo` = `zz_groups`.`id`
LEFT JOIN `zz_permissions` ON `zz_groups`.`id` = `zz_permissions`.`idgruppo`
    AND `zz_views`.`id_module` = `zz_permissions`.`idmodule`
WHERE `zz_groups`.`nome` != 'Amministratori'
    AND (`zz_permissions`.`id` IS NULL OR `zz_permissions`.`permessi` = '-');

-- Assicura che il gruppo Amministratori abbia accesso a tutte le viste
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT
    `admin_group`.`id` AS `id_gruppo`,
    `zz_views`.`id` AS `id_vista`
FROM `zz_views`
CROSS JOIN (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori') AS `admin_group`
LEFT JOIN `zz_group_view` AS `existing` ON `existing`.`id_gruppo` = `admin_group`.`id`
    AND `existing`.`id_vista` = `zz_views`.`id`
WHERE `existing`.`id_gruppo` IS NULL;

-- Assicura che i gruppi con permessi sui moduli abbiano accesso alle viste di base
INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT
    `zz_permissions`.`idgruppo` AS `id_gruppo`,
    `default_views`.`id` AS `id_vista`
FROM `zz_permissions`
INNER JOIN (
    SELECT `id`, `id_module`
    FROM `zz_views`
    WHERE `default` = 1
) AS `default_views` ON `zz_permissions`.`idmodule` = `default_views`.`id_module`
LEFT JOIN `zz_group_view` AS `existing_access` ON `zz_permissions`.`idgruppo` = `existing_access`.`id_gruppo`
    AND `existing_access`.`id_vista` IN (
        SELECT `id` FROM `zz_views` WHERE `id_module` = `zz_permissions`.`idmodule`
    )
WHERE `zz_permissions`.`permessi` IN ('r', 'rw')
    AND `existing_access`.`id_gruppo` IS NULL;