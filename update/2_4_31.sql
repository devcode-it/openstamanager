-- Aggiunta dicitura fissa nei segmenti fiscali
ALTER TABLE `zz_segments` ADD `dicitura_fissa` TEXT NOT NULL AFTER `note`; 

-- Fix codice iva 
UPDATE `co_iva` SET `codice`=`id` WHERE `codice` IS NULL; 

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
ALTER TABLE `zz_views` ADD `html_format` TINYINT NOT NULL DEFAULT '0' AFTER `format`; 
UPDATE `zz_views` SET `html_format` = '0'; 

-- Correzione widget valore magazzino
UPDATE `zz_widgets` SET `query` = 'SELECT CONCAT_WS(\" \", REPLACE(REPLACE(REPLACE(FORMAT(SUM(prezzo_acquisto*qta),2), \",\", \"#\"), \".\", \",\"), \"#\", \".\"), \"&euro;\") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND 1=1', `help` = 'Articoli a magazzino (tutti o solo attivi secondo il segmento)' WHERE `zz_widgets`.`name` = 'Valore magazzino';

-- Aggiunto pagamenti predefiniti per importazione FE
ALTER TABLE `co_pagamenti` ADD `predefined` TINYINT NOT NULL AFTER `idconto_acquisti`; 
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `idconto_vendite`, `idconto_acquisti`, `predefined`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Ri.Ba.', '0', '0', '100.00', '2', '2', '1', 'MP12');
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Contanti' AND `codice_modalita_pagamento_fe`='MP01'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Assegno' AND `codice_modalita_pagamento_fe`='MP02'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Assegno circolare' AND `codice_modalita_pagamento_fe`='MP03'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Contanti presso Tesoreria' AND `codice_modalita_pagamento_fe`='MP04'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Bonifico bancario' AND `codice_modalita_pagamento_fe`='MP05'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Vaglia cambiario' AND `codice_modalita_pagamento_fe`='MP06'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Bollettino bancario' AND `codice_modalita_pagamento_fe`='MP07'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Bancomat' AND `codice_modalita_pagamento_fe`='MP08'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'RID' AND `codice_modalita_pagamento_fe`='MP09'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'RID utenze' AND `codice_modalita_pagamento_fe`='MP10'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'RID veloce' AND `codice_modalita_pagamento_fe`='MP11'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'MAV' AND `codice_modalita_pagamento_fe`='MP13'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Quietanza erario' AND `codice_modalita_pagamento_fe`='MP14'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Giroconto su conti di contabilità speciale' AND `codice_modalita_pagamento_fe`='MP15'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Domiciliazione bancaria' AND `codice_modalita_pagamento_fe`='MP16'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Domiciliazione postale' AND `codice_modalita_pagamento_fe`='MP17'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Bollettino di c/c postale' AND `codice_modalita_pagamento_fe`='MP18'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'SEPA Direct Debit' AND `codice_modalita_pagamento_fe`='MP19'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'SEPA Direct Debit CORE' AND `codice_modalita_pagamento_fe`='MP20'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'SEPA Direct Debit B2B' AND `codice_modalita_pagamento_fe`='MP21'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'Trattenuta su somme già riscosse' AND `codice_modalita_pagamento_fe`='MP22'; 
UPDATE `co_pagamenti` SET `predefined` = '1' WHERE `co_pagamenti`.`descrizione` = 'PagoPA' AND `codice_modalita_pagamento_fe`='MP23'; 

-- Segmento Tutti/Solo attivi per articoli.
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Tutti', '1=1', 'WHR', '####', '', 1, 0, 0, 0),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Solo attivi', 'attivo=1', 'WHR', '####', '', 0, 0, 0, 0);

-- Correzione widget articoli in magazzino
UPDATE `zz_widgets` SET `help` = 'Articoli a magazzino (tutti o solo attivi secondo il segmento)' WHERE `zz_widgets`.`name` = 'Articoli in magazzino';
-- Aggiunta colonna "Servizio" per vista Articoli
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Servizio', 'IF(mg_articoli.servizio, ''Sì'', ''No'')', 13, 1, 0, 1);

-- Summable per Q.tà, Q.tà disponibile, Q.tà impegnata e Q.tà ordinata
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`summable` = '1' WHERE `zz_modules`.`name` = 'Articoli' AND (`zz_views`.`name` = 'Q.tà ordinata' OR `zz_views`.`name` = 'Q.tà' OR `zz_views`.`name` = 'Q.tà disponibile' OR `zz_views`.`name` = 'Q.tà impegnata');

