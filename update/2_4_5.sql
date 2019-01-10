INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Ricevute FE', 'Ricevute FE', (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita'), 'tab_main', 'receiptFE', 'custom');

UPDATE `fe_stati_documento` SET `icon` = 'fa fa-check text-success' WHERE `codice` = 'ACK';

-- Introduzione del flag split payment
ALTER TABLE `an_anagrafiche` ADD `split_payment` BOOLEAN NOT NULL DEFAULT FALSE AFTER `codice_destinatario`;

-- Prezzo di acquisto in Fatture di vendita e Preventivi

ALTER TABLE `co_righe_documenti` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;
ALTER TABLE `co_righe_preventivi` ADD `prezzo_unitario_acquisto` DECIMAL(12,4) NOT NULL AFTER `descrizione`;

-- Uniformati codici con standard SDI e aggiunta 2 stati mancanti
UPDATE `fe_stati_documento` SET `codice`='EC01' WHERE `codice`='ACK';
UPDATE `fe_stati_documento` SET `codice`='EC02', `descrizione`='Rifiutata' WHERE `codice`='REF';
UPDATE `fe_stati_documento` SET `codice`='RC', `descrizione`='Consegnata' WHERE `codice`='SENT';
INSERT INTO `fe_stati_documento`( `codice`, `descrizione`, `icon` ) VALUES
( 'MC', 'Mancata consegna', 'fa fa-exclamation-circle text-danger' ),
( 'DT', 'Decorrenza termini', 'fa fa-calendar-times-o text-danger' ),
( 'NS', 'Scartata', 'fa fa-times text-danger' );

-- ssl_no_verify
ALTER TABLE `zz_smtps` ADD `ssl_no_verify` BOOLEAN NOT NULL DEFAULT FALSE AFTER `encryption`;

-- Introduzione del flag split payment per documenti
ALTER TABLE `co_documenti` ADD `split_payment` BOOLEAN NOT NULL DEFAULT FALSE AFTER `bollo`;

-- Fix campo calcolo_ritenutaacconto
UPDATE `co_righe_documenti` SET `calcolo_ritenutaacconto` = 'IMP' WHERE `calcolo_ritenutaacconto` = 'Imponibile' OR `calcolo_ritenutaacconto` = '';
UPDATE `co_righe_documenti` SET `calcolo_ritenutaacconto` = 'IMP+RIV' WHERE `calcolo_ritenutaacconto` = 'Imponibile + rivalsa inps';
ALTER TABLE `co_righe_documenti` CHANGE `calcolo_ritenutaacconto` `calcolo_ritenuta_acconto` ENUM('IMP', 'IMP+RIV') DEFAULT 'IMP';
UPDATE `zz_settings` SET `tipo` = 'query=SELECT ''IMP'' AS id, ''Imponibile'' AS descrizione UNION SELECT ''IMP+RIV'' AS id, ''Imponibile + rivalsa inps'' AS descrizione', `valore` = REPLACE(REPLACE(`valore`, 'Imponibile + rivalsa inps', 'IMP+RIV'), 'Imponibile', 'IMP') WHERE `nome` = 'Metodologia calcolo ritenuta d''acconto predefinito';

-- Fix per province caricate a gestionale in minuscolo 
UPDATE `an_anagrafiche` SET `provincia` = UPPER(provincia);
UPDATE `an_sedi` SET `provincia` = UPPER(provincia);

-- Colonna Codice Modalità (Pagamenti)
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Pagamenti'), 'Codice Modalità', 'codice_modalita_pagamento_fe', 2, 1, 0, 0, NULL, NULL, 1, 0, 0);

-- Impostazione "Anagrafica del terzo intermediario"
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Terzo intermediario', '', 'query=SELECT `an_anagrafiche`.`idanagrafica` AS ''id'', `ragione_sociale` AS ''descrizione'' FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `idtipoanagrafica` FROM `an_tipianagrafiche` WHERE `descrizione` = ''Fornitore'') AND `deleted_at` IS NULL', '1', 'Fatturazione Elettronica');

--  Aggiungo campi nome e cognome
ALTER TABLE `an_anagrafiche` CHANGE `nome_cognome` `nome` VARCHAR(255) NOT NULL;
ALTER TABLE `an_anagrafiche` ADD `cognome` VARCHAR(255) NOT NULL AFTER `nome`;

-- Colonna Rif. fattura (Prima nota)
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Rif. fattura', '(SELECT numero_esterno FROM co_documenti WHERE id = iddocumento)', 2, 1, 0, 0, NULL, NULL, 1, 0, 0);

-- Aumento decimali percentuali delle rate pagamenti a 2
ALTER TABLE `co_pagamenti` CHANGE `prc` `prc` DECIMAL(5,2) NOT NULL; 

-- Ordino gestione documentale per data, nome
UPDATE `zz_modules` SET `options` = '{ "main_query": [ { "type": "table", "fields": "Categoria, Nome, Data", "query": "SELECT id,(SELECT descrizione FROM zz_documenti_categorie WHERE zz_documenti_categorie.id = idcategoria) AS Categoria, zz_documenti.nome AS Nome, DATE_FORMAT( zz_documenti.`data`, ''%d/%m/%Y'' ) AS `Data` FROM zz_documenti WHERE `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 1=1 ORDER BY data, nome"} ]}' WHERE `zz_modules`.`name` = 'Gestione documentale';

-- Ordino Ddt anche per created_at (nel caso di stessa data e che il cast del numero esterno non sia efficace)
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id` WHERE 1=1 AND `dir` = ''entrata'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di vendita';

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id` WHERE 1=1 AND `dir` = ''uscita'' AND `data` >= ''|period_start|'' AND `data` <= ''|period_end|'' HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC, `dt_ddt`.created_at DESC' WHERE `zz_modules`.`id` = 'Ddt di acquisto';

-- Aggiunti pagamenti mancanti Assegno circolare,Contanti presso Tesoreria, Vaglia cambiario, Bollettino bancario,  RID, RID utenze, RID veloce, MAV, Quietanza erario, Giroconto su conti di contabilità speciale, Domiciliazione bancaria,  Domiciliazione postale, Bollettino di c/c postale, SEPA Direct Debit, SEPA Direct Debit CORE, SEPA Direct Debit B2B, Trattenuta su somme già riscosse
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Assegno circolare', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP03');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Contanti presso Tesoreria', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP04');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Vaglia cambiario', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP06');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Bollettino bancario', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP07');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'RID', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP09');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'RID utenze', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP10');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'RID veloce', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP11');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'MAV', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP13');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Quietanza erario', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP14');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Giroconto su conti di contabilità speciale', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP15');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Domiciliazione bancaria', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP16');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Domiciliazione postale', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP17');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Bollettino di c/c postale', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP18');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'SEPA Direct Debit', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP19');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'SEPA Direct Debit CORE', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP20');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'SEPA Direct Debit B2B', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP21');
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `created_at`, `updated_at`, `idconto_vendite`, `idconto_acquisti`, `codice_modalita_pagamento_fe`) VALUES (NULL, 'Trattenuta su somme già riscosse', '0', '1', '100', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL, NULL, 'MP22');
