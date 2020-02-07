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