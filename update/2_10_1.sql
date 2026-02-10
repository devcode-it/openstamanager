-- Aggiunta impostazione per disabilitare il controllo di sessione concorrente
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
('Abilita controllo sessione singola per utente', '1', 'boolean', 1, 'Sicurezza', NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Abilita controllo sessione singola per utente', 'Se abilitato, blocca il login se l''utente ha già una sessione attiva. Se disabilitato, permette login multipli dallo stesso utente.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Enable single session per user', 'If enabled, blocks login if the user already has an active session. If disabled, allows multiple logins from the same user.');