ALTER TABLE `co_contratti` ADD `idsede` INT NOT NULL AFTER `idanagrafica`;

-- Imposto conto cassa per contanti e rimesse
UPDATE `co_pagamenti` SET `idconto_vendite` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Cassa'), `idconto_acquisti` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Cassa')  WHERE `co_pagamenti`.`descrizione` = 'Contanti' OR `co_pagamenti`.`descrizione` LIKE 'Rimessa %';

-- Imposto conto banca per tutti i bonifici e ri.ba.
UPDATE `co_pagamenti` SET `idconto_vendite` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C'), `idconto_acquisti` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C')  WHERE `co_pagamenti`.`descrizione` LIKE 'Bonifico %' OR `co_pagamenti`.`descrizione` LIKE 'Ri.Ba. %';

-- Indirizzo PEC
ALTER TABLE `an_anagrafiche` ADD `pec` VARCHAR(255) NOT NULL AFTER `email`;

-- ISO 3166-1 alpha-2 code per nazioni
ALTER TABLE `an_nazioni` ADD `iso2` VARCHAR(2) NOT NULL AFTER `nome`;

-- ISO 2 per ITALIA (https://it.wikipedia.org/wiki/ISO_3166-1_alpha-2)
UPDATE `an_nazioni` SET `iso2` = 'IT' WHERE `an_nazioni`.`nome` = 'ITALIA';

-- Aggiunto name per i filtri
ALTER TABLE `zz_group_module` ADD `name` VARCHAR(255) NOT NULL AFTER `idmodule`;

UPDATE `zz_group_module` SET `name` = 'Mostra interventi ai tecnici coinvolti' WHERE `zz_group_module`.`id` = 1;
UPDATE `zz_group_module` SET `name` = 'Mostra interventi ai clienti coinvolti' WHERE `zz_group_module`.`id` = 5;