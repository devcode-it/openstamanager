UPDATE `zz_views` SET `search` = 1 WHERE `zz_views`.`name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie documenti');
UPDATE `zz_views` SET `search` = 1 WHERE `zz_views`.`name` IN ('Categoria', 'Nome', 'Data') AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale');

-- Aggiunta rivalsa inps e relativa iva per il calcolo del totale ivato del documento
UPDATE `zz_views` SET `query` = 'righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Totale ivato';

UPDATE `zz_views` SET `query` = 'righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Totale ivato';

-- Aggiunta campi righe contratti --
ALTER TABLE `co_righe_contratti` ADD `original_id` INT(11) NULL DEFAULT NULL AFTER `abilita_serial` , ADD `original_type` VARCHAR(255) NULL DEFAULT NULL AFTER `original_id`;

-- Ripristino TD01 per fatture differite
UPDATE `co_tipidocumento` SET `codice_tipo_documento_fe` = 'TD01' WHERE `co_tipidocumento`.`codice_tipo_documento_fe` = 'TD24';

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-paper-plane-o text-success' WHERE `fe_stati_documento`.`codice` = 'MC';

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-check-circle text-warning' WHERE `fe_stati_documento`.`codice` = 'NE';

-- modifica vista tecnici attività --
UPDATE `zz_views` SET `query` = 'GROUP_CONCAT(DISTINCT((SELECT DISTINCT(ragione_sociale) FROM an_anagrafiche WHERE idanagrafica = in_interventi_tecnici.idtecnico)))' WHERE `zz_views`.`id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Interventi') AND `zz_views`.`name` = 'Tecnici';

-- fix valore data_ora_trasporto
UPDATE `dt_ddt` SET `data_ora_trasporto` = NULL WHERE `dt_ddt`.`id` = '0000-00-00 00:00:00';

-- fix widget Contratti in scadenza
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=''ore'' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

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

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Notifica al tecnico l\'assegnazione all\'attività', '0', 'boolean', '1', 'Interventi', NULL, 'Notifica via email al tecnico le nuove sessioni di lavoro che gli sono state assegnate (l\'indirizzo email deve essere specificato nella sua anagrafica)');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Notifica al tecnico la rimozione dall\'attività', '0', 'boolean', '1', 'Interventi', NULL, 'Notifica via email al tecnico la rimozione dalle sessioni di lavoro che gli erano state assegnate (l\'indirizzo email deve essere specificato nella sua anagrafica)');

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
);

INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Fornitori Articolo', 'Fornitori', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'fornitori_articolo', 'custom');

ALTER TABLE `or_righe_ordini` ADD `id_dettaglio_fornitore` int(11) NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `dt_righe_ddt` ADD `id_dettaglio_fornitore` int(11) NULL,
ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_preventivi` ADD `id_dettaglio_fornitore` int(11) NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_contratti` ADD `id_dettaglio_fornitore` int(11) NULL,
 ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_documenti` ADD `id_dettaglio_fornitore` int(11) NULL,
 ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `in_righe_interventi` ADD `id_dettaglio_fornitore` int(11) NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;
ALTER TABLE `co_righe_promemoria` ADD `id_dettaglio_fornitore` int(11) NULL,
  ADD FOREIGN KEY (`id_dettaglio_fornitore`) REFERENCES `mg_fornitore_articolo`(`id`) ON DELETE SET NULL;

-- Aggiunta campo prezzo_vendita_ivato per gli Articoli
ALTER TABLE `mg_articoli` ADD `prezzo_vendita_ivato` decimal(15,6) NOT NULL AFTER `prezzo_vendita`;
UPDATE `mg_articoli` SET `prezzo_vendita_ivato` = `prezzo_vendita`;

-- Aggiornamento ID per gli articoli degli Interventi
ALTER TABLE `mg_prodotti` DROP FOREIGN KEY `mg_prodotti_ibfk_4`;
UPDATE mg_prodotti SET mg_prodotti.id_riga_intervento = NULL WHERE mg_prodotti.id_riga_intervento NOT IN (SELECT old_id FROM in_righe_interventi);
UPDATE mg_prodotti SET mg_prodotti.id_riga_intervento = (SELECT id FROM in_righe_interventi WHERE mg_prodotti.id_riga_intervento = in_righe_interventi.old_id);
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

-- Aggiunta risorse dedicate all'applicazione
DELETE FROM `zz_api_resources` WHERE `version` = 'app-v1';
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'app-v1', 'create', 'login', 'API\\App\\v1\\Login', '1'),
(NULL, 'app-v1', 'retrieve', 'anagrafiche', 'API\\App\\v1\\Anagrafiche', '1'),
(NULL, 'app-v1', 'retrieve', 'anagrafiche-cleanup', 'API\\App\\v1\\Anagrafiche', '1'),
(NULL, 'app-v1', 'retrieve', 'anagrafica', 'API\\App\\v1\\Anagrafiche', '1'),
(NULL, 'app-v1', 'retrieve', 'sedi', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'sedi-cleanup', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'sede', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'referenti', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'referenti-cleanup', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'referente', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianti', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianti-cleanup', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianto', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'stati-intervento', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'stati-intervento-cleanup', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'stato-intervento', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipi-intervento', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipi-intervento-cleanup', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipo-intervento', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'articoli', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'articoli-cleanup', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'articolo', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'interventi', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'interventi-cleanup', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'sessioni', 'API\\App\\v1\\Sessioni', '1'),
(NULL, 'app-v1', 'retrieve', 'sessioni-cleanup', 'API\\App\\v1\\Sessioni', '1'),
(NULL, 'app-v1', 'retrieve', 'sessione', 'API\\App\\v1\\Sessioni', '1'),
(NULL, 'app-v1', 'retrieve', 'righe-intervento', 'API\\App\\v1\\Righe', '1'),
(NULL, 'app-v1', 'retrieve', 'righe-intervento-cleanup', 'API\\App\\v1\\Righe', '1'),
(NULL, 'app-v1', 'retrieve', 'riga-intervento', 'API\\App\\v1\\Righe', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquote-iva', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquote-iva-cleanup', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquota-iva', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazioni', 'API\\App\\v1\\Impostazioni', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazioni-cleanup', 'API\\App\\v1\\Impostazioni', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazione', 'API\\App\\v1\\Impostazioni', '1'),

(NULL, 'app-v1', 'create', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'update', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'delete', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'delete', 'sessione', 'API\\App\\v1\\Sessioni', '1'),
(NULL, 'app-v1', 'create', 'sessione', 'API\\App\\v1\\Sessioni', '1'),
(NULL, 'app-v1', 'update', 'sessione', 'API\\App\\v1\\Sessioni', '1'),
(NULL, 'app-v1', 'create', 'riga-intervento', 'API\\App\\v1\\Righe', '1'),
(NULL, 'app-v1', 'update', 'riga-intervento', 'API\\App\\v1\\Righe', '1'),
(NULL, 'app-v1', 'delete', 'riga-intervento', 'API\\App\\v1\\Righe', '1'),

(NULL, 'app-v1', 'retrieve', 'email-rapportino', 'API\\App\\v1\\RapportinoIntervento', '1'),
(NULL, 'app-v1', 'create', 'email-rapportino', 'API\\App\\v1\\RapportinoIntervento', '1');

-- Impostazioni relative all'applicazione
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Google Maps API key', '', 'string', '1', 'Applicazione', 1),
(NULL, 'Mostra prezzi', '1', 'boolean', '1', 'Applicazione', 1);
