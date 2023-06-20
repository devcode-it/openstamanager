-- Aggiunta vincolo fra ddt e righe ddt
ALTER TABLE `dt_righe_ddt` ADD CONSTRAINT `dt_righe_ddt_ibfk_2` FOREIGN KEY (`idddt`) REFERENCES `dt_ddt`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT; 

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Rimuovi avviso fatture estere', '0', 'boolean', '1', 'Fatturazione elettronica', NULL, "Abilitare per rimuovere l'avviso di fatture elettroniche estere da inviare, in caso le fatture elettroniche estere non vengano inviate.");

DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = "Mostra promemoria attività ai soli Tecnici assegnati";
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = "Inizio orario lavorativo";
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = "Fine orario lavorativo";

UPDATE `zz_settings` SET `help` = "Se abilitato viene effettuato un backup completo del gestionale secondo le impostazioni definite a database in zz_tasks (di default ogni giorno all'1)" WHERE `zz_settings`.`nome` = 'Backup automatico';

UPDATE `zz_settings` SET `editable` = 1 WHERE `zz_settings`.`nome` = 'Soft quota';
UPDATE `zz_settings` SET `help` = "Valore espresso in Giga superato il quale viene visualizzato un avviso di spazio in esaurimento." WHERE `zz_settings`.`nome` = 'Soft quota';

-- Rimozione google maps
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Google Maps API key';

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Tile server OpenStreetMap', 'https://{s}.tile.openstreetmap.de/{z}/{x}/{y}.png', 'string', '1', 'Generali', NULL, NULL); 