-- RIpristino tipo text alla descrizione articolo
ALTER TABLE `mg_articoli_lang` CHANGE `title` `title` TEXT NOT NULL; 

-- Correzione per file senza estensione
UPDATE zz_files SET filename = CONCAT(filename, 'xml') WHERE filename LIKE '%.';
UPDATE `zz_files` SET name = 'Ricevuta RC' WHERE `name` LIKE 'Ricevuta R';
