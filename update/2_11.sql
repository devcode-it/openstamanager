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

-- Aggiunta vista Data rate in Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Data pagamento rate', '`primanota`.`data_rate`', 16, 0);

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
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000040', 'Iva su vendite Extra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000050', 'Iva su acquisti Extra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000060', 'Iva su vendite Intra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000070', 'Iva su acquisti Intra UE', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000080', 'Iva su vendite Reverse charge', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `created_at`, `updated_at`, `percentuale_deducibile`) VALUES (NULL, '000090', 'Iva su acquisti Reverse charge', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`= "Conti transitori"), '', NULL, NULL, '100.00');

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

UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `my_impianti`
    LEFT JOIN `an_anagrafiche` AS clienti ON `clienti`.`idanagrafica` = `my_impianti`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS tecnici ON `tecnici`.`idanagrafica` = `my_impianti`.`idtecnico`
    LEFT JOIN `zz_categorie` ON `zz_categorie`.`id` = `my_impianti`.`id_categoria`
    LEFT JOIN `zz_categorie_lang` ON (`zz_categorie`.`id` = `zz_categorie_lang`.`id_record` AND `zz_categorie_lang`.|lang|)
    LEFT JOIN `zz_categorie` as sub ON sub.`id` = `my_impianti`.`id_sottocategoria`
    LEFT JOIN `zz_categorie_lang` as sub_lang ON (sub.`id` = sub_lang.`id_record` AND sub_lang.|lang|)
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi) AS sede ON sede.id = my_impianti.idsede
    LEFT JOIN `zz_marche` as marca ON `marca`.`id` = `my_impianti`.`id_marca`
    LEFT JOIN `zz_marche` as modello ON `modello`.`id` = `my_impianti`.`id_modello`
    LEFT JOIN `my_statiimpianti` ON `my_impianti`.`id_stato`=`my_statiimpianti`.`id`
    LEFT JOIN `my_statiimpianti_lang` ON (`my_statiimpianti`.`id` = `my_statiimpianti_lang`.`id_record` AND `my_statiimpianti_lang`.|lang|)
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `matricola`" WHERE `name` = 'Impianti';

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
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento`, `data`, GROUP_CONCAT(DISTINCT DATE_FORMAT(`data`, "%d/%m/%Y") SEPARATOR ", ") AS `data_rate` FROM `co_movimenti` WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `ultimo_movimento`.`iddocumento`, IF(`ultimo_movimento`.`is_insoluto` = 1, DATE_FORMAT(`ultimo_movimento`.`data`, "%d/%m/%Y"), NULL) AS `data_insoluto` FROM `co_movimenti` AS `ultimo_movimento` INNER JOIN (SELECT `iddocumento`, MAX(`id`) AS `id` FROM `co_movimenti` WHERE `primanota` = 1 GROUP BY `iddocumento`) AS `ultimo_movimento_idx` ON `ultimo_movimento_idx`.`id` = `ultimo_movimento`.`id`) AS `ultimo_movimento` ON `ultimo_movimento`.`iddocumento` = `co_documenti`.`id`
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

-- Aggiunta vista Data insoluto in Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Data insoluto', '`ultimo_movimento`.`data_insoluto`', 17, 0);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views`), 'Data insoluto'),
(2, (SELECT MAX(`id`) FROM `zz_views`), 'Unpaid date');
