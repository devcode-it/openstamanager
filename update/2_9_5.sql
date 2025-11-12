-- Aggiunta colonna name alla tabella an_nazioni dopo iso2
ALTER TABLE `an_nazioni` ADD COLUMN `name` VARCHAR(255) NULL AFTER `iso2`;

-- Popolamento della colonna name con i valori di an_nazioni_lang per id_lang = 1
UPDATE `an_nazioni`
SET `name` = (
    SELECT `an_nazioni_lang`.`title`
    FROM `an_nazioni_lang`
    WHERE `an_nazioni_lang`.`id_record` = `an_nazioni`.`id`
    AND `an_nazioni_lang`.`id_lang` = 1
    LIMIT 1
);

UPDATE `zz_modules` SET `options` = "SELECT |select|
FROM (
    SELECT
        zz_tasks_logs.id,
        name,
        zz_tasks_logs.level,
        zz_tasks_logs.message,
        IF(LEVEL = 'info', '#dff0d8', IF(LEVEL = 'error', '#f2dede', '#fcf8e3')) AS '_bg_',
        IF(CHAR_LENGTH(CONTEXT) > 200,
           CONCAT(SUBSTRING(CONTEXT, 1, 200), '<a title=\"', REPLACE(CONTEXT, '\">', '[...]'), '</a>'),
           CONTEXT) AS 'Contesto',
        CONTEXT AS 'contesto_esteso',
        zz_tasks_logs.created_at AS 'Data inizio',
        zz_tasks_logs.updated_at AS 'Data fine',
        CONCAT(TIMESTAMPDIFF(SECOND, zz_tasks_logs.created_at, zz_tasks_logs.updated_at), ' secondi') AS 'Eseguito in'
    FROM `zz_tasks_logs`
    INNER JOIN `zz_tasks` ON `zz_tasks`.`id`=`zz_tasks_logs`.`id_task`

    UNION ALL

    SELECT
        zz_api_log.id,
        NAME,
        zz_api_log.level,
        zz_api_log.message,
        IF(LEVEL = 'info', '#dff0d8', IF(LEVEL = 'error', '#f2dede', '#fcf8e3')) AS '_bg_',
        IF(CHAR_LENGTH(CONTEXT) > 200,
           CONCAT(SUBSTRING(CONTEXT, 1, 200), '<a title=\"', REPLACE(CONTEXT, '\">','[...]'), '</a>'),
           CONTEXT) AS 'Contesto',
        CONTEXT AS 'contesto_esteso',
        zz_api_log.created_at AS 'Data inizio',
        zz_api_log.updated_at AS 'Data fine',
        CONCAT(TIMESTAMPDIFF(SECOND, zz_api_log.created_at, zz_api_log.updated_at), ' secondi') AS 'Eseguito in'
    FROM `zz_api_log`
) AS dati
    WHERE 1=1
    HAVING 2=2
ORDER BY `Data inizio` DESC" WHERE `name` = 'Log eventi';

-- Allineamento vista Articoli
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_impegnata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'entrata' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) a ON `a`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT SUM(`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) AS `qta_ordinata`, `or_righe_ordini`.`idarticolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id` INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id` INNER JOIN `or_statiordine` ON `or_ordini`.`idstatoordine` = `or_statiordine`.`id` WHERE `or_tipiordine`.`dir` = 'uscita' AND `or_righe_ordini`.`confermato` = 1 AND `or_statiordine`.`impegnato` = 1 GROUP BY `idarticolo`) `ordini_fornitore` ON `ordini_fornitore`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN `zz_categorie` ON `mg_articoli`.`id_categoria` = `zz_categorie`.`id`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS `sottocategorie` ON `mg_articoli`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `zz_categorie_lang` AS `sottocategorie_lang` ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT `co_iva`.`percentuale` AS `perc`, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id` = `zz_settings`.`valore`) AS iva ON `iva`.`nome` = 'Iva predefinita'
    LEFT JOIN `mg_scorte_sedi` ON `mg_scorte_sedi`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT CASE WHEN MIN(`qta_attuale`) < MIN(`threshold_qta`) AND MIN(`threshold_qta`) > 0 THEN -1 WHEN MIN(`qta_attuale`) >= MIN(`threshold_qta`) AND MIN(`threshold_qta`) > 0 THEN 1 ELSE 0 END AS `stato_giacenza`, `idarticolo` FROM (SELECT COALESCE(SUM(`mg_movimenti`.`qta`), 0) AS `qta_attuale`, COALESCE(`mg_scorte_sedi`.`threshold_qta`, 0) AS `threshold_qta`, `mg_movimenti`.`idarticolo` FROM `mg_movimenti` LEFT JOIN `mg_scorte_sedi` ON (`mg_scorte_sedi`.`id_sede` = `mg_movimenti`.`idsede` AND `mg_scorte_sedi`.`id_articolo` = `mg_movimenti`.`idarticolo`) GROUP BY `mg_movimenti`.`idarticolo`, `mg_movimenti`.`idsede`) AS `subquery` WHERE `idarticolo` IS NOT NULL GROUP BY `idarticolo`) AS `giacenze` ON `giacenze`.`idarticolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(`mg_articoli_barcode`.`barcode` SEPARATOR '<br />') AS `lista`, `mg_articoli_barcode`.`idarticolo` FROM `mg_articoli_barcode` GROUP BY `mg_articoli_barcode`.`idarticolo`) AS `barcode` ON `barcode`.`idarticolo` = `mg_articoli`.`id`
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `name` = 'Articoli';

UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `query` = "IF(COALESCE(giacenze.stato_giacenza, 0) = -1, '#ec5353', IF(COALESCE(giacenze.stato_giacenza, 0) = 1, '#CCFFCC', ''))" WHERE `zz_views`.`name` = '_bg_' AND `zz_modules`.`name` = 'Articoli';

-- Aggiunta stampa intervento checklist con note
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`, `available_options`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), '1', 'Intervento & checklist con note', 'interventi', 'idintervento', '{\"pricing\":true, \"checklist\": true, \"note\":true}', 'fa fa-print', '', '', '0', '0', '1', NULL); 
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES ('1', (SELECT MAX(`id`) FROM `zz_prints`), 'Intervento & checklist con note', 'Intervento num {numero} del {data}'); 
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES ('2', (SELECT MAX(`id`) FROM `zz_prints`), 'Intervento & checklist con note', 'Intervento num {numero} del {data}'); 

-- Aggiunta impostazione per Base URL
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `is_user_setting`) VALUES (NULL, 'Base URL', '', 'string', '0', 'Generali', NULL, NULL, NULL, '0');
SELECT @id_setting := MAX(`id`) FROM `zz_settings`;
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, @id_setting, 'Base URL', ''),
(2, @id_setting, 'Base URL', '');