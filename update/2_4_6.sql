-- Aggiunto campo ritenuta per ENASARCO, ENPALS
ALTER TABLE `co_documenti` ADD `id_ritenuta_contributi` INT(11) NOT NULL AFTER `bollo`;
ALTER TABLE `co_documenti` ADD `ritenuta_contributi` DECIMAL(12,4) NOT NULL AFTER `id_ritenuta_contributi`;

-- Colonna tecnici per interventi
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Tecnici', 'GROUP_CONCAT((SELECT DISTINCT(ragione_sociale) FROM an_anagrafiche WHERE idanagrafica = in_interventi_tecnici.idtecnico))', '14', '1', '0', '0', '', '', '1', '0', '0');

-- Aggiornamento query per la lettura dei contratti in scadenza
 UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato, co_contratti.id, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti FROM co_contratti WHERE idstato IN(SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970 AND ISNULL((SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev=co_contratti.id )) ORDER BY giorni_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza'; 
 
-- Fix per tipi di spedizione fuorvianti (esiste già il campo "Porto" per stabilire di chi è a carico la spedizione)
UPDATE `dt_spedizione` SET `descrizione` = 'Ritiro in magazzino' WHERE `dt_spedizione`.`descrizione` = 'A carico del cliente';
UPDATE `dt_spedizione` SET `descrizione` = 'Espressa' WHERE `dt_spedizione`.`descrizione` = 'A nostro carico';

-- Fix Percentuale rivalsa INPS in Percentuale rivalsa
UPDATE `osm_totino`.`zz_settings` SET `nome` = 'Percentuale rivalsa' WHERE `zz_settings`.`nome` = 'Percentuale rivalsa INPS';