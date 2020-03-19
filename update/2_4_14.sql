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
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES (NULL, 'Utilizza prezzi di vendita comprensivi di IVA', '0', 'boolean', '1', 'Fatturazione', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, 'Abilita la gestione con importi ivati per i prezzi di vendita.');

-- Fix plugin "Pianificazione fatturazione"
UPDATE `zz_plugins` SET `options` = 'custom', `script` = '', `directory` = 'pianificazione_fatturazione' WHERE `name` = 'Pianificazione fatturazione';
DROP TABLE `co_ordiniservizio_vociservizio`;
ALTER TABLE `co_ordiniservizio_pianificazionefatture` RENAME TO `co_fatturazione_contratti`;

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_fatturazione_contratti WHERE idcontratto IN( SELECT id FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND co_fatturazione_contratti.iddocumento=0' WHERE `name` = 'Rate contrattuali';

-- Introduzione segmento scadenzario completo (su periodo temporale) il quale contempla tutte le scadenze (anche quelle chiuse)
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenziaro completo', '(`co_scadenziario`.`scadenza` BETWEEN ''|period_start|'' AND ''|period_end|'' )', 'WHR', '####', '', 0, 0, 0, 0);

-- Attiva scrociatoie da tastiera
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Attiva scorciatoie da tastiera', '1', 'boolean', '1', 'Generali', NULL, NULL);

-- Correzione conteggi magazzino --
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\" \", REPLACE(REPLACE(REPLACE(FORMAT(SUM(prezzo_acquisto*qta),2), \",\", \"#\"), \".\", \",\"), \"#\", \".\"), \"&euro;\") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND attivo=1' WHERE `zz_widgets`.`name` = 'Valore magazzino';
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\" \", REPLACE(REPLACE(REPLACE(FORMAT(SUM(qta),2), \",\", \"#\"), \".\", \",\"), \"#\", \".\"), \"unit&agrave;\") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND attivo=1' WHERE `zz_widgets`.`name` = 'Articoli in magazzino';

-- Disattivazione totali prezzi articoli
UPDATE `zz_views` SET `summable` = '0' WHERE `zz_views`.`id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Articoli') AND `zz_views`.`name`='Prezzo di acquisto';
UPDATE `zz_views` SET `summable` = '0' WHERE `zz_views`.`id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Articoli') AND `zz_views`.`name`='Prezzo di vendita';
UPDATE `zz_views` SET `summable` = '0' WHERE `zz_views`.`id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Articoli') AND `zz_views`.`name`='Prezzo vendita ivato';

-- Introduzione modulo Stampe
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stampe', 'Stampe', 'stampe', 'SELECT |select| FROM `zz_prints` WHERE 1=1 HAVING 2=2', '', 'fa fa-print', '2.4.14', '2.4.14', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Strumenti'), '1', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Nome del file', 'filename', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Titolo', 'title', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'id', 'id', 1, 1, 0, 0, 0);

-- Fix widget Attività confermate
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE in_interventi.idstatointervento = (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.codice=''WIP'') ORDER BY in_interventi.data_richiesta ASC' WHERE `name` = 'Attività confermate';

-- Permetto valore null per id_categoria articoli
ALTER TABLE `mg_articoli` CHANGE `id_categoria` `id_categoria` INT(11) NULL;

-- Correzione totali per le Viste

-- Fatture di vendita
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT OUTER JOIN (
        SELECT `iddocumento`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (
        SELECT `numero_esterno`, `id_segment`
        FROM `co_documenti`
        WHERE `co_documenti`.`idtipodocumento` IN(SELECT `id` FROM `co_tipidocumento` WHERE `dir` = ''entrata'') |date_period(`co_documenti`.`data`)| AND `numero_esterno` != ''''
        GROUP BY `id_segment`, `numero_esterno`
        HAVING COUNT(`numero_esterno`) > 1
    ) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment`
    LEFT OUTER JOIN (
        SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`
        FROM `zz_operations`
            INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`
            INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`
            INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`
        WHERE `zz_modules`.`name` = ''Fatture di vendita'' AND `zz_operations`.`op` = ''send-email''
        GROUP BY `zz_operations`.`id_record`
    ) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
WHERE 1=1 AND `dir` = ''entrata'' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Fatture di vendita';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Totale';
DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Imponibile';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Totale ivato', 'righe.totale', 9, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Netto a pagare', 'righe.totale + rivalsainps + iva_rivalsainps - ritenutaacconto', 10, 1, 1, 1, 1);

-- Fatture di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT OUTER JOIN (
        SELECT `iddocumento`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_documenti`
        GROUP BY `iddocumento`
    ) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
WHERE 1=1 AND `dir` = ''uscita'' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero_esterno` = '''', `co_documenti`.`numero`, `co_documenti`.`numero_esterno`) AS UNSIGNED) DESC' WHERE `name` = 'Fatture di acquisto';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Totale';
DELETE FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Imponibile';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Totale ivato', 'righe.totale', 6, 1, 1, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Netto a pagare', 'righe.totale + rivalsainps + iva_rivalsainps - ritenutaacconto', 7, 1, 1, 1, 1);

-- Contratti
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `co_contratti`
    INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT OUTER JOIN (
        SELECT `idcontratto`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_contratti`
        GROUP BY `idcontratto`
    ) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT OUTER JOIN (
        SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '''', CONCAT('' - '', nome), '''')) SEPARATOR ''<br>'') AS descrizione, my_impianti_contratti.idcontratto
        FROM my_impianti
            INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto
        GROUP BY my_impianti_contratti.idcontratto
    ) AS impianti ON impianti.idcontratto = co_contratti.id
WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')|
HAVING 2=2
ORDER BY `co_contratti`.`id` DESC' WHERE `name` = 'Contratti';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti') AND `name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 1);

-- Preventivi
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `co_preventivi`
    INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT OUTER JOIN (
        SELECT `idpreventivo`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_preventivi`
        GROUP BY `idpreventivo`
    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
WHERE 1=1 |date_period(custom,''|period_start|'' >= `data_bozza` AND ''|period_start|'' <= `data_conclusione`,''|period_end|'' >= `data_bozza` AND ''|period_end|'' <= `data_conclusione`,`data_bozza` >= ''|period_start|'' AND `data_bozza` <= ''|period_end|'',`data_conclusione` >= ''|period_start|'' AND `data_conclusione` <= ''|period_end|'',`data_bozza` >= ''|period_start|'' AND `data_conclusione` = ''0000-00-00'')|
HAVING 2=2
ORDER BY `co_preventivi`.`id` DESC' WHERE `name` = 'Preventivi';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi') AND `name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 1);

-- Ddt di acquisto
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt`
    INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT OUTER JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT OUTER JOIN (
        SELECT `idddt`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `dt_righe_ddt`
        GROUP BY `idddt`
    ) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `name` = 'Ddt di acquisto';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto') AND `name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Totale ivato', 'righe.totale', 9, 1, 1, 1, 1);

-- Ddt di vendita
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `dt_ddt`
    INNER JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
    LEFT OUTER JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT OUTER JOIN (
        SELECT `idddt`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `dt_righe_ddt`
        GROUP BY `idddt`
    ) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `name` = 'Ddt di vendita';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita') AND `name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Totale ivato', 'righe.totale', 9, 1, 1, 1, 1);

-- Ordini cliente
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `or_ordini`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT OUTER JOIN (
        SELECT `idordine`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `or_righe_ordini`
        GROUP BY `idordine`
    ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Ordini cliente';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente') AND `name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 1);

-- Ordini fornitore
UPDATE `zz_modules` SET `options` = 'SELECT |select|
FROM `or_ordini`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT OUTER JOIN (
        SELECT `idordine`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `or_righe_ordini`
        GROUP BY `idordine`
    ) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)|
HAVING 2=2
ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Ordini fornitore';

UPDATE `zz_views` SET `query` = 'righe.totale_imponibile' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore') AND `name` = 'Totale';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Totale ivato', 'righe.totale', 5, 1, 1, 1, 1);

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

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato,
       ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti,
       data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo
       FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL
       HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo))' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';


UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE id NOT IN (SELECT idintervento FROM in_interventi_tecnici) AND idstatointervento IN (SELECT idstatointervento FROM in_statiintervento WHERE is_completato = 0) ' WHERE `zz_widgets`.`name` = 'Attività da pianificare';


-- Lista con indirizzi email validi per ogni anagrafica caricata a sistema
INSERT INTO `em_lists` (`id`, `name`, `description`, `query`, `deleted_at`) VALUES
(NULL, 'Tutte le anagrafiche', 'Indirizzi email validi per ogni anagrafica caricata a sistema', 'SELECT idanagrafica AS id FROM an_anagrafiche WHERE email != \'\'', NULL);

-- Correzioni minori su widget
UPDATE `zz_widgets` SET `more_link` = './modules/interventi/widgets/interventi_da_programmare.php' WHERE `name` = 'Attività nello stato da programmare';
UPDATE `zz_widgets` SET `more_link` = './modules/interventi/widgets/interventi_da_pianificare.php' WHERE `name` = 'Attività da pianificare';

-- Cambio formato quantità in vista, per migliorare l'eventuale esportazione csv
UPDATE `zz_views` SET `query` = 'qta', `format` = 1 WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli') AND `name` = 'Q.tà';
