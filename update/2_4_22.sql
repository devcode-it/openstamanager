-- Rimozione campo formattabile su "Causale" e "Sede destinazione" dei Ddt
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`format` = 0 WHERE `zz_views`.`name` IN('Causale', 'Sede partenza') AND `zz_modules`.`name` IN('Ddt di vendita', 'Ddt di acquisto');

-- Aggiunta colonna reversed
ALTER TABLE `dt_causalet` ADD `reversed` TINYINT(1) NOT NULL AFTER `is_importabile`;
UPDATE `dt_causalet` SET `reversed`=1 WHERE `descrizione`='Reso';

-- Ottimizzazione per ricerca articoli da ajax select
ALTER TABLE `mg_movimenti` ADD INDEX(`idarticolo`);

-- Aggiunta possibilità di scegliere uno stato dopo la firma anche se non ha il flag completato
UPDATE `zz_settings` SET `tipo`='query=SELECT idstatointervento AS id, descrizione AS text FROM in_statiintervento' WHERE `nome`='Stato dell''attività dopo la firma';

-- Aggiunto filtro N3.% nella scelta aliquota per le dichiarazioni d'intento
UPDATE `zz_settings` SET `tipo` = 'query=SELECT id, descrizione FROM `co_iva` WHERE codice_natura_fe LIKE \'N3.%\' AND deleted_at IS NULL ORDER BY descrizione ASC' WHERE `zz_settings`.`nome` = 'Iva per lettere d''intento';


-- Aggiunte descrizioni aliquote IVA con codice natura 2.1
UPDATE `co_iva` SET `descrizione`='Art.7 bis DPR 633/1972 (cessione di beni extra-UE)' WHERE `descrizione`='Non soggetta ad IVA ai sensi degli artt. Da 7 a 7-septies del DPR 633/72' AND `codice_natura_fe`='N2.1';
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.7 ter  DPR 633/1972 prestazione servizi UE (vendite)', '0.00', '0.00', '1', NULL, 'N2.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.7 ter  DPR 633/1972 prestazione servizi extra-UE', '0.00', '0.00', '1', NULL, 'N2.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.7 quater DPR 633/1972 prestazione servizi UE (vendite)', '0.00', '0.00', '1', NULL, 'N2.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.7 quater DPR 633/1972 prestazione servizi extra-UE', '0.00', '0.00', '1', NULL, 'N2.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.7 quinquies DPR 633/1972 (prestazione servizi)', '0.00', '0.00', '1', NULL, 'N2.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.7 sexies, septies DPR 633/1972 (prestazione servizi)', '0.00', '0.00', '1', NULL, 'N2.1', NULL, NULL, 'I', '1');

-- Aggiunte descrizioni aliquote IVA con codice natura 2.2
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Fuori campo Iva art. 2 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Fuori campo Iva art. 3 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Fuori campo Iva art. 4 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Fuori campo Iva art. 5 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 38 c.5 DL  331/1993', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.17 c.3 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.19 c.3 lett. b DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 50 bis c.4 DL 331/1993', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.74 cc.1 e 2 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.19 c.3 lett. e DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.13 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 27 c.1 e 2 DL 98/2011 (contribuenti minimi)', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.1 c.54-89 L. 190/2014 e succ. modifiche (regime forfettario)', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.26 c.3 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'DM 9/4/1993', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.26 bis L.196/1997', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.8 c.35 L. 67/1988', '0.00', '0.00', '1', NULL, 'N2.2', NULL, NULL, 'I', '1');

-- Aggiunte descrizioni aliquote IVA con codice natura 3.()
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.8 c.1 lett.a DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.8 c.1 lett.b DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art. 8 c.1 lett. b-bis  DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.9 c.2 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp.art.72 c.1 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.50 bis c.4 lett. g DL 331/93', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.14 legge n. 49/1987', '0.00', '0.00', '1', NULL, 'N3.1', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.50 bis c.4 lett. f DL 331/93', '0.00', '0.00', '1', NULL, 'N3.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.41 DL 331/93', '0.00', '0.00', '1', NULL, 'N3.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.58 c.1 DL 331/93', '0.00', '0.00', '1', NULL, 'N3.2', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 8 bis DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.4', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art. 8 bis c.2 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.4', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art. 8 c.1 lett. c DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.5', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art.9 c.1 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.6', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.72 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.6', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 71 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.6', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Art. 2 c. 2, n. 4 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.6', NULL, NULL, 'I', '1');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Non imp. art.38 quater c.1 DPR 633/1972', '0.00', '0.00', '1', NULL, 'N3.6', NULL, NULL, 'I', '1');

-- Aggiunto ckeditor Condizioni generali di fornitura in impostazioni preventivi
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Condizioni generali di fornitura', '', 'ckeditor', '1', 'Preventivi', NULL, NULL);

-- Aggiunta colonna condizioni_fornitura in co_preventivi
ALTER TABLE `co_preventivi` ADD `condizioni_fornitura` TEXT NOT NULL AFTER `numero_revision`;