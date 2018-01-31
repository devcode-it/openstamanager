-- Rimosso il referente dalla lista sedi, per non mostrare sedi duplicate nella lista sedi
UPDATE `zz_plugins` SET `options` = '	{ "main_query": [	{	"type": "table", "fields": "Nome, Indirizzo, Città, CAP, Provincia",	"query": "SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia FROM an_sedi WHERE 1=1 AND an_sedi.idanagrafica=|idanagrafica| HAVING 2=2 ORDER BY an_sedi.id DESC"}	]}', `created_at` = NULL WHERE `zz_plugins`.`name` = 'Sedi';

-- Aggiunta possibilità di collegare allegati anche ai plugins
ALTER TABLE `zz_files` ADD `id_plugin` INT NOT NULL AFTER `id_module`; 