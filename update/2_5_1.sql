-- Valorizzato deleted_at per articolo DELETED
UPDATE `mg_articoli` SET `deleted_at` = CURDATE() WHERE `mg_articoli`.`codice` = 'DELETED';

-- Aggiunta colonna title nella tabella an_nazioni_lang
ALTER TABLE `an_nazioni_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `an_nazioni_lang` SET `title` = `name`;
ALTER TABLE `an_nazioni_lang`
    DROP `name`; 

-- Aggiunta colonna title nella tabella an_provenienze_lang
ALTER TABLE `an_provenienze_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `an_provenienze_lang` SET `title` = `name`;
UPDATE `an_provenienze_lang` INNER JOIN (SELECT * FROM `an_provenienze_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `an_provenienze_lang`.`id_record` SET `an_provenienze_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella an_regioni_lang
ALTER TABLE `an_regioni`
    DROP `iso2`,
    DROP `name`;
ALTER TABLE `an_regioni_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `an_regioni_lang` SET `title` = `name`;
UPDATE `an_regioni_lang` INNER JOIN (SELECT * FROM `an_regioni_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `an_regioni_lang`.`id_record` SET `an_regioni_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella an_relazioni_lang
ALTER TABLE `an_relazioni_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `an_relazioni_lang` SET `title` = `name`;
UPDATE `an_relazioni_lang` INNER JOIN (SELECT * FROM `an_relazioni_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `an_relazioni_lang`.`id_record` SET `an_relazioni_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella an_settori_lang
ALTER TABLE `an_settori_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `an_settori_lang` SET `title` = `name`;
UPDATE `an_settori_lang` INNER JOIN (SELECT * FROM `an_settori_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `an_settori_lang`.`id_record` SET `an_settori_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella an_tipianagrafiche_lang
ALTER TABLE `an_tipianagrafiche_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `an_tipianagrafiche_lang` SET `title` = `name`;
UPDATE `an_tipianagrafiche_lang` INNER JOIN (SELECT * FROM `an_tipianagrafiche_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `an_tipianagrafiche_lang`.`id_record` SET `an_tipianagrafiche_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella co_iva_lang
ALTER TABLE `co_iva_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `co_iva_lang` SET `title` = `name`;
UPDATE `co_iva_lang` INNER JOIN (SELECT * FROM `co_iva_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `co_iva_lang`.`id_record` SET `co_iva_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella co_pagamenti_lang
ALTER TABLE `co_pagamenti_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `co_pagamenti_lang` SET `title` = `name`;
UPDATE `co_pagamenti_lang` INNER JOIN (SELECT * FROM `co_pagamenti_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `co_pagamenti_lang`.`id_record` SET `co_pagamenti_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella co_staticontratti_lang
ALTER TABLE `co_staticontratti_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `co_staticontratti_lang` SET `title` = `name`;
UPDATE `co_staticontratti_lang` INNER JOIN (SELECT * FROM `co_staticontratti_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `co_staticontratti_lang`.`id_record` SET `co_staticontratti_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella co_statidocumento_lang
ALTER TABLE `co_statidocumento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `co_statidocumento_lang` SET `title` = `name`;
UPDATE `co_statidocumento_lang` INNER JOIN (SELECT * FROM `co_statidocumento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `co_statidocumento_lang`.`id_record` SET `co_statidocumento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella co_statipreventivi_lang
ALTER TABLE `co_statipreventivi_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `co_statipreventivi_lang` SET `title` = `name`;
UPDATE `co_statipreventivi_lang` INNER JOIN (SELECT * FROM `co_statipreventivi_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `co_statipreventivi_lang`.`id_record` SET `co_statipreventivi_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella co_tipidocumento_lang
ALTER TABLE `co_tipidocumento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `co_tipidocumento_lang` SET `title` = `name`;
UPDATE `co_tipidocumento_lang` INNER JOIN (SELECT * FROM `co_tipidocumento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `co_tipidocumento_lang`.`id_record` SET `co_tipidocumento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella do_categorie_lang
ALTER TABLE `do_categorie_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `do_categorie_lang` SET `title` = `name`;
UPDATE `do_categorie_lang` INNER JOIN (SELECT * FROM `do_categorie_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `do_categorie_lang`.`id_record` SET `do_categorie_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella dt_aspettobeni_lang
ALTER TABLE `dt_aspettobeni_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `dt_aspettobeni_lang` SET `title` = `name`;
UPDATE `dt_aspettobeni_lang` INNER JOIN (SELECT * FROM `dt_aspettobeni_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `dt_aspettobeni_lang`.`id_record` SET `dt_aspettobeni_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella dt_causalet_lang
ALTER TABLE `dt_causalet_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `dt_causalet_lang` SET `title` = `name`;
UPDATE `dt_causalet_lang` INNER JOIN (SELECT * FROM `dt_causalet_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `dt_causalet_lang`.`id_record` SET `dt_causalet_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella dt_porto_lang
ALTER TABLE `dt_porto_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `dt_porto_lang` SET `title` = `name`;
UPDATE `dt_porto_lang` INNER JOIN (SELECT * FROM `dt_porto_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `dt_porto_lang`.`id_record` SET `dt_porto_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella dt_spedizione_lang
ALTER TABLE `dt_spedizione_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `dt_spedizione_lang` SET `title` = `name`;
UPDATE `dt_spedizione_lang` INNER JOIN (SELECT * FROM `dt_spedizione_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `dt_spedizione_lang`.`id_record` SET `dt_spedizione_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella dt_statiddt_lang
ALTER TABLE `dt_statiddt_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `dt_statiddt_lang` SET `title` = `name`;
UPDATE `dt_statiddt_lang` INNER JOIN (SELECT * FROM `dt_statiddt_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `dt_statiddt_lang`.`id_record` SET `dt_statiddt_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella dt_tipiddt_lang
ALTER TABLE `dt_tipiddt_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `dt_tipiddt_lang` SET `title` = `name`;
UPDATE `dt_tipiddt_lang` INNER JOIN (SELECT * FROM `dt_tipiddt_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `dt_tipiddt_lang`.`id_record` SET `dt_tipiddt_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella em_lists_lang
ALTER TABLE `em_lists_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `em_lists_lang` SET `title` = `name`;
UPDATE `em_lists_lang` INNER JOIN (SELECT * FROM `em_lists_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `em_lists_lang`.`id_record` SET `em_lists_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella em_templates_lang
ALTER TABLE `em_templates_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `em_templates_lang` SET `title` = `name`;
UPDATE `em_templates_lang` INNER JOIN (SELECT * FROM `em_templates_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `em_templates_lang`.`id_record` SET `em_templates_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella fe_modalita_pagamento_lang
ALTER TABLE `fe_modalita_pagamento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `fe_modalita_pagamento_lang` SET `title` = `name`;
UPDATE `fe_modalita_pagamento_lang` INNER JOIN (SELECT * FROM `fe_modalita_pagamento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `fe_modalita_pagamento_lang`.`id_record` SET `fe_modalita_pagamento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella fe_natura_lang
ALTER TABLE `fe_natura_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `fe_natura_lang` SET `title` = `name`;
UPDATE `fe_natura_lang` INNER JOIN (SELECT * FROM `fe_natura_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `fe_natura_lang`.`id_record` SET `fe_natura_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella fe_regime_fiscale_lang
ALTER TABLE `fe_regime_fiscale_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `fe_regime_fiscale_lang` SET `title` = `name`;
UPDATE `fe_regime_fiscale_lang` INNER JOIN (SELECT * FROM `fe_regime_fiscale_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `fe_regime_fiscale_lang`.`id_record` SET `fe_regime_fiscale_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella fe_stati_documento_lang
ALTER TABLE `fe_stati_documento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `fe_stati_documento_lang` SET `title` = `name`;
UPDATE `fe_stati_documento_lang` INNER JOIN (SELECT * FROM `fe_stati_documento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `fe_stati_documento_lang`.`id_record` SET `fe_stati_documento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella fe_tipi_documento_lang
ALTER TABLE `fe_tipi_documento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `fe_tipi_documento_lang` SET `title` = `name`;
UPDATE `fe_tipi_documento_lang` INNER JOIN (SELECT * FROM `fe_tipi_documento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `fe_tipi_documento_lang`.`id_record` SET `fe_tipi_documento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella in_fasceorarie_lang
ALTER TABLE `in_fasceorarie_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `in_fasceorarie_lang` SET `title` = `name`;
UPDATE `in_fasceorarie_lang` INNER JOIN (SELECT * FROM `in_fasceorarie_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `in_fasceorarie_lang`.`id_record` SET `in_fasceorarie_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella in_statiintervento_lang
ALTER TABLE `in_statiintervento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `in_statiintervento_lang` SET `title` = `name`;
UPDATE `in_statiintervento_lang` INNER JOIN (SELECT * FROM `in_statiintervento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `in_statiintervento_lang`.`id_record` SET `in_statiintervento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella in_tipiintervento_lang
ALTER TABLE `in_tipiintervento_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `in_tipiintervento_lang` SET `title` = `name`;
UPDATE `in_tipiintervento_lang` INNER JOIN (SELECT * FROM `in_tipiintervento_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `in_tipiintervento_lang`.`id_record` SET `in_tipiintervento_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella mg_articoli_lang
ALTER TABLE `mg_articoli_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `mg_articoli_lang` SET `title` = `name`;
UPDATE `mg_articoli_lang` INNER JOIN (SELECT * FROM `mg_articoli_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `mg_articoli_lang`.`id_record` SET `mg_articoli_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella mg_categorie_lang
ALTER TABLE `mg_categorie_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`;
ALTER TABLE `mg_categorie_lang` ADD `note` VARCHAR(255) NOT NULL AFTER `title`;
UPDATE `mg_categorie_lang` SET `title` = `name`;
UPDATE `mg_categorie_lang` INNER JOIN (SELECT * FROM `mg_categorie_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `mg_categorie_lang`.`id_record` SET `mg_categorie_lang`.`name` = `tmp`.`name`;
UPDATE `mg_categorie_lang` INNER JOIN (SELECT * FROM `mg_categorie`) AS `tmp` ON `tmp`.`id` = `mg_categorie_lang`.`id_record` SET `mg_categorie_lang`.`note` = `tmp`.`nota`;
ALTER TABLE `mg_categorie`
    DROP `nota`; 

-- Aggiunta colonna title nella tabella mg_causali_movimenti_lang
ALTER TABLE `mg_causali_movimenti_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `mg_causali_movimenti_lang` SET `title` = `name`;
UPDATE `mg_causali_movimenti_lang` INNER JOIN (SELECT * FROM `mg_causali_movimenti_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `mg_causali_movimenti_lang`.`id_record` SET `mg_causali_movimenti_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella mg_combinazioni_lang
ALTER TABLE `mg_combinazioni_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `mg_combinazioni_lang` SET `title` = `name`;
UPDATE `mg_combinazioni_lang` INNER JOIN (SELECT * FROM `mg_combinazioni_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `mg_combinazioni_lang`.`id_record` SET `mg_combinazioni_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella my_impianti_categorie_lang
ALTER TABLE `my_impianti_categorie_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `my_impianti_categorie_lang` SET `title` = `name`;
UPDATE `my_impianti_categorie_lang` INNER JOIN (SELECT * FROM `my_impianti_categorie_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `my_impianti_categorie_lang`.`id_record` SET `my_impianti_categorie_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella or_statiordine_lang
ALTER TABLE `or_statiordine_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `or_statiordine_lang` SET `title` = `name`;
UPDATE `or_statiordine_lang` INNER JOIN (SELECT * FROM `or_statiordine_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `or_statiordine_lang`.`id_record` SET `or_statiordine_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella or_tipiordine_lang
ALTER TABLE `or_tipiordine_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `or_tipiordine_lang` SET `title` = `name`;
UPDATE `or_tipiordine_lang` INNER JOIN (SELECT * FROM `or_tipiordine_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `or_tipiordine_lang`.`id_record` SET `or_tipiordine_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_cache_lang
ALTER TABLE `zz_cache_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_cache_lang` SET `title` = `name`;
UPDATE `zz_cache_lang` INNER JOIN (SELECT * FROM `zz_cache_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_cache_lang`.`id_record` SET `zz_cache_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_groups_lang
ALTER TABLE `zz_groups_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_groups_lang` SET `title` = `name`;
UPDATE `zz_groups_lang` INNER JOIN (SELECT * FROM `zz_groups_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_groups_lang`.`id_record` SET `zz_groups_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_group_module_lang
ALTER TABLE `zz_group_module_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_group_module_lang` SET `title` = `name`;
UPDATE `zz_group_module_lang` INNER JOIN (SELECT * FROM `zz_group_module_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_group_module_lang`.`id_record` SET `zz_group_module_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_hooks_lang
ALTER TABLE `zz_hooks_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_hooks_lang` SET `title` = `name`;
UPDATE `zz_hooks_lang` INNER JOIN (SELECT * FROM `zz_hooks_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_hooks_lang`.`id_record` SET `zz_hooks_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_imports_lang
ALTER TABLE `zz_imports_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_imports_lang` SET `title` = `name`;
UPDATE `zz_imports_lang` INNER JOIN (SELECT * FROM `zz_imports_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_imports_lang`.`id_record` SET `zz_imports_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_segments_lang
ALTER TABLE `zz_segments_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_segments_lang` SET `title` = `name`;
UPDATE `zz_segments_lang` INNER JOIN (SELECT * FROM `zz_segments_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_segments_lang`.`id_record` SET `zz_segments_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_tasks_lang
ALTER TABLE `zz_tasks_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_tasks_lang` SET `title` = `name`;
UPDATE `zz_tasks_lang` INNER JOIN (SELECT * FROM `zz_tasks_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_tasks_lang`.`id_record` SET `zz_tasks_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_views_lang
ALTER TABLE `zz_views_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_views_lang` SET `title` = `name`;
UPDATE `zz_views_lang` INNER JOIN (SELECT * FROM `zz_views_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_views_lang`.`id_record` SET `zz_views_lang`.`name` = `tmp`.`name`;

-- Aggiunta colonna title nella tabella zz_widgets_lang
ALTER TABLE `zz_widgets_lang` ADD `title` VARCHAR(255) NOT NULL AFTER `name`; 
UPDATE `zz_widgets_lang` SET `title` = `name`;
UPDATE `zz_widgets_lang` INNER JOIN (SELECT * FROM `zz_widgets_lang` WHERE `id_lang` = 1) AS `tmp` ON `tmp`.`id_record` = `zz_widgets_lang`.`id_record` SET `zz_widgets_lang`.`name` = `tmp`.`name`;

-- Aggiunta user-agent nei log
ALTER TABLE `zz_logs` ADD `user_agent` VARCHAR(255) NULL DEFAULT NULL AFTER `ip`;

-- Allineamento vista Scadenzario
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenziario`
    LEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN `co_pagamenti` as b ON `b`.`id` = `co_scadenziario`.`id_pagamento`
    LEFT JOIN `co_pagamenti_lang` as b_lang ON (`b_lang`.`id_record` = `b`.`id` AND `b_lang`.|lang|)
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN (SELECT COUNT(id_email) as emails, zz_operations.id_record FROM zz_operations WHERE id_module IN(SELECT `id_record` FROM `zz_modules_lang` WHERE `name` = 'Scadenzario' AND |lang|) AND `zz_operations`.`op` = 'send-email' GROUP BY zz_operations.id_record) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`
WHERE 
    1=1 AND (`co_statidocumento`.`id` IS NULL OR `co_statidocumento`.`id` IN(SELECT id_record FROM co_statidocumento_lang WHERE name IN ('Emessa', 'Parzialmente pagato', 'Pagato'))) 
GROUP BY 
    `co_scadenziario`.`id`
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `zz_modules`.`name` = 'Scadenzario';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module` SET `zz_views`.`query` = 'IF(`co_scadenziario`.`id_pagamento` < 1, `co_pagamenti_lang`.`name`, `b_lang`.`name`)' WHERE `zz_views`.`name` = 'Tipo di pagamento' AND `zz_modules`.`name` = 'Scadenzario';

-- Allineamento vista Listini
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `mg_prezzi_articoli`
    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `mg_prezzi_articoli`.`id_anagrafica`
    INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `mg_prezzi_articoli`.`id_articolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `mg_categorie` AS `categoria` ON `mg_articoli`.`id_categoria` = `categoria`.`id`
    LEFT JOIN `mg_categorie_lang` AS `categorialang` ON (`categorialang`.`id_record` = `categoria`.`id` AND `categorialang`.|lang|)
    LEFT JOIN `mg_categorie` AS `sottocategoria` ON `mg_articoli`.`id_sottocategoria` = `sottocategoria`.`id`
    LEFT JOIN `mg_categorie_lang` AS `sottocategorialang` ON (`sottocategorialang`.`id_record` = `sottocategoria`.`id` AND `sottocategorialang`.|lang|)
    LEFT JOIN `zz_modules` ON `zz_modules`.`name`= 'Articoli'
    LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.|lang|)
    LEFT JOIN (SELECT `codice_fornitore` AS codice, `id_articolo`, `id_fornitore`, `barcode_fornitore` AS barcode, `deleted_at` FROM `mg_fornitore_articolo`) AS fornitore ON `mg_prezzi_articoli`.`id_articolo`= `fornitore`.`id_articolo` AND `mg_prezzi_articoli`.`id_anagrafica`=`fornitore`.`id_fornitore` AND `fornitore`.`deleted_at` IS NULL
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL AND `an_anagrafiche`.`deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `an_anagrafiche`.`ragione_sociale`" WHERE `zz_modules`.`name` = 'Listini';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module` SET `zz_views`.`query` = 'CONCAT(`mg_articoli`.`codice`, " - ", `mg_articoli_lang`.`title`)' WHERE `zz_views`.`name` = 'Articolo' AND `zz_modules`.`name` = 'Listini';

-- Allineamento zz_views Categorie in Listini
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module` SET `zz_views`.`query` = '`categorialang`.`title`' WHERE `zz_views`.`name` = 'Categoria' AND `zz_modules`.`name` = 'Listini';

-- Allineamento zz_views Sottocategorie in Listini
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_views`.`id_module` SET `zz_views`.`query` = '`sottocategorialang`.`title`' WHERE `zz_views`.`name` = 'Sottocategoria' AND `zz_modules`.`name` = 'Listini';

-- Sposto impostazione sotto sezione Aggiornamenti
UPDATE `zz_settings` SET `sezione` = 'Aggiornamenti' WHERE `zz_settings`.`nome` = 'Abilita canale pre-release per aggiornamenti'; 

-- Allineamento vista Adattatori di archiviazione
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `zz_storage_adapters` 
WHERE 
    1=1 AND `deleted_at` IS NULL
HAVING 
    2=2" WHERE `zz_modules`.`name` = 'Adattatori di archiviazione';

UPDATE `zz_modules` SET `name` = 'Adattatori di archiviazione' WHERE `zz_modules`.`directory` = 'adattatori_archiviazione';
UPDATE `zz_views` SET `name` = 'id' WHERE `zz_views`.`query` = 'id' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Adattatori di archiviazione');
UPDATE `zz_views` SET `name` = 'Nome' WHERE `zz_views`.`query` = 'name' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Adattatori di archiviazione');
UPDATE `zz_views` SET `name` = 'icon_Predefinito' WHERE `zz_views`.`query` = 'if(is_default=1, "fa fa-check", "")' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Adattatori di archiviazione');

-- Rinomino plugin Sedi in Anagrafica
UPDATE `zz_plugins_lang` SET `title` = 'Sedi aggiuntive' WHERE `zz_plugins_lang`.`id_lang` = (SELECT `id` FROM `zz_langs` WHERE `predefined` = 1) AND `name` = 'Sedi'; 
UPDATE `zz_plugins` SET `name` = 'Sedi aggiuntive' WHERE `zz_plugins`.`name` = 'Sedi';

-- Aggiunte chiavi esterne con zz_langs
ALTER TABLE `an_nazioni_lang` ADD CONSTRAINT `an_nazioni_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `an_provenienze_lang` ADD CONSTRAINT `an_provenienze_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `an_regioni_lang` ADD CONSTRAINT `an_regioni_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `an_relazioni_lang` ADD CONSTRAINT `an_relazioni_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `an_settori_lang` ADD CONSTRAINT `an_settori_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `an_tipianagrafiche_lang` ADD CONSTRAINT `an_tipianagrafiche_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_iva_lang` ADD CONSTRAINT `co_iva_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_pagamenti_lang` ADD CONSTRAINT `co_pagamenti_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_staticontratti_lang` ADD CONSTRAINT `co_staticontratti_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_statidocumento_lang` ADD CONSTRAINT `co_statidocumento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_statipreventivi_lang` ADD CONSTRAINT `co_statipreventivi_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_tipidocumento_lang` ADD CONSTRAINT `co_tipidocumento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `co_tipi_scadenze_lang` ADD CONSTRAINT `co_tipi_scadenze_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `do_categorie_lang` ADD CONSTRAINT `do_categorie_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `dt_aspettobeni_lang` ADD CONSTRAINT `dt_aspettobeni_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `dt_causalet_lang` ADD CONSTRAINT `dt_causalet_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `dt_porto_lang` ADD CONSTRAINT `dt_porto_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `dt_spedizione_lang` ADD CONSTRAINT `dt_spedizione_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `dt_statiddt_lang` ADD CONSTRAINT `dt_statiddt_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `dt_tipiddt_lang` ADD CONSTRAINT `dt_tipiddt_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `em_lists_lang` ADD CONSTRAINT `em_lists_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `em_templates_lang` ADD CONSTRAINT `em_templates_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `fe_modalita_pagamento_lang` ADD CONSTRAINT `fe_modalita_pagamento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `fe_natura_lang` ADD CONSTRAINT `fe_natura_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `fe_regime_fiscale_lang` ADD CONSTRAINT `fe_regime_fiscale_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `fe_stati_documento_lang` ADD CONSTRAINT `fe_stati_documento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `fe_tipi_documento_lang` ADD CONSTRAINT `fe_tipi_documento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `in_fasceorarie_lang` ADD CONSTRAINT `in_fasceorarie_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `in_statiintervento_lang` ADD CONSTRAINT `in_statiintervento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `in_tipiintervento_lang` ADD CONSTRAINT `in_tipiintervento_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `mg_articoli_lang` ADD CONSTRAINT `mg_articoli_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `mg_attributi_lang` ADD CONSTRAINT `mg_attributi_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `mg_categorie_lang` ADD CONSTRAINT `mg_categorie_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `my_impianti_categorie_lang` ADD CONSTRAINT `my_impianti_categorie_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `mg_causali_movimenti_lang` ADD CONSTRAINT `mg_causali_movimenti_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `mg_combinazioni_lang` ADD CONSTRAINT `mg_combinazioni_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `or_statiordine_lang` ADD CONSTRAINT `or_statiordine_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `or_tipiordine_lang` ADD CONSTRAINT `or_tipiordine_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_currencies_lang` ADD CONSTRAINT `zz_currencies_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_widgets_lang` ADD CONSTRAINT `zz_widgets_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_plugins_lang` ADD CONSTRAINT `zz_plugins_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_modules_lang` ADD CONSTRAINT `zz_modules_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_segments_lang` ADD CONSTRAINT `zz_segments_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_views_lang` ADD CONSTRAINT `zz_views_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_settings_lang` ADD CONSTRAINT `zz_settings_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_tasks_lang` ADD CONSTRAINT `zz_tasks_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_prints_lang` ADD CONSTRAINT `zz_prints_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_imports_lang` ADD CONSTRAINT `zz_imports_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_hooks_lang` ADD CONSTRAINT `zz_hooks_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_groups_lang` ADD CONSTRAINT `zz_groups_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_group_module_lang` ADD CONSTRAINT `zz_group_module_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 
ALTER TABLE `zz_cache_lang` ADD CONSTRAINT `zz_cache_lang_ibfk_2` FOREIGN KEY (`id_lang`) REFERENCES `zz_langs`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

ALTER TABLE `zz_settings_lang` CHANGE `help` `help` VARCHAR(500) NULL; 
-- Aggiunta impostazione per numero da visualizzare negli ordini
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Visualizza numero ordine cliente', '1', 'boolean', '1', 'Ordini', NULL);
INSERT INTO `zz_settings_lang` (`id_record`, `id_lang`, `title`, `help`) VALUES ((SELECT `id` FROM `zz_settings` WHERE `nome` = 'Visualizza numero ordine cliente'), (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"), 'Visualizza numero ordine cliente', 'Se abilitata, utilizza nei documenti il numero d\'ordine del cliente al posto del numero interno dell\'ordine');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie documenti'), 'Gruppi abilitati', '(SELECT GROUP_CONCAT(\' \', `nome`) FROM `zz_groups` WHERE `id` IN (SELECT `id_gruppo` FROM `do_permessi` WHERE `id_categoria` = `do_categorie`.`id`))', 5);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Gruppi abilitati'), 'Gruppi abilitati');

-- Aggiunta tabella my_impianti_marche
CREATE TABLE IF NOT EXISTS `my_impianti_marche` (
    `id` INT NOT NULL AUTO_INCREMENT , 
    `nota` TEXT DEFAULT NULL,
    `parent` int DEFAULT NULL,
    PRIMARY KEY (`id`)
); 

ALTER TABLE `my_impianti` ADD `id_marca` INT NULL AFTER `idanagrafica`;
ALTER TABLE `my_impianti` ADD `id_modello` INT NULL AFTER `id_marca`;

-- Modulo Marche impianto
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Marche impianti', 'impianti_marche', 'SELECT |select| FROM `my_impianti_marche` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.5.1', '2.5.1', '2', (SELECT `id` FROM `zz_modules` `m` WHERE `name`='Impianti'), '1', '1', '0', '0');
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES ('1', (SELECT MAX(id) FROM `zz_modules`), 'Marche impianti');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Marche impianti'), 'id', 'id', 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Marche impianti'), 'Nome','name', 1);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`)-1 FROM `zz_views` ), 'id'),
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Nome');

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_currencies`.`id`, `zz_currencies_lang`.`title` AS descrizione FROM `zz_currencies` LEFT JOIN `zz_currencies_lang` ON (`zz_currencies_lang`.`id_record` = `zz_currencies`.`id` AND `zz_currencies_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua"))' WHERE `nome` = 'Valuta';

-- Allineamento vista Viste
UPDATE `zz_modules` SET `options` = 'SELECT \n |select| \nFROM \n `zz_modules` \n LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)\nWHERE \n 1=1 AND `enabled` = 1 HAVING 2=2 ORDER BY `title` ASC' WHERE `zz_modules`.`name` = 'Viste'; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'GROUP_CONCAT(\' \',`an_tipianagrafiche_lang`.`title`)' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Tipo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_articoli_lang`.`title`' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Aspetto beni
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_aspettobeni_lang`.`title`' WHERE `zz_modules`.`name` = 'Aspetto beni' AND `zz_views`.`name` = 'Descrizione';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento_lang`.`title`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento_lang`.`title`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Tipo';

-- Fix viste moduli
UPDATE `zz_modules` SET `options2` = '' WHERE `options2` IS NULL;

-- Allineamento vista Causali trasporto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`do_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Categorie documenti' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Coda di invio
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `em_emails`
    LEFT JOIN `em_templates` ON `em_templates`.`id` = `em_emails`.`id_template`
    INNER JOIN `zz_users` ON `zz_users`.`id` = `em_emails`.`created_by`
    LEFT JOIN (SELECT `id_email`, GROUP_CONCAT(`address` SEPARATOR '<br>') as `nomi` FROM `em_email_receiver` GROUP BY `id_email`)AS `destinatari` ON `destinatari`.`id_email` = `em_emails`.`id`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `em_templates`.`id_module`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.|lang|)
WHERE 
    1=1 
AND 
    (`em_emails`.`created_at` BETWEEN '|period_start|' AND '|period_end|' OR `em_emails`.`sent_at` IS NULL)
HAVING 
    2=2
ORDER BY 
    `em_emails`.`created_at` DESC" WHERE `zz_modules`.`name` = 'Stato email';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_modules_lang`.`title`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Modulo';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_staticontratti_lang`.`title`' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'icon_title_Stato';

-- Allineamento vista Ddt di acquisto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet_lang`.`title`' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Causale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione_lang`.`title`' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Tipo spedizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_statiddt_lang`.`title`' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet_lang`.`title`' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Causale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione_lang`.`title`' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Tipo spedizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_statiddt_lang`.`title`' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'icon_title_Stato';

-- Allineamento vista Fatture di acquisto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Tipo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Pagamento';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Tipo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Pagamento';

-- Allineamento vista Gestione documentale
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`do_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Gestione documentale' AND `zz_views`.`name` = 'Categoria';

-- Allineamento vista Gestione task
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_tasks_lang`.`title`' WHERE `zz_modules`.`name` = 'Gestione task' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_articoli_lang`.`title`' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista IVA
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_iva_lang`.`title`' WHERE `zz_modules`.`name` = 'IVA' AND `zz_views`.`name` = 'descrizione';

-- Allineamento vista Listini cliente
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM 
    `mg_listini`
    LEFT JOIN (SELECT `mg_listini_articoli`.`id_listino`, COUNT(`id_listino`) AS num FROM `mg_listini_articoli` GROUP BY `id_listino`) AS articoli ON `mg_listini`.`id`=`articoli`.`id_listino`
    LEFT JOIN (SELECT `an_anagrafiche`.`id_listino`, COUNT(`id_listino`) AS num FROM `an_anagrafiche` GROUP BY `id_listino`) AS anagrafiche ON `mg_listini`.`id`=`anagrafiche`.`id_listino`
    LEFT JOIN (SELECT `zz_users`.`id`, `zz_users`.`username` FROM `zz_users` INNER JOIN (SELECT `zz_operations`.`id_utente`, `zz_operations`.`id_record` FROM `zz_operations` LEFT JOIN `zz_modules` ON `zz_modules`.`name` = 'Listini cliente' LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) ORDER BY `id_utente` DESC LIMIT 0, 1) AS `id`) AS `utente` ON `utente`.`id` = `mg_listini`.`id`
WHERE 
    1=1 
HAVING 
    2=2" WHERE `zz_modules`.`name` = 'Listini cliente';

-- Allineamento vista Movimenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_movimenti`
	INNER JOIN `mg_articoli` ON `mg_articoli`.id = `mg_movimenti`.`idarticolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.|lang|)
	LEFT JOIN `an_sedi` ON `mg_movimenti`.`idsede` = `an_sedi`.`id`
    LEFT JOIN `zz_modules` ON `zz_modules`.`name` = 'Articoli'
	LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.|lang|)
	LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `co_documenti`.`id`, `ragione_sociale` AS nomi FROM `co_documenti` LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` GROUP BY `idanagrafica`, `co_documenti`.`id`) AS fattura ON `fattura`.`id`= `mg_movimenti`.`reference_id`
	LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `dt_ddt`.`id`, `ragione_sociale` AS nomi FROM `dt_ddt` LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` GROUP BY `idanagrafica`, `dt_ddt`.`id`) AS ddt ON `ddt`.`id`= `mg_movimenti`.`reference_id`
	LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `in_interventi`.`id`, `ragione_sociale` AS nomi FROM `in_interventi` LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` GROUP BY `idanagrafica`, `in_interventi`.`id`) AS intervento ON `intervento`.`id`= `mg_movimenti`.`reference_id`
    LEFT JOIN (SELECT CONCAT('tab_', `zz_plugins`.`id`) AS link FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins_lang`.`id_record` = `zz_plugins`.`id` AND `zz_plugins_lang`.|lang|) INNER JOIN `zz_modules` ON `zz_plugins`.`idmodule_to` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) WHERE `zz_modules`.`name` = 'Articoli' AND `zz_plugins`.`name` = 'Movimenti') AS page ON `mg_movimenti`.`id` != ''
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
GROUP BY 
    `mg_movimenti`.`id`
HAVING
    2=2
ORDER BY
    `mg_movimenti`.`data` DESC,
    `mg_movimenti`.`created_at` DESC" WHERE `zz_modules`.`name` = 'Movimenti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`mg_articoli_lang`.`title` != "", CONCAT(`mg_articoli`.`codice`, " - ", `mg_articoli_lang`.`title`), `mg_articoli`.`codice`)' WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = 'Articolo';

-- Allineamento vista Nazioni
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_nazioni_lang`.`title`' WHERE `zz_modules`.`name` = 'Eventi' AND `zz_views`.`name` = 'Nazione';

-- Allineamento vista Newsletter
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates_lang`.`title`' WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Template';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`or_statiordine_lang`.`title`' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'icon_title_Stato';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`or_statiordine_lang`.`title`' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'icon_title_Stato';

-- Allineamento vista Pagamenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_pagamenti`
	LEFT JOIN (SELECT `fe_modalita_pagamento`.`codice`, CONCAT(`fe_modalita_pagamento`.`codice`, ' - ', `fe_modalita_pagamento_lang`.`title`) AS tipo FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento`.`codice` = `fe_modalita_pagamento_lang`.`id_record` AND `fe_modalita_pagamento_lang`.|lang|)) AS pagamenti ON `pagamenti`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
WHERE
    1=1
GROUP BY
    `co_pagamenti_lang`.`title`
HAVING
    2=2" WHERE `name` = 'Pagamenti';


UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`title`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'COUNT(`co_pagamenti_lang`.`title`)' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Rate';

-- Allineamento vista Porto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_porto_lang`.`title`' WHERE `zz_modules`.`name` = 'Porto' AND `zz_views`.`name` = 'Descrizione';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statipreventivi_lang`.`title`' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'icon_title_Stato';

-- Allineamento vista Provenienze clienti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_provenienze_lang`.`title`' WHERE `zz_modules`.`name` = 'Provenienze' AND `zz_views`.`name` = 'descrizione';

-- Allineamento vista Relazioni
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_relazioni_lang`.`title`' WHERE `zz_modules`.`name` = 'Relazioni' AND `zz_views`.`name` = 'descrizione';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_pagamenti_lang`.`title`' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Tipo di pagamento';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'descrizione';

-- Allineamento vista Segmenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `zz_segments`
    LEFT JOIN `zz_segments_lang` ON (`zz_segments_lang`.`id_record` = `zz_segments`.`id` AND `zz_segments_lang`.|lang|)
    INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_segments`.`id_module`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(`zz_groups_lang`.`title` ORDER BY `zz_groups_lang`.`title`  SEPARATOR ', ') AS `gruppi`, `zz_group_segment`.`id_segment` FROM `zz_group_segment` INNER JOIN `zz_groups` ON `zz_groups`.`id` = `zz_group_segment`.`id_gruppo` LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.|lang|) GROUP BY  `zz_group_segment`.`id_segment`) AS `t` ON `t`.`id_segment` = `zz_segments`.`id`
WHERE
    1=1
HAVING
    2=2
ORDER BY `zz_segments_lang`.`title`,
    `zz_segments`.`id_module`" WHERE `zz_modules`.`name` = 'Segmenti';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_segments_lang`.`title`' WHERE `zz_modules`.`name` = 'Segmenti' AND `zz_views`.`name` = 'Nome';

-- Allineamento vista Settori merceologici
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_settori_lang`.`title`' WHERE `zz_modules`.`name` = 'Settori' AND `zz_views`.`name` = 'descrizione';

-- Allineamento vista Stati degli ordini
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`or_statiordine_lang`.`title`' WHERE `zz_modules`.`name` = 'Stati degli ordini' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Stati dei contratti 
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_staticontratti_lang`.`title`' WHERE `zz_modules`.`name` = 'Stati dei contratti' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Stati dei preventivi
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statipreventivi_lang`.`title`' WHERE `zz_modules`.`name` = 'Stati dei preventivi' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Stati di attività
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento_lang`.`title`' WHERE `zz_modules`.`name` = 'Stati di intervento' AND `zz_views`.`name` = 'descrizione';

-- Allineamento vista Stati fatture
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_statidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Stati fatture' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Template email
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `em_templates`
    LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.|lang|)
    INNER JOIN `zz_modules` on `zz_modules`.`id` = `em_templates`.`id_module`
    LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)
WHERE
    1=1 AND `deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `zz_modules_lang`.`title`" WHERE `zz_modules`.`name` = 'Template email';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates_lang`.`title`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Nome';

-- Allineamento vista Tipi di anagrafiche
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_tipianagrafiche_lang`.`title`' WHERE `zz_modules`.`name` = 'Tipi di anagrafiche' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Tipi di attività
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento_lang`.`title`' WHERE `zz_modules`.`name` = 'Tipi di intervento' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Tipi di spedizione
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_spedizione_lang`.`title`' WHERE `zz_modules`.`name` = 'Tipi di spedizione' AND `zz_views`.`name` = 'Descrizione';

-- Allineamento vista Tipi documento
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipidocumento_lang`.`title`' WHERE `zz_modules`.`name` = 'Tipi documento' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_segments_lang`.`title`' WHERE `zz_modules`.`name` = 'Tipi documento' AND `zz_views`.`name` = 'Sezionale';

-- Allineamento vista Tipi scadenze
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipi_scadenze`.`name`' WHERE `zz_modules`.`name` = 'Tipi scadenze' AND `zz_views`.`name` = 'Nome';

UPDATE `zz_widgets` SET `query` = "SELECT\n    CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n    SELECT SUM(\n    (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`) * IF(`co_tipidocumento`.`reversed`, -1, 1)\n    )\n    ), 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\'), \'&euro;\') AS dato\nFROM \n    `co_righe_documenti`\n    INNER JOIN `co_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`\n    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\n    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\n    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)\nWHERE \n    `co_statidocumento_lang`.`title`!=\'Bozza\' AND `co_tipidocumento`.`dir`=\'entrata\' |segment(`co_documenti`.`id_segment`)| AND `data` >= \'|period_start|\' AND `data` <= \'|period_end|\' AND 1=1" WHERE `zz_widgets`.`name` = 'Fatturato';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_lists_lang`.`title`' WHERE `zz_modules`.`name` = 'Liste newsletter' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`fe_stati_documento_lang`.`title`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_FE';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_fasceorarie_lang`.`title`' WHERE `zz_modules`.`name` = 'Fasce orarie' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_attributi_lang`.`title`' WHERE `zz_modules`.`name` = 'Attributi Combinazioni' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Categorie articoli' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Categoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Sottocategoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`my_impianti_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Categorie impianti' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`my_impianti_categorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Categoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`sub_lang`.`title`' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Sottocategoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_causali_movimenti_lang`.`title`' WHERE `zz_modules`.`name` = 'Causali movimenti' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_combinazioni_lang`.`title`' WHERE `zz_modules`.`name` = 'Combinazioni' AND `zz_views`.`name` = 'Nome';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_plugins_lang`.`title`' WHERE `zz_modules`.`name` = 'Campi personalizzati' AND `zz_views`.`name` = 'Plugin';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_plugins_lang`.`title`' WHERE `zz_modules`.`name` = 'Checklists' AND `zz_views`.`name` = 'Plugin';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`categoria_lang`.`title`' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Categoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`sottocategoria_lang`.`title`' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Sottocategoria';


-- Allineamento impostazioni
UPDATE `zz_settings` SET `tipo` = 'query=SELECT `zz_prints`.`id`, `zz_prints_lang`.`title` AS descrizione FROM `zz_prints` LEFT JOIN `zz_prints_lang` ON (`zz_prints_lang`.`id_record` = `zz_prints`.`id` AND `zz_prints_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = "Interventi") AND `is_record` = 1' WHERE `zz_settings`.`nome` = 'Stampa per anteprima e firma';

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS id, `ragione_sociale` AS descrizione FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `an_tipianagrafiche`.`id` FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON(`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record`) WHERE `title` = \'Azienda\') AND `deleted_at` IS NULL' WHERE `zz_settings`.`nome` = 'Azienda predefinita'; 

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`co_tipi_scadenze_lang`.`title`' WHERE `zz_modules`.`name` = 'Tipi scadenze' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`dt_causalet_lang`.`title`' WHERE `zz_modules`.`name` = 'Causali' AND `zz_views`.`name` = 'Descrizione';

-- Fix plugin Ddt del cliente
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Numero, Data, Descrizione, Qtà\", \"query\": \"SELECT dt_ddt.id, IF(dt_tipiddt.dir = \'entrata\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di vendita\'), (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt di acquisto\')) AS _link_module_, dt_ddt.id AS _link_record_, IF(dt_ddt.numero_esterno = \'\', dt_ddt.numero, dt_ddt.numero_esterno) AS Numero, DATE_FORMAT(dt_ddt.data, \'%d/%m/%Y\') AS Data, dt_righe_ddt.descrizione AS `Descrizione`, REPLACE(REPLACE(REPLACE(FORMAT(dt_righe_ddt.qta, 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\') AS `Qtà` FROM dt_ddt LEFT JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt JOIN dt_tipiddt ON dt_ddt.idtipoddt = dt_tipiddt.id WHERE dt_ddt.idanagrafica=|id_parent| HAVING 2=2 ORDER BY dt_ddt.id DESC\"} ]}' WHERE `zz_plugins`.`name` = 'Ddt del cliente';

-- Plugin Assicurazione crediti
CREATE TABLE `an_assicurazione_crediti` ( 
    `id` INT NOT NULL AUTO_INCREMENT, 
    `id_anagrafica` INT NOT NULL, 
    `data_inizio` DATE NULL, 
    `data_fine` DATE NULL, 
    `fido_assicurato` DECIMAL(15,6) NOT NULL, 
    `totale` DECIMAL(15,6) NOT NULL,
    PRIMARY KEY (`id`)); 

INSERT INTO `zz_plugins` (`name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES ('Assicurazione crediti', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'tab', '', '1', '1', '0', '2.*', '', NULL, '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Fido assicurato, Data inizio, Data fine\", \"query\": \"SELECT id, DATE_FORMAT(data_inizio,\'%d/%m/%Y\') AS \'Data inizio\', DATE_FORMAT(data_fine,\'%d/%m/%Y\') AS \'Data fine\', ROUND(fido_assicurato, 2) AS \'Fido assicurato\', ROUND(totale, 2) AS Totale FROM an_assicurazione_crediti WHERE 1=1 AND id_anagrafica = |id_parent| HAVING 2=2 ORDER BY an_assicurazione_crediti.id DESC\"} ]}', 'assicurazione_crediti', '');

INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`) VALUES ((SELECT `id` FROM `zz_langs` WHERE `predefined` = 1), (SELECT `id` FROM `zz_plugins` WHERE `name` = 'Assicurazione crediti'), 'Assicurazione crediti');
INSERT INTO `zz_plugins_lang` (`id_lang`, `id_record`, `title`) VALUES (2, (SELECT `id` FROM `zz_plugins` WHERE `name` = 'Assicurazione crediti'), 'Credit insurance');