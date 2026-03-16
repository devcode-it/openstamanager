-- Aggiornamento campo uid di interventi tecnici
ALTER TABLE `in_interventi_tecnici` CHANGE `uid` `uid` VARCHAR(255) NULL DEFAULT NULL;