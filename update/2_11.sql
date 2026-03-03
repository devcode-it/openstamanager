-- Impostazione per abilitare/disabilitare il controllo sessione singola
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Abilita controllo sessione singola', '1', 'boolean', 1, 'Sicurezza');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Abilita controllo sessione singola', 'Se abilitato, impedisce il login multiplo dello stesso utente da dispositivi diversi.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Enable single session control', 'If enabled, prevents multiple logins of the same user from different devices.');

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

-- Aggiunta impostazione Limita conteggio ore ad oggi nell'intestazione
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Limita conteggio ore ad oggi nell''intestazione', '0', 'boolean', 1, 'Attività');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Limita conteggio ore ad oggi nell''intestazione', 'Conteggia nell''intestazione attività solo le ore di lavoro dall''inizio al giorno corrente; se disabilitato conteggia tutte le ore collegate a un documento.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Limita conteggio ore ad oggi nell''intestazione', 'Conteggia nell''intestazione attività solo le ore di lavoro dall''inizio al giorno corrente; se disabilitato conteggia tutte le ore collegate a un documento.');

-- Aggiunta campo per calendario
ALTER TABLE `in_interventi_tecnici` ADD `description` TEXT NOT NULL AFTER `summary`;

-- Aggiunta colonna id_tipointervento alla tabella co_righe_contratti
ALTER TABLE `co_righe_contratti` ADD `id_tipointervento` INT(11) NULL;

ALTER TABLE `co_contratti_tipiintervento` ADD `is_abilitato` TINYINT(1) NOT NULL DEFAULT 1;

-- Aggiunta gestione per conto di in scheda anagrafica
ALTER TABLE `an_anagrafiche` ADD `idclientefinale` INT NOT NULL AFTER `idanagrafica`; 

-- Aggiunta impostazione per il ritardo di apertura dei tooltip sulla Dashboard
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Ritardo apertura tooltip dashboard (ms)', '300', 'integer', 1, 'Dashboard');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Ritardo apertura tooltip dashboard (ms)', 'Definisce il ritardo in millisecondi prima che il tooltip venga mostrato al passaggio del mouse sugli eventi del calendario nella Dashboard.'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Dashboard tooltip opening delay (ms)', 'Defines the delay in milliseconds before the tooltip is shown when hovering over calendar events in the Dashboard.');

-- Registrazione risorse API per automezzi e articoli automezzo
INSERT INTO `zz_api_resources` (`version`, `type`, `resource`, `class`, `enabled`) VALUES
('app-v1', 'retrieve', 'automezzi', 'API\\App\\v1\\Automezzi', 1),
('app-v1', 'retrieve', 'automezzi-cleanup', 'API\\App\\v1\\Automezzi', 1),
('app-v1', 'retrieve', 'automezzo', 'API\\App\\v1\\Automezzi', 1),
('app-v1', 'retrieve', 'articoli-automezzo', 'API\\App\\v1\\ArticoliAutomezzo', 1),
('app-v1', 'retrieve', 'articoli-automezzo-cleanup', 'API\\App\\v1\\ArticoliAutomezzo', 1),
('app-v1', 'retrieve', 'articolo-automezzo', 'API\\App\\v1\\ArticoliAutomezzo', 1);

CREATE TABLE `an_anagrafiche_tipiintervento` (
  `idanagrafica` int NOT NULL,
  `idtipointervento` varchar(25) NOT NULL
);

ALTER TABLE `an_anagrafiche_tipiintervento`
  ADD PRIMARY KEY (`idanagrafica`,`idtipointervento`);

-- Aggiunta impostazione per l'applicazione del diritto di chiamata
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Applica diritto di chiamata una volta al giorno', '1', 'boolean', 1, 'Attività');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Applica diritto di chiamata una volta al giorno', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Apply call right once a day', '');

-- Valorizzazione dell'impostazione "Descrizione personalizzata in fatturazione" se vuota
UPDATE `zz_settings` SET `valore` = 'Attività numero {numero} del {data}' WHERE `nome` = 'Descrizione personalizzata in fatturazione' AND (`valore` IS NULL OR `valore` = '');

-- Aggiunta campo Data rate in Fatture di vendita
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento`, `data`, GROUP_CONCAT(DISTINCT DATE_FORMAT(`data`, "%d/%m/%Y") SEPARATOR ", ") AS `data_rate` FROM `co_movimenti` WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND co_tipidocumento_lang.|lang|)
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, \' - \', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record` AND `fe_stati_documento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Fatture di vendita\') AND `zz_operations`.`op` = \'send-email\' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND co_pagamenti_lang.|lang|)
    LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `dir` = \'entrata\') AND `numero_esterno` != \'\' |date_period(`co_documenti`.`data`)| GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento` HAVING COUNT(`numero_esterno`) > 1) AS dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1=1
    AND `dir` = \'entrata\'
    |segment(`co_documenti`.`id_segment`)|
    |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di vendita';

-- Aggiunta vista Data rate in Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Data pagamento rate', '`primanota`.`data_rate`', 16, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Data pagamento rate'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Payment dates');

