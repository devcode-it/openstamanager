-- 
ALTER TABLE `an_anagrafiche` CHANGE `colore` `colore` VARCHAR(30) NOT NULL DEFAULT '#FFFFFF'; 
ALTER TABLE `mg_categorie` CHANGE `colore` `colore` VARCHAR(30) NOT NULL; 
ALTER TABLE `my_impianti_categorie` CHANGE `colore` `colore` VARCHAR(30) NOT NULL; 
ALTER TABLE `an_provenienze` CHANGE `colore` `colore` VARCHAR(30) NOT NULL;
ALTER TABLE `an_relazioni` CHANGE `colore` `colore` VARCHAR(30) NOT NULL; 
ALTER TABLE `in_statiintervento` CHANGE `colore` `colore` VARCHAR(30) NOT NULL DEFAULT '#FFFFFF'; 
ALTER TABLE `co_statipreventivi` CHANGE `colore` `colore` VARCHAR(30) NOT NULL; 