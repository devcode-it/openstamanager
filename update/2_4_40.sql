-- Aggiunta campo Pagamento predefinito in vista Anagrafiche
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Anagrafiche'), 'Pagamento cliente', '`pagvendita`.`nome`', '15', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Anagrafiche'), 'Pagamento fornitore', '`pagacquisto`.`nome`', '16', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

-- Aggiunta descrizione codice natura N7
SELECT @codice := MAX(CAST(codice AS UNSIGNED))+1 FROM co_iva WHERE deleted_at IS NULL;
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Regime OSS, D.Lgs. 83/2021', '0.00', '0.00', '1', NULL, 'N7', NULL, @codice, 'I', '1'); 

-- Aggiunta campo agente in Preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Preventivi'), 'Agente', '`agente`.`nome`', '11', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

-- Aggiunta colonna Inviato in DDT in uscita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Ddt di vendita'), 'icon_Inviato', "IF(`email`.`id_email` IS NOT NULL, 'fa fa-envelope text-success', '')", '13', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

-- Fix problemi integrità db se si aggiorna (o si è aggiornato in passato) da una versione precedente alla 2.4.28
ALTER TABLE `an_mansioni` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `an_mansioni` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `em_mansioni_template` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `em_mansioni_template` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Fix query vista Pagamenti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`pagamenti`.`tipo`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Codice pagamento';

-- Aggiunta impostazione Numero massimo widget per colonna
INSERT INTO `zz_settings`(`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Numero massimo Widget per riga','6','list[1,2,3,4,6]','1','Generali');

-- Modifica widget Magazzino
UPDATE `zz_widgets` SET `query` = 'SELECT REPLACE(REPLACE(REPLACE(FORMAT(SUM(qta),2), ",", "#"), ".", ","), "#", ".") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND 1=1' WHERE `zz_widgets`.`name` = 'Articoli in magazzino';
UPDATE `zz_widgets` SET `text` = 'Unità' WHERE `zz_widgets`.`name` = 'Articoli in magazzino';
UPDATE `zz_widgets` SET `text` = 'Valore' WHERE `zz_widgets`.`name` = 'Valore magazzino';

-- Aggiunta colonna Banca azienda in Scadenzario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Scadenzario'), 'Banca azienda', '`banca`.`nome`', '18', '1', '0', '0', '0', NULL, NULL, '0', '0', '0'); 

-- Aggiunta dichiarazione d'intento predefinita
ALTER TABLE `an_anagrafiche` ADD `id_dichiarazione_intento_default` INT NULL AFTER `idtipointervento_default`, ADD FOREIGN KEY (`id_dichiarazione_intento_default`) REFERENCES `co_dichiarazioni_intento`(`id`);

-- Aggiunta impostazione Movimentazione articoli da fatture di acquisto
INSERT INTO `zz_settings`(`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Movimenta magazzino da fatture di acquisto','1','boolean','1','Fatturazione Elettronica');

-- Permetto valore null per numero_esterno di co_documenti
ALTER TABLE `co_documenti` CHANGE `numero_esterno` `numero_esterno` VARCHAR(100) NULL DEFAULT NULL; 

-- Aggiunta impostazione Posizione della valuta
INSERT INTO `zz_settings`(`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Posizione del simbolo valuta','Dopo','list[Prima,Dopo]','1','Generali');

-- Miglioria segmenti scadenzario
UPDATE `zz_segments` SET `name` = 'Scadenzario completo' WHERE `zz_segments`.`name` = 'Scadenziaro completo'; 
UPDATE `zz_segments` SET `clause` = "(`co_scadenziario`.`scadenza` BETWEEN '|period_start|' AND '|period_end|' AND codice_tipo_documento_fe NOT IN ('TD16', 'TD17', 'TD18', 'TD19', 'TD20', 'TD21', 'TD22', 'TD23', 'TD26', 'TD27', 'TD28'))" WHERE `zz_segments`.`name` = 'Scadenzario completo';
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`,`note`, `dicitura_fissa`,`predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenzario autofatture', "(codice_tipo_documento_fe IN ('TD16', 'TD17', 'TD18', 'TD19', 'TD20', 'TD21', 'TD22', 'TD23', 'TD26', 'TD27', 'TD28'))", 'WHR', '####', '', '', 0, 0, 0, 0, 0); 

-- Impostazione per fatturare attività collegati ad altri documenti
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Permetti fatturazione delle attività collegate a contratti, ordini e preventivi', '0', 'boolean', '1', 'Fatturazione', NULL, NULL);

-- Aggiunta impostazione stato predefinito attività
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Stato predefinito dell\'attività da Dashboard", IFNULL ((SELECT idstatointervento FROM in_statiintervento WHERE codice = "WIP"), 0), 'query=SELECT idstatointervento AS id, descrizione AS text FROM in_statiintervento', '1', 'Attività', NULL, NULL);
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Stato predefinito dell\'attività", IFNULL((SELECT idstatointervento FROM in_statiintervento WHERE codice = "TODO"), 0), 'query=SELECT idstatointervento AS id, descrizione AS text FROM in_statiintervento', '1', 'Attività', NULL, NULL);

-- Aggiunta colonna KM in vista Attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'KM', 'sum(in_interventi_tecnici.km)', '29', '1', '0', '1', '0', NULL, NULL, '0', '1', '0'); 

-- Aggiunta impostazione data emissione automatica
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, "Data emissione fattura automatica", '0', 'boolean', '1', 'Fatturazione', NULL, "Impedisce l'emissione di fatture di vendita con data precedente alla data dell'ultima fattura emessa");

-- Fix name file Fatture Elettroniche in zz_files se si aggiorna da una versione precedente alla 2.4.4
UPDATE `zz_files` SET `name` = 'Fattura Elettronica' WHERE `name` = 'Fattura Elettronica (XML)'; 

ALTER TABLE `dt_ddt` CHANGE `numero` `numero` VARCHAR(100) NOT NULL; 
