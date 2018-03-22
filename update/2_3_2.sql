-- Lo stato 'FAT' è da considerarsi completato 
UPDATE `in_statiintervento` SET `completato` = '1' WHERE `in_statiintervento`.`idstatointervento` = 'FAT';

-- Nuovi campi per iva su righe 'Materiale utilizzato' in interventi
ALTER TABLE `mg_articoli_interventi` CHANGE `idiva_vendita` `idiva` INT(11) NOT NULL;
ALTER TABLE `mg_articoli_interventi` ADD `desc_iva` VARCHAR(255) NOT NULL AFTER `idiva`, ADD `iva` DECIMAL(12,4) NOT NULL AFTER `desc_iva`;

-- Nuovi campi per iva su righe 'Altre spese' in interventi
ALTER TABLE `in_righe_interventi` ADD `idiva` INT(11) NOT NULL AFTER `prezzo_acquisto`, ADD `desc_iva` VARCHAR(255) NOT NULL AFTER `idiva`, ADD `iva` DECIMAL(12,4) NOT NULL AFTER `desc_iva`; 