-- RIpristino tipo text alla descrizione articolo
ALTER TABLE `mg_articoli_lang` CHANGE `title` `title` TEXT NOT NULL; 

-- Correzione per file senza estensione
UPDATE zz_files SET filename = CONCAT(filename, 'xml') WHERE filename LIKE '%.';
UPDATE `zz_files` SET name = 'Ricevuta AT' WHERE `original` = 'Ricevuta AT';
UPDATE `zz_files` SET name = 'Ricevuta DT' WHERE `original` = 'Ricevuta DT';
UPDATE `zz_files` SET name = 'Ricevuta EC01' WHERE `original` = 'Ricevuta EC01';
UPDATE `zz_files` SET name = 'Ricevuta EC02' WHERE `original` = 'Ricevuta EC02';
UPDATE `zz_files` SET name = 'Ricevuta ERR' WHERE `original` = 'Ricevuta ERR';
UPDATE `zz_files` SET name = 'Ricevuta ERVAL' WHERE `original` = 'Ricevuta ERVAL';
UPDATE `zz_files` SET name = 'Ricevuta GEN' WHERE `original` = 'Ricevuta GEN';
UPDATE `zz_files` SET name = 'Ricevuta MC' WHERE `original` = 'Ricevuta MC';
UPDATE `zz_files` SET name = 'Ricevuta NE' WHERE `original` = 'Ricevuta NE';
UPDATE `zz_files` SET name = 'Ricevuta NS' WHERE `original` = 'Ricevuta NS';
UPDATE `zz_files` SET name = 'Ricevuta RC' WHERE `original` = 'Ricevuta RC';
UPDATE `zz_files` SET name = 'Ricevuta WAIT' WHERE `original` = 'Ricevuta WAIT';

