-- Aggiunta tipologia reply to
ALTER TABLE `em_templates` ADD `tipo_reply_to` VARCHAR(255) NOT NULL AFTER `subject`; 
UPDATE `em_templates` SET `tipo_reply_to` = 'email_fissa' WHERE `reply_to` != '';