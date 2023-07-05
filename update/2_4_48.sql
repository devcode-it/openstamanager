-- Aggiunta tipologia reply to
ALTER TABLE `em_templates` ADD `tipo_reply_to` VARCHAR(255) NOT NULL AFTER `subject`; 
UPDATE `em_templates` SET `tipo_reply_to` = 'email_fissa' WHERE `reply_to` != '';

-- Fix help setting
UPDATE `zz_settings` SET `help` = "Modifica automaticamente la data di emissione di una fattura al momento della sua emissione, impostando data successiva a quella dell'ultima fattura emessa" WHERE `zz_settings`.`nome` = 'Data emissione fattura automatica';
