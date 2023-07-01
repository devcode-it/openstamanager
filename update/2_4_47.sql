-- Aggiunta vincolo fra ddt e righe ddt
ALTER TABLE `dt_righe_ddt` ADD CONSTRAINT `dt_righe_ddt_ibfk_2` FOREIGN KEY (`idddt`) REFERENCES `dt_ddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Rimuovi avviso fatture estere', '0', 'boolean', '1', 'Fatturazione elettronica', NULL, "Abilitare per rimuovere l'avviso di fatture elettroniche estere da inviare, in caso le fatture elettroniche estere non vengano inviate.");

DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = "Mostra promemoria attività ai soli Tecnici assegnati";
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = "Inizio orario lavorativo";
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = "Fine orario lavorativo";

UPDATE `zz_settings` SET `help` = "Se abilitato viene effettuato un backup completo del gestionale secondo le impostazioni definite a database in zz_tasks (di default ogni giorno all'1)" WHERE `zz_settings`.`nome` = 'Backup automatico';

UPDATE `zz_settings` SET `editable` = 1 WHERE `zz_settings`.`nome` = 'Soft quota';
UPDATE `zz_settings` SET `help` = "Valore espresso in Gigabyte superato il quale verrà visualizzato un avviso di spazio in esaurimento." WHERE `zz_settings`.`nome` = 'Soft quota';

-- Rimozione google maps
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Google Maps API key';

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Tile server OpenStreetMap', 'https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', 'string', '1', 'Generali', NULL, NULL); 

-- Nuova colonna Giorni scadenza in Scadenzario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Scadenzario'), 'Scadenza giorni', 'DATEDIFF(co_scadenziario.scadenza,NOW())', 19, 1, 0, 0, 0, '', '', 1, 0, 1);

-- Flag rientrabile
ALTER TABLE `dt_causalet` ADD `is_rientrabile` INT NOT NULL AFTER `is_importabile`;

-- Allegati stampe standard
CREATE TABLE `zz_files_print` ( `id` INT NOT NULL AUTO_INCREMENT , `id_print` INT NOT NULL , `id_file` INT NOT NULL , PRIMARY KEY (`id`));

-- Modifica vista inviato Scadenzario
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'SÌ\', \'NO\')' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'icon_title_Inviato';

-- Fix problemi integrità db per campi created_at e updated_at
ALTER TABLE `in_righe_tipiinterventi` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `mg_listini` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `mg_listini_articoli` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `my_componenti` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `in_righe_tipiinterventi` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `mg_listini` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `mg_listini_articoli` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `my_componenti` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Aggiunta colonna Agente in vista Contratti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Agente', '`agente`.`ragione_sociale`', 15, 1, 0, 0, 0, 1);
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br>') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN(SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN(SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
HAVING 
    2=2" WHERE `name` = 'Contratti';

UPDATE `zz_views` SET `name` = 'Richiesta' WHERE `zz_views`.`query` = 'richiesta' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'); 
UPDATE `zz_views` SET `name` = 'Descrizione' WHERE `zz_views`.`query` = 'in_interventi.descrizione' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'); 