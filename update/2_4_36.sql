-- Fix eliminazione attività collegata
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_3`; 
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_3` FOREIGN KEY (`id_preventivo`) REFERENCES `co_preventivi`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_4`;
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_4` FOREIGN KEY (`id_contratto`) REFERENCES `co_contratti`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_7`;
ALTER TABLE `in_interventi` ADD CONSTRAINT `in_interventi_ibfk_7` FOREIGN KEY (`id_ordine`) REFERENCES `or_ordini`(`id`) ON DELETE SET NULL ON UPDATE RESTRICT;

-- Aggiunta anagrafica alle scadenze
ALTER TABLE `co_scadenziario` ADD `idanagrafica` INT NOT NULL AFTER `iddocumento`; 

-- Aggiunto campo ctgypurp per esportazione xml bonifici
ALTER TABLE `co_tipi_scadenze` ADD `ctgypurp` VARCHAR(255) NOT NULL AFTER `descrizione`; 

UPDATE `co_tipi_scadenze` SET `ctgypurp` = 'TAXS' WHERE `co_tipi_scadenze`.`nome` = 'f24'; 
UPDATE `co_tipi_scadenze` SET `ctgypurp` = 'SUPP' WHERE `co_tipi_scadenze`.`nome` = 'generico'; 
INSERT INTO `co_tipi_scadenze` (`id`, `nome`, `descrizione`, `ctgypurp`, `can_delete`) VALUES (NULL, 'stipendio', 'Stipendi', 'SALA', '1');

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Anagrafica';
-- Aggiunta causale e tipologia in ritenute contributi
ALTER TABLE `co_ritenuta_contributi` ADD `tipologia` VARCHAR(100) NOT NULL AFTER `descrizione`, ADD `causale` VARCHAR(100) NOT NULL AFTER `tipologia`; 