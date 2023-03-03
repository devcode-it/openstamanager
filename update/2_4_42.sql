-- Ampliamento decimali fattore moltiplicativo per unità di misura secondaria
ALTER TABLE `mg_articoli` CHANGE `fattore_um_secondaria` `fattore_um_secondaria` DECIMAL(19,10) NULL DEFAULT NULL; 