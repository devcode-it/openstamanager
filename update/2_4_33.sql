-- Nuovo modulo "Fasce orarie"
CREATE TABLE IF NOT EXISTS `in_fasceorarie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `giorni` varchar(255) DEFAULT NULL,
  `ora_inizio` time DEFAULT NULL,
  `ora_fine` time DEFAULT NULL,
  `can_delete` BOOLEAN NOT NULL DEFAULT TRUE,
  `is_predefined` BOOLEAN NOT NULL DEFAULT FALSE,
  `include_bank_holidays` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Fasce orarie', 'Fasce orarie', 'fasce_orarie', 'SELECT |select| FROM `in_fasceorarie` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.32', '2.4.32', '1', (SELECT id FROM zz_modules t WHERE t.name = 'Interventi'), '1', '1', '0', '0'); 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'id', 'in_fasceorarie.id', 1, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Nome', 'in_fasceorarie.nome', 2, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Giorni', 'IF(in_fasceorarie.giorni = ''1,2,3,4,5'', ''Lavorativi'',  IF(in_fasceorarie.giorni = ''6,7'', ''Fine settimana'', IF(in_fasceorarie.giorni = ''6'', ''Solo Sabato'', IF(in_fasceorarie.giorni = ''1,2,3,4,5,6,7'', ''Tutti'', ''Solo inclusi'' ))))', 3, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Ora inizio', 'in_fasceorarie.ora_inizio', 4, 1, 1, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Ora fine', 'in_fasceorarie.ora_fine', 5, 1, 1, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fasce orarie'), 'Includi festività', 'IF(in_fasceorarie.include_bank_holidays, ''S&igrave;'', ''No'')', 6, 1, 0, 1);



-- Fascia oraria "Ordinaria"
INSERT INTO `in_fasceorarie` (`id`, `nome`, `giorni`, `ora_inizio`, `ora_fine`, `can_delete`, `is_predefined`) VALUES (NULL, 'Ordinario', '1,2,3,4,5,6,7', '00:00', '23:59', '0', '1'); 

-- Relazione fasca oraria / tipo intervento
CREATE TABLE IF NOT EXISTS `in_fasceorarie_tipiintervento` (
  `idfasciaoraria` int NOT NULL,
  `idtipointervento` int NOT NULL,
  `costo_orario` decimal(12,6) NOT NULL,
  `costo_km` decimal(12,6) NOT NULL,
  `costo_diritto_chiamata` decimal(12,6) NOT NULL,
  `costo_orario_tecnico` decimal(12,6) NOT NULL,
  `costo_km_tecnico` decimal(12,6) NOT NULL,
  `costo_diritto_chiamata_tecnico` decimal(12,6) NOT NULL,
  PRIMARY KEY (`idfasciaoraria`,`idtipointervento`),
  FOREIGN KEY (`idfasciaoraria`) REFERENCES `in_fasceorarie` (`id`),
  FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento` (`idtipointervento`),
  KEY `idtipointervento` (`idtipointervento`)
) ENGINE=InnoDB;


-- Nuovo modulo "Eventi"
CREATE TABLE IF NOT EXISTS `zz_events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `id_nazione` int NOT NULL,
  `id_regione` int DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `is_bank_holiday` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni` (`id`)
) ENGINE=InnoDB;


INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Eventi', 'Eventi', 'eventi', 'SELECT |select| FROM `zz_events` INNER JOIN `an_nazioni` ON `an_nazioni`.id = `zz_events`.id_nazione WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.4.32', '2.4.32', '1', (SELECT id FROM zz_modules t WHERE t.name = 'Tabelle'), '1', '1', '0', '0');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `visible`, `format`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'id', 'zz_events.id', 1, 0, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Nome', 'zz_events.nome', 2, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Nazione', 'an_nazioni.nome', 3, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Data', 'zz_events.data', 4, 1, 1, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Ricorrente', 'IF(zz_events.is_recurring, ''S&igrave;'', ''No'')', 5, 1, 0, 1),
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Eventi'), 'Festività', 'IF(zz_events.is_bank_holiday, ''S&igrave;'', ''No'')', 6, 1, 0, 1);

-- Natale
INSERT INTO `zz_events` (`id`, `nome`, `data`, `id_nazione`, `id_regione`, `is_recurring`, `is_bank_holiday`) VALUES (NULL, 'Natale', '2022-12-25', (SELECT id FROM an_nazioni WHERE nome = 'Italia'), NULL, '1', '1'); 

-- Fix ordine colonne Conto dare e Conto avere in Prima nota
UPDATE `zz_views` SET `order` = '8' WHERE `zz_views`.`name` = 'Conto dare';
UPDATE `zz_views` SET `order` = '9' WHERE `zz_views`.`name` = 'Conto avere';
UPDATE `zz_views` SET `order` = '20' WHERE `zz_views`.`name` = '_print_';
 
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '(righe.totale_imponibile + righe.iva + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Totale ivato';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '(righe.totale_imponibile + righe.iva + `co_documenti`.`rivalsainps` + `co_documenti`.`iva_rivalsainps`) * IF(co_tipidocumento.reversed, -1, 1)' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Totale ivato';

INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name`='Interventi'), 1, 'Intervento & checklist', 'Intervento & checklist', 'Intervento num {numero} del {data}', 'interventi', 'idintervento', '{\"pricing\":true, \"checklist\": true}', 'fa fa-print', '', '', 0, 1, 1, 1),
(NULL, (SELECT id FROM zz_modules WHERE `name`='Interventi'), 1, 'Intervento & checklist (senza costi)', 'Intervento & checklist (senza prezzi)', 'Intervento num {numero} del {data}', 'interventi', 'idintervento', '{\"pricing\":false, \"checklist\": true}', 'fa fa-print', '', '', 0, 1, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name`='Interventi'), 'Contratto', 'contratto.info', 21, 1, 0, 0, 0, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name`='Interventi'), 'Preventivo', 'preventivo.info', 22, 1, 0, 0, 0, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name`='Interventi'), 'Ordine', 'ordine.info', 23, 1, 0, 0, 0, '', '', 0, 0, 1);



CREATE TABLE `an_regioni` (
  `id` int NOT NULL,
  `id_nazione` int NOT NULL,
  `nome` varchar(255) NOT NULL,
  `iso2` varchar(2) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni`(`id`)
) ENGINE=InnoDB;



INSERT INTO `an_regioni` (`id`, `nome`, `id_nazione`) VALUES
(1, 'Abruzzo', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(2, 'Basilicata', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(3, 'Calabria', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(4, 'Campania', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(5, 'Emilia-Romagna', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(6, 'Friuli-Venezia Giulia', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(7, 'Lazio', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(8, 'Liguria', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(9, 'Lombardia', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(10, 'Marche', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(11, 'Molise', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(12, 'Piemonte', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(13, 'Puglia', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(14, 'Sardegna', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(15, 'Sicilia', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(16, 'Toscana', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(17, 'Trentino-Alto Adige', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(18, 'Umbria', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(19, 'Valle d''Aosta', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT')),
(20, 'Veneto', (SELECT `id` FROM `an_nazioni` WHERE `iso2` = 'IT'));

-- Aggiunta sezionale per fatture non elettroniche
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Fatture non elettroniche', '1=1', 'WHR', '#', '0', '0', '0', '0', '0', '1');

-- Ordinamento vista N. Prot.
UPDATE `zz_views` SET `order_by` = 'CAST(co_documenti.numero AS UNSIGNED)' WHERE `zz_views`.`name` = 'N. Prot.'; 

-- Gestione autofattura
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `is_fiscale`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Autofatture', '1=1', 'WHR', '####', '', '', 0, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Autofatture', '1=1', 'WHR', '####', '', '', 0, 0, 0, 1);

INSERT INTO `co_pianodeiconti2` (`numero`, `descrizione`, `idpianodeiconti1`, `dir`) VALUES
('910', 'Conti compensativi', (SELECT `id` FROM `co_pianodeiconti1` WHERE `descrizione`='Patrimoniale'), 'entrata/uscita');

INSERT INTO `co_pianodeiconti3` (`numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES
('000010', 'Compensazione per autofattura', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`='Conti compensativi'), '', '100.00');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Sezionale per autofatture di vendita', (SELECT `id` FROM `zz_segments` WHERE `name`='Autofatture' AND `id_module`=(SELECT `id` FROM `zz_modules` WHERE name="Fatture di vendita")), 'query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=(SELECT id FROM zz_modules WHERE name=\"Fatture di vendita\") ORDER BY name', '1', 'Fatturazione', NULL, NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Sezionale per autofatture di acquisto', (SELECT `id` FROM `zz_segments` WHERE `name`='Autofatture' AND `id_module`=(SELECT `id` FROM `zz_modules` WHERE name="Fatture di acquisto")), 'query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=(SELECT id FROM zz_modules WHERE name=\"Fatture di acquisto\") ORDER BY name', '1', 'Fatturazione', NULL, NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto per autofattura', (SELECT id FROM `co_pianodeiconti3` WHERE `descrizione`="Compensazione per autofattura"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti3` ORDER BY `descrizione` ASC', '1', 'Piano dei conti', NULL, NULL);

ALTER TABLE `co_documenti` ADD `id_autofattura` INT NULL AFTER `id_ricevuta_principale`, ADD FOREIGN KEY (`id_autofattura`) REFERENCES `co_documenti`(`id`) ON DELETE SET NULL; 

-- Fix widget Scadenze
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(co_documenti.id) AS dato FROM co_scadenziario INNER JOIN (((co_documenti INNER JOIN an_anagrafiche ON co_documenti.idanagrafica=an_anagrafiche.idanagrafica) INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id) INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id) ON co_scadenziario.iddocumento=co_documenti.id WHERE ABS(pagato) < ABS(da_pagare) AND scadenza >= \"|period_start|\" AND scadenza <= \"|period_end|\" ORDER BY scadenza ASC' WHERE `zz_widgets`.`name` = 'Scadenze'; 

-- Impostazione per gestire conto per la creazione conti anagrafiche
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto di secondo livello per i crediti clienti', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`="Crediti clienti e crediti diversi"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti2` WHERE idpianodeiconti1=(SELECT id FROM co_pianodeiconti1 WHERE descrizione="Patrimoniale") ORDER BY `descrizione` ASC', '1', 'Piano dei conti', NULL, NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto di secondo livello per i debiti fornitori', (SELECT `id` FROM `co_pianodeiconti2` WHERE `descrizione`="Debiti fornitori e debiti diversi"), 'query=SELECT `id`, CONCAT_WS(\' - \', `numero`, `descrizione`) AS descrizione FROM `co_pianodeiconti2` WHERE idpianodeiconti1=(SELECT id FROM co_pianodeiconti1 WHERE descrizione="Patrimoniale") ORDER BY `descrizione` ASC', '1', 'Piano dei conti', NULL, NULL);

-- Rimozione filtro obsoleto su ricerca tipo anagrafica
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `search_inside`=NULL WHERE `zz_modules`.`name`='Anagrafiche' AND `zz_views`.`name`='Tipo';
