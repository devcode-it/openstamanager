-- Miglioramento supporto autenticazione OAuth 2
ALTER TABLE `em_accounts` ADD `oauth2_config` TEXT;

-- Aggiunto sistema di gestione Combinazioni Articoli
CREATE TABLE IF NOT EXISTS `mg_attributi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `titolo` varchar(255) NOT NULL,
    `ordine` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mg_valori_attributi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_attributo` int(11) NOT NULL,
    `nome` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY(`id`),
    FOREIGN KEY (`id_attributo`) REFERENCES `mg_attributi`(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mg_combinazioni` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `codice` varchar(255) NOT NULL,
    `nome` varchar(255) NOT NULL,
    `id_categoria` int(11),
    `id_sottocategoria` int(11),
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY(`id`),
    FOREIGN KEY (`id_categoria`) REFERENCES `mg_categorie`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`id_sottocategoria`) REFERENCES `mg_categorie`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mg_attributo_combinazione` (
    `id_combinazione` int(11) NOT NULL,
    `id_attributo` int(11) NOT NULL,
    `order` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(`id_attributo`, `id_combinazione`),
    FOREIGN KEY (`id_attributo`) REFERENCES `mg_attributi`(`id`),
    FOREIGN KEY (`id_combinazione`) REFERENCES `mg_combinazioni`(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `mg_articolo_attributo` (
    `id_articolo` int(11) NOT NULL,
    `id_valore` int(11) NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(`id_articolo`, `id_valore`),
    FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`),
    FOREIGN KEY (`id_valore`) REFERENCES `mg_valori_attributi`(`id`)
) ENGINE=InnoDB;

ALTER TABLE `mg_articoli` ADD `id_combinazione` int(11), ADD FOREIGN KEY (`id_combinazione`) REFERENCES `mg_combinazioni`(`id`);

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
(NULL, 'Attributi Combinazioni', 'Attributi Combinazioni', 'attributi_combinazioni', 'SELECT |select| FROM mg_attributi WHERE mg_attributi.deleted_at IS NULL AND 1=1 HAVING 2=2', NULL, 'fa fa-angle-right', '1.0', '2.*', '100', (SELECT id FROM zz_modules t WHERE t.name = 'Magazzino'), '1', '1'),
(NULL, 'Combinazioni', 'Combinazioni', 'combinazioni_articoli', 'SELECT |select| FROM mg_combinazioni WHERE mg_combinazioni.deleted_at IS NULL AND 1=1 HAVING 2=2', NULL, 'fa fa-angle-right', '1.0', '2.*', '100', (SELECT id FROM zz_modules t WHERE t.name = 'Magazzino'), '1', '1');

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Varianti Articolo', 'Varianti Articolo', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'varianti_articolo', 'custom');

-- Aggiunta colonne per il nuovo modulo Attributi Combinazioni
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Attributi Combinazioni'), 'id', 'mg_attributi.id', 1, 0, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Attributi Combinazioni'), 'Nome', 'mg_attributi.nome', 2, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Combinazioni'), 'id', 'mg_combinazioni.id', 1, 0, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Combinazioni'), 'Nome', 'mg_combinazioni.nome', 2, 1, 0, 1);

-- Introduzione della Banca nelle tabelle Fatture
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Banca', '(SELECT CONCAT(co_banche.nome, '' - '' , co_banche.iban) AS descrizione FROM co_banche WHERE co_banche.id = id_banca_azienda)', 6, 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Banca',
'(SELECT CONCAT(co_banche.nome, '' - '' , co_banche.iban) FROM co_banche WHERE co_banche.id = id_banca_azienda)', 6, 1, 0, 1);

-- Rimosso reversed sulle note di debito
UPDATE `co_tipidocumento` SET `reversed` = '0' WHERE `co_tipidocumento`.`descrizione` = 'Nota di debito';

-- Fix recupero informazioni sui servizi attivi
UPDATE `zz_cache` SET `expire_at` = NULL WHERE `zz_cache`.`name` = 'Informazioni su Services';

-- Fix flag default per i plugin
UPDATE `zz_plugins` SET `default` = 1, `version` = '' WHERE `zz_plugins`.`name` IN ('Impianti del cliente', 'Impianti', 'Referenti', 'Sedi', 'Statistiche', 'Interventi svolti', 'Componenti ini', 'Movimenti', 'Serial', 'Consuntivo', 'Consuntivo', 'Pianificazione interventi', 'Ddt del cliente', 'Fatturazione Elettronica', 'Fatturazione Elettronica', 'Revisioni', 'Ricevute FE', 'Giacenze', 'Rinnovi', 'Statistiche', 'Dichiarazioni d''Intento', 'Pianificazione fatturazione', 'Listino Clienti', 'Storico attività', 'Consuntivo', 'Allegati', 'Componenti', 'Listino Fornitori', 'Piani di sconto/maggiorazione', 'Varianti Articolo');

-- Aggiunta eliminazione causale DDT
ALTER TABLE `dt_causalet` ADD `deleted_at` TIMESTAMP NULL;

-- Fix query per la lista newsletter predefinita
UPDATE `em_lists` SET `query` = 'SELECT idanagrafica AS id, ''Modules\\\\Anagrafiche\\\\Anagrafica'' AS tipo FROM an_anagrafiche WHERE deleted_at IS NULL', `name` = 'Tutte le Anagrafiche (Sedi legali)' WHERE `query` = 'SELECT idanagrafica AS id FROM an_anagrafiche WHERE email != ''''';

INSERT INTO `em_lists` (`id`, `name`, `description`, `query`, `deleted_at`) VALUES
(NULL, 'Tutti i Referenti', 'Indirizzi email validi per ogni referente caricata a sistema', 'SELECT id, ''Modules\\\\Anagrafiche\\\\Referente'' AS tipo FROM an_referenti', NULL);

-- Fix riferimento documento per righe create da Interventi
UPDATE `co_righe_documenti` SET `original_document_id` = `idintervento`, `original_document_type` = 'Modules\\Interventi\\Intervento' WHERE `idintervento` IS NOT NULL;

-- Generalizzazione della configurazione OAuth2
CREATE TABLE IF NOT EXISTS `zz_oauth2` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `class` varchar(255) DEFAULT NULL,
    `client_id` text DEFAULT NULL,
    `client_secret` text DEFAULT NULL,
    `config` text DEFAULT NULL,
    `state` text DEFAULT NULL,
    `access_token` text DEFAULT NULL,
    `refresh_token` text DEFAULT NULL,
    `after_configuration` text DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

ALTER TABLE `em_accounts` ADD `id_oauth2` INT(11) DEFAULT NULL,
    ADD FOREIGN KEY (`id_oauth2`) REFERENCES `zz_oauth2`(`id`);

-- Aggiunta opt-out Newsletter per Referenti e Sedi
ALTER TABLE `an_referenti` ADD `enable_newsletter` BOOLEAN DEFAULT TRUE;
ALTER TABLE `an_sedi` ADD `enable_newsletter` BOOLEAN DEFAULT TRUE;