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
