-- Eliminazione impostazione non utilizzata
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Modifica Viste di default';

UPDATE `zz_settings` SET `sezione` = 'Backup' WHERE `zz_settings`.`nome` = 'Numero di backup da mantenere';
UPDATE `zz_settings` SET `sezione` = 'Backup' WHERE `zz_settings`.`nome` = 'Backup automatico';

UPDATE `zz_settings` SET `sezione` = 'Aggiornamenti' WHERE `zz_settings`.`nome` = 'Attiva aggiornamenti';
UPDATE `zz_settings` SET `sezione` = 'API' WHERE `zz_settings`.`nome` = 'apilayer API key for VAT number';
UPDATE `zz_settings` SET `sezione` = 'API' WHERE `zz_settings`.`nome` = 'Google Maps API key';

-- Abilita la possibilità di ripristinare backup da archivi esterni al gestionale
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`, `help`) VALUES (NULL, 'Permetti il ripristino di backup da file esterni', '1', 'boolean', '0', 'Backup', CURRENT_TIMESTAMP, NULL, 'Abilita la possibilità di ripristinare backup da archivi esterni al gestionale.');


UPDATE `zz_settings` SET `help` = 'Esegue automaticamente un backup completo del gestionale al primo accesso della giornata.' WHERE `zz_settings`.`nome` = 'Backup automatico';

-- Aggiungo come completatato lo stato "Accettato" e "Parzialmente evaso" dell'ordine
UPDATE `or_statiordine` SET `completato` = '1' WHERE `or_statiordine`.`descrizione` = 'Accettato' OR `or_statiordine`.`descrizione` = 'Parzialmente evaso';

-- Aumento dimensione campo qta
ALTER TABLE `co_righe_preventivi` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `mg_movimenti` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `qta` `qta` decimal(15, 6) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `qta_evasa` `qta_evasa` decimal(15, 6) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `qta_evasa` `qta_evasa` decimal(15, 6) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `qta_evasa` `qta_evasa` decimal(15, 6) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `qta_evasa` `qta_evasa` decimal(15, 6) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `qta_evasa` `qta_evasa` decimal(15, 6) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `threshold_qta` `threshold_qta` decimal(15, 6) NOT NULL;

INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name`='Preventivi'), 1, 'Preventivo (solo totale)', 'Preventivo (solo totale)', 'Preventivo num. {numero} del {data}', 'preventivi', 'idpreventivo', '{\"pricing\":false, \"show_only_total\":true}', 'fa fa-print', '', '', 0, 0, 1, 1);

-- Unificazione righe e articoli interventi
ALTER TABLE `in_righe_interventi` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`;
ALTER TABLE `in_righe_interventi` ADD `idimpianto` int(11);
ALTER TABLE `in_righe_interventi` ADD `old_id` int(11);

-- Agli articoli utilizzati negli interventi che fanno riferimento ad articoli eliminati assegno l'articolo fittizio DELETED
INSERT INTO `mg_articoli` (`id`, `codice`, `descrizione`, `um`, `abilita_serial`, `immagine`, `note`, `qta`, `threshold_qta`, `ubicazione`, `prezzo_acquisto`, `prezzo_vendita`, `idiva_vendita`, `gg_garanzia`, `peso_lordo`, `volume`, `componente_filename`, `contenuto`, `attivo`, `created_at`, `id_categoria`, `id_sottocategoria`, `servizio`, `idconto_vendita`, `idconto_acquisto`, `deleted_at`, `barcode`, `id_fornitore`) VALUES (NULL, 'DELETED', 'ARTICOLO RIMOSSO', '', '0', NULL, '', '0', '0', '', '0', '0', '0', '0', '0', '0', '', '', '0', CURRENT_TIMESTAMP, 0, NULL, '1', NULL, NULL, NULL, NULL, NULL);

UPDATE `mg_articoli_interventi` SET `idarticolo` = (SELECT `id` FROM `mg_articoli` WHERE `codice` = 'DELETED' )  WHERE `idarticolo` NOT IN (SELECT `id` FROM `mg_articoli`);

INSERT INTO `in_righe_interventi` (`old_id`, `idarticolo`, `idintervento`, `is_descrizione`, `is_sconto`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto`) SELECT `id`, `idarticolo`, `idintervento`, `is_descrizione`, `is_sconto`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto` FROM `mg_articoli_interventi`;

ALTER TABLE `co_promemoria_righe` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`, ADD `idimpianto` int(11), ADD `idarticolo` int(11), ADD FOREIGN KEY  (`idarticolo`) REFERENCES `mg_articoli`(`id`) ON DELETE SET NULL, CHANGE `um` `um` varchar(25);
ALTER TABLE `co_promemoria_righe` ADD `is_descrizione` TINYINT(1) DEFAULT FALSE NOT NULL, ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;

