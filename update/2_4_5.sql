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

