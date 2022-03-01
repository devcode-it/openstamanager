-- Aggiunta dicitura fissa nei segmenti fiscali
ALTER TABLE `zz_segments` ADD `dicitura_fissa` TEXT NOT NULL AFTER `note`; 

-- Fix codice iva 
UPDATE `co_iva` SET `codice`=`id` WHERE `codice` IS NULL; 

-- Aggiunta vista Inviato in Scadenzario
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_scadenziario`\nLEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`\nLEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`\nLEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`\nLEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`\nLEFT JOIN (\n         SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\n         FROM `zz_operations`\n                INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\n                INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\n                INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`\n         WHERE `zz_modules`.`name` = ''Scadenzario'' AND `zz_operations`.`op` = ''send-email''\n         GROUP BY `zz_operations`.`id_record`\n     ) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`\nWHERE 1=1 AND\n(`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN(''Emessa'',''Parzialmente pagato'',''Pagato''))\nHAVING 2=2\nORDER BY `scadenza` ASC' WHERE `zz_modules`.`name` = 'Scadenzario';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'icon_Inviato', 'IF(`email`.`id_email` IS NOT NULL, ''fa fa-envelope text-success'', '''')', 16, 1, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'icon_title_Inviato', 'IF(`email`.`id_email` IS NOT NULL, ''Inviato'', '''')', 17, 1, 0, 0, '', '', 0, 0, 1);

-- Set tipo intervento tempo_standard = 1
UPDATE `in_tipiintervento` SET `tempo_standard` = '1' WHERE `in_tipiintervento`.`tempo_standard` = 0 OR `in_tipiintervento`.`tempo_standard` IS NULL; 

-- Aggiunto campo Barcode fornitore
ALTER TABLE `mg_fornitore_articolo` ADD `barcode_fornitore` VARCHAR(255) NOT NULL AFTER `codice_fornitore`; 

-- Aggiunta impostazione per scegliere colore sessioni dashboard
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Visualizzazione colori sessioni', 'Sfondo colore stato - bordo colore tecnico', 'list[Sfondo colore stato - bordo colore tecnico,Sfondo colore tecnico - bordo colore stato]', '1', 'Dashboard', '7', '');

-- Aggiunta impostazione per riportare nei documenti tutti i riferimenti collegati
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Mantieni riferimenti tra tutti i documenti collegati', '1', 'boolean', '1', 'Generali', '19', 'Permette l''aggiunta dei riferimenti di tutti i documenti collegati');

-- Aggiunta colonna Codice in Anagrafiche
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Codice', 'an_anagrafiche.codice', 1, 1, 0, 0, '', '', 0, 0, 1);

-- Aggiunta opzione formattazione HTML nelle viste per la gestione dei campi CKeditor
ALTER TABLE `zz_views` ADD `html_format` TINYINT NOT NULL DEFAULT '1' AFTER `format`; 
UPDATE `zz_views` SET `html_format` = '1'; 

-- Correzione widget valore magazzino
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\" \", REPLACE(REPLACE(REPLACE(FORMAT(SUM(prezzo_acquisto*qta),2), \",\", \"#\"), \".\", \",\"), \"#\", \".\"), \"&euro;\") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND 1=1', `help` = 'Articoli a magazzino (tutti o solo attivi secondo il segmento)' WHERE `zz_widgets`.`name` = 'Valore magazzino';

-- Aggiunte informazioni nella colonna sede per la Sede legale in Interventi
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_interventi`\nINNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`\nLEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`\nLEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`\nLEFT JOIN (\n    SELECT an_sedi.id, CONCAT(an_sedi.nomesede, \'<br />\',IF(an_sedi.telefono!=\'\',CONCAT(an_sedi.telefono,\'<br />\'),\'\'),IF(an_sedi.cellulare!=\'\',CONCAT(an_sedi.cellulare,\'<br />\'),\'\'),an_sedi.citta,IF(an_sedi.indirizzo!=\'\',CONCAT(\' - \',an_sedi.indirizzo),\'\')) AS info FROM an_sedi\n) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione\nLEFT JOIN (\n    SELECT co_righe_documenti.idintervento, CONCAT(\'Fatt. \', co_documenti.numero_esterno, \' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento\n) AS fattura ON fattura.idintervento = in_interventi.id\nLEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\n    FROM `zz_operations`\n    INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\n    INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\n    INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` \n    WHERE `zz_modules`.`name` = \'Interventi\' AND `zz_operations`.`op` = \'send-email\' \n    GROUP BY `zz_operations`.`id_record`) AS email ON email.id_record=in_interventi.id\nWHERE 1=1 |date_period(`orario_inizio`,`data_richiesta`)|\nGROUP BY `in_interventi`.`id`\nHAVING 2=2\nORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';
UPDATE `zz_views` SET `query` = 'IF(in_interventi.idsede_destinazione > 0, sede_destinazione.info, CONCAT(\'Sede legale<br>\',IF(an_anagrafiche.telefono!=\'\',CONCAT(an_anagrafiche.telefono,\'<br />\'),\'\'),IF(an_anagrafiche.cellulare!=\'\',CONCAT(an_anagrafiche.cellulare,\'<br />\'),\'\'),IF(an_anagrafiche.citta!=\'\',an_anagrafiche.citta,\'\'),IF(an_anagrafiche.indirizzo!=\'\',CONCAT(\' - \',an_anagrafiche.indirizzo),\'\')))' WHERE `zz_views`.`name` = 'Sede' AND `id_module`=(SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');

-- Segmento Tutti/Solo attivi per articoli.
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Tutti', '1=1', 'WHR', '####', '', 1, 0, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Solo attivi', 'attivo=1', 'WHR', '####', '', 0, 0, 0, 0);

-- Correzione widget articoli in magazzino
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\" \", REPLACE(REPLACE(REPLACE(FORMAT(SUM(qta),2), \",\", \"#\"), \".\", \",\"), \"#\", \".\"), \"unit&agrave;\") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND 1=1' WHERE `zz_widgets`.`name` = 'Articoli in magazzino', `help` = 'Articoli a magazzino (tutti o solo attivi secondo il segmento)';

-- Aggiunta colonna "Servizio" per vista Articoli
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Servizio', 'IF(mg_articoli.servizio, ''Sì'', ''No'')', 13, 1, 0, 1);

-- Summable per Q.tà, Q.tà disponibile, Q.tà impegnata e Q.tà ordinata
UPDATE `zz_views` SET `summable` = '1' WHERE (`zz_views`.`name` = 'Q.tà ordinata' OR  `zz_views`.`name` = 'Q.tà' OR  `zz_views`.`name` = 'Q.tà disponibile' OR  `zz_views`.`name` = 'Q.tà impegnata') AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'); 