INSERT INTO `co_promemoria_righe` (`idarticolo`, `id_promemoria`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto`) SELECT `idarticolo`, `id_promemoria`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto` FROM `co_promemoria_articoli`;

ALTER TABLE `co_righe_documenti` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `co_righe_preventivi` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `co_righe_contratti` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `dt_righe_ddt` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `or_righe_ordini` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_percentuale`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `in_righe_interventi` CHANGE `prezzo_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    CHANGE `prezzo_vendita` `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `co_promemoria_righe` CHANGE `prezzo_acquisto` `costo_unitario` decimal(15,6) NOT NULL AFTER `qta`,
    CHANGE `prezzo_vendita` `prezzo_unitario` decimal(15,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(15,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(15,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(15,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(15,6) NOT NULL AFTER `sconto_iva_unitario`;

UPDATE `co_righe_documenti` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario` = `subtotale` / `qta`,
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `co_righe_preventivi` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario` = `subtotale` / `qta`,
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `co_righe_contratti` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario` = `subtotale` / `qta`,
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `dt_righe_ddt` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario` = `subtotale` / `qta`,
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `or_righe_ordini` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario` = `subtotale` / `qta`,
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `in_righe_interventi` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `co_promemoria_righe` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;

ALTER TABLE `co_promemoria_righe` RENAME TO `co_righe_promemoria`;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE `mg_articoli_interventi`;
DROP TABLE `co_promemoria_articoli`;
SET FOREIGN_KEY_CHECKS=1;

ALTER TABLE `co_righe_promemoria` ADD `original_id` int(11), ADD `original_type` varchar(255);
ALTER TABLE `in_righe_interventi` ADD `original_id` int(11), ADD `original_type` varchar(255);

-- Aggiunta supporto a prezzi ivati
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`, `help`) VALUES (NULL, 'Utilizza prezzi di vendita comprensivi di IVA', '0', 'boolean', '1', 'Fatturazione', CURRENT_TIMESTAMP, NULL, 'Abilita la gestione con importi ivati per i prezzi di vendita.');

-- Fix plugin "Pianificazione fatturazione"
UPDATE `zz_plugins` SET `options` = 'custom', `script` = '', `directory` = 'pianificazione_fatturazione' WHERE `name` = 'Pianificazione fatturazione';
DROP TABLE `co_ordiniservizio_vociservizio`;
ALTER TABLE `co_ordiniservizio_pianificazionefatture` RENAME TO `co_fatturazione_contratti`;

-- Introduzione segmento scadenzario completo (su periodo temporale) il quale contempla tutte le scadenze (anche quelle chiuse)
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenziaro completo', '(`co_scadenziario`.`scadenza` BETWEEN ''|period_start|'' AND ''|period_end|'' )', 'WHR', '####', '', 0, 0, 0, 0);

-- Attiva scrociatoie da tastiera
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Attiva scorciatoie da tastiera', '1', 'boolean', '1', 'Generali', NULL, NULL);

-- Disattivazione totali prezzi articoli
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`summable` = 0 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Prezzo di acquisto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`summable` = 0 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Prezzo di vendita';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`summable` = 0 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Prezzo vendita ivato';

-- Introduzione modulo Stampe
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stampe', 'Stampe', 'stampe', 'SELECT |select| FROM `zz_prints` WHERE 1=1 HAVING 2=2', '', 'fa fa-print', '2.4.14', '2.4.14', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Nome del file', 'filename', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Titolo', 'title', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'id', 'id', 1, 1, 0, 0, 0);

-- Permetto valore null per id_categoria articoli
ALTER TABLE `mg_articoli` CHANGE `id_categoria` `id_categoria` INT(11) NULL;

-- Correzione totali per le Viste
DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Imponibile';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Totale ivato', 'righe.totale', 9, 1, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Netto a pagare', 'righe.totale + rivalsainps + iva_rivalsainps - ritenutaacconto', 10, 1, 1, 1, 1);

DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Imponibile';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Totale ivato', 'righe.totale', 6, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Netto a pagare', 'righe.totale + rivalsainps + iva_rivalsainps - ritenutaacconto', 7, 1, 1, 1, 1);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'righe.totale_imponibile' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 0);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'righe.totale_imponibile' WHERE `zz_modules`.`name` = 'Preventivi' AND `zz_views`.`name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 0);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'righe.totale_imponibile' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Totale ivato', 'righe.totale', 9, 1, 1, 1, 0);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'righe.totale_imponibile' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Totale ivato', 'righe.totale', 9, 1, 1, 1, 0);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'righe.totale_imponibile' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 0);
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'righe.totale_imponibile' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'Totale';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 0);

