-- Impostazione per selezionare i gruppi abilitati alla modifica CC e CCN in fase di invio mail
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Gruppi abilitati alla modifica CC e CCN', (SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `zz_groups`), 'query=SELECT `zz_groups`.`id`, `zz_groups_lang`.`title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) ORDER BY `zz_groups`.`id`', 1, 'Mail', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Gruppi abilitati alla modifica CC e CCN', 'Seleziona i gruppi di utenti che possono modificare i campi CC e CCN durante l''invio di email.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Groups enabled to edit CC and BCC', 'Select the user groups that can edit CC and BCC fields when sending emails.');

-- Allineamento vista Articoli
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`id`
    LEFT JOIN `co_iva` ON `mg_articoli`.`id_iva_vendita` = `co_iva`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_impegnata`, `or_righe_ordini`.`id_articolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`id_ordine` = `or_ordini`.`id` INNER JOIN `or_tipi_ordine` ON `or_ordini`.`id_tipo_ordine` = `or_tipi_ordine`.`id` INNER JOIN `or_stati_ordine` ON `or_ordini`.`id_stato` = `or_stati_ordine`.`id` WHERE `or_tipi_ordine`.`dir` = 'entrata' AND `or_righe_ordini`.`confermato` = 1 AND `or_stati_ordine`.`impegnato` = 1 GROUP BY `id_articolo`) a ON `a`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_ordinata`, `or_righe_ordini`.`id_articolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`id_ordine` = `or_ordini`.`id` INNER JOIN `or_tipi_ordine` ON `or_ordini`.`id_tipo_ordine` = `or_tipi_ordine`.`id` INNER JOIN `or_stati_ordine` ON `or_ordini`.`id_stato` = `or_stati_ordine`.`id` WHERE `or_tipi_ordine`.`dir` = 'uscita' AND `or_righe_ordini`.`confermato` = 1 AND `or_stati_ordine`.`impegnato` = 1 GROUP BY `id_articolo`) `ordini_fornitore` ON `ordini_fornitore`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN `zz_categorie` ON `mg_articoli`.`id_categoria` = `zz_categorie`.`id`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT `co_iva`.`percentuale` AS `perc`, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id` = `zz_settings`.`valore`) AS iva ON `iva`.`nome` = 'Iva predefinita'
    LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT CASE WHEN MIN(`differenza`) < 0 THEN -1 WHEN MAX(`threshold_qta`) > 0 THEN 1 ELSE 0 END AS `stato_giacenza`, `id_articolo` FROM (SELECT SUM(`mg_movimenti`.`qta`) - COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `differenza`, COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `threshold_qta`, `mg_movimenti`.`id_articolo` FROM `mg_movimenti` LEFT JOIN `mg_scorte_sedi` ON (`mg_scorte_sedi`.`id_sede` = `mg_movimenti`.`id_sede` AND `mg_scorte_sedi`.`id_articolo` = `mg_movimenti`.`id_articolo`) GROUP BY `mg_movimenti`.`id_articolo`, `mg_movimenti`.`id_sede`) AS `subquery` GROUP BY `id_articolo`) AS `giacenze` ON `giacenze`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT mg_articoli.id AS id_articolo, GROUP_CONCAT( mg_articoli_barcode.barcode ORDER BY mg_articoli_barcode.barcode SEPARATOR '<br />' ) AS lista FROM mg_articoli LEFT JOIN mg_articoli_barcode ON mg_articoli_barcode.id_articolo = mg_articoli.id GROUP BY mg_articoli.id) AS barcode ON barcode.id_articolo = mg_articoli.id
    LEFT JOIN `zz_marche` as marca ON `marca`.`id` = `mg_articoli`.`id_marca`
    LEFT JOIN `zz_marche` as modello ON `modello`.`id` = `mg_articoli`.`id_modello`
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
GROUP BY
    `mg_articoli`.`id`
HAVING
    2=2
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `name` = "Articoli";