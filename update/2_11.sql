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

-- Aggiunta colonna id_tipo_intervento alla tabella co_righe_contratti
ALTER TABLE `co_righe_contratti` ADD `id_tipo_intervento` INT(11) NULL;

ALTER TABLE `co_contratti_tipiintervento` ADD `is_abilitato` TINYINT(1) NOT NULL DEFAULT 1;

-- Aggiunta gestione per conto di in scheda anagrafica
ALTER TABLE `an_anagrafiche` ADD `id_cliente_finale` INT NOT NULL AFTER `idanagrafica`; 
ALTER TABLE `in_interventi` CHANGE `idclientefinale` `id_cliente_finale` INT NOT NULL;

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

-- Aggiunta vista Data rate in Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Data pagamento rate', '`prima_nota`.`data_rate`', 16, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Data pagamento rate'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Payment dates');

-- Creazione tabella stati impianti
CREATE TABLE IF NOT EXISTS `my_statiimpianti` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NULL,
    `icona`      VARCHAR(255) NOT NULL DEFAULT '',
    `colore`     VARCHAR(7)   NOT NULL DEFAULT '#ffffff',
    `can_delete` TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME     NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabella traduzioni stati impianti
CREATE TABLE IF NOT EXISTS `my_statiimpianti_lang` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `id_lang`    INT(11)      NOT NULL,
    `id_record`  INT(11)      NOT NULL,
    `title`      VARCHAR(255) NOT NULL DEFAULT '',
    `created_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `id_lang` (`id_lang`),
    KEY `id_record` (`id_record`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registrazione modulo Stati Impianti sotto Tabelle (parent = id di 'Tabelle')
INSERT INTO `zz_modules` (`name`, `directory`, `attachments_directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
('Stati impianti', 'stati_impianti', 'stati_impianti',
'
SELECT
    |select|
FROM
    `my_statiimpianti`
    LEFT JOIN `my_statiimpianti_lang` ON (`my_statiimpianti`.`id` = `my_statiimpianti_lang`.`id_record` AND |lang|)
WHERE
    1=1 AND deleted_at IS NULL
HAVING
    2=2',
'', 'fa fa-circle-o', '2.11', '2.11', 1, (SELECT `id` FROM (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tabelle') AS `tmp_tabelle`), 1, 1);

-- Traduzione nome modulo
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_modules`), 'Stati impianti', 'Stati impianti'),
(2, (SELECT MAX(`id`) FROM `zz_modules`), 'Plant statuses', 'Plant statuses');

-- Viste del modulo
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT MAX(`id`) FROM `zz_modules`), 'id',          '`my_statiimpianti`.`id`', 1, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Descrizione', '`my_statiimpianti_lang`.`title`',  2, 1),
((SELECT MAX(`id`) FROM `zz_modules`), 'Icona',       '`my_statiimpianti`.`icona`',       3, 1),
((SELECT MAX(`id`) FROM `zz_modules`), 'Colore',      '`my_statiimpianti`.`colore`',      4, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'id'), 'id'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'id'), 'id'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Descrizione'), 'Descrizione'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Descrizione'), 'Description'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Icona'),       'Icona'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Icona'),       'Icon'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Colore'),      'Colore'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Colore'),      'Color');

-- Inserimento stati predefiniti: Attivo (verde) e Disattivato (rosso)
INSERT INTO `my_statiimpianti` (`name`, `icona`, `colore`) VALUES
('Attivo',      'fa fa-check-circle', '#28a745'),
('Disattivato', 'fa fa-times-circle', '#dc3545');

INSERT INTO `my_statiimpianti_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `my_statiimpianti` WHERE `name` = 'Attivo'),      'Attivo'),
(2, (SELECT `id` FROM `my_statiimpianti` WHERE `name` = 'Attivo'),      'Active'),
(1, (SELECT `id` FROM `my_statiimpianti` WHERE `name` = 'Disattivato'), 'Disattivato'),
(2, (SELECT `id` FROM `my_statiimpianti` WHERE `name` = 'Disattivato'), 'Disabled');

-- Aggiunta colonna id_stato in my_impianti (FK verso my_statiimpianti)
ALTER TABLE `my_impianti`
    ADD COLUMN `id_stato` INT(11) NULL DEFAULT NULL AFTER `id`,
    ADD CONSTRAINT `fk_my_impianti_stato` FOREIGN KEY (`id_stato`) REFERENCES `my_statiimpianti` (`id`) ON UPDATE CASCADE ON DELETE SET NULL;

-- Aggiunta conti per Iva Extra Intra UE e Reverse charge
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `id_piano_dei_conti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000040', 'Iva su vendite Extra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `id_piano_dei_conti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000050', 'Iva su acquisti Extra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `id_piano_dei_conti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000060', 'Iva su vendite Intra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `id_piano_dei_conti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000070', 'Iva su acquisti Intra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `id_piano_dei_conti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000080', 'Iva su vendite Reverse charge', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `id_piano_dei_conti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000090', 'Iva su acquisti Reverse charge', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`) VALUES (NULL, 'Conto per Iva su acquisti Extra UE', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`= "Iva su acquisti Extra UE"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei Conti', NULL, NULL, NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`) VALUES (NULL, 'Conto per Iva su vendite Extra UE', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`= "Iva su vendite Extra UE"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei Conti',  NULL, NULL, NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`) VALUES (NULL, 'Conto per Iva su acquisti Intra UE', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`= "Iva su acquisti Intra UE"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei Conti', NULL, NULL, NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`) VALUES (NULL, 'Conto per Iva su vendite Intra UE', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`= "Iva su vendite Intra UE"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei Conti', NULL, NULL, NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`) VALUES (NULL, 'Conto per Iva su acquisti Reverse charge', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`= "Iva su acquisti Reverse charge"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei Conti', NULL, NULL, NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`) VALUES (NULL, 'Conto per Iva su vendite Reverse charge', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`= "Iva su vendite Reverse charge"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei Conti', NULL, NULL, NULL);

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`)-5 FROM `zz_settings`), 'Conto per Iva su acquisti Extra UE', ''),
(2, (SELECT MAX(`id`)-5 FROM `zz_settings`), 'Account for IVA on purchases from outside the EU', ''),
(1, (SELECT MAX(`id`)-4 FROM `zz_settings`), 'Conto per Iva su vendite Extra UE', ''),
(2, (SELECT MAX(`id`)-4 FROM `zz_settings`), 'Account for IVA on sales outside the EU', ''),
(1, (SELECT MAX(`id`)-3 FROM `zz_settings`), 'Conto per Iva su acquisti Intra UE', ''),
(2, (SELECT MAX(`id`)-3 FROM `zz_settings`), 'Account for IVA on purchases within the EU', ''),
(1, (SELECT MAX(`id`)-2 FROM `zz_settings`), 'Conto per Iva su vendite Intra UE', ''),
(2, (SELECT MAX(`id`)-2 FROM `zz_settings`), 'Account for IVA on sales within the EU', ''),
(1, (SELECT MAX(`id`)-1 FROM `zz_settings`), 'Conto per Iva su acquisti Reverse charge', ''),
(2, (SELECT MAX(`id`)-1 FROM `zz_settings`), 'Account for IVA on purchases with reverse charge', ''),
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Conto per Iva su vendite Reverse charge', ''),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'Account for IVA on sales with reverse charge', '');

-- Aggiunta stampa GDPR
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`,  `enabled`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name`='Anagrafiche'), '1', 'GDPR', 'GDPR', 'idanagrafica', '', 'fa fa-print', '', '', '0', '0',  '1');

-- Traduzioni stampa GDPR
INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`) VALUES
(1, (SELECT `id` FROM `zz_prints` WHERE `name` = 'GDPR'), 'GDPR', 'GDPR'),
(2, (SELECT `id` FROM `zz_prints` WHERE `name` = 'GDPR'), 'GDPR', 'GDPR');

-- Aggiunta impostazione Condizioni GDPR
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Condizioni GDPR', '', 'ckeditor', 1, 'Generali');

INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_settings`), 'Condizioni GDPR', 'Condizioni generali da includere nella stampa GDPR'),
(2, (SELECT MAX(`id`) FROM `zz_settings`), 'GDPR Terms', 'General terms to include in GDPR print');

-- Aggiornamento nome segmenti fatture
UPDATE `zz_segments_lang` SET `title`='Vendite' WHERE `title` LIKE 'Standard vendite';
UPDATE `zz_segments_lang` SET `title`='Sales' WHERE `title` LIKE 'Standard sales';
UPDATE `zz_segments` SET `name`='Vendite' WHERE `name` LIKE 'Standard vendite';
UPDATE `zz_segments_lang` SET `title`='Acquisti' WHERE `title` LIKE 'Standard acquisti';
UPDATE `zz_segments_lang` SET `title`='Purchases' WHERE `title` LIKE 'Standard purchases';
UPDATE `zz_segments` SET `name`='Acquisti' WHERE `name` LIKE 'Standard acquisti';

-- Nuova colonna stato impianto
ALTER TABLE `my_statiimpianti` ADD `is_abilitato` BOOLEAN NOT NULL DEFAULT TRUE AFTER `deleted_at`; 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Stato', '`my_statiimpianti_lang`.`title`', '13', '1', '0', '0', '0', '', '', '1', '0', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Stato'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Status');

-- Segmenti impianti
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `for_fe`, `is_sezionale`, `is_fiscale`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Disabilitati', '1=1 AND `my_statiimpianti`.`is_abilitato`=0', 'WHR', '####', '', '', 0, 0, 0, 0, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Abilitati', '1=1 AND (`my_statiimpianti`.`is_abilitato`=1 OR `my_statiimpianti`.`is_abilitato` IS NULL)', 'WHR', '####', '', '', 0, 0, 0, 0, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Tutti', '1=1', 'WHR', '####', '', '', 1, 0, 0, 0, 0, 0, 1);

INSERT INTO `zz_segments_lang` (`id`, `id_lang`, `id_record`, `title`) VALUES
(NULL, '1', (SELECT MAX(`id`) FROM `zz_segments`), 'Tutti'),
(NULL, '2', (SELECT MAX(`id`) FROM `zz_segments`), 'All'),
(NULL, '1', (SELECT MAX(`id`)-1 FROM `zz_segments`), 'Abilitati'),
(NULL, '2', (SELECT MAX(`id`)-1 FROM `zz_segments`), 'Enabled'),
(NULL, '1', (SELECT MAX(`id`)-2 FROM `zz_segments`), 'Disabilitati'),
(NULL, '2', (SELECT MAX(`id`)-2 FROM `zz_segments`), 'Disabled');

-- forza impostazione aggiornamento prezzi e fornitore in fase di import FE
UPDATE `zz_settings` SET `valore` = "Aggiorna prezzo di acquisto + imposta fornitore predefinito" WHERE `nome` = "Aggiorna info di acquisto";

-- Aggiunta listino cliente per sedi
ALTER TABLE `an_sedi` ADD `id_listino` INT NULL;
ALTER TABLE `an_sedi` ADD CONSTRAINT `an_sedi_ibfk_5` FOREIGN KEY (`id_listino`) REFERENCES `mg_listini`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- Aggiunta colonna Validità in Contratti
UPDATE `zz_views` SET `order` = `order` + 1 WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `order` >= (SELECT * FROM (SELECT COALESCE((SELECT `order` + 1 FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'Data conclusione' LIMIT 1), (SELECT MAX(`order`) + 1 FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'))) AS `new_order`) AS `t`);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `html_format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Validità', "IF(IFNULL(`co_contratti`.`validita`, 0) = 0, '', CONCAT(IF(`co_contratti`.`tipo_validita` IS NOT NULL AND `co_contratti`.`data_accettazione` IS NOT NULL AND `co_contratti`.`data_conclusione` IS NOT NULL AND NOT ((`co_contratti`.`tipo_validita` = 'years' AND `co_contratti`.`data_conclusione` = DATE_ADD(DATE_ADD(`co_contratti`.`data_accettazione`, INTERVAL `co_contratti`.`validita` YEAR), INTERVAL -1 DAY)) OR (`co_contratti`.`tipo_validita` = 'months' AND `co_contratti`.`data_conclusione` = DATE_ADD(DATE_ADD(`co_contratti`.`data_accettazione`, INTERVAL `co_contratti`.`validita` MONTH), INTERVAL -1 DAY)) OR (`co_contratti`.`tipo_validita` = 'days' AND `co_contratti`.`data_conclusione` = DATE_ADD(DATE_ADD(`co_contratti`.`data_accettazione`, INTERVAL `co_contratti`.`validita` DAY), INTERVAL -1 DAY))), '<i class=\"fa fa-warning text-orange\"></i> ', ''), `co_contratti`.`validita`, ' ', CASE COALESCE(`co_contratti`.`tipo_validita`, 'days') WHEN 'years' THEN IF(`co_contratti`.`validita` <= 1, 'anno', 'anni') WHEN 'months' THEN IF(`co_contratti`.`validita` <= 1, 'mese', 'mesi') WHEN 'days' THEN IF(`co_contratti`.`validita` <= 1, 'giorno', 'giorni') ELSE '' END))", (SELECT * FROM (SELECT COALESCE((SELECT `order` + 1 FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'Data conclusione' LIMIT 1), (SELECT MAX(`order`) + 1 FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'))) AS `new_order`) AS `t`), 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, LAST_INSERT_ID(), 'Validità'),
(2, LAST_INSERT_ID(), 'Validity');

-- Allineamento query Fatture di vendita
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `id_documento`, `data`, GROUP_CONCAT(DISTINCT DATE_FORMAT(`data`, "%d/%m/%Y") SEPARATOR ", ") AS `data_rate` FROM `co_movimenti` WHERE `totale` > 0 AND `prima_nota` = 1 GROUP BY `id_documento`) AS `prima_nota` ON `prima_nota`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `ultimo_movimento`.`id_documento`, IF(`ultimo_movimento`.`is_insoluto` = 1, DATE_FORMAT(`ultimo_movimento`.`data`, "%d/%m/%Y"), NULL) AS `data_insoluto` FROM `co_movimenti` AS `ultimo_movimento` INNER JOIN (SELECT `id_documento`, MAX(`id`) AS `id` FROM `co_movimenti` WHERE `prima_nota` = 1 GROUP BY `id_documento`) AS `ultimo_movimento_idx` ON `ultimo_movimento_idx`.`id` = `ultimo_movimento`.`id`) AS `ultimo_movimento` ON `ultimo_movimento`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`id_tipo_documento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND co_tipidocumento_lang.|lang|)
    LEFT JOIN (SELECT `id_documento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsa_inps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`id_iva` GROUP BY `id_documento`) AS `righe` ON `co_documenti`.`id` = `righe`.`id_documento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, \' - \', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`id_stato` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record` AND `fe_stati_documento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Fatture di vendita\') AND `zz_operations`.`op` = \'send-email\' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`id_pagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND co_pagamenti_lang.|lang|)
    LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `id_tipo_documento`, `data` FROM `co_documenti` WHERE `co_documenti`.`id_tipo_documento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `dir` = \'entrata\') AND `numero_esterno` != \'\' |date_period(`co_documenti`.`data`)| GROUP BY `id_segment`, `numero_esterno`, `id_tipo_documento` HAVING COUNT(`numero_esterno`) > 1) AS dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`id_tipo_documento` = `co_documenti`.`id_tipo_documento`
WHERE
    1=1
    AND `dir` = \'entrata\'
    |segment(`co_documenti`.`id_segment`)|
    |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di vendita';

-- Aggiunta vista Data insoluto in Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Data insoluto', '`ultimo_movimento`.`data_insoluto`', 17, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Data insoluto'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Unpaid date');

-- Aggiunta del campo serial alla tabella my_componenti per la gestione dei seriali nei componenti degli impianti
ALTER TABLE `my_componenti` ADD `serial` VARCHAR(255) NULL AFTER `id_articolo`;

-- Tabelle per la gestione DB-driven delle voci della Navbar Right Menu.
-- I moduli consumer aggiungono righe qui invece di iniettare <li> via JS DOM-manipulation.
CREATE TABLE IF NOT EXISTS `zz_links` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `icon`       VARCHAR(255) NOT NULL DEFAULT '',
    `color`      VARCHAR(64)  NULL DEFAULT NULL,
    `order`      INT(11)      NOT NULL DEFAULT 0,
    `enabled`    TINYINT(1)   NOT NULL DEFAULT 1,
    `type`       ENUM('link','javascript','module','plugin') NOT NULL,
    `value`      VARCHAR(500) NOT NULL,
    `parent`     INT(11)      NULL DEFAULT NULL,
    `id_module`  INT(11)      NULL DEFAULT NULL,
    `assets`     TEXT         NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_zz_links_name` (`name`),
    KEY `idx_zz_links_parent` (`parent`),
    KEY `idx_zz_links_enabled_order` (`enabled`, `order`),
    KEY `idx_zz_links_id_module` (`id_module`),
    CONSTRAINT `fk_zz_links_parent`
        FOREIGN KEY (`parent`) REFERENCES `zz_links`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `fk_zz_links_module`
        FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `zz_links_lang` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `id_lang`    INT(11)      NOT NULL,
    `id_record`  INT(11)      NOT NULL,
    `label`      VARCHAR(255) NOT NULL DEFAULT '',
    `title`      VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_zz_links_lang_record` (`id_lang`, `id_record`),
    KEY `idx_zz_links_lang_record` (`id_record`),
    CONSTRAINT `fk_zz_links_lang_record`
        FOREIGN KEY (`id_record`) REFERENCES `zz_links`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
    CONSTRAINT `fk_zz_links_lang_lang`
        FOREIGN KEY (`id_lang`)   REFERENCES `zz_langs`(`id`)  ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registrazione modulo "Link navbar" per gestione CRUD voci zz_links
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `parent`, `default`, `enabled`) VALUES
('Link navbar', 'link_navbar',
'SELECT
    |select|
FROM
    `zz_links`
    LEFT JOIN `zz_links_lang` ON (`zz_links`.`id` = `zz_links_lang`.`id_record` AND |lang|)
    LEFT JOIN `zz_links` AS `parent_link` ON `zz_links`.`parent` = `parent_link`.`id`
    LEFT JOIN `zz_modules` AS `mod` ON `zz_links`.`id_module` = `mod`.`id`
WHERE
    1=1
HAVING
    2=2',
'', 'fa fa-bars', '2.11', '2.11',
(SELECT `id` FROM (SELECT `id` FROM `zz_modules` WHERE `name` = 'Strumenti') AS `tmp_strumenti`),
1, 1);

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_modules`), 'Link navbar', 'Link navbar'),
(2, (SELECT MAX(`id`) FROM `zz_modules`), 'Navbar links', 'Navbar links');

