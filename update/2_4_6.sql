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

-- Rinomino tabella gestione rivalse
RENAME TABLE co_rivalsainps TO co_rivalse;

-- Fix Percentuale rivalsa INPS in Percentuale rivalsa in impostazioni
UPDATE `zz_settings` SET `nome` = 'Percentuale rivalsa', `tipo` = 'query=SELECT id, descrizione FROM `co_rivalse` ORDER BY descrizione ASC'  WHERE `zz_settings`.`nome` = 'Percentuale rivalsa INPS';

-- Fix Metodologia calcolo ritenuta 
UPDATE `zz_settings` SET `tipo` = 'query=SELECT ''IMP'' AS id, ''Imponibile'' AS descrizione UNION SELECT ''IMP+RIV'' AS id, ''Imponibile + rivalsa'' AS descrizione' WHERE `zz_settings`.`nome` = 'Metodologia calcolo ritenuta d\'acconto predefinito';

-- Aggiunto modulo per gestire le rivalse
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Rivalse', 'Rivalse', 'rivalse', 'SELECT |select| FROM `co_rivalse` WHERE 1=1 HAVING 2=2', '', 'fa fa-percent', '2.4.6', '2.4.6', '1', (SELECT id FROM zz_modules t WHERE t.name = 'Tabelle' ), '1', '1');

-- Colonne per modulo 'Rivalse'
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES 
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Rivalse'), 'id', 'id', 1, 1, 0, 0, NULL, NULL, 0, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Rivalse'), 'Descrizione', 'descrizione', 2, 1, 0, 0, NULL, NULL, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Rivalse'), 'Percentuale', 'percentuale', 3, 1, 0, 0, NULL, NULL, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Rivalse'), 'Indetraibile', 'indetraibile', 4, 1, 0, 0, NULL, NULL, 1, 0, 1);

-- Aggiunto campo help per impostazioni
ALTER TABLE `zz_settings` ADD `help` VARCHAR(255) NOT NULL AFTER `order`;

-- Aggiunto help per impostazione tipo cassa
UPDATE `zz_settings` SET `help` = 'Definisce il tipo della rivalsa' WHERE `zz_settings`.`nome` = 'Tipo Cassa';

-- Aggiorno indirizzo a cui inviare le FE
UPDATE `zz_emails` SET `cc` = 'sdi24@pec.fatturapa.it' WHERE `zz_emails`.`name` = 'PEC' AND `zz_emails`.`cc` = 'sdi01@pec.fatturapa.it';

-- Aggiunto id documento codice cig e codice cup per preventivi
ALTER TABLE `co_preventivi` ADD `codice_cig` VARCHAR(15) AFTER `master_revision`, ADD `codice_cup` VARCHAR(15) AFTER `codice_cig`, ADD `id_documento_fe` VARCHAR(20) AFTER `codice_cup`;

-- Migliorata visualizzazione impostazione
UPDATE `zz_settings` SET `tipo` = 'query=SELECT codice AS id, CONCAT_WS(\' - \', codice, descrizione) AS descrizione FROM fe_causali_pagamento_ritenuta' WHERE `zz_settings`.`nome` = 'Causale ritenuta d\'acconto';

UPDATE `fe_stati_documento` SET `descrizione`='In elaborazione' WHERE `codice`='WAIT';

INSERT INTO `fe_stati_documento`( `codice`, `descrizione`, `icon` ) VALUES
( 'ERVAL', 'Errore di validazione', 'fa fa-edit text-danger' );