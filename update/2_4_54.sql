-- Aggiunto colore in per gruppi utenti
ALTER TABLE `zz_groups` ADD `theme` VARCHAR(25) NULL AFTER `id_module_start`;

UPDATE `zz_groups` SET `theme` = 'black' WHERE `zz_groups`.`nome` = 'Amministratori';
UPDATE `zz_groups` SET `theme` = 'red' WHERE `zz_groups`.`nome` = 'Tecnici'; 
UPDATE `zz_groups` SET `theme` = 'blue' WHERE `zz_groups`.`nome` = 'Agenti';
UPDATE `zz_groups` SET `theme` = 'green' WHERE `zz_groups`.`nome` = 'Clienti';