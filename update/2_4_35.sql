-- Aggiunte note nelle righe dei documenti
ALTER TABLE `co_righe_contratti` ADD `note` TEXT NULL AFTER `tipo_sconto`;
ALTER TABLE `co_righe_documenti` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `co_righe_preventivi` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `dt_righe_ddt` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `in_righe_interventi` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `or_righe_ordini` ADD `note` TEXT NULL AFTER `tipo_sconto`; 

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Aggiungi le note delle righe tra documenti', '0', 'boolean', '1', 'Generali', '24', 'Permette di riportare le note della riga in fase di importazione tra documenti');