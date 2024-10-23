-- Rimozione impostazioni deprecate per sezionale predefinito autofatture
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Sezionale per autofatture di vendita';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Sezionale per autofatture di acquisto';

-- Aggiunta flag is_autofattura, is_nota_debito e is_nota_credito in fe_tipi_documento
ALTER TABLE `fe_tipi_documento` ADD `is_autofattura` INT NOT NULL DEFAULT '0' AFTER `name`, ADD `is_nota_credito` INT NOT NULL DEFAULT '0' AFTER `is_autofattura`, ADD `is_nota_debito` INT NOT NULL DEFAULT '0' AFTER `is_nota_credito`; 
UPDATE `fe_tipi_documento` SET `is_nota_credito` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD04'; 
UPDATE `fe_tipi_documento` SET `is_nota_debito` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD05'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD16'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD17'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD18'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD19'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD20'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD21'; 
UPDATE `fe_tipi_documento` SET `is_autofattura` = 1 WHERE `fe_tipi_documento`.`codice` = 'TD28';