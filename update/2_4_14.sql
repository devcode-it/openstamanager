-- Eliminazione impostazione non utilizzata
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Modifica Viste di default';

UPDATE `zz_settings` SET `sezione` = 'Backup' WHERE `zz_settings`.`nome` = 'Numero di backup da mantenere';
UPDATE `zz_settings` SET `sezione` = 'Backup' WHERE `zz_settings`.`nome` = 'Backup automatico';

UPDATE `zz_settings` SET `sezione` = 'Aggiornamenti' WHERE `zz_settings`.`nome` = 'Attiva aggiornamenti';
UPDATE `zz_settings` SET `sezione` = 'API' WHERE `zz_settings`.`nome` = 'apilayer API key for VAT number';
UPDATE `zz_settings` SET `sezione` = 'API' WHERE `zz_settings`.`nome` = 'Google Maps API key';

-- Abilita la possibilità di ripristinare backup da archivi esterni al gestionale
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES (NULL, 'Permetti il ripristino di backup da file esterni', '1', 'boolean', '0', 'Backup', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, 'Abilita la possibilità di ripristinare backup da archivi esterni al gestionale.');


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

INSERT INTO `in_righe_interventi` (`old_id`, `idarticolo`, `idintervento`, `is_descrizione`, `is_sconto`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto`) SELECT `id`, `idarticolo`, `idintervento`, `is_descrizione`, `is_sconto`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto` FROM `mg_articoli_interventi`;

ALTER TABLE `co_promemoria_righe` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`, ADD `idimpianto` int(11), ADD `idarticolo` int(11), ADD FOREIGN KEY  (`idarticolo`) REFERENCES `mg_articoli`(`id`) ON DELETE SET NULL, CHANGE `um` `um` varchar(25);
ALTER TABLE `co_promemoria_righe` ADD `is_descrizione` TINYINT(1) DEFAULT FALSE NOT NULL, ADD `is_sconto` BOOLEAN DEFAULT FALSE NOT NULL AFTER `is_descrizione`;

INSERT INTO `co_promemoria_righe` (`idarticolo`, `id_promemoria`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto`) SELECT `idarticolo`, `id_promemoria`, `descrizione`, `prezzo_acquisto`, `prezzo_vendita`, `sconto`, `sconto_unitario`, `tipo_sconto`, `idiva`, `desc_iva`, `iva`, `qta`, `um`, `abilita_serial`, `idimpianto` FROM `co_promemoria_articoli`;

ALTER TABLE `co_righe_documenti` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `co_righe_preventivi` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `co_righe_contratti` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `dt_righe_ddt` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `or_righe_ordini` CHANGE `prezzo_unitario_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    ADD `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_percentuale`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `in_righe_interventi` CHANGE `prezzo_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    CHANGE `prezzo_vendita` `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;
ALTER TABLE `co_promemoria_righe` CHANGE `prezzo_acquisto` `costo_unitario` decimal(12,6) NOT NULL AFTER `qta`,
    CHANGE `prezzo_vendita` `prezzo_unitario` decimal(12,6) NOT NULL AFTER `costo_unitario`,
    ADD `iva_unitaria` decimal(12,6) NOT NULL AFTER `prezzo_unitario`,
    ADD `prezzo_unitario_ivato` decimal(12,6) NOT NULL AFTER `iva_unitaria`,
    ADD `sconto_percentuale` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_iva_unitario` decimal(12,6) NOT NULL AFTER `sconto_unitario`,
    ADD `sconto_unitario_ivato` decimal(12,6) NOT NULL AFTER `sconto_iva_unitario`;

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
    `sconto_unitario_ivato` = `sconto_unitario`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `in_righe_interventi` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `iva_unitaria` = `iva` / `qta`,
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
    `sconto_percentuale` = IF(`tipo_sconto` = 'PRC', `sconto_unitario`, 0),
    `sconto_unitario` = IF(`tipo_sconto` = 'PRC', `sconto` / `qta`, `sconto_unitario`),
    `sconto_unitario_ivato` = `sconto_unitario`;
UPDATE `co_promemoria_righe` SET `qta` = IF(`qta` = 0, 1, `qta`),
    `prezzo_unitario_ivato` = `prezzo_unitario` + `iva_unitaria`,
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
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES (NULL, 'Utilizza prezzi di vendita con IVA incorporata', '0', 'boolean', '1', 'Fatturazione', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, 'Abilita la gestione degli importi ivati per i documenti di vendita.');

-- Fix plugin "Pianificazione fatturazione"
UPDATE `zz_plugins` SET `options` = 'custom', `script` = '', `directory` = 'pianificazione_fatturazione' WHERE `name` = 'Pianificazione fatturazione';
DROP TABLE `co_ordiniservizio_vociservizio`;
ALTER TABLE `co_ordiniservizio_pianificazionefatture` RENAME TO `co_fatturazione_contratti`;

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_fatturazione_contratti WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND co_fatturazione_contratti.iddocumento=0' WHERE `name` = 'Rate contrattuali';
