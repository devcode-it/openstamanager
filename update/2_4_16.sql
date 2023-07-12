UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search` = 1 WHERE `zz_modules`.`name` = 'Categorie documenti' AND `zz_views`.`name` = 'Descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search` = 1 WHERE `zz_modules`.`name` = 'Gestione documentale' AND `zz_views`.`name` IN ('Categoria', 'Nome', 'Data');
-- Aggiunta campi righe contratti --
ALTER TABLE `co_righe_contratti` ADD `original_id` INT(11) NULL DEFAULT NULL AFTER `abilita_serial` , ADD `original_type` VARCHAR(255) NULL DEFAULT NULL AFTER `original_id`;

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-paper-plane-o text-success' WHERE `fe_stati_documento`.`codice` = 'MC';

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-check-circle text-warning' WHERE `fe_stati_documento`.`codice` = 'NE';

-- fix valore data_ora_trasporto
UPDATE `dt_ddt` SET `data_ora_trasporto` = NULL WHERE `dt_ddt`.`id` = '0000-00-00 00:00:00';

-- Segmento Attività/Promemoria per attività.
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Tutti', '1=1', 'WHR', '####', '', 1, 0, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Attività', 'orario_inizio BETWEEN ''|period_start|'' AND ''|period_end|'' OR orario_fine BETWEEN ''|period_start|'' AND ''|period_end|''', 'WHR', '####', '', 0, 0, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Promemoria', '((in_interventi_tecnici.orario_inizio=''0000-00-00 00:00:00'' AND in_interventi_tecnici.orario_fine=''0000-00-00 00:00:00'') OR in_interventi_tecnici.id IS NULL)', 'WHR', '####', '', 0, 0, 0, 0);

-- Aggiunta dei template predefiniti che non possono essere rinominati o eliminati
UPDATE `em_templates` SET `predefined` = '1' WHERE `em_templates`.`name` = 'Notifica intervento';
UPDATE `em_templates` SET `predefined` = '1' WHERE `em_templates`.`name` = 'Notifica rimozione intervento';
UPDATE `em_templates` SET `predefined` = '1' WHERE `em_templates`.`name` = 'Reset password';
UPDATE `em_templates` SET `predefined` = '1' WHERE `em_templates`.`name` = 'Rapportino intervento';

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Notifica al tecnico l''assegnazione all''attività', '0', 'boolean', '1', 'Interventi', NULL, 'Notifica via email al tecnico le nuove sessioni di lavoro che gli sono state assegnate (l''indirizzo email deve essere specificato nella sua anagrafica)');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Notifica al tecnico la rimozione dall''attività', '0', 'boolean', '1', 'Interventi', NULL, 'Notifica via email al tecnico la rimozione dalle sessioni di lavoro che gli erano state assegnate (l''indirizzo email deve essere specificato nella sua anagrafica)');

UPDATE `zz_settings` SET `sezione` = 'Fatturazione Elettronica' WHERE `zz_settings`.`nome` = 'Riferimento dei documenti in Fattura Elettronica';

-- Fix campo iva con prezzi fino a 6 decimali
ALTER TABLE `co_righe_documenti` CHANGE `iva_unitaria` `iva_unitaria` DECIMAL(17,8) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `iva_unitaria` `iva_unitaria` DECIMAL(17,8) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `iva_unitaria` `iva_unitaria` DECIMAL(17,8) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `iva_unitaria` `iva_unitaria` DECIMAL(17,8) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `iva_unitaria` `iva_unitaria` DECIMAL(17,8) NOT NULL;
ALTER TABLE `in_righe_interventi` CHANGE `iva_unitaria` `iva_unitaria` DECIMAL(17,8) NOT NULL;

-- Aggiunta stato ordine "Annullato"
INSERT INTO `or_statiordine` (`id`, `descrizione`, `annullato`, `icona`, `completato`) VALUES (NULL, 'Annullato', '0', 'fa fa-thumbs-down text-danger', '1');

