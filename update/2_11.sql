-- Impostazione per abilitare/disabilitare il controllo sessione singola
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Abilita controllo sessione singola', '1', 'boolean', 1, 'Sicurezza');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Abilita controllo sessione singola', 'Se abilitato, impedisce il login multiplo dello stesso utente da dispositivi diversi.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Enable single session control', 'If enabled, prevents multiple logins of the same user from different devices.');

-- Aggiunta colonne minimo e massimo alla tabella mg_listini_articoli per gestire i prezzi per range
ALTER TABLE `mg_listini_articoli` ADD `minimo` decimal(15,6) DEFAULT NULL;
ALTER TABLE `mg_listini_articoli` ADD `massimo` decimal(15,6) DEFAULT NULL;

-- Aggiunta provider OAuth2 Keycloak
INSERT INTO `zz_oauth2` (`name`, `class`, `client_id`, `client_secret`, `config`, `state`, `access_token`, `refresh_token`, `after_configuration`, `is_login`, `enabled`) VALUES
('Keycloak', 'Modules\\Emails\\OAuth2\\KeycloakLogin', '', '', '{\"auth_server_url\":\"\",\"realm\":\"\"}', '', NULL, NULL, '', 1, 0);

-- Aggiunto campo nome in Ordini
ALTER TABLE `or_ordini` ADD `nome` VARCHAR(100) NOT NULL; 

-- Aggiunto flag Attivo in Iva
ALTER TABLE `co_iva` ADD `enabled` BOOLEAN NOT NULL DEFAULT TRUE AFTER `default`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Iva'), 'id', 'id', 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Iva'), 'Attivo', 'IF(`enabled`=1, \'SI\', \'NO\')', 10, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Attivo'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Enabled');