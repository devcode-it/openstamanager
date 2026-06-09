-- Corregge la query di listato del modulo Articoli: il sottoquery dei barcode usava una
-- derived table correlata (SELECT ... FROM (SELECT ... FROM `mg_articoli_barcode` `b2`
-- WHERE `b2`.`id_articolo` = `mg_articoli_barcode`.`id_articolo` ...) `b1`), che referenzia il
-- `mg_articoli_barcode` della query esterna dall'interno di un sottoquery in FROM. MariaDB non
-- supporta le lateral/correlated derived table, quindi l'apertura di Magazzino > Articoli falliva
-- con errore 1054 "Unknown column 'mg_articoli_barcode.id_articolo' in 'WHERE'".
-- Il ramo ELSE viene sostituito da un GROUP_CONCAT ordinato: stesso risultato (i barcode
-- dell'articolo, ordinati) e valido sia su MariaDB sia su MySQL.
UPDATE `zz_modules`
SET `options` = REPLACE(
    `options`,
    'CONCAT((SELECT GROUP_CONCAT(`b1`.`barcode` SEPARATOR ''<br />'') FROM (SELECT `barcode` FROM `mg_articoli_barcode` `b2` WHERE `b2`.`id_articolo` = `mg_articoli_barcode`.`id_articolo` ORDER BY `b2`.`barcode` ASC) `b1`))',
    'GROUP_CONCAT(`mg_articoli_barcode`.`barcode` ORDER BY `mg_articoli_barcode`.`barcode` ASC SEPARATOR ''<br />'')'
)
WHERE `name` = 'Articoli';