-- Aggiunta dei riferimenti n-n tra righe di documenti diversi
CREATE TABLE IF NOT EXISTS `co_riferimenti_righe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_type` varchar(255) NOT NULL,
  `source_id` int(11) NOT NULL,
  `target_type` varchar(255) NOT NULL,
  `target_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Aggiunta relazione tra articoli e fornitori
CREATE TABLE IF NOT EXISTS `mg_fornitore_articolo` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `id_articolo` int(11) NOT NULL,
   `id_fornitore` int(11) NOT NULL,
   `codice_fornitore` varchar(255) NOT NULL,
   `descrizione` varchar(255) NOT NULL,
   `prezzo_acquisto` decimal(15, 6) NOT NULL,
   `qta_minima` decimal(15, 6) NOT NULL,
   `giorni_consegna` int(11) NOT NULL,
   `deleted_at` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`) ON DELETE CASCADE,
   FOREIGN KEY (`id_fornitore`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Fornitori Articolo', 'Fornitori', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'fornitori_articolo', 'custom');

ALTER TABLE `or_righe_ordini` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `dt_righe_ddt` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_preventivi` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_contratti` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
 ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_documenti` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
 ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `in_righe_interventi` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_promemoria` ADD `id_dettaglio_fornitore` int(11) NULL DEFAULT NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;

-- Aggiunta campo prezzo_vendita_ivato per gli Articoli
ALTER TABLE `mg_articoli` ADD `prezzo_vendita_ivato` decimal(15,6) NOT NULL AFTER `prezzo_vendita`;
UPDATE `mg_articoli` SET `prezzo_vendita_ivato` = `prezzo_vendita`;

-- Aggiornamento ID per gli articoli degli Interventi
ALTER TABLE `mg_prodotti` DROP FOREIGN KEY `mg_prodotti_ibfk_4`;
UPDATE `mg_prodotti` SET `mg_prodotti`.`id_riga_intervento` = NULL WHERE `mg_prodotti`.`id_riga_intervento` NOT IN (SELECT `old_id` FROM `in_righe_interventi`);
UPDATE `mg_prodotti` SET `mg_prodotti`.`id_riga_intervento` = (SELECT `id` FROM `in_righe_interventi` WHERE `mg_prodotti`.`id_riga_intervento` = `in_righe_interventi`.`old_id`);
ALTER TABLE `mg_prodotti` ADD FOREIGN KEY (`id_riga_intervento`) REFERENCES `in_righe_interventi`(`id`) ON DELETE CASCADE;

-- Periodi di validità (Contratti e Preventivi)
ALTER TABLE `co_contratti` ADD COLUMN `tipo_validita` ENUM('days', 'months', 'years') NULL DEFAULT NULL AFTER `validita`;
ALTER TABLE `co_preventivi` ADD COLUMN `tipo_validita` ENUM('days', 'months', 'years') NULL DEFAULT NULL AFTER `validita`;

-- Aggiunta campi Peso e Volume in DDT e Fatture accompagnatorie
ALTER TABLE `dt_ddt` ADD COLUMN `peso` decimal(12, 4) AFTER `n_colli`, ADD COLUMN `volume` decimal(12, 4) AFTER `peso`;
ALTER TABLE `co_documenti` ADD COLUMN `peso` decimal(12, 4) AFTER `n_colli`, ADD COLUMN `volume` decimal(12, 4) AFTER `peso`;

-- Aggiunta data di connessione agli account SMTP
ALTER TABLE `em_accounts` ADD `connected_at` TIMESTAMP NULL DEFAULT NULL AFTER `timeout`;
UPDATE `em_accounts` SET `connected_at` = NOW();

-- Aggiunta del flag is_importabile sulle causali per permettere/bloccare l'importazione dei DDT
ALTER TABLE `dt_causalet` ADD `is_importabile` BOOLEAN DEFAULT TRUE AFTER `descrizione`;

-- Impostazione "Totali delle tabelle ristretti alla selezione"
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Totali delle tabelle ristretti alla selezione', '0', 'boolean', '1', 'Generali', 119);

-- Ottimizzazione caricamento lista fatture
ALTER TABLE `co_righe_documenti` ADD INDEX(`iddocumento`);

-- Aggiunta colonna data negli ordini
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Data', 'or_ordini.data', 3, 1, 0, 0, 1);

-- Plugin storico attività scheda Anagrafiche
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Storico attività', 'Storico attività', (SELECT id FROM zz_modules WHERE name = 'Interventi'), (SELECT id FROM zz_modules WHERE name='Anagrafiche'), 'tab', '', '1', '1', '0', '2.*', '0.1', NULL, '{ "main_query": [	{	"type": "table", "fields": "Numero, Data inizio, Data fine, Tipo", "query": "SELECT in_interventi.id, in_interventi.codice AS Numero,  DATE_FORMAT(MAX(orario_inizio),''%d/%m/%Y'') AS ''Data inizio'', DATE_FORMAT(MAX(orario_fine),''%d/%m/%Y'') AS ''Data fine'', (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS ''Tipo'', (SELECT `id` FROM `zz_modules` WHERE `name` = ''Interventi'') AS _link_module_, in_interventi.id AS _link_record_ FROM in_interventi LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`  WHERE 1=1 AND in_interventi.deleted_at IS NULL AND idanagrafica = |id_parent| HAVING 2=2 ORDER BY in_interventi.id DESC"}	]}', '', '');

-- Fix prezzo_unitario_ivato e sconto_iva_unitario per i documenti
UPDATE `co_righe_contratti` SET `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`;
UPDATE `co_righe_documenti` SET `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`;

UPDATE `co_righe_documenti` INNER JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;
UPDATE `co_righe_preventivi` INNER JOIN `co_iva` ON `co_iva`.`id` = `co_righe_preventivi`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;
UPDATE `co_righe_contratti` INNER JOIN `co_iva` ON `co_iva`.`id` = `co_righe_contratti`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;
UPDATE `dt_righe_ddt` INNER JOIN `co_iva` ON `co_iva`.`id` = `dt_righe_ddt`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;
UPDATE `or_righe_ordini` INNER JOIN `co_iva` ON `co_iva`.`id` = `or_righe_ordini`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;
UPDATE `in_righe_interventi` INNER JOIN `co_iva` ON `co_iva`.`id` = `in_righe_interventi`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;
UPDATE `co_righe_promemoria` INNER JOIN `co_iva` ON `co_iva`.`id` = `co_righe_promemoria`.`idiva` SET
    `sconto_iva_unitario` = (`co_iva`.`percentuale` * `sconto_unitario` / 100),
    `sconto_unitario_ivato` = `sconto_unitario` + `sconto_iva_unitario`;

-- Fix namespace classi Stampa e Allegato per API
UPDATE `zz_api_resources` SET `class` = 'API\\Common\\Stampa' WHERE `class` = 'Api\\Common\\Stampa';
UPDATE `zz_api_resources` SET `class` = 'API\\Common\\Allegato' WHERE `class` = 'Api\\Common\\Allegato';
