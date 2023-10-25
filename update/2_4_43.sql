-- Fix problemi integrità db per campo valore
ALTER TABLE `zz_settings` CHANGE `valore` `valore` TEXT NULL DEFAULT NULL;

-- Invio sollecito automatico
INSERT INTO `zz_tasks` (`name`, `class`, `expression`) VALUES
('Solleciti scadenze', 'Modules\\Scadenzario\\SollecitoTask', '0 8 * * *');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Invio solleciti in automatico', '0', 'boolean', '1', 'Scadenzario', '1', 'Invia automaticamente delle mail di sollecito secondo le tempistiche definite nelle impostazioni');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Template email invio sollecito', (SELECT `id` FROM `em_templates` WHERE `name`="Sollecito di pagamento"), 'query=SELECT id, name AS descrizione FROM em_templates WHERE deleted_at IS NULL ORDER BY descrizione', '1', 'Scadenzario', '2', NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Ritardo in giorni della scadenza della fattura per invio sollecito pagamento", '12', 'integer', '1', 'Scadenzario', '3', '');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Ritardo in giorni dall'ultima email per invio sollecito pagamento", '10', 'integer', '1', 'Scadenzario', '4', '');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Segmenti'), 'Gruppi con accesso', '`t`.`gruppi`', '6', '1', '0', '0', '0', NULL, NULL, '1', '0', '1');

-- Set NULL campi vuoti search_inside e order_by
UPDATE `zz_views` SET `search_inside` = NULL WHERE `search_inside` = '';
UPDATE `zz_views` SET `order_by` = NULL WHERE `order_by` = '';

-- Fix per anagrafiche senza ragione sociale
UPDATE `an_anagrafiche` SET `ragione_sociale` = CONCAT(cognome, " ", nome) WHERE `an_anagrafiche`.`ragione_sociale` = "";

-- Aggiunto options images su stampe preventivi e ordini per gestire la visualizzazione delle immagini
UPDATE `zz_prints` SET `options` = '{\"pricing\": false, \"last-page-footer\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (senza costi)";
UPDATE `zz_prints` SET `options` = '{\"pricing\": true, \"last-page-footer\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo";  
UPDATE `zz_prints` SET `options` = '{\"pricing\":true, \"hide-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (senza totali)"; 
UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"show-only-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (solo totale)";
UPDATE `zz_prints` SET `options` = '{\"pricing\": false, \"last-page-footer\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Ordine cliente (senza costi)"; 
UPDATE `zz_prints` SET `options` = '{\"pricing\": true, \"last-page-footer\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Ordine cliente";
UPDATE `zz_prints` SET `options` = '{\"pricing\": true, \"last-page-footer\": true, \"hide-codice\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Ordine cliente (senza codici)";
UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"images\": false}' WHERE `zz_prints`.`name` = "Ordine fornitore (senza costi)";
UPDATE `zz_prints` SET `options` = '{\"pricing\":true, \"images\": false}' WHERE `zz_prints`.`name` = "Ordine fornitore";
