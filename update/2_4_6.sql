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

-- Aggiunto stato WAIT, fattura elettronica in elaborazione
UPDATE `fe_stati_documento` SET `descrizione`='In elaborazione' WHERE `codice`='WAIT';

-- Inserito stato errore interno OSM
INSERT INTO `fe_stati_documento`( `codice`, `descrizione`, `icon` ) VALUES( 'ERVAL', 'Errore di validazione', 'fa fa-edit text-danger' );
ALTER TABLE `co_documenti` ADD `data_stato_fe` TIMESTAMP, ADD `descrizione_ricevuta_fe` TEXT;

-- Rimozione iva eliminata
UPDATE `zz_settings` SET `tipo`='query=SELECT id, descrizione FROM `co_iva` WHERE deleted_at IS NULL ORDER BY descrizione ASC' WHERE `nome`='Iva predefinita';

-- Flag fattura per conto terzi
ALTER TABLE `co_documenti` ADD `is_fattura_conto_terzi` BOOLEAN NOT NULL DEFAULT FALSE AFTER `split_payment`;

-- Migliorata visualizzazione impostazione
UPDATE `zz_settings` SET `tipo` = 'query=SELECT codice AS id, CONCAT_WS(\' - \', codice, descrizione) AS descrizione FROM fe_tipo_cassa' WHERE `zz_settings`.`nome` = 'Tipo Cassa';

-- Tabella co_ritenuta_contributi (ENASARCO, ECC..)
CREATE TABLE IF NOT EXISTS `co_ritenuta_contributi` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `descrizione` varchar(100) NOT NULL,
    `percentuale` decimal(5,2) NOT NULL,
    `percentuale_imponibile` decimal(5,2) NOT NULL,
    PRIMARY KEY (`id`)
);

-- Fix icon_Inviata Fatture di vendita per errore subquery
UPDATE `zz_views` SET `query` = '(SELECT GROUP_CONCAT(DISTINCT `name` SEPARATOR \'\r\n \') FROM zz_operations INNER JOIN zz_emails ON zz_operations.id_email = zz_emails.id WHERE zz_operations.id_module = (SELECT id FROM zz_modules WHERE `name` = \'Fatture di vendita\') AND op = \'send-email\' AND id_record = co_documenti.id ORDER BY zz_operations.created_at DESC LIMIT 1)' WHERE `zz_views`.`name` = 'icon_title_Inviata' AND id_module = (SELECT id FROM zz_modules WHERE name = 'Fatture di vendita');

UPDATE `zz_views` SET `query` = 'IF((SELECT GROUP_CONCAT(DISTINCT `name` SEPARATOR \'\r\n \') FROM zz_operations INNER JOIN zz_emails ON zz_operations.id_email = zz_emails.id WHERE zz_operations.id_module = (SELECT id FROM zz_modules WHERE `name` = \'Fatture di vendita\') AND op = \'send-email\' AND id_record = co_documenti.id ORDER BY zz_operations.created_at DESC LIMIT 1) IS NOT NULL, \'fa fa-envelope text-success\', \'\') ' WHERE `zz_views`.`name` = 'icon_Inviata' AND id_module = (SELECT id FROM zz_modules WHERE name = 'Fatture di vendita');

-- Limitato all'anno in corso il controllo per i numeri duplicati nelle fatture
UPDATE `zz_views` SET `query` = 'IF((SELECT COUNT(t.numero_esterno) FROM co_documenti AS t WHERE t.numero_esterno = co_documenti.numero_esterno AND t.numero_esterno != '''' AND t.id_segment = co_documenti.id_segment AND idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir = ''entrata'') AND t.data >= ''|period_start|'' AND t.data <= ''|period_end|'' AND YEAR(t.data) = YEAR(co_documenti.data)  ) > 1, ''red'', '''') ' WHERE `zz_views`.`name` = '_bg_' AND id_module = (SELECT id FROM zz_modules WHERE name = 'Fatture di vendita');

-- Fornitore terzo intermediario abilitato all'invio della fattura elettronica
UPDATE `zz_settings` SET `help` = 'Fornitore, mio intermediario abilitato all\'invio della fattura elettronica (es. Aruba, Teamsystem ecc...)' WHERE `zz_settings`.`nome` = 'Terzo intermediario';

-- Aggiunta percentuale_imponibile su cui applicare il calcolo della ritenuta
ALTER TABLE `co_ritenutaacconto` ADD `percentuale_imponibile` DECIMAL(5,2) NOT NULL AFTER `esente`;

-- Aggiunto modulo per gestire la seconda ritenuta (ENASARCO, ECC..)
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Ritenute contributi', 'Ritenute contributi','ritenute_contributi', 'SELECT |select| FROM `co_ritenuta_contributi` WHERE 1=1 HAVING 2=2', '', 'fa fa-percent', '2.4.6', '2.4.6', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute contributi'), 'Percentuale imponibile', 'percentuale_imponibile', 4, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute contributi'), 'Percentuale', 'percentuale', 3, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute contributi'), 'Descrizione', 'descrizione', 2, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute contributi'), 'id', 'id', 1, 1, 0, 0);

UPDATE `zz_views` SET `query` = 'percentuale_imponibile', `name` = 'Percentuale imponibile' WHERE `zz_views`.`name` = 'Indetraibile' AND id_module = (SELECT id FROM zz_modules WHERE name = 'Ritenute acconto');
