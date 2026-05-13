-- Aumento dimensione massima codicerea
ALTER TABLE `an_anagrafiche` CHANGE `codicerea` `codicerea` VARCHAR(23) DEFAULT NULL; 

-- Pulizia campi inutilizzati
ALTER TABLE `an_anagrafiche` DROP `cciaa`;
ALTER TABLE `an_anagrafiche` DROP `cciaa_citta`;

-- Aggiunta nazioni
INSERT INTO `an_nazioni` (`id`, `nome`, `iso2`, `name`) VALUES (NULL, 'Palestina', 'PS', 'Palestine');

-- Impostazione per visualizzare i promemoria su app
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Visualizza promemoria', '1', 'boolean', '1', 'Applicazione', '5', '');

-- Aggiunta del riferimento utente nei movimenti
ALTER TABLE `mg_movimenti` ADD `idutente` INT NULL DEFAULT NULL;

-- Aggiunta valori buffer Datatables
UPDATE `zz_settings` SET `tipo` = 'list[5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100,250,500,1000]' WHERE `zz_settings`.`nome` = 'Lunghezza in pagine del buffer Datatables';