-- Viste del modulo Link navbar
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`, `html_format`) VALUES
((SELECT MAX(`id`) FROM `zz_modules`), 'id',         '`zz_links`.`id`',                              1, 0, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Etichetta',  '`zz_links_lang`.`label`',                      2, 1, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Icona',      '`zz_links`.`icon`',                            3, 1, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Tipo',       '`zz_links`.`type`',                            4, 1, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Ordine',     '`zz_links`.`order`',                           5, 1, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Padre',      '`parent_link`.`name`',                         6, 1, 0),
((SELECT MAX(`id`) FROM `zz_modules`), 'Abilitato',  'IF(`zz_links`.`enabled`=1, \'Sì\', \'No\')',  7, 1, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'id'),         'id'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'id'),         'id'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Etichetta'),  'Etichetta'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Etichetta'),  'Label'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Icona'),      'Icona'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Icona'),      'Icon'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Tipo'),       'Tipo'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Tipo'),       'Type'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Ordine'),     'Ordine'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Ordine'),     'Order'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Padre'),      'Padre'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Padre'),      'Parent'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Abilitato'),  'Abilitato'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT MAX(`id`) FROM `zz_modules`) AND `name` = 'Abilitato'),  'Enabled');

-- Modifica plugin Registrazioni in Contabilizzazione
UPDATE `zz_plugins` SET `name` = 'Contabilizzazione' WHERE `zz_plugins`.`name` = 'Registrazioni';
UPDATE `zz_plugins_lang` SET `title` = 'Contabilizzazione' WHERE `title` = 'Registrazioni';
UPDATE `zz_plugins_lang` SET `title` = 'Accounting' WHERE `title` = 'Registrations';

-- Riorganizzazione plugins Fatture di vendita
UPDATE `zz_plugins` SET `order` = '1' WHERE `zz_plugins`.`name` = 'Fatturazione Elettronica' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_plugins` SET `order` = '2' WHERE `zz_plugins`.`name` = 'Contabilizzazione' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_plugins` SET `order` = '3' WHERE `zz_plugins`.`name` = 'Movimenti contabili' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');

-- Riorganizzazione plugins Fatture di acquisto
UPDATE `zz_plugins` SET `order` = '1' WHERE `zz_plugins`.`name` = 'Fatturazione Elettronica' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto');
UPDATE `zz_plugins` SET `order` = '2' WHERE `zz_plugins`.`name` = 'Contabilizzazione' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto');
UPDATE `zz_plugins` SET `order` = '3' WHERE `zz_plugins`.`name` = 'Movimenti contabili' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto');

-- Rinomino impostazione Data inizio controlli su stati FE in Data inizio controlli Fatture di vendita
UPDATE `zz_settings` SET `nome` = 'Data inizio controlli Fatture di vendita' WHERE `zz_settings`.`nome` = 'Data inizio controlli su stati FE';
UPDATE `zz_settings_lang` SET `title` = 'Data inizio controlli Fatture di vendita' WHERE `zz_settings_lang`.`title` = 'Data inizio controlli su stati FE';

-- Allineamento riferimento Scadenziario -> Scadenzario 
RENAME TABLE `openstamanager`.`co_scadenziario` TO `openstamanager`.`co_scadenzario`;

-- Allineamento widgets
UPDATE `zz_widgets` SET `query` = 'SELECT \n CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((\n SELECT SUM(da_pagare-pagato)), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenzario INNER JOIN co_documenti ON co_scadenzario.id_documento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'entrata\' AND co_documenti.id_stato!=1 |segment(`co_documenti`.`id_segment`)| AND 1=1' WHERE `zz_widgets`.`name` = "Crediti da clienti";
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\' \', REPLACE(REPLACE(REPLACE(FORMAT((SELECT ABS(SUM(da_pagare-pagato))), 2), \',\', \'#\'), \'.\', \',\'),\'#\', \'.\'), \'&euro;\') AS dato FROM (co_scadenzario INNER JOIN co_documenti ON co_scadenzario.id_documento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'uscita\' AND co_documenti.id_stato!=1 |segment(`co_documenti`.`id_segment`)| AND 1=1' WHERE `zz_widgets`.`name` = "Debiti verso fornitori";

-- Allineamento viste
UPDATE `zz_views` SET `query` = '`co_scadenzario`.`id`' WHERE `zz_views`.`name` = "id" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');
UPDATE `zz_views` SET `query` = '`co_scadenzario`.`descrizione`' WHERE `zz_views`.`name` = "Descrizione scadenza" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');
UPDATE `zz_views` SET `query` = '`co_scadenzario`.`note`' WHERE `zz_views`.`name` = "Note" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');
UPDATE `zz_views` SET `query` = '`co_scadenzario`.`distinta`' WHERE `zz_views`.`name` = "Distinta" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');
UPDATE `zz_views` SET `query` = 'DATEDIFF(`co_scadenzario`.`scadenza`,NOW())' WHERE `zz_views`.`name` = "Scadenza giorni" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');
UPDATE `zz_views` SET `query` = "IF(pagato = da_pagare, \'#CCFFCC\', IF(data_concordata IS NOT NULL AND data_concordata != \'0000-00-00\', IF(data_concordata < NOW(), \'#ec5353\', \'#b3d2e3\'), IF(scadenza < NOW(), \'#f08080\', IF(DATEDIFF(co_scadenzario.scadenza,NOW()) < 10, \'#f9f9c6\', \'\'))))" WHERE `zz_views`.`name` = "_bg_" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');

-- Allineamento segmenti
UPDATE `zz_segments` SET `clause` = 'ABS(`co_scadenzario`.`pagato`) < ABS(`co_scadenzario`.`da_pagare`)' WHERE `zz_segments`.`name` = "Scadenzario totale";
UPDATE `zz_segments` SET `clause` = '((SELECT dir FROM co_tipidocumento WHERE co_tipidocumento.id=co_documenti.id_tipo_documento)=\'entrata\') AND ABS(`co_scadenzario`.`pagato`) < ABS(`co_scadenzario`.`da_pagare`)' WHERE `zz_segments`.`name` = "Scadenzario clienti";
UPDATE `zz_segments` SET `clause` = '((SELECT dir FROM co_tipidocumento WHERE co_tipidocumento.id=co_documenti.id_tipo_documento)=\'uscita\') AND ABS(`co_scadenzario`.`pagato`) < ABS(`co_scadenzario`.`da_pagare`)' WHERE `zz_segments`.`name` = "Scadenzario fornitori";
UPDATE `zz_segments` SET `clause` = 'co_scadenzario.tipo=\"generico\"' WHERE `zz_segments`.`name` = "Scadenzario generico";
UPDATE `zz_segments` SET `clause` = 'co_scadenzario.tipo=\"f24\"' WHERE `zz_segments`.`name` = "Scadenzario F24";
UPDATE `zz_segments` SET `clause` = '(`co_scadenzario`.`scadenza` BETWEEN \'|period_start|\' AND \'|period_end|\' AND codice_tipo_documento_fe NOT IN (\'TD16\', \'TD17\', \'TD18\', \'TD19\', \'TD20\', \'TD21\', \'TD22\', \'TD23\', \'TD26\', \'TD27\', \'TD28\'))' WHERE `zz_segments`.`name` = "Scadenzario completo";
UPDATE `zz_segments` SET `clause` = 'co_pagamenti.codice_modalita_pagamento_fe= \'MP12\' AND co_tipidocumento.dir=\"entrata\" AND ABS(`co_scadenzario`.`pagato`) < ABS(`co_scadenzario`.`da_pagare`)' WHERE `zz_segments`.`name` = "Scadenzario Ri.Ba. Clienti";
UPDATE `zz_segments` SET `clause` = 'co_pagamenti.codice_modalita_pagamento_fe= \'MP12\' AND co_tipidocumento.dir=\"uscita\" AND ABS(`co_scadenzario`.`pagato`) < ABS(`co_scadenzario`.`da_pagare`)' WHERE `zz_segments`.`name` = "Scadenzario Ri.Ba. Fornitori";

-- Modifica campo idanagrafica -> id in an_anagrafiche e id_anagrafica nelle altre tabelle
ALTER TABLE `an_anagrafiche` CHANGE `idanagrafica` `id` INT NOT NULL AUTO_INCREMENT;
ALTER TABLE `an_referenti` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `an_tipianagrafiche_anagrafiche` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `zz_users` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `an_anagrafiche_tipiintervento` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `an_anagrafiche_agenti` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `an_sedi` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `an_pagamenti_anagrafiche` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `co_contratti` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `co_documenti` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `co_scadenzario` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `my_impianti` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;
ALTER TABLE `or_ordini` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_anagrafiche`
    LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
    LEFT JOIN `an_relazioni_lang` ON (`an_relazioni_lang`.`id_record` = `an_relazioni`.`id` AND `an_relazioni_lang`.|lang|)
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.|lang|)
    LEFT JOIN (SELECT `id_anagrafica`, GROUP_CONCAT(`nomesede` SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY `id_anagrafica`) AS sedi ON `an_anagrafiche`.`id` = `sedi`.`id_anagrafica`
    LEFT JOIN (SELECT `id_anagrafica`, GROUP_CONCAT(`nome` SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY `id_anagrafica`) AS referenti ON `an_anagrafiche`.`id` = `referenti`.`id_anagrafica`
    LEFT JOIN (
        SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS `nome`
        FROM `co_pagamenti`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    ) AS pagvendita ON `an_anagrafiche`.`id_pagamento_vendite` = `pagvendita`.`id`
    LEFT JOIN (
        SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS `nome`
        FROM `co_pagamenti`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    ) AS pagacquisto ON `an_anagrafiche`.`id_pagamento_acquisti` = `pagacquisto`.`id`
    LEFT JOIN `an_zone` ON `an_anagrafiche`.`id_zona` = `an_zone`.`id`
WHERE
    1=1
    AND `an_anagrafiche`.`deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`id`, `pagvendita`.`nome`, `pagacquisto`.`nome`
HAVING
    2=2
ORDER BY
    `ragione_sociale`" WHERE `name` = "Anagrafiche";

-- Allineamento widgets
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.id) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.id=an_tipianagrafiche_anagrafiche.id_anagrafica WHERE 1=1 AND name="Cliente" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = "Numero di clienti";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.id) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.id=an_tipianagrafiche_anagrafiche.id_anagrafica WHERE 1=1 AND name="Tecnico" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = "Numero di tecnici";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.id) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.id=an_tipianagrafiche_anagrafiche.id_anagrafica WHERE 1=1 AND name="Fornitore" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = "Numero di fornitori";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.id) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.id=an_tipianagrafiche_anagrafiche.id_anagrafica WHERE 1=1 AND name="Agente" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = "Numero di agenti";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(co_documenti.id) AS dato FROM co_scadenzario INNER JOIN (((co_documenti INNER JOIN an_anagrafiche ON co_documenti.id_anagrafica=an_anagrafiche.id) INNER JOIN co_pagamenti ON co_documenti.id_pagamento=co_pagamenti.id) INNER JOIN co_tipidocumento ON co_documenti.id_tipo_documento=co_tipidocumento.id) ON co_scadenzario.id_documento=co_documenti.id WHERE ABS(pagato) < ABS(da_pagare) AND scadenza >= "|period_start|" AND scadenza <= "|period_end|" ORDER BY scadenza ASC' WHERE `zz_widgets`.`name` = "Scadenze";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(`dati`.`id`) AS dato FROM (SELECT `co_contratti`.`id`,((SELECT SUM(`co_righe_contratti`.`qta`) FROM `co_righe_contratti` WHERE `co_righe_contratti`.`um` = "ore" AND `co_righe_contratti`.`id_contratto` = `co_contratti`.`id`) - IFNULL((SELECT SUM(`in_interventi_tecnici`.`ore`) FROM `in_interventi_tecnici` INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id` WHERE `in_interventi`.`id_contratto` = `co_contratti`.`id` AND `in_interventi`.`id_stato` IN (SELECT `in_statiintervento`.`id` FROM `in_statiintervento` WHERE `in_statiintervento`.`is_bloccato` = 1)),0)) AS `ore_rimanenti`, DATEDIFF(`data_conclusione`, NOW()) AS giorni_rimanenti, `data_conclusione`, `ore_preavviso_rinnovo`, `giorni_preavviso_rinnovo`, (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `id` = `co_contratti`.`id_anagrafica`) AS ragione_sociale FROM `co_contratti` INNER JOIN `co_staticontratti` ON `co_staticontratti`.`id` = `co_contratti`.`id_stato` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = "Lingua")) WHERE `rinnovabile` = 1 AND YEAR(`data_conclusione`) > 1970 AND `co_contratti`.`id` NOT IN (SELECT `id_contratto_prev` FROM `co_contratti` contratti) AND `co_staticontratti_lang`.`title` NOT IN ("Concluso", "Rifiutato", "Bozza") HAVING (`ore_rimanenti` <= `ore_preavviso_rinnovo` OR DATEDIFF(`data_conclusione`, NOW()) <= ABS(`giorni_preavviso_rinnovo`)) ORDER BY `giorni_rimanenti` ASC,`ore_rimanenti` ASC) dati' WHERE `zz_widgets`.`name` = "Contratti in scadenza";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.id) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.id=an_tipianagrafiche_anagrafiche.id_anagrafica WHERE 1=1 AND name="Vettore" AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = "Numero di vettori";
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.id) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id LEFT JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_record = an_tipianagrafiche.id AND |lang|)) ON an_anagrafiche.id=an_tipianagrafiche_anagrafiche.id_anagrafica WHERE 1=1 AND `deleted_at` IS NULL HAVING 2=2' WHERE `zz_widgets`.`name` = "Tutte le anagrafiche";

