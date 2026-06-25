-- Impostazione per selezionare i gruppi abilitati alla modifica CC e CCN in fase di invio mail
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Gruppi abilitati alla modifica CC e CCN', (SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `zz_groups`), 'query=SELECT `zz_groups`.`id`, `zz_groups_lang`.`title` AS descrizione FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) ORDER BY `zz_groups`.`id`', 1, 'Mail', 10, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Gruppi abilitati alla modifica CC e CCN', 'Seleziona i gruppi di utenti che possono modificare i campi CC e CCN durante l''invio di email.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Groups enabled to edit CC and BCC', 'Select the user groups that can edit CC and BCC fields when sending emails.');

-- Rimozione impostazione "Logo stampe"
DELETE FROM `zz_settings` WHERE `nome` = 'Logo stampe';

-- Spostamento impostazioni in sezione "Personalizzazioni grafiche"
UPDATE `zz_settings` SET `sezione` = 'Personalizzazioni grafiche' WHERE `nome` = 'Filigrana stampe';
UPDATE `zz_settings` SET `sezione` = 'Personalizzazioni grafiche' WHERE `nome` = 'CSS Personalizzato';

-- Impostazione "Filigrana stampe" trasformata in campo di caricamento file (tipo media, editabile)
UPDATE `zz_settings` SET `tipo` = 'media', `editable` = 1 WHERE `nome` = 'Filigrana stampe';

-- Aggiornamento traduzioni dell'impostazione "Filigrana stampe"
UPDATE `zz_settings_lang` SET `help` = 'Carica un\'immagine da utilizzare come filigrana per le stampe dell\'azienda.' WHERE `id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Filigrana stampe') AND `id_lang` = 1;
UPDATE `zz_settings_lang` SET `help` = 'Upload an image to use as a watermark for company prints.' WHERE `id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Filigrana stampe') AND `id_lang` = 2;