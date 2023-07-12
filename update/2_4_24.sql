-- Colonna n. protocollo per fatture di acquisto
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Fatture di acquisto'), 'N. Prot.', 'co_documenti.numero', 1, 1, 0, 0, '', '', 0, 0, 0);

-- Formattazione colonna data modulo Ordini fornitore
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`format` = 1 WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'Data';
-- Colonna stato per newsletter
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Newsletter'), 'Stato', 'IF(em_newsletters.state = ''DEV'', ''Bozza'', IF(em_newsletters.state = ''WAIT'', ''Invio in corso'', ''Completata''))', 4, 1, 0, 0, '', '', 1, 0, 0);

-- Colonna destinatari per newsletter
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Newsletter'), 'Destinatari', '(SELECT COUNT(*) FROM `em_newsletter_anagrafica` WHERE `em_newsletter_anagrafica`.`id_newsletter` = `em_newsletters`.`id`)', 5, 1, 0, 0, '', '', 1, 0, 0);

-- Aggiorno colonna completato per newsletter
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`order` = 6 WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Completato';
-- Visualizza informazioni aggiuntive sul calendario
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Visualizza informazioni aggiuntive sul calendario', '0', 'boolean', '1', 'Dashboard', '1', 'Visualizza sul calendario il box Tutto il giorno dove possono essere presenti informazioni aggiuntve');

-- Rinominate stampe ordini fornitore
UPDATE `zz_prints` SET `title` = 'Richiesta di offerta (RdO)' WHERE `zz_prints`.`name` = 'Ordine fornitore (senza costi)';
UPDATE `zz_prints` SET `title` = 'Richiesta di acquisto (RdA)' WHERE `zz_prints`.`name` = 'Ordine fornitore';

-- Aggiunta impostazione formato ore in stampa intervento
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,`order`, `help`) VALUES (NULL, 'Formato ore in stampa', 'Decimale', 'list[Decimale,Sessantesimi]', '1', 'Attività', '1', '');

-- Aggiunta plugin allegati dell'anagrafica
INSERT INTO `zz_plugins` (`name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES ('Allegati', 'Allegati', (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), 'tab', 'allegati.php', '1', '0', '0', '', '', NULL, NULL, '', '');

-- Aggiunta idsede nelle righe dell'intervento
ALTER TABLE `in_righe_interventi` ADD `idsede_partenza` INT NOT NULL AFTER `id_dettaglio_fornitore`;


-- Aggiunto nuovo plugin Componenti e disabilitato quello precedente
UPDATE `zz_plugins` SET `name`='Componenti ini', `title`='Componenti ini' WHERE `name`='Componenti';
UPDATE `zz_plugins` SET `enabled`=0 WHERE `name`='Componenti ini';

INSERT INTO `zz_plugins` ( `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES ('Componenti', 'Componenti', (SELECT `id` FROM `zz_modules` WHERE name='Impianti'), (SELECT `id` FROM `zz_modules` WHERE name='Impianti'), 'tab', '', '1', '0', '0', '', '', NULL, 'custom', 'componenti', '');

CREATE TABLE `my_componenti_articoli` ( `id` INT NOT NULL AUTO_INCREMENT, `id_impianto` INT NOT NULL , `id_articolo` INT NOT NULL , `pre_id_articolo` INT NOT NULL, `note` TEXT NOT NULL , `data_registrazione` DATE NULL , `data_installazione` DATE NULL , `data_disinstallazione` DATE NULL , PRIMARY KEY (`id`));

-- Aggiunta vista referente in modulo attività
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Interventi' ), 'Referente', '(SELECT an_referenti.nome FROM an_referenti WHERE an_referenti.id=in_interventi.idreferente)', '7', '1', '0', '0', '', '', '1', '0', '0');

-- Aggiunta vista scaduto in scadenzario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Scadenzario' ), 'Scaduto', 'IF(pagato = da_pagare, ''NO'', IF(data_concordata IS NOT NULL AND data_concordata > NOW(), ''NO'', IF(scadenza < NOW(), ''SÌ'', ''NO'')))', 14, 1, 0, 0, '', '', 1, 0, 0);

-- Aggiunta vista "N. utenti" per il modulo "Utenti e permessi"
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Utenti e permessi'), 'N. utenti', '(SELECT COUNT(`id`) FROM `zz_users` WHERE `idgruppo` = `zz_groups`.`id`)', 3, 1, 0, 0, '', '', 1, 0, 0);

-- Aggiunto campo confermato, data e ora evasione in righe preventivi
ALTER TABLE `co_righe_preventivi` ADD `data_evasione` DATE NULL DEFAULT NULL AFTER `id`, ADD `ora_evasione` TIME NULL DEFAULT NULL AFTER `data_evasione`;
ALTER TABLE `co_righe_preventivi` ADD `confermato` BOOLEAN NOT NULL AFTER `id_dettaglio_fornitore`;
UPDATE `co_righe_preventivi` SET `confermato` = 1;

-- Aggiunta impostazione per impegnare o meno automaticamente le quantità nei preventivi
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conferma automaticamente le quantità nei preventivi', '1', 'boolean', '1', 'Preventivi', NULL, NULL);
-- Aggiunta vista "Esigibilità" per il modulo "IVA"
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='IVA'), 'Esigibilità', 'IF(esigibilita=''I'', ''IVA ad esigibilità immediata'', IF(esigibilita=''D'', ''IVA ad esigibilità differita'', ''Scissione dei pagamenti''))', 5, 1, 0, 0, '', '', 1, 0, 0);

