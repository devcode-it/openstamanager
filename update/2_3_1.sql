-- Aggiunta possibilità di collegare allegati anche ai plugins
ALTER TABLE `zz_files` ADD `id_plugin` int(11) AFTER `id_module`;

-- Aggiunto valore NULL a idarticolo in co_righe2_contratti
-- ALTER TABLE `co_righe2_contratti` CHANGE `idarticolo` `idarticolo` int(11);
-- UPDATE `co_righe2_contratti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

-- Aggiornamento ORDER BY nei moduli Fatture, DDT, Ordini, Preventivi, Contratti e Interventi
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'CAST(numero_esterno AS UNSIGNED)' WHERE `zz_modules`.`name` IN ('Fatture di vendita', 'Fatture di acquisto', 'Ordini cliente', 'Ordini fornitore', 'Ddt di vendita', 'Ddt di acquisto') AND `zz_views`.`name` = 'Numero';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'CAST(in_interventi.codice AS UNSIGNED)' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Numero';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'CAST(numero AS UNSIGNED)' WHERE `zz_modules`.`name` IN ('Ordini Preventivi', 'Contratti') AND `zz_views`.`name` = 'Numero';
-- Query per ignorare i punti nella ricerca in Ragione sociale
-- UPDATE `zz_views` SET `search_inside` = CONCAT('REPLACE(', `query`, ', ''.'', '''') LIKE |search|') WHERE `name` = 'Ragione sociale';

-- Aggiornate le viste standard, separando i simboli < e > per non dare errore nell'aggiornamento
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'GROUP_CONCAT(CASE WHEN totale > 0 THEN co_pianodeiconti3.descrizione ELSE NULL END)' WHERE `zz_modules`.`name` = 'Prima nota' AND `zz_views`.`name` = 'Conto avere';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'GROUP_CONCAT(CASE WHEN totale < 0 THEN co_pianodeiconti3.descrizione ELSE NULL END)' WHERE `zz_modules`.`name` = 'Prima nota' AND `zz_views`.`name` = 'Conto dare';
-- Spostamento conti di apertura e chiusura stato patrimoniale sotto lo stato patrimoniale
UPDATE `co_pianodeiconti2` SET `idpianodeiconti1`=1 WHERE `descrizione` = 'Perdite e profitti';

-- Aggiungo flag deleted per gli stati intervento
ALTER TABLE `in_statiintervento` ADD `deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `completato`;

-- Aggiungo il flag can_delete ed elimino il flag `default` in quanto non serve più
ALTER TABLE `in_statiintervento` ADD `can_delete` BOOLEAN NOT NULL DEFAULT TRUE AFTER `default`;
ALTER TABLE `in_statiintervento` DROP `default`;

-- Aggiunti come eliminabili gli stati "Chiamata" e "In programmazione"
UPDATE `in_statiintervento` SET `can_delete` = 1 WHERE `idstatointervento` IN( 'CALL', 'WIP' );

-- Aggiunti come non eliminabili ma modificabili gli stati "Fatturato" e "Completato"
UPDATE `in_statiintervento` SET `can_delete` = 0 WHERE `idstatointervento` IN( 'FAT', 'OK' );

-- Impostazione di tutti gli eventuali altri tipi di intervento a modificabili e cancellabili
UPDATE `in_statiintervento` SET `can_delete` = 1 WHERE `idstatointervento` NOT IN( 'FAT', 'OK', 'CALL', 'WIP' );