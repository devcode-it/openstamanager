-- Cambio tipo di dati decimali da FLOAT a DECIMAL
ALTER TABLE `co_contratti` CHANGE `budget` `budget` DECIMAL(12, 4) NOT NULL, CHANGE `costo_diritto_chiamata` `costo_diritto_chiamata` DECIMAL(12, 4) NOT NULL, CHANGE `ore_lavoro` `ore_lavoro` DECIMAL(12, 4) NOT NULL, CHANGE `costo_orario` `costo_orario` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km` `costo_km` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_documenti` CHANGE `rivalsainps` `rivalsainps` DECIMAL(12, 4) NOT NULL, CHANGE `iva_rivalsainps` `iva_rivalsainps` DECIMAL(12, 4) NOT NULL, CHANGE `ritenutaacconto` `ritenutaacconto` DECIMAL(12, 4) NOT NULL, CHANGE `bollo` `bollo` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_movimenti` CHANGE `totale` `totale` DECIMAL(12, 4) NULL DEFAULT NULL;
ALTER TABLE `co_preventivi` CHANGE `budget` `budget` DECIMAL(12, 4) NOT NULL, CHANGE `costo_diritto_chiamata` `costo_diritto_chiamata` DECIMAL(12, 4) NOT NULL, CHANGE `ore_lavoro` `ore_lavoro` DECIMAL(12, 4) NOT NULL, CHANGE `costo_orario` `costo_orario` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km` `costo_km` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_preventivi_interventi` CHANGE `costo_orario` `costo_orario` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km` `costo_km` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_righe2_contratti` CHANGE `subtotale` `subtotale` DECIMAL(12, 4) NOT NULL, CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_righe_documenti` CHANGE `iva` `iva` DECIMAL(12, 4) NOT NULL, CHANGE `iva_indetraibile` `iva_indetraibile` DECIMAL(12, 4) NOT NULL, CHANGE `subtotale` `subtotale` DECIMAL(12, 4) NOT NULL, CHANGE `sconto` `sconto` DECIMAL(12, 4) NOT NULL, CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_righe_preventivi` CHANGE `iva` `iva` DECIMAL(12, 4) NOT NULL, CHANGE `iva_indetraibile` `iva_indetraibile` DECIMAL(12, 4) NOT NULL, CHANGE `subtotale` `subtotale` DECIMAL(12, 4) NOT NULL, CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_scadenziario` CHANGE `da_pagare` `da_pagare` DECIMAL(12, 4) NULL DEFAULT NULL, CHANGE `pagato` `pagato` DECIMAL(12, 4) NULL DEFAULT NULL;
ALTER TABLE `dt_ddt` CHANGE `rivalsainps` `rivalsainps` DECIMAL(12, 4) NOT NULL, CHANGE `iva_rivalsainps` `iva_rivalsainps` DECIMAL(12, 4) NOT NULL, CHANGE `ritenutaacconto` `ritenutaacconto` DECIMAL(12, 4) NOT NULL, CHANGE `bollo` `bollo` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `iva` `iva` DECIMAL(12, 4) NOT NULL, CHANGE `iva_indetraibile` `iva_indetraibile` DECIMAL(12, 4) NOT NULL, CHANGE `subtotale` `subtotale` DECIMAL(12, 4) NOT NULL, CHANGE `sconto` `sconto` DECIMAL(12, 4) NOT NULL, CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL, CHANGE `qta_evasa` `qta_evasa` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `in_interventi_tecnici` CHANGE `km` `km` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_ore_unitario` `prezzo_ore_unitario` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_km_unitario` `prezzo_km_unitario` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_ore_consuntivo` `prezzo_ore_consuntivo` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_km_consuntivo` `prezzo_km_consuntivo` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_dirittochiamata` `prezzo_dirittochiamata` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_ore_unitario_tecnico` `prezzo_ore_unitario_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_km_unitario_tecnico` `prezzo_km_unitario_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_ore_consuntivo_tecnico` `prezzo_ore_consuntivo_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_km_consuntivo_tecnico` `prezzo_km_consuntivo_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_dirittochiamata_tecnico` `prezzo_dirittochiamata_tecnico` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `in_tariffe` CHANGE `costo_ore` `costo_ore` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km` `costo_km` DECIMAL(12, 4) NOT NULL, CHANGE `costo_dirittochiamata` `costo_dirittochiamata` DECIMAL(12, 4) NOT NULL, CHANGE `costo_ore_tecnico` `costo_ore_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km_tecnico` `costo_km_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `costo_dirittochiamata_tecnico` `costo_dirittochiamata_tecnico` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `in_tipiintervento` CHANGE `costo_orario` `costo_orario` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km` `costo_km` DECIMAL(12, 4) NOT NULL, CHANGE `costo_diritto_chiamata` `costo_diritto_chiamata` DECIMAL(12, 4) NOT NULL, CHANGE `costo_orario_tecnico` `costo_orario_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `costo_km_tecnico` `costo_km_tecnico` DECIMAL(12, 4) NOT NULL, CHANGE `costo_diritto_chiamata_tecnico` `costo_diritto_chiamata_tecnico` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `mg_articoli` CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL, CHANGE `threshold_qta` `threshold_qta` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_acquisto` `prezzo_acquisto` DECIMAL(12, 4) NOT NULL, CHANGE `prezzo_vendita` `prezzo_vendita` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `mg_articoli_automezzi` CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `prezzo_vendita` `prezzo_vendita` DECIMAL(12, 4) NOT NULL, CHANGE `sconto` `sconto` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `mg_movimenti` CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `or_ordini` CHANGE `rivalsainps` `rivalsainps` DECIMAL(12, 4) NOT NULL, CHANGE `iva_rivalsainps` `iva_rivalsainps` DECIMAL(12, 4) NOT NULL, CHANGE `ritenutaacconto` `ritenutaacconto` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `iva` `iva` DECIMAL(12, 4) NOT NULL, CHANGE `iva_indetraibile` `iva_indetraibile` DECIMAL(12, 4) NOT NULL, CHANGE `subtotale` `subtotale` DECIMAL(12, 4) NOT NULL, CHANGE `sconto` `sconto` DECIMAL(12, 4) NOT NULL, CHANGE `qta` `qta` DECIMAL(12, 4) NOT NULL, CHANGE `qta_evasa` `qta_evasa` DECIMAL(12, 4) NOT NULL;
ALTER TABLE `co_iva` CHANGE `percentuale` `percentuale` DECIMAL(5,2) NOT NULL, CHANGE `indetraibile` `indetraibile` DECIMAL(5,2) NOT NULL;
ALTER TABLE `co_ritenutaacconto` CHANGE `percentuale` `percentuale` DECIMAL(5,2) NOT NULL, CHANGE `indetraibile` `indetraibile` DECIMAL(5,2) NOT NULL;
ALTER TABLE `co_rivalsainps` CHANGE `percentuale` `percentuale` DECIMAL(5,2) NOT NULL, CHANGE `indetraibile` `indetraibile` DECIMAL(5,2) NOT NULL;
ALTER TABLE `mg_articoli_interventi` CHANGE `idiva_vendita` `idiva_vendita` INT NOT NULL, CHANGE `qta` `qta` DECIMAL(10,2) NOT NULL;
ALTER TABLE `mg_listini` CHANGE `prc_guadagno` `prc_guadagno` DECIMAL(5,2) NOT NULL;
ALTER TABLE `or_ordini` CHANGE `bollo` `bollo` DECIMAL(10,2) NOT NULL;
ALTER TABLE `an_anagrafiche` CHANGE `km` `km` DECIMAL(10,2) NOT NULL;
ALTER TABLE `an_sedi` CHANGE `km` `km` DECIMAL(10,2) NOT NULL;
ALTER TABLE `co_movimenti` CHANGE `primanota` `primanota` TINYINT NOT NULL;
ALTER TABLE `in_interventi` CHANGE `km` `km` DECIMAL(7,2) NOT NULL, CHANGE `prezzo_ore_unitario` `prezzo_ore_unitario` DECIMAL(10,2) NOT NULL;

-- Aggiunta ritenuta d'acconto e rivalsa inps per ogni riga della fattura
ALTER TABLE `co_righe_documenti` ADD `ritenutaacconto` DECIMAL(12, 4) NOT NULL AFTER `sconto`, ADD `rivalsainps` DECIMAL(12, 4) NOT NULL AFTER `ritenutaacconto`;
ALTER TABLE `co_righe_documenti` ADD `idritenutaacconto` INT NOT NULL AFTER `sconto`;
ALTER TABLE `co_righe_documenti` ADD `idrivalsainps` INT NOT NULL AFTER `ritenutaacconto`;


-- Aggiornamento modulo scadenzario
UPDATE `zz_modules` SET `module_dir` = 'scadenzario' WHERE `name` = 'Scadenzario';

-- Aggiunta collegamento conto partitario a cliente/fornitore
ALTER TABLE `an_anagrafiche` ADD `idconto_cliente` INT NOT NULL AFTER `idsede_fatturazione`, ADD `idconto_fornitore` INT NOT NULL AFTER `idconto_cliente`;

-- Aggiunta stato "parzialmente pagato per le fatture"
INSERT INTO `co_statidocumento` (`descrizione`, `icona`) VALUES ('Parzialmente pagato', 'fa fa-2x fa-dot-circle-o text-warning');

-- Ridenominazione "Partitario" in "Piano dei conti"
UPDATE `zz_modules` SET `name` = 'Piano dei conti' WHERE `name` = 'Partitario';

-- Impostazione modulo "MyImpianti" come modulo di default e non disinstallabile
UPDATE `zz_modules` SET `default`=1 WHERE `name` = 'MyImpianti';

