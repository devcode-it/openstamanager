UPDATE `zz_views` SET `search` = 1 WHERE `zz_views`.`name` = 'Descrizione' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie documenti');
UPDATE `zz_views` SET `search` = 1 WHERE `zz_views`.`name` IN ('Categoria', 'Nome', 'Data') AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Gestione documentale');


-- Aggiunta rivalsa inps e relativa iva per il calcolo del totale ivato del documento
UPDATE `zz_views` SET `query` = 'righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND `name` = 'Totale ivato';

UPDATE `zz_views` SET `query` = 'righe.totale + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND `name` = 'Totale ivato';

-- Aggiunta campi righe contratti --
ALTER TABLE `co_righe_contratti` ADD `original_id` INT(11) NULL DEFAULT NULL AFTER `abilita_serial` , ADD `original_type` VARCHAR(255) NULL DEFAULT NULL AFTER `original_id`;

-- Ripristino TD01 per fatture differite
UPDATE `co_tipidocumento` SET `codice_tipo_documento_fe` = 'TD01' WHERE `co_tipidocumento`.`codice_tipo_documento_fe` = 'TD24';


UPDATE `fe_stati_documento` SET `icon` = 'fa fa-paper-plane-o text-success' WHERE `fe_stati_documento`.`codice` = 'MC'; 

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-check-circle text-warning' WHERE `fe_stati_documento`.`codice` = 'NE'; 

-- modifica vista tecnici attività --
UPDATE `zz_views` SET `query` = 'GROUP_CONCAT(DISTINCT((SELECT DISTINCT(ragione_sociale) FROM an_anagrafiche WHERE idanagrafica = in_interventi_tecnici.idtecnico)))' WHERE `zz_views`.`id_module` = (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Interventi') AND `zz_views`.`name` = 'Tecnici';

-- fix valore data_ora_trasporto
UPDATE `dt_ddt` SET `data_ora_trasporto` = NULL WHERE `dt_ddt`.`id` = '0000-00-00 00:00:00'; 

-- fix widget Contratti in scadenza
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(dati.id) AS dato FROM(SELECT id, ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=''ore'' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.is_completato = 1)), 0) ) AS ore_rimanenti, DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti, data_conclusione, ore_preavviso_rinnovo, giorni_preavviso_rinnovo, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND rinnovabile = 1 AND YEAR(data_conclusione) > 1970 AND (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo)) ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC) dati' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';