-- Aggiunta gestione dinamica dei movimenti degli Articoli
ALTER TABLE `mg_movimenti` ADD `reference_id` int(11), ADD `reference_type` varchar(255);
UPDATE `mg_movimenti` SET `reference_id`  = `iddocumento`, `reference_type` = 'Modules\\Fatture\\Fattura' WHERE `iddocumento` IS NOT NULL AND `iddocumento` != 0;
UPDATE `mg_movimenti` SET `reference_id`  = `idintervento`, `reference_type` = 'Modules\\Interventi\\Intervento' WHERE `idintervento` IS NOT NULL AND `idintervento` != 0;
UPDATE `mg_movimenti` SET `reference_id`  = `idddt`, `reference_type` = 'Modules\\DDT\\DDT' WHERE `idddt` IS NOT NULL AND `idddt` != 0;

-- Descrizioni movimenti predefinite per l'aggiunta dal modulo Movimenti
CREATE TABLE IF NOT EXISTS `mg_causali_movimenti` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `descrizione` varchar(255) NOT NULL,
    `movimento_carico` BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `mg_causali_movimenti` (`id`, `nome`, `descrizione`, `movimento_carico`) VALUES
(NULL, 'Carico', 'Carico manuale', '1'),
(NULL, 'Scarico', 'Scarico manuale', '0');

-- Introduzione modulo Movimenti predefiniti
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Causali movimenti', 'Causali movimenti', 'causali_movimenti', 'SELECT |select| FROM `mg_causali_movimenti` WHERE 1=1 HAVING 2=2', '', 'fa fa-truck', '2.4.14', '2.4.14', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali movimenti'), 'Movimento di carico', 'IF(movimento_carico, ''Si'',  ''No'')', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali movimenti'), 'Descrizione', 'descrizione', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali movimenti'), 'Nome', 'nome', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali movimenti'), 'id', 'id', 1, 1, 0, 0, 0);