-- Allineamento viste
UPDATE `zz_views` SET `query` = '`an_anagrafiche`.`id`' WHERE `zz_views`.`name` = "id" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche');
UPDATE `zz_views` SET `query` = '`in_interventi`.`id_anagrafica`' WHERE `zz_views`.`name` = "idanagrafica" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');
UPDATE `zz_views` SET `query` = '`my_impianti`.`id_anagrafica`' WHERE `zz_views`.`name` = "idanagrafica" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti');
UPDATE `zz_views` SET `query` = '`an_anagrafiche`.`id`' WHERE `zz_views`.`name` = "idanagrafica" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = '`co_preventivi`.`id_anagrafica`' WHERE `zz_views`.`name` = "idanagrafica" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi');
UPDATE `zz_views` SET `query` = '`an_anagrafiche.`.`id`' WHERE `zz_views`.`name` = "id" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tecnici e tariffe');


-- Allineamento moduli
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `id_intervento`, SUM(`prezzo_unitario` * `qta` - `sconto`) AS `ricavo_righe`, SUM(`costo_unitario` * `qta`) AS `costo_righe` FROM `in_righe_interventi` GROUP BY `id_intervento`) AS `righe` ON `righe`.`id_intervento` = `in_interventi`.`id`
    INNER JOIN `in_statiintervento` ON `in_interventi`.`id_stato` = `in_statiintervento`.`id`
    LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.|lang|)
    LEFT JOIN `an_referenti` ON `in_interventi`.`id_referente` = `an_referenti`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />', IF(`an_sedi`.`telefono` != '', CONCAT(`an_sedi`.`telefono`, '<br />'), ''), IF(`an_sedi`.`cellulare` != '', CONCAT(`an_sedi`.`cellulare`, '<br />'), ''), `an_sedi`.`citta`, IF(`an_sedi`.`indirizzo` != '', CONCAT(' - ', `an_sedi`.`indirizzo`), '')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `in_interventi`.`id_sede_destinazione`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `id_intervento` FROM `co_documenti`INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`id_documento` WHERE `original_document_type` = 'Modules\\Interventi\\Intervento' GROUP BY `id_intervento`, `original_document_id`) AS `fattura` ON `fattura`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT(DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`id` GROUP BY `id_intervento`) AS `tecnici_assegnati` ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (SELECT `in_interventi_tecnici`.`id_intervento`, GROUP_CONCAT(DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`id_tecnico` = `an_anagrafiche`.`id` GROUP BY `id_intervento`) AS `tecnici` ON `in_interventi`.`id` = `tecnici`.`id_intervento`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS `descrizione`,`my_impianti_interventi`.`id_intervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`id_impianto` GROUP BY `my_impianti_interventi`.`id_intervento`) AS `impianti` ON `impianti`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `co_contratti`.`id`, CONCAT(`co_contratti`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_contratti`) AS `contratto` ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (SELECT `co_preventivi`.`id`, CONCAT(`co_preventivi`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_preventivi`) AS `preventivo` ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (SELECT `or_ordini`.`id`, CONCAT(`or_ordini`.`numero`, ' del ', DATE_FORMAT(`data`, '%d/%m/%Y')) AS `info` FROM `or_ordini`) AS `ordine` ON `ordine`.`id` = `in_interventi`.`id_ordine`
    INNER JOIN `in_tipiintervento` ON `in_interventi`.`id_tipo_intervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(' ', `zz_files`.`name`) AS name, `zz_files`.`id_record` FROM `zz_files` INNER JOIN `zz_modules` ON `zz_files`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) WHERE `zz_modules`.`name` = 'Interventi' GROUP BY id_record) AS `files` ON `files`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tags`.`id_intervento`, GROUP_CONCAT(DISTINCT `name` SEPARATOR ', ') AS `nomi` FROM `in_tags` INNER JOIN `in_interventi_tags` ON `in_interventi_tags`.`id_tag` = `in_tags`.`id` GROUP BY `in_interventi_tags`.`id_intervento`) AS `tags` ON `in_interventi`.`id` = `tags`.`id_intervento`
    LEFT JOIN `an_zone` ON `an_anagrafiche`.`id_zona` = `an_zone`.`id`
WHERE
    1=1
    |segment(`in_interventi`.`id_segment`)|
    |date_period(`orario_inizio`, `data_richiesta`)|
GROUP BY
    `in_interventi`.`id`
HAVING
    2=2
ORDER BY
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `name` = "Interventi";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_preventivi`
    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`id_stato` = `co_statipreventivi`.`id`
    LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND co_statipreventivi_lang.id_lang = |lang|)
    LEFT JOIN (SELECT `id_preventivo`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_preventivi` GROUP BY `id_preventivo`) AS righe ON `co_preventivi`.`id` = `righe`.`id_preventivo`
    LEFT JOIN (SELECT `an_anagrafiche`.`id`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`) AS agente ON `agente`.`id` = `co_preventivi`.`id_agente`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `id_preventivo` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`id_documento` WHERE `original_document_type` = 'ModulesPreventiviPreventivo' GROUP BY `id_preventivo`, `original_document_id`) AS `fattura` ON `fattura`.`id_preventivo` = `co_preventivi`.`id`
    LEFT JOIN (SELECT COUNT(em_emails.id) AS emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `zz_operations`.`op` = 'send-email' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_preventivi`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />', IF(`an_sedi`.`telefono` != '', CONCAT(`an_sedi`.`telefono`, '<br />'), ''), IF(`an_sedi`.`cellulare` != '', CONCAT(`an_sedi`.`cellulare`, '<br />'), ''), `an_sedi`.`citta`, IF(`an_sedi`.`indirizzo` != '', CONCAT(' - ', `an_sedi`.`indirizzo`), '')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `co_preventivi`.`id_sede_destinazione`
WHERE
    1=1
    |segment(`co_preventivi`.`id_segment`)|
    |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
    AND `default_revision` = 1
GROUP BY
    `co_preventivi`.`id`,
    `fattura`.`info`
HAVING
    2=2
ORDER BY
    `co_preventivi`.`data_bozza` DESC, `numero` ASC" WHERE `name` = "Preventivi";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `id_documento`, `data`, GROUP_CONCAT(DISTINCT DATE_FORMAT(`data`, '%d/%m/%Y') SEPARATOR ', ') AS `data_rate` FROM `co_movimenti` WHERE `totale` > 0 AND `prima_nota` = 1 GROUP BY `id_documento`) AS `prima_nota` ON `prima_nota`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `ultimo_movimento`.`id_documento`, IF(`ultimo_movimento`.`is_insoluto` = 1, DATE_FORMAT(`ultimo_movimento`.`data`, '%d/%m/%Y'), NULL) AS `data_insoluto` FROM `co_movimenti` AS `ultimo_movimento` INNER JOIN (SELECT `id_documento`, MAX(`id`) AS `id` FROM `co_movimenti` WHERE `prima_nota` = 1 GROUP BY `id_documento`) AS `ultimo_movimento_idx` ON `ultimo_movimento_idx`.`id` = `ultimo_movimento`.`id`) AS `ultimo_movimento` ON `ultimo_movimento`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`id_tipo_documento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND co_tipidocumento_lang.|lang|)
    LEFT JOIN (SELECT `id_documento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsa_inps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`id_iva` GROUP BY `id_documento`) AS `righe` ON `co_documenti`.`id` = `righe`.`id_documento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`id_stato` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record` AND `fe_stati_documento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`id_pagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND co_pagamenti_lang.|lang|)
    LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `id_tipo_documento`, `data` FROM `co_documenti` WHERE `co_documenti`.`id_tipo_documento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata') AND `numero_esterno` != '' |date_period(`co_documenti`.`data`)| GROUP BY `id_segment`, `numero_esterno`, `id_tipo_documento` HAVING COUNT(`numero_esterno`) > 1) AS dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`id_tipo_documento` = `co_documenti`.`id_tipo_documento`
WHERE
    1=1
    AND `dir` = 'entrata'
    |segment(`co_documenti`.`id_segment`)|
    |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = "Fatture di vendita";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`id_tipo_documento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.|lang|)
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`id_stato` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`id_pagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`nome`, ' - ', `iban`) AS `descrizione` FROM `co_banche`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN (SELECT `id_documento`, GROUP_CONCAT(DISTINCT `co_pianodeiconti3`.`descrizione` SEPARATOR ', ') AS `descrizione` FROM `co_righe_documenti` INNER JOIN `co_pianodeiconti3` ON `co_pianodeiconti3`.`id` = `co_righe_documenti`.`id_conto` GROUP BY id_documento) AS `conti` ON `conti`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `id_documento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `id_documento`) AS `righe` ON `co_documenti`.`id` = `righe`.`id_documento`
    LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`id_anagrafica` AS `anagrafica`, `d`.`id_segment`, YEAR(`d`.`data`) AS `anno` FROM `co_documenti` AS `d`
    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`id_tipo_documento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `d`.`id_segment`, `numero_documento`, `d`.`id_anagrafica`, YEAR(`d`.`data`)) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`id_anagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment` AND `d`.`anno` = YEAR(`co_documenti`.`data`))
