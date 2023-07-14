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
(NULL, (SELECT `id` FROM `zz_modules` WHERE name = 'Anagrafiche'), 'Referenti', '(SELECT GROUP_CONCAT(nome SEPARATOR '', '') FROM an_referenti WHERE an_referenti .idanagrafica = an_anagrafiche.idanagrafica)', 11, 1, 0, 0, '', '', 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE name = 'Anagrafiche'), 'Sedi', '(SELECT GROUP_CONCAT(nomesede SEPARATOR '', '') FROM an_sedi WHERE an_sedi.idanagrafica = an_anagrafiche.idanagrafica)', 10, 1, 0, 0, '', '', 1, 0, 1);
