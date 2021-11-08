-- Aggiunto help per impostazione
UPDATE `zz_settings` SET `help` = 'Documenti di Vendita quali Fatture, DDT e Attività' WHERE `zz_settings`.`nome` = 'Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita'; 

-- Aggiunto flag calcola km
ALTER TABLE `in_tipiintervento` ADD `calcola_km` TINYINT NOT NULL AFTER `costo_diritto_chiamata_tecnico`;
UPDATE `in_tipiintervento` SET `calcola_km`=1;

-- Aggiunto colonna email in Attività
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_interventi`\nINNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`\nLEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`\nLEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`\nLEFT JOIN (\n    SELECT an_sedi.id, CONCAT(an_sedi.nomesede, \'<br>\',an_sedi.telefono, \'<br>\',an_sedi.cellulare,\'<br>\',an_sedi.citta, \' - \', an_sedi.indirizzo) AS info FROM an_sedi\n) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione\nLEFT JOIN (\n    SELECT co_righe_documenti.idintervento, CONCAT(\'Fatt. \', co_documenti.numero_esterno, \' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento\n) AS fattura ON fattura.idintervento = in_interventi.id\nLEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\n    FROM `zz_operations`\n    INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\n    INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\n    INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` \n    WHERE `zz_modules`.`name` = \'Interventi\' AND `zz_operations`.`op` = \'send-email\' \n    GROUP BY `zz_operations`.`id_record`) AS email ON email.id_record=in_interventi.id\nWHERE 1=1 |date_period(`orario_inizio`,`data_richiesta`)|\nGROUP BY `in_interventi`.`id`\nHAVING 2=2\nORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'icon_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'fa fa-envelope text-success\', \'\')', 18, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'icon_title_Inviata', 'IF(`email`.`id_email` IS NOT NULL, \'Inviata via email\', \'\')', 19, 1, 0, 0, '', '', 0, 0, 0);

-- Rimozione delle aliquote iva eliminate dalla lista
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_iva` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2' WHERE `name` = 'IVA';

-- Aggiunta colonna Codice in Combinazioni
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`, `created_at`, `updated_at`) VALUES
(442, 94, 'Codice', 'mg_combinazioni.codice', 1, 1, 0, 0, '', '', 1, 0, 0, '2021-11-08 10:17:47', '2021-11-08 10:18:07');

-- Aggiunta impostazione per impostare o meno il riferimento del documento
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Aggiungi riferimento tra documenti', '1', 'boolean', '1', 'Generali', '18', 'Permette l\'aggiunta del riferimento al documento nella descrizione della riga importata');