-- Miglioramento della cache interna
CREATE TABLE IF NOT EXISTS `zz_cache` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `valid_time` VARCHAR(255),
    `expire_at` timestamp NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_cache` (`id`, `name`, `content`, `valid_time`, `expire_at`) VALUES
(NULL, 'Ricevute Elettroniche', '', '1 day', NULL),
(NULL, 'Ricevute Elettroniche importate', '', '1 day', NULL),
(NULL, 'Fatture Elettroniche', '', '1 day', NULL),
(NULL, 'Ultima versione di OpenSTAManager disponibile', '', '7 day', NULL);

DROP TABLE IF EXISTS `zz_hook_cache`;
ALTER TABLE `zz_hooks` DROP `frequency`;

-- Fix nome hook Aggiornamenti
UPDATE `zz_hooks` SET `name` = 'Aggiornamenti' WHERE `class` = 'Modules\\Aggiornamenti\\UpdateHook';

-- Aggiunta stampa Barcode
INSERT INTO `zz_prints` (`id_module`, `name`, `title`, `filename`, `directory`, `icon`, `options`, `predefined`, `previous`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Barcode', 'Barcode', 'Barcode', 'barcode', 'fa fa-print', '{"width": 54, "height": 20, "format": [64, 55]}', 1, '', 1, 1);

-- Disabilito modulo voci di servizio INUTILIZZATO
UPDATE `zz_modules` SET `enabled` = '0' WHERE `zz_modules`.`name` = 'Voci di servizio';

ALTER TABLE `in_statiintervento` CHANGE `completato` `is_completato` TINYINT(1) NOT NULL;

-- Aggiunto flag per stabilire se un intervento è fatturabile
ALTER TABLE `in_statiintervento` ADD `is_fatturabile` TINYINT(1) NOT NULL AFTER `is_completato`;

UPDATE `in_statiintervento` SET `is_fatturabile` = '1' WHERE `in_statiintervento`.`codice` = 'OK';

-- Lista con indirizzi email validi per ogni anagrafica caricata a sistema
INSERT INTO `em_lists` (`id`, `name`, `description`, `query`, `deleted_at`) VALUES
(NULL, 'Tutte le anagrafiche', 'Indirizzi email validi per ogni anagrafica caricata a sistema', 'SELECT idanagrafica AS id FROM an_anagrafiche WHERE email != \'\'', NULL);

-- Correzioni minori su widget
UPDATE `zz_widgets` SET `more_link` = './modules/interventi/widgets/interventi_da_programmare.php' WHERE `name` = 'Attività nello stato da programmare';
UPDATE `zz_widgets` SET `more_link` = './modules/interventi/widgets/interventi_da_pianificare.php' WHERE `name` = 'Attività da pianificare';

-- Cambio formato quantità in vista, per migliorare l'eventuale esportazione csv
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'qta', `format` = 1 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Q.tà';
-- Aggiornamento API
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'retrieve', 'impianti', 'Modules\\Impianti\\API\\v1\\Impianti', '1'),
(NULL, 'v1', 'retrieve', 'impianti_intervento', 'Modules\\Interventi\\API\\v1\\Impianti', '1'),
(NULL, 'v1', 'create', 'impianti_intervento', 'Modules\\Interventi\\API\\v1\\Impianti', '1'),
(NULL, 'v1', 'retrieve', 'rapportino', 'Modules\\Interventi\\API\\v1\\Rapportino', '1'),
(NULL, 'v1', 'create', 'rapportino', 'Modules\\Interventi\\API\\v1\\Rapportino', '1');

-- Aggiunta vincolo a pianificazione rate contratti
DELETE FROM `co_fatturazione_contratti` WHERE `idcontratto` NOT IN(SELECT `id` FROM `co_contratti`);
ALTER TABLE `co_fatturazione_contratti` ADD CONSTRAINT `fk_contratti_fatturazione` FOREIGN KEY (`idcontratto`) REFERENCES `co_contratti`(`id`) ON DELETE CASCADE;

-- Aggiunta flag per gestione revisioni per stato
ALTER TABLE `co_statipreventivi` ADD `is_revisionabile` BOOLEAN NOT NULL AFTER `is_pianificabile`;

-- Impostazione flag revisionabile per i preventivi non completati o rifiutati
UPDATE `co_statipreventivi` SET `is_revisionabile` = 1 WHERE `is_completato` = 0 OR `descrizione` = 'Rifiutato';

-- Spostamento moduli "Stati preventivi" e "Stati contratti" sotto "Tabelle"
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Stati dei preventivi' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Stati dei contratti' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunta campo per salvare il numero di revisione del preventivo
ALTER TABLE `co_preventivi` ADD `numero_revision` INT NOT NULL AFTER `default_revision`;

-- Riordinamento campi Fatture di vendita
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order` = 11, `zz_views`.`query` = 'co_statidocumento.icona' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_Stato';
-- Aggiornamento stampa inventario
UPDATE `zz_widgets` SET `more_link` = './modules/articoli/widgets/stampa_inventario.php', `more_link_type` = 'popup' WHERE `zz_widgets`.`name` = 'Stampa inventario';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_categorie.nome' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Categoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'sottocategorie.nome' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Sottocategoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Fornitore';
-- Fix id ambigui modulo articoli
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.id' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`query` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.codice' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`query` = 'codice';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.descrizione' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`query` = 'descrizione';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.note' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`query` = 'note';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'in_statiintervento.descrizione' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'in_interventi.descrizione' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Descrizione';
-- Aggiunta colonna Rif. sede per attività (se diversa dalla sede legale)
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Sede', 'sede_destinazione.info', 4, 1, 0, 0, 1);