WHERE
    1=1
AND
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)| |date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    `co_documenti`.`id`, `d`.`conteggio`
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = "Fatture di acquisto";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `co_movimenti`
    INNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`id_conto` = `co_pianodeiconti3`.`id`
    LEFT JOIN `co_documenti` ON `co_documenti`.`id` = `co_movimenti`.`id_documento`
    LEFT JOIN `an_anagrafiche` ON `co_movimenti`.`id_anagrafica` = `an_anagrafiche`.`id`
WHERE
    1=1 AND `prima_nota` = 1  |date_period(`co_movimenti`.`data`)|
GROUP BY
    `id_mastrino`,
    `prima_nota`,
    `co_movimenti`.`data`,
    `numero_esterno`,
    `co_movimenti`.`descrizione`,
    `an_anagrafiche`.`ragione_sociale`
HAVING
    2=2
ORDER BY
    `co_movimenti`.`data` DESC" WHERE `name` = "Prima nota";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenzario`
    LEFT JOIN `co_documenti` ON `co_scadenzario`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `an_anagrafiche` ON `co_scadenzario`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`id_pagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`id_tipo_documento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`id_stato` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN (SELECT COUNT(id_email) as emails, zz_operations.id_record FROM zz_operations WHERE id_module IN(SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `zz_operations`.`op` = 'send-email' GROUP BY zz_operations.id_record) AS `email` ON `email`.`id_record` = `co_scadenzario`.`id`
WHERE 
    1=1 AND (`co_statidocumento`.`id` IS NULL OR `co_statidocumento`.`name` IN ('Emessa', 'Parzialmente pagato', 'Pagato')) 
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `name` = "Scadenzario";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenzario`
    LEFT JOIN `co_documenti` ON `co_scadenzario`.`id_documento` = `co_documenti`.`id`
    LEFT JOIN `co_banche` ON `co_banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `an_anagrafiche` ON `co_scadenzario`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`id_pagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`id_tipo_documento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`id_stato` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN (SELECT COUNT(id_email) as emails, zz_operations.id_record FROM zz_operations WHERE id_module IN(SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `zz_operations`.`op` = 'send-email' GROUP BY zz_operations.id_record) AS `email` ON `email`.`id_record` = `co_scadenzario`.`id`
WHERE 
    1=1 AND (`co_statidocumento`.`id` IS NULL OR `co_statidocumento`.`name` IN ('Emessa', 'Parzialmente pagato', 'Pagato')) 
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `name` = "Articoli";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `or_ordini`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `an_anagrafiche` AS agente ON `or_ordini`.`id_agente` = `agente`.`id`
    LEFT JOIN (SELECT `id_ordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `id_ordine`) AS righe ON `or_ordini`.`id` = `righe`.`id_ordine`
    LEFT JOIN (SELECT `id_ordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`) > 0 GROUP BY `id_ordine`) AS `righe_da_evadere` ON `righe`.`id_ordine` = `righe_da_evadere`.`id_ordine`
    INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`id_stato`
    LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ', `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `id_ordine` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`id_documento` WHERE `original_document_type` = 'ModulesOrdiniOrdine' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`id_ordine` = `or_ordini`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'DDT ', `dt_ddt`.`numero_esterno` SEPARATOR ', ') AS `info`, `dt_righe_ddt`.`original_document_id` AS `id_ddt` FROM `dt_ddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`id_ddt` WHERE `original_document_type` = 'ModulesOrdiniOrdine' GROUP BY `original_document_id`) AS `ddt` ON `ddt`.`id_ddt` = `or_ordini`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS email ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1
    |segment(`or_ordini`.`id_segment`)|
    AND `dir` = 'entrata'
    |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = "Ordini cliente";

UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `or_ordini`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN (SELECT `id_ordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `id_ordine`) AS righe ON `or_ordini`.`id` = `righe`.`id_ordine`
    LEFT JOIN (SELECT `id_ordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`) > 0 GROUP BY `id_ordine`) AS `righe_da_evadere` ON `righe`.`id_ordine` = `righe_da_evadere`.`id_ordine`
    INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`id_stato`
    LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR ', ') AS info, co_righe_documenti.original_document_id AS id_ordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.id_documento WHERE original_document_type = 'Modules\\Ordini\\Ordine' GROUP BY id_ordine, original_document_id) AS fattura ON fattura.id_ordine = or_ordini.id
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS email ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1
    |segment(`or_ordini`.`id_segment`)|
    AND `dir` = 'uscita'
    |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = "Ordini fornitore";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`id_tipo_ddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`id_causale_t` = `dt_causalet`.`id`
    LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.|lang|)
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`id_spedizione` = `dt_spedizione`.`id`
    LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id` AND `dt_spedizione_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` AS `vettori` ON `dt_ddt`.`id_vettore` = `vettori`.`id`
    LEFT JOIN `an_sedi` AS `sedi` ON `dt_ddt`.`id_sede_partenza` = `sedi`.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`id_sede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN (SELECT `id_ddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `id_ddt`) AS righe ON `dt_ddt`.`id` = `righe`.`id_ddt`
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`id_statoddt`
    LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ', `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `id_ddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`id_documento` WHERE `original_document_type` = 'Modules\\DDT\\DDT' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`id_ddt` = `dt_ddt`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt in uscita') AND `zz_operations`.`op` = 'send-email' GROUP BY `id_record`) AS `email` ON `email`.`id_record` = `dt_ddt`.`id`
WHERE
    1=1
    |segment(`dt_ddt`.`id_segment`)|
    AND `dir` = 'entrata'
    |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC, `dt_ddt`.`created_at` DESC" WHERE `name` = "Ddt in uscita";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`id_tipo_ddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`id_causale_t` = `dt_causalet`.`id`
    LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.|lang|)
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`id_spedizione` = `dt_spedizione`.`id`
    LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id` AND `dt_spedizione_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`id_vettore` = `vettori`.`id`
    LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`id_sede_partenza` = sedi.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`id_sede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN(SELECT `id_ddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `id_ddt`) AS righe ON `dt_ddt`.`id` = `righe`.`id_ddt` 
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`id_statoddt`
    LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `id_ddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`id_documento` WHERE `original_document_type`='Modules\DDT\DDT' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`id_ddt` = `dt_ddt`.`id`
WHERE
    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = 'uscita' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.`created_at` DESC" WHERE `name` = "Ddt in entrata";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `an_anagrafiche`
    INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`id_anagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche`.`id`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND |lang|)
WHERE 
    1=1 AND `an_tipianagrafiche`.`name` = 'Tecnico' AND `an_anagrafiche`.`deleted_at` IS NULL
HAVING 
    2=2 
ORDER BY 
    `ragione_sociale`" WHERE `name` = "Tecnici e tariffe";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `my_impianti`
    LEFT JOIN `an_anagrafiche` AS clienti ON `clienti`.`id` = `my_impianti`.`id_anagrafica`
    LEFT JOIN `an_anagrafiche` AS tecnici ON `tecnici`.`id` = `my_impianti`.`id_tecnico`
    LEFT JOIN `zz_categorie` ON `zz_categorie`.`id` = `my_impianti`.`id_categoria`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` as sub ON sub.`id` = `my_impianti`.`id_sottocategoria`
    LEFT JOIN `zz_categorie_lang` as sub_lang ON (sub.`id` = sub_lang.`id_record` AND sub_lang.|lang|)
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi) AS sede ON sede.id = my_impianti.id_sede
    LEFT JOIN `zz_marche` as marca ON `marca`.`id` = `my_impianti`.`id_marca`
    LEFT JOIN `zz_marche` as modello ON `modello`.`id` = `my_impianti`.`id_modello`
    LEFT JOIN `my_statiimpianti` ON `my_impianti`.`id_stato`=`my_statiimpianti`.`id`
    LEFT JOIN `my_statiimpianti_lang` ON (`my_statiimpianti`.`id` = `my_statiimpianti_lang`.`id_record` AND `my_statiimpianti_lang`.|lang|)
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `matricola`" WHERE `name` = "Impianti";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`id_anagrafica` = `an_anagrafiche`.`id`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`id_agente` = `agente`.`id`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`id_stato` = `co_staticontratti`.`id`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
    LEFT JOIN (SELECT `id_contratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `id_contratto`) AS righe ON `co_contratti`.`id` = `righe`.`id_contrattoo`
    LEFT JOIN (WITH RigheAgg AS (SELECT id_intervento,SUM(prezzo_unitario * qta) AS sommacosti_per_intervento FROM in_righe_interventi GROUP BY id_intervento), TecniciAgg AS (SELECT id_intervento, SUM(prezzo_ore_consuntivo) AS sommasessioni_per_intervento FROM in_interventi_tecnici GROUP BY id_intervento) SELECT SUM(COALESCE(RigheAgg.sommacosti_per_intervento, 0)) AS sommacosti, SUM(COALESCE(TecniciAgg.sommasessioni_per_intervento, 0)) AS sommasessioni, i.id_contratto FROM in_interventi i LEFT JOIN RigheAgg ON RigheAgg.id_intervento = i.id LEFT JOIN TecniciAgg ON TecniciAgg.id_intervento = i.id GROUP BY i.id_contratto) AS spesacontratto ON spesacontratto.id_contratto = co_contratti.id
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br />') AS descrizione, my_impianti_contratti.id_contratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.id_impianto GROUP BY my_impianti_contratti.id_contratto) AS impianti ON impianti.id_contratto = co_contratti.id
    LEFT JOIN (SELECT um, SUM(qta) AS somma, id_contratto FROM co_righe_contratti GROUP BY um, id_contratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.id_contratto = co_contratti.id
    LEFT JOIN (SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.id_intervento = in_interventi.id LEFT JOIN in_tipiintervento ON in_interventi_tecnici.id_tipo_intervento=in_tipiintervento.id WHERE non_conteggiare=0 GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
    LEFT JOIN `co_categorie_contratti` ON `co_contratti`.`id_categoria` = `co_categorie_contratti`.`id`
    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)
    LEFT JOIN `co_categorie_contratti` AS sottocategorie ON `co_contratti`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `co_categorie_contratti_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `id_contratto` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`id_documento` WHERE `original_document_type`='Modules\\Contratti\\Contratto' GROUP BY `id_contratto`, `original_document_id`) AS `fattura` ON `fattura`.`id_contratto` = `co_contratti`.`id`
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
GROUP BY
    `co_contratti`.`id`