-- Stampe definitive registri iva
CREATE TABLE `co_stampecontabili` ( `id` INT NOT NULL AUTO_INCREMENT , `id_print` INT NOT NULL , `date_start` DATE NOT NULL , `date_end` DATE NOT NULL , `first_page` INT NOT NULL , `last_page` INT NOT NULL , `dir` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`));

-- Coefficiente di vendita
ALTER TABLE `mg_articoli` ADD `coefficiente` DECIMAL(12,6) NOT NULL AFTER `prezzo_acquisto`; 

-- Codice iva in selezione Iva per lettere d'intento
UPDATE `zz_settings` SET `tipo` = 'query=SELECT id, CONCAT(codice,\' - \',descrizione) AS descrizione FROM `co_iva` WHERE codice_natura_fe LIKE \'N3.%\' AND deleted_at IS NULL ORDER BY descrizione ASC' WHERE `zz_settings`.`nome` = 'Iva per lettere d''intento'; 

-- Aggiunte colonne codice e barcode fornitore in listini
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Codice', '(SELECT codice_fornitore FROM mg_fornitore_articolo WHERE id_articolo=mg_prezzi_articoli.id_articolo AND id_fornitore=mg_prezzi_articoli.id_anagrafica AND deleted_at IS NULL)', 8, 1, 0, 0, 1, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Barcode', '(SELECT barcode_fornitore FROM mg_fornitore_articolo WHERE id_articolo=mg_prezzi_articoli.id_articolo AND id_fornitore=mg_prezzi_articoli.id_anagrafica AND deleted_at IS NULL)', 9, 1, 0, 0, 1, '', '', 0, 0, 1);

-- Rimozione widget Stampa riepilogo
DELETE FROM `zz_widgets` WHERE `zz_widgets`.`name` = 'Stampa riepilogo';


INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Prima nota'), (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Prima nota'), 'Prima nota', 'Prima nota', 'Prima nota del {data}', 'prima_nota', 'idmastrino', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- Aggiunto plugin Regole pagamenti
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Regole pagamenti', 'Regole pagamenti', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'tab', '', '1', '1', '0', '', '', NULL, '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Mese da posticipare, Giorno riprogrammazione scadenza\", \"query\": \"SELECT id, IF(mese=\'01\', \'Gennaio\', IF(mese=\'02\', \'Febbraio\',IF(mese=\'03\', \'Marzo\',IF(mese=\'04\', \'Aprile\',IF(mese=\'05\', \'Maggio\', IF(mese=\'06\', \'Giugno\', IF(mese=\'07\', \'Luglio\',IF(mese=\'08\', \'Agosto\',IF(mese=\'09\', \'Settembre\', IF(mese=\'10\', \'Ottobre\', IF(mese=\'11\', \'Novembre\',\'Dicembre\'))))))))))) AS `Mese da posticipare`, giorno_fisso AS `Giorno riprogrammazione scadenza` FROM an_pagamenti_anagrafiche WHERE 1=1 AND idanagrafica=|id_parent| GROUP BY id HAVING 2=2 ORDER BY an_pagamenti_anagrafiche.mese ASC\"} ]}', 'pagamenti_anagrafiche', '');
CREATE TABLE `an_pagamenti_anagrafiche` ( `id` INT NOT NULL AUTO_INCREMENT , `mese` INT NOT NULL , `giorno_fisso` INT NOT NULL , `idanagrafica` INT NOT NULL , PRIMARY KEY (`id`));

-- Aggiunta impostazione per personalizzare dicitura riferimento attività
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Descrizione personalizzata in fatturazione', '', 'textarea', '1', 'Attività', '17', 'Variabili utilizzabili: \n {email}\n {numero}\n {ragione_sociale}\n {richiesta}\n {descrizione}\n {data}\n {data richiesta}\n {data fine intervento}\n {id_anagrafica}\n {stato}\n');

-- Aggiunto plugin Registrazioni in Fatture
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Registrazioni', 'Registrazioni', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'tab', '', '1', '0', '0', '', '', NULL, 'custom', 'registrazioni', '');
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Registrazioni', 'Registrazioni', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'tab', '', '1', '0', '0', '', '', NULL, 'custom', 'registrazioni', '');