-- Gestione righe da documenti esterni
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ('Permetti il superamento della soglia quantità dei documenti di origine', '0', 'boolean', '1', 'Generali', '20', NULL);

-- Aggiunta colonna Rif. fattura negli ordini
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'), 'Rif. fattura', 'fattura.info', 11, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'), 'Rif. fattura', 'fattura.info', 8, 1, 0, 0, '', '', 1, 0, 0);

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'app-v1', 'retrieve', 'sedi-azienda', 'API\\App\\v1\\SediAzienda', '1'),
(NULL, 'app-v1', 'retrieve', 'sedi-azienda-cleanup', 'API\\App\\v1\\SediAzienda', '1'),
(NULL, 'app-v1', 'retrieve', 'sede-azienda', 'API\\App\\v1\\SediAzienda', '1'),
(NULL, 'app-v1', 'retrieve', 'movimenti-manuali', 'API\\App\\v1\\MovimentiManuali', '1'),
(NULL, 'app-v1', 'retrieve', 'movimenti-manuali-cleanup', 'API\\App\\v1\\MovimentiManuali', '1'),
(NULL, 'app-v1', 'create', 'movimento-manuale', 'API\\App\\v1\\MovimentiManuali', '1'),
(NULL, 'app-v1', 'retrieve', 'controllo-clienti', 'API\\App\\v1\\ControlloClienti', '1'),
(NULL, 'app-v1', 'retrieve', 'segnalazione-bug', 'API\\App\\v1\\SegnalazioneBug', '1'),
(NULL, 'app-v1', 'create', 'segnalazione-bug', 'API\\App\\v1\\SegnalazioneBug', '1');

-- Aggiunto collegamento tra DDT in direzioni opposte per gestione movimentazioni interne tra sedi
ALTER TABLE `dt_ddt` ADD `id_ddt_trasporto_interno` INT(11) NULL, ADD FOREIGN KEY (`id_ddt_trasporto_interno`) REFERENCES `dt_ddt`(`id`) ON DELETE CASCADE;