HAVING
    2=2
ORDER BY
    `co_contratti`.`data_bozza` DESC" WHERE `name` = "Contratti";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_movimenti`
	INNER JOIN `mg_articoli` ON `mg_articoli`.id = `mg_movimenti`.`id_articolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.|lang|)
	LEFT JOIN `an_sedi` ON `mg_movimenti`.`id_sede` = `an_sedi`.`id`
    LEFT JOIN `zz_modules` ON `zz_modules`.`name` = 'Articoli'
	LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.|lang|)
	LEFT JOIN (SELECT `an_anagrafiche`.`id`, `co_documenti`.`id`, `ragione_sociale` AS nomi FROM `co_documenti` LEFT JOIN `an_anagrafiche` ON `co_documenti`.`id_anagrafica` = `an_anagrafiche`.`id` GROUP BY `id`, `co_documenti`.`id`) AS fattura ON `fattura`.`id`= `mg_movimenti`.`reference_id`
	LEFT JOIN (SELECT `an_anagrafiche`.`id`, `dt_ddt`.`id`, `ragione_sociale` AS nomi FROM `dt_ddt` LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`id_anagrafica` = `an_anagrafiche`.`id` GROUP BY `id`, `dt_ddt`.`id`) AS ddt ON `ddt`.`id`= `mg_movimenti`.`reference_id`
	LEFT JOIN (SELECT `an_anagrafiche`.`id`, `in_interventi`.`id`, `ragione_sociale` AS nomi FROM `in_interventi` LEFT JOIN `an_anagrafiche` ON `in_interventi`.`id_anagrafica` = `an_anagrafiche`.`id` GROUP BY `id`, `in_interventi`.`id`) AS intervento ON `intervento`.`id`= `mg_movimenti`.`reference_id`
    LEFT JOIN (SELECT CONCAT('tab_', `zz_plugins`.`id`) AS link FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins_lang`.`id_record` = `zz_plugins`.`id` AND `zz_plugins_lang`.|lang|) INNER JOIN `zz_modules` ON `zz_plugins`.`idmodule_to` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) WHERE `zz_modules`.`name` = 'Articoli' AND `zz_plugins`.`name` = 'Movimenti') AS page ON `mg_movimenti`.`id` != ''
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
GROUP BY 
    `mg_movimenti`.`id`
HAVING
    2=2
ORDER BY
    `mg_movimenti`.`data` DESC,
    `mg_movimenti`.`created_at` DESC" WHERE `name` = "Movimenti";

UPDATE `zz_modules` SET `options` = "
SELECT 
    |select| 
FROM 
    `co_banche` 
    INNER JOIN an_anagrafiche ON `an_anagrafiche`.`id` = `co_banche`.`id_anagrafica` 
WHERE 
    1=1 AND `co_banche`.`deleted_at` IS NULL AND `an_anagrafiche`.`deleted_at` IS NULL 
HAVING 
    2=2" WHERE `name` = "Banche";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` ON `mg_articoli`.`id_fornitore` = `an_anagrafiche`.`id`
    LEFT JOIN `co_iva` ON `mg_articoli`.`id_iva_vendita` = `co_iva`.`id`
    LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.|lang|)
    LEFT JOIN (SELECT SUM(`qta` - `qta_evasa`) AS qta_impegnata, `id_articolo` FROM `or_righe_ordini` INNER JOIN `or_ordini` ON `or_righe_ordini`.`id_ordine` = `or_ordini`.`id` WHERE `id_stato` IN(SELECT `id` FROM `or_statiordine` WHERE `is_bloccato` = 0) GROUP BY `id_articolo`) ordini ON `ordini`.`id_articolo` = `mg_articoli`.`id`
    LEFT JOIN (SELECT `id_articolo`, `id_sede`, SUM(`qta`) AS `qta` FROM `mg_movimenti` WHERE `id_sede` = |giacenze_sedi_id_sede| GROUP BY `id_articolo`, `id_sede`) movimenti ON `mg_articoli`.`id` = `movimenti`.`id_articolo`
    LEFT JOIN `zz_categorie` AS categoria ON `categoria`.`id`= `mg_articoli`.`id_categoria`
    LEFT JOIN `zz_categorie_lang` AS categoria_lang ON (`categoria_lang`.`id_record` = `categoria`.`id` AND `categoria_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS sottocategoria ON `sottocategoria`.`id`=`mg_articoli`.`id_sottocategoria`
    LEFT JOIN `zz_categorie_lang` AS sottocategoria_lang ON (`sottocategoria_lang`.`id_record` = `sottocategoria`.`id` AND `sottocategoria_lang`.|lang|)
	LEFT JOIN (SELECT `co_iva`.`percentuale` AS perc, `co_iva`.`id`, `zz_settings`.`nome` FROM `co_iva` INNER JOIN `zz_settings` ON `co_iva`.`id`=`zz_settings`.`valore`)AS iva ON `iva`.`nome`= 'Iva predefinita'
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL
HAVING
    2=2 AND `qta` > 0
ORDER BY
    `mg_articoli_lang`.`title`" WHERE `name` = "Giacenze sedi";

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `mg_prezzi_articoli`
    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `mg_prezzi_articoli`.`id_anagrafica`
    INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `mg_prezzi_articoli`.`id_articolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.|lang|)
    LEFT JOIN `zz_categorie` AS `categoria` ON `mg_articoli`.`id_categoria` = `categoria`.`id`
    LEFT JOIN `zz_categorie_lang` AS `categorialang` ON (`categorialang`.`id_record` = `categoria`.`id` AND `categorialang`.|lang|)
    LEFT JOIN `zz_categorie` AS `sottocategoria` ON `mg_articoli`.`id_sottocategoria` = `sottocategoria`.`id`
    LEFT JOIN `zz_categorie_lang` AS `sottocategorialang` ON (`sottocategorialang`.`id_record` = `sottocategoria`.`id` AND `sottocategorialang`.|lang|)
    LEFT JOIN `zz_modules` ON `zz_modules`.`name`= 'Articoli'
    LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.|lang|)
    LEFT JOIN (SELECT `codice_fornitore` AS codice, `id_articolo`, `id_fornitore`, `barcode_fornitore` AS barcode, `deleted_at` FROM `mg_fornitore_articolo`) AS fornitore ON `mg_prezzi_articoli`.`id_articolo`= `fornitore`.`id_articolo` AND `mg_prezzi_articoli`.`id_anagrafica`=`fornitore`.`id_fornitore` AND `fornitore`.`deleted_at` IS NULL
