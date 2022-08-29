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
UPDATE `zz_group_module` SET `name`='Mostra al cliente solo le attività in cui è impostato come \'Cliente\'' WHERE `name`='Mostra interventi ai clienti coinvolti';
UPDATE `zz_group_module` SET `name`='Mostra al tecnico solo le attività a cui è stato assegnato' WHERE `name`='Mostra interventi ai tecnici assegnati';
UPDATE `zz_group_module` SET `name`='Mostra agli agenti solo le anagrafiche di cui sono agenti' WHERE `name`='Mostra preventivi ai clienti coinvolti'; 

-- Fix segmenti scadenzario RiBa
UPDATE `zz_segments` SET `clause` = 'co_pagamenti.codice_modalita_pagamento_fe= \'MP12\' AND co_tipidocumento.dir=\"uscita\" AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)' WHERE `zz_segments`.`name` = 'Scadenzario Ri.Ba. Fornitori';
UPDATE `zz_segments` SET `clause` = 'co_pagamenti.codice_modalita_pagamento_fe= \'MP12\' AND co_tipidocumento.dir=\"entrata\" AND ABS(`co_scadenziario`.`pagato`) < ABS(`co_scadenziario`.`da_pagare`)' WHERE `zz_segments`.`name` = 'Scadenzario Ri.Ba. Clienti'; 

-- Aggiunta filtri in viste
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_groups` WHERE `nome`='Agenti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Preventivi'), 'Mostra agli agenti solo i preventivi dei clienti dei quali si è agenti', 'an_anagrafiche.idagente=|id_anagrafica|', 'WHR', 1, 0);
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_groups` WHERE `nome`='Agenti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Preventivi'), 'Mostra agli agenti solo i preventivi di cui sono agenti', 'co_preventivi.idagente=|id_anagrafica|', 'WHR', 1, 0);

INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_groups` WHERE `nome`='Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Interventi'), 'Mostra al cliente solo le attività in cui è impostato come \'Per conto di\'', 'in_interventi.idclientefinale=|id_anagrafica|', 'WHR', '0', '1');
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_groups` WHERE `nome`='Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name`='Interventi'), 'Mostra al cliente tutte le attività in cui è coinvolto', '(in_interventi.idanagrafica=|id_anagrafica| OR in_interventi.idclientefinale=|id_anagrafica|)', 'WHR', '0', '1');

-- Aggiornamento title e icona per Causali (Causali trasporto) e Causali movimenti
UPDATE `zz_modules` SET `title` = 'Causali trasporto', `icon` = 'fa fa-truck'  WHERE `zz_modules`.`name` = 'Causali'; 
UPDATE `zz_modules` SET `icon` = 'fa fa-exchange'  WHERE `zz_modules`.`name` = 'Causali movimenti'; 

-- Prima pagina per gruppo utenti 
ALTER TABLE `zz_groups` ADD `id_module_start` INT NULL AFTER `editable`; 