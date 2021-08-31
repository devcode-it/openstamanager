-- Aggiornato widget Contratti in scadenza (sostituito fatturabile con pianificabile)
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=''ore'' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) AND rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Aggiunto numero di email da inviare in contemporanea
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Numero email da inviare in contemporanea per account', '10', 'integer', 1, 'Newsletter', 2, 'Numero di email della Coda di invio da inviare in contemporanea per account email');

-- Aggiornamento gestione destinatari per newsletter e liste relative
ALTER TABLE `em_newsletter_anagrafica` DROP FOREIGN KEY `em_newsletter_anagrafica_ibfk_2`;
ALTER TABLE `em_newsletter_anagrafica`
    ADD `record_type` VARCHAR(255) NOT NULL AFTER `id_newsletter`, CHANGE `id_anagrafica` `record_id` INT(11) NOT NULL;
UPDATE `em_newsletter_anagrafica`
SET `record_type` ='Modules\\Anagrafiche\\Anagrafica';

ALTER TABLE `em_list_anagrafica` DROP FOREIGN KEY `em_list_anagrafica_ibfk_2`;
ALTER TABLE `em_list_anagrafica`
    ADD `record_type` VARCHAR(255) NOT NULL AFTER `id_list`, CHANGE `id_anagrafica` `record_id` INT(11) NOT NULL;
UPDATE `em_list_anagrafica`
SET `record_type` ='Modules\\Anagrafiche\\Anagrafica';

ALTER TABLE `em_list_anagrafica` RENAME TO `em_list_receiver`;
ALTER TABLE `em_newsletter_anagrafica` RENAME TO `em_newsletter_receiver`;

UPDATE `zz_views` SET `query` = '(SELECT COUNT(*) FROM `em_newsletter_receiver` WHERE `em_newsletter_receiver`.`id_newsletter` = `em_newsletters`.`id`)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Newsletter') AND `name` = 'Destinatari';

ALTER TABLE `em_newsletter_receiver` ADD `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT;
ALTER TABLE `em_list_receiver` ADD `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT;

-- Aggiunta procedura import conti
INSERT INTO `zz_imports` (`id_module`, `name`, `class`) VALUES ((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Piano dei conti'), 'Piano dei conti', 'Modules\\Partitario\\Import\\CSV');
--
-- Passaggio Componenti Impianti esistenti al nuovo formato come Articoli
--

-- Correzioni tabella Componenti
ALTER TABLE `my_componenti_articoli` RENAME TO `my_componenti`;
ALTER TABLE `my_componenti` ADD `id_componente_vecchio` INT(11), ADD `id_intervento` INT(11), ADD `id_sostituzione` INT(11), CHANGE `note` `note` TEXT, CHANGE `data_disinstallazione` `data_rimozione` DATE NULL, ADD `data_sostituzione` DATE NULL;

-- Introduzione categoria dedicata ai Componenti
INSERT INTO `mg_categorie` (`nome`, `colore`, `nota`) VALUES ('Componenti', '#ffffff', '');
INSERT INTO `mg_articoli` (`codice`, `descrizione`, `id_categoria`, `attivo`) SELECT DISTINCT(`filename`), `nome`, (SELECT `id` FROM `mg_categorie` WHERE `nome` = 'Componenti' LIMIT 1), 1 FROM `my_impianto_componenti`;

-- Trasposizione componenti esistenti
INSERT INTO `my_componenti` (`id_componente_vecchio`, `id_impianto`, `id_intervento`, `id_articolo`, `data_registrazione`, `data_sostituzione`) SELECT `id`, `idimpianto`, `idintervento`, (SELECT `id` FROM `mg_articoli` WHERE `codice` = `my_impianto_componenti`.`filename` LIMIT 1), `data`, `data_sostituzione` FROM `my_impianto_componenti`;

UPDATE `my_componenti`
    INNER JOIN `my_componenti` t ON `t`.`id_componente_vecchio` = `my_componenti`.`id_sostituzione`
SET `my_componenti`.`id_sostituzione` = `t`.`id` WHERE `my_componenti`.`id_componente_vecchio` IS NOT NULL;

-- Aggiornamento collegamenti dinamico Componenti-Interventi
ALTER TABLE `my_componenti_interventi` DROP FOREIGN KEY `my_componenti_interventi_ibfk_2`;
DELETE FROM `my_componenti_interventi` WHERE `id_componente` NOT IN (SELECT `id_componente_vecchio` FROM `my_componenti`);
UPDATE `my_componenti_interventi` SET `id_componente` = (SELECT `id` FROM `my_componenti` WHERE `id_componente_vecchio` = `my_componenti_interventi`.`id_componente`);
ALTER TABLE `my_componenti_interventi` ADD FOREIGN KEY (`id_componente`) REFERENCES `my_componenti`(`id`) ON DELETE CASCADE;

-- Aggiornamento foreign keys
ALTER TABLE `my_componenti` ADD FOREIGN KEY (`id_intervento`) REFERENCES `in_interventi`(`id`) ON DELETE SET NULL,
    ADD FOREIGN KEY (`id_sostituzione`) REFERENCES `my_componenti`(`id`) ON DELETE SET NULL,
    ADD FOREIGN KEY (`id_impianto`) REFERENCES `my_impianti`(`id`) ON DELETE CASCADE,
    ADD FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE;

-- Aggiunte colonne Sedi e Referenti in tabella Anagrafiche
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE name = 'Anagrafiche'), 'Referenti', '(SELECT GROUP_CONCAT(nome SEPARATOR '', '') FROM an_referenti WHERE an_referenti .idanagrafica = an_anagrafiche.idanagrafica)', 11, 0, 0, 0, '', '', 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE name = 'Anagrafiche'), 'Sedi', '(SELECT GROUP_CONCAT(nomesede SEPARATOR '', '') FROM an_sedi WHERE an_sedi.idanagrafica = an_anagrafiche.idanagrafica)', 10, 0, 0, 0, '', '', 1, 0, 1);

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
    FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`),
    FOREIGN KEY (`id_valore`) REFERENCES `mg_valori_attributi`(`id`)
) ENGINE=InnoDB;

ALTER TABLE mg_articoli ADD `id_combinazione` int(11), ADD FOREIGN KEY (`id_combinazione`) REFERENCES `mg_combinazioni`(`id`);

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES
    (NULL, 'Attributi Combinazioni', 'Attributi Combinazioni', 'attributi_combinazioni', 'SELECT |select| FROM mg_attributi WHERE mg_attributi.deleted_at IS NULL AND 1=1 HAVING 2=2', NULL, 'fa fa-angle-right', '1.0', '2.*', '100', '20', '1', '1'),
    (NULL, 'Combinazioni', 'Combinazioni', 'combinazioni_articoli', 'SELECT |select| FROM mg_combinazioni WHERE mg_combinazioni.deleted_at IS NULL AND 1=1 HAVING 2=2', NULL, 'fa fa-angle-right', '1.0', '2.*', '100', '20', '1', '1');

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Varianti Articolo', 'Varianti Articolo', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'varianti_articolo', 'custom');

-- Aggiunta colonne per il nuovo modulo Attributi Combinazioni
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
    (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Attributi Combinazioni'), 'id', 'mg_attributi.id', 1, 0, 0, 1),
    (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Attributi Combinazioni'), 'Nome', 'mg_attributi.nome', 2, 1, 0, 1),
    (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Combinazioni'), 'id', 'mg_combinazioni.id', 1, 0, 0, 1),
    (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Combinazioni'), 'Nome', 'mg_combinazioni.nome', 2, 1, 0, 1);
