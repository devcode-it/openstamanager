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