WHERE
    1=1 AND `mg_articoli`.`deleted_at` IS NULL AND `an_anagrafiche`.`deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `an_anagrafiche`.`ragione_sociale`" WHERE `name` = "Listini";

UPDATE `zz_modules` SET `options` = "
SELECT 
    |select| 
FROM 
    an_sedi 
    INNER JOIN zz_settings ON (zz_settings.valore=an_sedi.id_anagrafica AND zz_settings.nome='Azienda predefinita') 
WHERE 
    1=1 AND an_sedi.is_automezzo=1 
HAVING 
    2=2 
ORDER BY 
    nomesede ASC" WHERE `name` = "Automezzi";

UPDATE `zz_modules` SET `options` = "
SELECT 
    |select| 
FROM 
    zz_operations 
    LEFT JOIN zz_users ON zz_operations.id_utente = zz_users.id 
    LEFT JOIN an_anagrafiche ON zz_users.id_anagrafica = an_anagrafiche.id 
    LEFT JOIN zz_modules_lang ON zz_operations.id_module = zz_modules_lang.id_record AND zz_modules_lang.id_lang = 1 
    LEFT JOIN zz_plugins_lang ON zz_operations.id_plugin = zz_plugins_lang.id_record AND zz_plugins_lang.id_lang = 1 
WHERE 
    1=1 |date_period(zz_operations.created_at)| 
HAVING 
    2=2 
ORDER BY 
    zz_operations.created_at DESC" WHERE `name` = "Log operazioni";


-- Allineamento plugins
UPDATE `zz_plugins` SET `options` = '{ "main_query": [{"type": "table", "fields": "Matricola, Nome, Data, Descrizione", "query": "SELECT id, (SELECT `id` FROM `zz_modules` WHERE `name` = \'Impianti\') AS _link_module_, id AS _link_record_, matricola AS Matricola, nome AS Nome, DATE_FORMAT(data, \'%d/%m/%Y\') AS Data, descrizione AS Descrizione FROM my_impianti WHERE id_anagrafica=|id_parent| HAVING 2=2"}]}' WHERE `name` = "Impianti del cliente";

UPDATE `zz_plugins` SET `options` = '{ "main_query": [	{	"type": "table", "fields": "Nominativo, Mansione, Telefono, Indirizzo email, Sede",	"query": "SELECT an_referenti.id, an_referenti.nome AS Nominativo, an_mansioni.nome AS Mansione, an_referenti.telefono AS Telefono, an_referenti.email AS \'Indirizzo email\', IF(id_sede = 0, \'Sede legale\', an_sedi.nomesede) AS Sede FROM an_referenti LEFT OUTER JOIN an_sedi ON id_sede = an_sedi.id LEFT OUTER JOIN an_mansioni ON idmansione = an_mansioni.id WHERE 1=1 AND an_referenti.id_anagrafica=|id_parent| HAVING 2=2 ORDER BY an_referenti.id DESC"}]}' WHERE `name` = "Referenti";

UPDATE `zz_plugins` SET `options` = ' { "main_query": [ { "type": "table", "fields": "Nome, Indirizzo, Città, CAP, Provincia, Referente", "query": "SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia, GROUP_CONCAT(an_referenti.nome SEPARATOR \', \') AS Referente FROM an_sedi LEFT OUTER JOIN an_referenti ON id_sede = an_sedi.id WHERE 1=1 AND an_sedi.id_anagrafica=|id_parent| AND deleted_at IS NULL GROUP BY an_sedi.id HAVING 2=2 ORDER BY an_sedi.id DESC"} ]}' WHERE `name` = "Sedi aggiuntive";

UPDATE `zz_plugins` SET `options` = '{ "main_query": [ { "type": "table", "fields": "Numero, Data, Descrizione, Qtà", "query": "SELECT `dt_ddt`.`id`, (CASE WHEN `dt_tipiddt`.`dir` = \'entrata\' THEN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt in uscita\') ELSE (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt in entrata\') END) AS _link_module_, `dt_ddt`.`id` AS _link_record_, IF(`dt_ddt`.`numero_esterno` = \'\', `dt_ddt`.`numero`, `dt_ddt`.`numero_esterno`) AS Numero, DATE_FORMAT(`dt_ddt`.`data`, \'%d/%m/%Y\') AS Data, `dt_righe_ddt`.`descrizione` AS `Descrizione`, REPLACE(REPLACE(REPLACE(FORMAT(`dt_righe_ddt`.`qta`, 2), \',\', \'#\'), \'.\', \',\'), \'#\', \'.\') AS `Qtà` FROM `dt_ddt` LEFT JOIN `dt_righe_ddt` ON `dt_ddt`.`id`=`dt_righe_ddt`.`id_ddt` JOIN `dt_tipiddt` ON `dt_ddt`.`id_tipo_ddt` = `dt_tipiddt`.`id` WHERE `dt_ddt`.`id_anagrafica`=|id_parent| ORDER BY `dt_ddt`.`id` DESC"} ]}' WHERE `name` = "Ddt del cliente";

UPDATE `zz_plugins` SET `options` = '{ "main_query": [ {  "type": "table", "fields": "Numero, Data inizio, Data fine, Tipo", "query": "SELECT in_interventi.id, in_interventi.codice AS Numero, DATE_FORMAT(MAX(orario_inizio), \'%d/%m/%Y\') AS \'Data inizio\', DATE_FORMAT(MAX(orario_fine), \'%d/%m/%Y\') AS \'Data fine\', `in_tipiintervento_lang`.`title`AS \'Tipo\', (SELECT `id` FROM `zz_modules` WHERE `name` = \'Interventi\' LIMIT 1) AS _link_module_, in_interventi.id AS _link_record_ FROM in_interventi LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`id_intervento` = `in_interventi`.`id` INNER JOIN `in_statiintervento` ON `in_interventi`.`id_stato`=`in_statiintervento`.`id` INNER JOIN `in_tipiintervento` ON (`in_interventi`.`id_tipo_intervento` = `in_tipiintervento`.`id`) LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \'Lingua\')) WHERE 1=1 AND in_interventi.deleted_at IS NULL AND id_anagrafica = |id_parent| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY in_interventi.id DESC"}]}' WHERE `name` = "Storico attività";

UPDATE `zz_plugins` SET `options` = '{ "main_query": [ { "type": "table", "fields": "Numero, Nome, Cliente, Totale, Stato, Predefinito", "query": "SELECT `co_contratti`.`id`, `numero` AS Numero, `co_contratti`.`nome` AS Nome, `an_anagrafiche`.`ragione_sociale` AS Cliente, FORMAT(`righe`.`totale_imponibile`,2) AS Totale, `co_staticontratti_lang`.`title` AS Stato, IF(`co_contratti`.`predefined`=1, \'SÌ\', \'NO\') AS Predefinito FROM `co_contratti` LEFT JOIN `an_anagrafiche` ON `co_contratti`.`id_anagrafica` = `an_anagrafiche`.`id` LEFT JOIN `co_staticontratti` ON `co_contratti`.`id_stato` = `co_staticontratti`.`id` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \'Lingua\')) LEFT JOIN (SELECT `id_contratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `id_contratto` ) AS righe ON `co_contratti`.`id` =`righe`.`id_contratto` WHERE 1=1 AND `co_contratti`.`id_anagrafica`=|id_parent| GROUP BY `co_contratti`.`id` HAVING 2=2 ORDER BY `co_contratti`.`id` ASC"} ]}' WHERE `name` = "Contratti del cliente";

UPDATE `zz_plugins` SET `options` = '{ "main_query": [ { "type": "table", "fields": "Mese di chiusura, Giorno di riprogrammazione", "query": "SELECT id, IF(mese=\'01\', \'Gennaio\', IF(mese=\'02\', \'Febbraio\',IF(mese=\'03\', \'Marzo\',IF(mese=\'04\', \'Aprile\',IF(mese=\'05\', \'Maggio\', IF(mese=\'06\', \'Giugno\', IF(mese=\'07\', \'Luglio\',IF(mese=\'08\', \'Agosto\',IF(mese=\'09\', \'Settembre\', IF(mese=\'10\', \'Ottobre\', IF(mese=\'11\', \'Novembre\',\'Dicembre\'))))))))))) AS `Mese di chiusura`, giorno_fisso AS `Giorno di riprogrammazione` FROM an_pagamenti_anagrafiche WHERE 1=1 AND id_anagrafica=|id_parent| GROUP BY id HAVING 2=2 ORDER BY an_pagamenti_anagrafiche.mese ASC"} ]}' WHERE `name` = "Regole pagamenti";

UPDATE `zz_plugins` SET `options` = '{ "main_query": [ { "type": "table", "fields": "Agente, Provvigione", "query": "SELECT co_provvigioni.id, an_anagrafiche.ragione_sociale AS `Agente`, CONCAT(FORMAT(co_provvigioni.provvigione,2), \' \', IF(co_provvigioni.tipo_provvigione=\'UNT\', \'€\', \'%\')) AS `Provvigione` FROM co_provvigioni LEFT JOIN an_anagrafiche ON co_provvigioni.id_agente=an_anagrafiche.id WHERE co_provvigioni.id_articolo=|id_parent| HAVING 2=2 ORDER BY co_provvigioni.id DESC"} ]}' WHERE `name` = "Provvigioni";

-- Modifica colonne an_anagrafiche
ALTER TABLE `an_anagrafiche` CHANGE `id_pagamento_vendite` `id_pagamento_vendite` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `id_pagamento_acquisti` `id_pagamento_acquisti` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `idiva_vendite` `id_iva_vendite` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `idiva_acquisti` `id_iva_acquisti` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `idsede_fatturazione` `id_sede_fatturazione` INT NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `piva` `p_iva` VARCHAR(16) NOT NULL;
ALTER TABLE `an_sedi` CHANGE `piva` `p_iva` VARCHAR(15) NOT NULL COMMENT 'P.Iva';

UPDATE an_anagrafiche SET indirizzo = CONCAT_WS(' ', indirizzo, indirizzo2) WHERE indirizzo2 IS NOT NULL AND TRIM(indirizzo2) <> '';
UPDATE an_sedi SET indirizzo = CONCAT_WS(' ', indirizzo, indirizzo2) WHERE indirizzo2 IS NOT NULL AND TRIM(indirizzo2) <> '';
ALTER TABLE `an_anagrafiche` DROP `indirizzo2`;
ALTER TABLE `an_sedi` DROP `indirizzo2`;

ALTER TABLE `an_anagrafiche` CHANGE `sitoweb` `sito_web` VARCHAR(255) NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `codiceri` `codice_r_i` VARCHAR(15) NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `codicerea` `codice_rea` VARCHAR(23) NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `appoggiobancario` `appoggio_bancario` VARCHAR(255) NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `codiceiban` `codice_iban` VARCHAR(40) NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `diciturafissafattura` `dicitura_fissa_fattura` VARCHAR(255) NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `id_conto_cliente` `id_conto_cliente` INT NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `idbanca_vendite` `id_banca_vendite` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `idbanca_acquisti` `id_banca_acquisti` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `id_conto_fornitore` `id_conto_fornitore` INT NOT NULL;

ALTER TABLE `an_anagrafiche` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `co_contratti` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `co_documenti` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `or_ordini` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `co_provvigioni` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `an_anagrafiche_agenti` CHANGE `idagente` `id_agente` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idagente` `id_agente` INT NOT NULL;

ALTER TABLE `an_anagrafiche` CHANGE `idrelazione` `id_relazione` INT NOT NULL;
ALTER TABLE `an_anagrafiche` DROP `agentemaster`;

ALTER TABLE `an_anagrafiche` CHANGE `idzona` `id_zona` INT NOT NULL;
ALTER TABLE `an_sedi` CHANGE `idzona` `id_zona` INT NOT NULL;
ALTER TABLE `co_fatturazione_contratti` CHANGE `idzona` `id_zona` INT NOT NULL;

ALTER TABLE `an_anagrafiche` CHANGE `idtipointervento_default` `id_tipo_intervento_default` INT NULL DEFAULT NULL;
ALTER TABLE `an_anagrafiche_tipiintervento` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25) NOT NULL;

ALTER TABLE `an_automezzi_danni` CHANGE `idsede` `id_sede` INT NOT NULL;
ALTER TABLE `an_automezzi_scadenze` CHANGE `idsede` `id_sede` INT NOT NULL;
ALTER TABLE `an_automezzi_viaggi` CHANGE `idsede` `id_sede` INT NOT NULL;
ALTER TABLE `an_referenti` CHANGE `idsede` `id_sede` INT NOT NULL;
ALTER TABLE `co_promemoria` CHANGE `idsede` `id_sede` INT NOT NULL;

ALTER TABLE `an_automezzi_rifornimenti` CHANGE `idviaggio` `id_viaggio` INT NOT NULL;

ALTER TABLE `an_automezzi_viaggi` CHANGE `idtecnico` `id_tecnico` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idtecnico` `id_tecnico` INT NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `idtecnico` `id_tecnico` INT NOT NULL;

ALTER TABLE `an_referenti` CHANGE `idmansione` `id_mansione` INT NOT NULL;
ALTER TABLE `em_mansioni_template` CHANGE `idmansione` `id_mansione` INT NOT NULL;

ALTER TABLE `an_sedi` CHANGE `nomesede` `nome_sede` VARCHAR(255) NOT NULL COMMENT 'Nome sede';
ALTER TABLE `an_tipianagrafiche_anagrafiche` CHANGE `idtipoanagrafica` `id_tipo_anagrafica` INT NOT NULL;

