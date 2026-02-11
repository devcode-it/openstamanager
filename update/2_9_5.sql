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

UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `query` = "IF(COALESCE(giacenze.stato_giacenza, 0) = -1, '#ec5353', IF(COALESCE(giacenze.stato_giacenza, 0) = 1, '#CCFFCC', ''))" WHERE `zz_views`.`name` = '_bg_' AND `zz_modules`.`name` = 'Articoli';

-- Aggiunta stampa intervento checklist con note
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`, `available_options`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), '1', 'Intervento & checklist con note', 'interventi', 'idintervento', '{\"pricing\":true, \"checklist\": true, \"note\":true}', 'fa fa-print', '', '', '0', '0', '1', NULL); 
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES ('1', (SELECT MAX(`id`) FROM `zz_prints`), 'Intervento & checklist con note', 'Intervento num {numero} del {data}'); 
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES ('2', (SELECT MAX(`id`) FROM `zz_prints`), 'Intervento & checklist con note', 'Intervento num {numero} del {data}'); 

-- Aggiunta impostazione per Base URL
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Base URL', '', 'string', '0', 'Generali', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Base URL', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Base URL', '');