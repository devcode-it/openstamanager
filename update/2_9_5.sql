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