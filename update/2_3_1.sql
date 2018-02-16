-- Rimosso il referente dalla lista sedi, per non mostrare sedi duplicate nella lista sedi
UPDATE `zz_plugins` SET `options` = '	{ "main_query": [	{	"type": "table", "fields": "Nome, Indirizzo, Città, CAP, Provincia",	"query": "SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia FROM an_sedi WHERE 1=1 AND an_sedi.idanagrafica=|idanagrafica| HAVING 2=2 ORDER BY an_sedi.id DESC"}	]}', `created_at` = NULL WHERE `zz_plugins`.`name` = 'Sedi';

-- Aggiunta possibilità di collegare allegati anche ai plugins
ALTER TABLE `zz_files` ADD `id_plugin` int(11) AFTER `id_module`;

-- Aggiunto valore NULL a idarticolo in co_righe2_contratti
-- ALTER TABLE `co_righe2_contratti` CHANGE `idarticolo` `idarticolo` int(11);
-- UPDATE `co_righe2_contratti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

-- Aggiornamento ORDER BY nei moduli Fatture, DDT, Ordini, Preventivi, Contratti e Interventi
UPDATE `zz_views` SET `order_by` = 'CAST(numero_esterno AS UNSIGNED)' WHERE `name` = 'Numero' AND `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN ('Fatture di vendita', 'Fatture di acquisto', 'Ordini cliente', 'Ordini fornitore', 'Ddt di vendita', 'Ddt di acquisto'));

UPDATE `zz_views` SET `order_by` = 'CAST(in_interventi.codice AS UNSIGNED)' WHERE `name` = 'Numero' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');

UPDATE `zz_views` SET `order_by` = 'CAST(numero AS UNSIGNED)' WHERE `name` = 'Numero' AND `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` IN ('Ordini Preventivi', 'Contratti'));

-- Query per ignorare i punti nella ricerca in Ragione sociale
-- UPDATE `zz_views` SET `search_inside` = CONCAT('REPLACE(', `query`, ', ''.'', '''') LIKE |search|') WHERE `name` = 'Ragione sociale';

-- Aggiornate le viste standard, separando i simboli < e > per non dare errore nell'aggiornamento
UPDATE `zz_views` SET `query` = 'IF(scadenza < NOW(), \'#ff7777\', \'\')' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = '_bg_';
UPDATE `zz_views` SET `query` = 'CONCAT(co_tipidocumento.descrizione, CONCAT(\' numero \', IF(numero_esterno <> \'\', numero_esterno, numero)))' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = 'Data emissione';
UPDATE `zz_views` SET `query` = 'GROUP_CONCAT(CASE WHEN totale > 0 THEN co_pianodeiconti3.descrizione ELSE NULL END)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota') AND `name` = 'Conto avere';
UPDATE `zz_views` SET `query` = 'GROUP_CONCAT(CASE WHEN totale < 0 THEN co_pianodeiconti3.descrizione ELSE NULL END)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota') AND `name` = 'Conto dare';

-- Spostamento conti di apertura e chiusura stato patrimoniale sotto lo stato patrimoniale
UPDATE `co_pianodeiconti2` SET `idpianodeiconti1`=1 WHERE `descrizione` = 'Perdite e profitti';

-- Aggiungo 1=1 nel WHERE e 2=2 nel HAVING per il widget clienti in anagrafica
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND descrizione="Cliente" AND deleted=0 HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di clienti';

-- Aggiungo 1=1 nel WHERE e 2=2 nel HAVING per il widget tecnici in anagrafica
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND descrizione="Tecnico" AND deleted=0 HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di tecnici';

-- Aggiungo 1=1 nel WHERE e 2=2 nel HAVING per il widget fornitori in anagrafica
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND descrizione="Fornitore" AND deleted=0 HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di fornitori';

-- Aggiungo 1=1 nel WHERE e 2=2 nel HAVING per il widget agenti in anagrafica
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND descrizione="Agente" AND deleted=0 HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di agenti';

-- Aggiungo 1=1 nel WHERE e 2=2 nel HAVING per il widget vettori in anagrafica
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND descrizione="Vettore" AND deleted=0 HAVING 2=2' WHERE `zz_widgets`.`name` = 'Numero di vettori';

-- Aggiungo 1=1 nel WHERE e 2=2 nel HAVING per il widget tutte le anagrafiche
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(an_anagrafiche.idanagrafica) AS dato FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica WHERE 1=1 AND deleted=0 HAVING 2=2' WHERE `zz_widgets`.`name` = 'Tutte le anagrafiche';

-- Aggiornamento widget "Crediti da clienti" anche con totali parziali
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT(da_pagare-pagato, 2), ",", "#"), ".", ","), "#", "."), "€") AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'entrata\'' WHERE `zz_widgets`.`name` = 'Crediti da clienti';

-- Aggiornamento widget "Debiti verso fornitori" anche con totali parziali
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(" ", REPLACE(REPLACE(REPLACE(FORMAT( -(da_pagare-pagato), 2), ",", "#"), ".", ","), "#", "."), "€") AS dato FROM (co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_tipidocumento.dir=\'uscita\'' WHERE `zz_widgets`.`name` = 'Debiti verso fornitori';

-- Aggiungo flag deleted per gli stati intervento
ALTER TABLE `in_statiintervento` ADD `deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `completato`;

-- Aggiorno query modulo stati intervento
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_statiintervento` WHERE 1=1 AND deleted = 0 HAVING 2=2' WHERE `zz_modules`.`name` = 'Stati di intervento';
<<<<<<< HEAD

=======
>>>>>>> e002fe7591b839da72978296455e2e39e383e747

-- Aggiungo il flag can_delete ed elimino il flag `default` in quanto non serve più
ALTER TABLE `in_statiintervento` ADD `can_delete` BOOLEAN NOT NULL DEFAULT TRUE AFTER `default`;
ALTER TABLE `in_statiintervento` DROP `default`;

-- Aggiunti come eliminabili gli stati "Chiamata" e "In programmazione"
UPDATE `in_statiintervento` SET `can_delete` = 1 WHERE `idstatointervento` IN( 'CALL', 'WIP' );

-- Aggiunti come non eliminabili ma modificabili gli stati "Fatturato" e "Completato"
UPDATE `in_statiintervento` SET `can_delete` = 0 WHERE `idstatointervento` IN( 'FAT', 'OK' );

-- Impostazione di tutti gli eventuali altri tipi di intervento a modificabili e cancellabili
<<<<<<< HEAD
UPDATE `in_statiintervento` SET `can_delete` = 1 WHERE `idstatointervento` NOT IN( 'FAT', 'OK', 'CALL', 'WIP' );
=======
UPDATE `in_statiintervento` SET `can_delete` = 1, `can_edit` = 1 WHERE `idstatointervento` NOT IN( 'FAT', 'OK', 'CALL', 'WIP' );
>>>>>>> e002fe7591b839da72978296455e2e39e383e747