-- Aggiornamento widget Rate contrattuali
UPDATE `zz_widgets` SET `more_link` = './plugins/pianificazione_fatturazione/widgets/rate_contrattuali.php' WHERE `zz_widgets`.`name` = 'Rate contrattuali';

-- Aggiornamento widget Promemoria contratti da pianificare
UPDATE `zz_widgets` SET `name` = 'Promemoria contratti da pianificare' WHERE `zz_widgets`.`name` = 'Interventi da pianificare';
UPDATE `zz_widgets` SET `more_link` = './plugins/pianificazione_interventi/widgets/promemoria_da_pianificare.php' WHERE `zz_widgets`.`name` = 'Promemoria contratti da pianificare';

-- Rimozione completa co_ordiniservizio
DROP TABLE IF EXISTS `co_ordiniservizio`;
DROP TABLE IF EXISTS `co_ordiniservizio_vociservizio`;
DELETE FROM `zz_widgets` WHERE `name` = 'Ordini di servizio da impostare';

--
-- Aggiornamento FE in base alla normativa del 28/02/2020
--
ALTER TABLE `co_iva` DROP FOREIGN KEY `co_iva_ibfk_1`;
ALTER TABLE `fe_natura` MODIFY `codice` VARCHAR(5) NOT NULL;
ALTER TABLE `co_iva` ADD CONSTRAINT `co_iva_ibfk_1` FOREIGN KEY (`codice_natura_fe`) REFERENCES `fe_natura`(`codice`) ON DELETE CASCADE;

-- Nuove nature IVA
INSERT INTO `fe_natura` (`codice`, `descrizione`) VALUES
('N2.1', 'non soggette ad IVA ai sensi degli artt. Da 7 a 7-septies del DPR 633/72'),
('N2.2', 'non soggette - altri casi'),
('N3.1', 'non imponibili - esportazioni'),
('N3.2', 'non imponibili - cessioni intracomunitarie'),
('N3.3', 'non imponibili - cessioni verso San Marino'),
('N3.4', 'non imponibili - operazioni assimilate alle cessioni all\'esportazione'),
('N3.5', 'non imponibili - a seguito di dichiarazioni d\'intento'),
('N3.6', 'non imponibili - altre operazioni che non concorrono alla formazione del plafond'),
('N6.1', 'inversione contabile - cessione di rottami e altri materiali di recupero'),
('N6.2', 'inversione contabile - cessione di oro e argento pure'),
('N6.3', 'inversione contabile - subappalto nel settore edile'),
('N6.4', 'inversione contabile - cessione di fabbricati'),
('N6.5', 'inversione contabile - cessione di telefoni cellulari'),
('N6.6', 'inversione contabile - cessione di prodotti elettronici'),
('N6.7', 'inversione contabile - prestazioni comparto edile e settori connessi'),
('N6.8', 'inversione contabile - operazioni settore energetico'),
('N6.9', 'inversione contabile - altri casi');

