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

-- Impostazione "Login" per caricare il logo personalizzato nella schermata di login
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Login logo', '', 'media', 1, 'Personalizzazioni grafiche', 5, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Login logo'), 'Login', 'Carica un\'immagine da visualizzare nella schermata di login. Dimensioni consigliate: 489x91 px. Se non viene caricato nessun file, viene utilizzato il logo predefinito.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Login logo'), 'Login', 'Upload an image to display on the login screen. Recommended dimensions: 489x91 px. If no file is uploaded, the default logo is used.');

-- Impostazioni per i loghi del menu laterale
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
('Logo menu', '', 'media', 1, 'Personalizzazioni grafiche', 6, 0),
('Logo menu quadrato / favicon', '', 'media', 1, 'Personalizzazioni grafiche', 7, 0);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu'), 'Logo menu', 'Carica un\'immagine da visualizzare nel menu laterale quando è esteso. Dimensioni consigliate: 489x91 px. Se non viene caricato nessun file, viene utilizzato il logo predefinito.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu'), 'Extended menu logo', 'Upload an image to display in the sidebar when expanded. Recommended dimensions: 489x91 px. If no file is uploaded, the default logo is used.'),
(1, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu quadrato / favicon'), 'Logo menu quadrato / favicon', 'Carica un\'immagine da visualizzare nel menu laterale quando è compresso (favicon/quadrato). Dimensioni consigliate: 1041x1024 px. Se non viene caricato nessun file, viene utilizzato il logo predefinito.'),
(2, (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Logo menu quadrato / favicon'), 'Collapsed menu logo', 'Upload an image to display in the sidebar when collapsed (favicon/square). Recommended dimensions: 1041x1024 px. If no file is uploaded, the default logo is used.');
