-- Aggiunte note nelle righe dei documenti
ALTER TABLE `co_righe_contratti` ADD `note` TEXT NULL AFTER `tipo_sconto`;
ALTER TABLE `co_righe_documenti` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `co_righe_preventivi` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `dt_righe_ddt` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `in_righe_interventi` ADD `note` TEXT NULL AFTER `tipo_sconto`; 
ALTER TABLE `or_righe_ordini` ADD `note` TEXT NULL AFTER `tipo_sconto`; 

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Aggiungi le note delle righe tra documenti', '0', 'boolean', '1', 'Generali', '24', 'Permette di riportare le note della riga in fase di importazione tra documenti');

-- Fix calcolo Costi e Ricavi su colonne attività
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IFNULL(SUM(in_interventi_tecnici.prezzo_ore_unitario_tecnico*in_interventi_tecnici.ore + in_interventi_tecnici.prezzo_km_unitario_tecnico*in_interventi_tecnici.km + in_interventi_tecnici.prezzo_dirittochiamata_tecnico), 0) + IFNULL(costo_righe, 0)' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Costi';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IFNULL(SUM(in_interventi_tecnici.prezzo_ore_unitario*in_interventi_tecnici.ore-in_interventi_tecnici.sconto + in_interventi_tecnici.prezzo_km_unitario*in_interventi_tecnici.km-in_interventi_tecnici.scontokm + in_interventi_tecnici.prezzo_dirittochiamata), 0) + IFNULL(ricavo_righe, 0)' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Ricavi';

-- Modifica nomi filtri utenti
UPDATE `zz_group_module` SET `name`='Mostra al tecnico solo le sue attività programmate e assegnate' WHERE `name`='Mostra interventi ai tecnici coinvolti';
UPDATE `zz_group_module` SET `name`='Mostra al cliente solo le attività che ha richiesto' WHERE `name`='Mostra interventi ai clienti coinvolti';
UPDATE `zz_group_module` SET `name`='Mostra al tecnico solo le attività a cui è stato assegnato' WHERE `name`='Mostra interventi ai tecnici assegnati';