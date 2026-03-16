-- Aggiornamento campo uid di interventi tecnici
ALTER TABLE `in_interventi_tecnici` CHANGE `uid` `uid` VARCHAR(255) NULL DEFAULT NULL;

-- Allineamento vista Stati dei preventivi
UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_statipreventivi`
    LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND |lang|)
WHERE 
    1=1 AND deleted_at IS NULL
HAVING 
    2=2" WHERE `name` = 'Stati dei preventivi';