-- Aggiunto ragruppamento referenti per sede
UPDATE `zz_plugins` SET `options` = ' { \"main_query\": [ { \"type\": \"table\", \"fields\": \"Nome, Indirizzo, Città, CAP, Provincia, Referente\", \"query\": \"SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia, GROUP_CONCAT(an_referenti.nome SEPARATOR \\\", \\\") AS Referente FROM an_sedi LEFT OUTER JOIN an_referenti ON idsede = an_sedi.id WHERE 1=1 AND an_sedi.idanagrafica=|id_parent| GROUP BY an_sedi.id HAVING 2=2 ORDER BY an_sedi.id DESC\"} ]}' WHERE `zz_plugins`.`name` = 'Sedi';
UPDATE `zz_group_module` SET `clause` = 'in_interventi.id IN (SELECT idintervento FROM in_interventi_tecnici WHERE idintervento=in_interventi.id AND idtecnico=|id_anagrafica| UNION SELECT id_intervento FROM in_interventi_tecnici_assegnati WHERE id_intervento=in_interventi.id AND id_tecnico=|id_anagrafica|)' WHERE `zz_group_module`.`name` = 'Mostra interventi ai tecnici coinvolti';

-- Aggiunto supporto autenticazione OAuth 2
ALTER TABLE `em_accounts` ADD `provider` varchar(255),
    ADD `client_id` TEXT,
    ADD `client_secret` TEXT,
    ADD `oauth2_state` TEXT,
    ADD `access_token` TEXT,
    ADD `refresh_token` TEXT;

-- Aggiornamento plugin Listini per Articoli
UPDATE `zz_plugins` SET `name` = 'Listino Clienti', `title` = 'Listino clienti', `directory` = 'listino_clienti' WHERE `zz_plugins`.`name` = 'Prezzi specifici articolo';
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `directory`, `options`) VALUES
(NULL, 'Listino Fornitori', 'Listino fornitori', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'listino_fornitori', 'custom'),
(NULL, 'Piani di sconto/maggiorazione', 'Piani di sconto/magg.', (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name`='Articoli'), 'tab', 'piani_sconto_maggiorazione', 'custom');

UPDATE `zz_modules` SET `name` = 'Piani di sconto/maggiorazione' WHERE `name` = 'Piani di sconto/magg.';

-- Aggiunta riferimento documenti
ALTER TABLE `co_righe_promemoria` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;
ALTER TABLE `in_righe_interventi` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;
ALTER TABLE `co_righe_contratti` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;
ALTER TABLE `co_righe_preventivi` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;
ALTER TABLE `co_righe_documenti` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;
ALTER TABLE `or_righe_ordini` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;
ALTER TABLE `dt_righe_ddt` ADD `original_document_type` varchar(255) AFTER `original_type`, ADD `original_document_id` int(11) AFTER `original_type`;

UPDATE `co_righe_documenti` SET `original_document_id` = `idcontratto`, `original_document_type` = 'Modules\\Contratti\\Contratto' WHERE `idcontratto` != 0;
UPDATE `co_righe_documenti` SET `original_document_id` = `idpreventivo`, `original_document_type` = 'Modules\\Preventivi\\Preventivo' WHERE `idpreventivo` != 0;
UPDATE `co_righe_documenti` SET `original_document_id` = `idintervento`, `original_document_type` = 'Modules\\Interventi\\Intervento' WHERE `idintervento` IS NOT NULL;
UPDATE `co_righe_documenti` SET `original_document_id` = `idordine`, `original_document_type` = 'Modules\\Ordini\\Ordine' WHERE `idordine` != 0;
UPDATE `co_righe_documenti` SET `original_document_id` = `idintervento`, `original_document_type` = 'Modules\\DDT\\DDT' WHERE `idddt` != 0;
UPDATE `co_righe_documenti` INNER JOIN `co_righe_documenti` AS origine ON `origine`.`id` = `co_righe_documenti`.`ref_riga_documento` SET `co_righe_documenti`.`original_document_id` = `origine`.`iddocumento`, `co_righe_documenti`.`original_document_type` = 'Modules\\Fatture\\Fattura' WHERE `co_righe_documenti`.`ref_riga_documento` IS NOT NULL;

UPDATE `dt_righe_ddt` SET `original_document_id` = `idordine`, `original_document_type` = 'Modules\\Ordini\\Ordine' WHERE `idordine` != 0;

UPDATE `or_righe_ordini` SET `original_document_id` = `idpreventivo`, `original_document_type` = 'Modules\\Preventivi\\Preventivo' WHERE `idpreventivo` != 0;
