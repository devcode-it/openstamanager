UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND co_contratti.id NOT IN (SELECT idcontratto_prev FROM co_contratti contratti) HAVING (ore_rimanenti <= ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) <= ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

ALTER TABLE `zz_settings` CHANGE `valore` `valore` TEXT NULL DEFAULT NULL;
-- Invio sollecito automatico
INSERT INTO `zz_tasks` (`name`, `class`, `expression`) VALUES
('Solleciti scadenze', 'Modules\\Scadenzario\\SollecitoTask', '0 8 * * *');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Invio solleciti in automatico', '0', 'boolean', '1', 'Scadenzario', '1', 'Invia automaticamente delle mail di sollecito secondo le tempistiche definite nelle impostazioni');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Template email invio sollecito', (SELECT `id` FROM `em_templates` WHERE `name`="Sollecito di pagamento"), 'query=SELECT id, name AS descrizione FROM em_templates WHERE deleted_at IS NULL ORDER BY descrizione', '1', 'Scadenzario', '2', NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Ritardo in giorni della scadenza della fattura per invio sollecito pagamento", '12', 'integer', '1', 'Scadenzario', '3', '');
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Ritardo in giorni dall'ultima email per invio sollecito pagamento", '10', 'integer', '1', 'Scadenzario', '4', '');

-- Aggiunta colonna "Gruppi con accesso" in vista "Segmenti"
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `zz_segments`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_segments`.`id_module`
    INNER JOIN (SELECT GROUP_CONCAT(`zz_groups`.`nome` ORDER BY `zz_groups`.`nome`  SEPARATOR ', ') AS `gruppi`, `zz_group_segment`.`id_segment` FROM `zz_group_segment` INNER JOIN `zz_groups` ON `zz_groups`.`id` = `zz_group_segment`.`id_gruppo` GROUP BY  `zz_group_segment`.`id_segment`) AS `t` ON `t`.`id_segment` = `zz_segments`.`id`
WHERE
    1=1
HAVING
    2=2
ORDER BY `zz_segments`.`name`,
    `zz_segments`.`id_module`" WHERE `name` = 'Segmenti';

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