ALTER TABLE `co_banche` CHANGE `id_pianodeiconti3` `id_piano_dei_conti3` INT NULL DEFAULT NULL;
ALTER TABLE `co_banche` CHANGE `creditor_id` `id_creditor` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `co_contratti` CHANGE `idstato` `id_stato` TINYINT NULL DEFAULT NULL;
ALTER TABLE `co_documenti` CHANGE `idstatodocumento` `id_stato` TINYINT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idstatointervento` `id_stato` INT NOT NULL;
ALTER TABLE `or_ordini` CHANGE `idstatoordine` `id_stato` TINYINT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idstatoddt` `id_stato` TINYINT NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `idstato` `id_stato` TINYINT NOT NULL;

ALTER TABLE `co_contratti` CHANGE `idreferente` `id_referente` INT NULL DEFAULT NULL;
ALTER TABLE `co_documenti` CHANGE `idreferente` `id_referente` INT NULL DEFAULT NULL;
ALTER TABLE `co_preventivi` CHANGE `idreferente` `id_referente` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idreferente` `id_referente` INT NULL DEFAULT NULL;
ALTER TABLE `in_interventi` CHANGE `idreferente` `id_referente` INT NOT NULL;

ALTER TABLE `co_contratti` CHANGE `idsede_partenza` `id_sede_partenza` INT NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `idsede_partenza` `id_sede_partenza` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idsede_partenza` `id_sede_partenza` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idsede_partenza` `id_sede_partenza` INT NOT NULL;

ALTER TABLE `co_contratti` CHANGE `idsede_destinazione` `id_sede_destinazione` INT NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `idsede_destinazione` `id_sede_destinazione` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idsede_destinazione` `id_sede_destinazione` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idsede_destinazione` `id_sede_destinazione` INT NOT NULL;

ALTER TABLE `co_contratti` CHANGE `idpagamento` `id_pagamento` INT NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `idpagamento` `id_pagamento` INT NULL DEFAULT NULL;
ALTER TABLE `co_documenti` CHANGE `idpagamento` `id_pagamento` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idpagamento` `id_pagamento` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idpagamento` `id_pagamento` INT NOT NULL;

ALTER TABLE `co_contratti` CHANGE `id_contratto_prev` `id_contratto_prev` INT NOT NULL;

ALTER TABLE `co_contratti` CHANGE `informazioniaggiuntive` `informazioni_aggiuntive` TEXT NULL DEFAULT NULL;
ALTER TABLE `co_preventivi` CHANGE `informazioniaggiuntive` `informazioni_aggiuntive` TEXT NULL DEFAULT NULL;
ALTER TABLE `in_interventi` CHANGE `informazioniaggiuntive` `informazioni_aggiuntive` TEXT NULL DEFAULT NULL;

ALTER TABLE `co_contratti_tipiintervento` CHANGE `idcontratto` `id_contratto` INT NOT NULL;
ALTER TABLE `co_fatturazione_contratti` CHANGE `idcontratto` `id_contratto` INT NOT NULL;
ALTER TABLE `co_promemoria` CHANGE `idcontratto` `id_contratto` INT NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `idcontratto` `id_contratto` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idcontratto` `id_contratto` INT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idtipodocumento` `id_tipo_documento` TINYINT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idtipoddt` `id_tipo_ddt` TINYINT NOT NULL;

ALTER TABLE `co_preventivi` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;
ALTER TABLE `co_contratti` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;
ALTER TABLE `co_promemoria` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;
ALTER TABLE `in_fasceorarie_tipiintervento` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `idtipointervento` `id_tipo_intervento` INT NOT NULL;

ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_dirittochiamata` `costo_diritto_chiamata` DECIMAL(15,6) NOT NULL;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `costo_dirittochiamata_tecnico` `costo_diritto_chiamata_tecnico` DECIMAL(15,6) NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idcausalet` `id_causale_t` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idcausalet` `id_causale_t` INT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idspedizione` `id_spedizione` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idspedizione` `id_spedizione` TINYINT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idporto` `id_porto` INT NOT NULL;
ALTER TABLE `co_preventivi` CHANGE `idporto` `id_porto` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idporto` `id_porto` TINYINT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idaspettobeni` `id_aspetto_beni` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idaspettobeni` `id_aspetto_beni` TINYINT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idvettore` `id_vettore` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idvettore` `id_vettore` INT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idconto` `id_conto` INT NOT NULL;
ALTER TABLE `co_movimenti` CHANGE `idconto` `id_conto` INT NOT NULL;
ALTER TABLE `co_movimenti_modelli` CHANGE `idconto` `id_conto` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idconto` `id_conto` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idconto` `id_conto` INT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idrivalsainps` `id_rivalsa_inps` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idrivalsainps` `id_rivalsa_inps` INT NULL DEFAULT NULL;
ALTER TABLE `dt_ddt` CHANGE `idrivalsainps` `id_rivalsa_inps` INT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `rivalsainps` `rivalsa_inps` DECIMAL(15,6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `rivalsainps` `rivalsa_inps` DECIMAL(15,6) NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `rivalsainps` `rivalsa_inps` DECIMAL(15,6) NOT NULL;

ALTER TABLE `co_documenti` CHANGE `iva_rivalsainps` `iva_rivalsa_inps` DECIMAL(15,6) NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `iva_rivalsainps` `iva_rivalsa_inps` DECIMAL(15,6) NOT NULL;

ALTER TABLE `co_documenti` CHANGE `idritenutaacconto` `id_ritenuta_acconto` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idritenutaacconto` `id_ritenuta_acconto` INT NULL DEFAULT NULL;
ALTER TABLE `dt_ddt` CHANGE `idritenutaacconto` `id_ritenuta_acconto` INT NOT NULL;

ALTER TABLE `co_documenti` CHANGE `ritenutaacconto` `ritenuta_acconto` DECIMAL(15,6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `ritenutaacconto` `ritenuta_acconto` DECIMAL(15,6) NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `ritenutaacconto` `ritenuta_acconto` DECIMAL(15,6) NOT NULL;

ALTER TABLE `co_fatturazione_contratti` CHANGE `iddocumento` `id_documento` INT NOT NULL;
ALTER TABLE `co_movimenti` CHANGE `iddocumento` `id_documento` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `iddocumento` `id_documento` INT NOT NULL;
ALTER TABLE `co_scadenzario` CHANGE `iddocumento` `id_documento` INT NOT NULL;

ALTER TABLE `co_movimenti` CHANGE `idmastrino` `id_mastrino` INT NOT NULL;
ALTER TABLE `co_movimenti_modelli` CHANGE `idmastrino` `id_mastrino` INT NOT NULL;
ALTER TABLE `co_stampecontabili` CHANGE `idmastrino` `id_mastrino` INT NULL DEFAULT NULL;

ALTER TABLE `co_movimenti` CHANGE `primanota` `prima_nota` TINYINT NOT NULL;
ALTER TABLE `co_pagamenti` CHANGE `idconto_vendite` `id_conto_vendite` INT NULL DEFAULT NULL;
ALTER TABLE `co_pagamenti` CHANGE `idconto_acquisti` `id_conto_acquisti` INT NULL DEFAULT NULL;
ALTER TABLE `co_pianodeiconti2` CHANGE `idpianodeiconti1` `id_piano_dei_conti1` INT NOT NULL;
ALTER TABLE `co_pianodeiconti3` CHANGE `idpianodeiconti2` `id_piano_dei_conti2` INT NOT NULL;

ALTER TABLE `co_preventivi` CHANGE `idanagrafica` `id_anagrafica` INT NOT NULL;

ALTER TABLE `co_preventivi` CHANGE `idiva` `id_iva` INT NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `idiva` `id_iva` INT NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idiva` `id_iva` INT NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `idiva` `id_iva` INT NOT NULL;
ALTER TABLE `co_righe_promemoria` CHANGE `idiva` `id_iva` INT NOT NULL;
ALTER TABLE `dt_ddt` CHANGE `idiva` `id_iva` INT NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `idiva` `id_iva` INT NOT NULL;

ALTER TABLE `co_promemoria` CHANGE `idintervento` `id_intervento` INT NULL DEFAULT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idintervento` `id_intervento` INT NULL DEFAULT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `idintervento` `id_intervento` INT NULL DEFAULT NULL;

ALTER TABLE `co_promemoria` CHANGE `idimpianti` `id_impianti` VARCHAR(255) NOT NULL;
ALTER TABLE `co_righe_promemoria` CHANGE `idimpianto` `id_impianto` INT NULL DEFAULT NULL;

ALTER TABLE `co_promemoria` CHANGE `idtecnici` `id_tecnici` VARCHAR(255) NOT NULL;

ALTER TABLE `co_provvigioni` CHANGE `idarticolo` `id_articolo` INT NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `idarticolo` `id_articolo` INT NULL DEFAULT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `idarticolo` `id_articolo` INT NULL DEFAULT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `idarticolo` `id_articolo` INT NULL DEFAULT NULL;
ALTER TABLE `co_righe_promemoria` CHANGE `idarticolo` `id_articolo` INT NULL DEFAULT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `idarticolo` `id_articolo` INT NULL DEFAULT NULL;

ALTER TABLE `co_righe_contratti` CHANGE `idpianificazione` `id_pianificazione` INT NULL DEFAULT NULL;

ALTER TABLE `co_righe_documenti` CHANGE `idordine` `id_ordine` INT NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `idordine` `id_ordine` INT NOT NULL;

ALTER TABLE `co_righe_documenti` CHANGE `idddt` `id_ddt` INT NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `idddt` `id_ddt` INT NOT NULL;

ALTER TABLE `co_righe_documenti` CHANGE `idpreventivo` `id_preventivo` INT NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `idpreventivo` `id_preventivo` INT NOT NULL;

ALTER TABLE `do_documenti` CHANGE `idcategoria` `id_categoria` INT NOT NULL;

ALTER TABLE `in_fasceorarie_tipiintervento` CHANGE `idfasciaoraria` `id_fascia_oraria` INT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `nomefile` `nome_file` VARCHAR(255) NOT NULL;

ALTER TABLE `in_interventi_tecnici` CHANGE `scontokm` `sconto_km` DECIMAL(17,8) NOT NULL;