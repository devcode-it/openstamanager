-- Aggiunto flag non conteggiare e campo note in tipologie interventi
ALTER TABLE `in_tipiintervento` ADD `non_conteggiare` TINYINT NOT NULL;
ALTER TABLE `in_tipiintervento` ADD `note` TEXT NOT NULL;

-- Aggiunto tipo attività in contratti
ALTER TABLE `co_contratti` ADD `idtipointervento` INT NOT NULL;