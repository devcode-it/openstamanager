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

UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_views`.`name` = 'Anagrafica' AND `id_module`=(SELECT `id` FROM `zz_modules` WHERE `name`='Scadenzario'); 

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_scadenziario`\nLEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`\nLEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`\nLEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\nLEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\nLEFT JOIN (\n         SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\n         FROM `zz_operations`\n                INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\n                INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\n                INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`\n         WHERE `zz_modules`.`name` = \'Scadenzario\' AND `zz_operations`.`op` = \'send-email\'\n         GROUP BY `zz_operations`.`id_record`\n     ) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`\nWHERE 1=1 AND\n(`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN(\'Emessa\',\'Parzialmente pagato\',\'Pagato\'))\nHAVING 2=2\nORDER BY `scadenza` ASC' WHERE `zz_modules`.`name` = 'Scadenzario';

-- Aggiunta causale e tipologia in ritenute contributi
ALTER TABLE `co_ritenuta_contributi` ADD `tipologia` VARCHAR(100) NOT NULL AFTER `descrizione`, ADD `causale` VARCHAR(100) NOT NULL AFTER `tipologia`; 