-- Nuove aliquote di default collegate alle nuove nature IVA
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `esente`, `codice_natura_fe`, `esigibilita`, `default`) VALUES
(NULL, 'Non soggetta ad IVA ai sensi degli artt. Da 7 a 7-septies del DPR 633/72', '0', '1', 'N2.1', 'I', '1'),
(NULL, 'Non soggetta - altri casi', '0', '1', 'N2.2', 'I', '1'),
(NULL, 'Non imponibile - esportazioni', '0', '1', 'N3.1', 'I', '1'),
(NULL, 'Non imponibile - cessioni intracomunitarie', '0', '1', 'N3.2', 'I', '1'),
(NULL, 'Non imponibile - cessioni verso San Marino', '0', '1', 'N3.3', 'I', '1'),
(NULL, 'Non imponibile - operazioni assimilate alle cessioni all\'esportazione', '0', '1', 'N3.4', 'I', '1'),
(NULL, 'Non imponibile - a seguito di dichiarazioni d\'intento', '0', '1', 'N3.5', 'I', '1'),
(NULL, 'Non imponibile - altre operazioni che non concorrono alla formazione del plafond', '0', '1', 'N3.6', 'I', '1'),
(NULL, 'Inversione contabile - cessione di rottami e altri materiali di recupero', '0', '1', 'N6.1', 'I', '1'),
(NULL, 'Inversione contabile - cessione di oro e argento pure', '0', '1', 'N6.2', 'I', '1'),
(NULL, 'Inversione contabile - subappalto nel settore edile', '0', '1', 'N6.3', 'I', '1'),
(NULL, 'Inversione contabile - cessione di fabbricati', '0', '1', 'N6.4', 'I', '1'),
(NULL, 'Inversione contabile - cessione di telefoni cellulari', '0', '1', 'N6.5', 'I', '1'),
(NULL, 'Inversione contabile - cessione di prodotti elettronici', '0', '1', 'N6.6', 'I', '1'),
(NULL, 'Inversione contabile - prestazioni comparto edile e settori connessi', '0', '1', 'N6.7', 'I', '1'),
(NULL, 'Inversione contabile - operazioni settore energetico', '0', '1', 'N6.8', 'I', '1'),
(NULL, 'Inversione contabile - altri casi', '0', '1', 'N6.9', 'I', '1');

-- Nuovi tipi di documento
INSERT INTO `fe_tipi_documento` (`codice`, `descrizione`) VALUES
('TD16', 'Integrazione fattura reverse charge interno'),
('TD17', 'Integrazione/autofattura per acquisto servizi dall\'estero'),
('TD18', 'Integrazione per acquisto di beni intracomunitari'),
('TD19', 'Integrazione/autofattura per acquisto di beni ex art.17 c.2 DPR 633/72'),
('TD20', 'Autofattura per regolarizzazione e integrazione delle fatture (art.6 c.8 d.lgs. 471/97 o art.46 c.5 D.L. 331/93)'),
('TD21', 'Autofattura per splafonamento'),
('TD22', 'Estrazione beni da deposito IVA'),
('TD23', 'Estrazione beni da deposito IVA con versamento dell\'IVA'),
('TD24', 'Fattura differita di cui all\'art.21, comma 4, lett. a)'),
('TD25', 'Fattura differita di cui all\'art.21, comma 4, terzo periodo lett. b)'),
('TD26', 'Cessione di beni ammortizzabili e per passaggi interni (ex art.36 DPR 633/72)'),
('TD27', 'Fattura per autoconsumo o per cessioni gratuite senza rivalsa');

INSERT INTO `co_tipidocumento` (`id`, `descrizione`, `dir`, `reversed`, `codice_tipo_documento_fe`) VALUES
(NULL, 'Integrazione fattura reverse charge interno', 'entrata', '0', 'TD16'),
(NULL, 'Integrazione/autofattura per acquisto servizi dall\'estero', 'entrata', '0', 'TD17'),
(NULL, 'Integrazione per acquisto di beni intracomunitari', 'entrata', '0', 'TD18'),
(NULL, 'Integrazione/autofattura per acquisto di beni ex art.17 c.2 DPR 633/72', 'entrata', '0', 'TD19'),
(NULL, 'Autofattura per regolarizzazione e integrazione delle fatture (art.6 c.8 d.lgs. 471/97 o art.46 c.5 D.L. 331/93)', 'entrata', '0', 'TD20'),
(NULL, 'Autofattura per splafonamento', 'entrata', '0', 'TD21'),
(NULL, 'Estrazione beni da deposito IVA', 'entrata', '0', 'TD22'),
(NULL, 'Estrazione beni da deposito IVA con versamento dell\'IVA', 'entrata', '0', 'TD23'),
(NULL, 'Cessione di beni ammortizzabili e per passaggi interni (ex art.36 DPR 633/72)', 'entrata', '0', 'TD26'),
(NULL, 'Fattura per autoconsumo o per cessioni gratuite senza rivalsa', 'entrata', '0', 'TD27');

-- Aggiornamento tipo documento FE per fatture differite
UPDATE `co_tipidocumento` SET `codice_tipo_documento_fe` = 'TD24' WHERE `descrizione` IN('Fattura differita di acquisto', 'Fattura differita di vendita');
