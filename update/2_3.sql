-- Standardizzazione delle tabelle principali
ALTER TABLE `zz_utenti` RENAME `zz_users`;
ALTER TABLE `zz_impostazioni` RENAME `zz_settings`;
ALTER TABLE `zz_permessi` RENAME `zz_permissions`;
ALTER TABLE `zz_widget_modules` RENAME `zz_widgets`;
ALTER TABLE `zz_modules_plugins` RENAME `zz_plugins`;
ALTER TABLE `zz_log` RENAME `zz_logs`;
ALTER TABLE `zz_gruppi` RENAME `zz_groups`;
ALTER TABLE `zz_gruppi_modules` RENAME `zz_group_module`;

ALTER TABLE `zz_settings` CHANGE `valore` `valore` text NOT NULL;

-- Ridenominazione dell'attributo module_dir in directory
ALTER TABLE `zz_modules` CHANGE `module_dir` `directory` varchar(50) NOT NULL, CHANGE `name2` `title` varchar(255) NOT NULL, DROP `type`, DROP `new`;
UPDATE `zz_modules` SET `name` = REPLACE(`name`, '&agrave;', 'à'), `title` = REPLACE(`title`, '&agrave;', 'à');

-- Adattamento della tabella in_interventi
ALTER TABLE `in_interventi` ADD `id` int(11) NOT NULL;
CREATE INDEX primary_key ON `in_interventi` (`id`);
ALTER TABLE `in_interventi` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT FIRST;

ALTER TABLE `my_impianti_interventi` DROP PRIMARY KEY;

UPDATE `co_ordiniservizio` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `co_ordiniservizio`.`idintervento`);
DELETE FROM `co_preventivi_interventi` WHERE idpreventivo = 0;
UPDATE `co_preventivi_interventi` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `co_preventivi_interventi`.`idintervento`);
UPDATE `co_righe_contratti` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `co_righe_contratti`.`idintervento`);
UPDATE `co_righe_documenti` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `co_righe_documenti`.`idintervento`);
UPDATE `in_righe_interventi` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `in_righe_interventi`.`idintervento`);
UPDATE `mg_movimenti` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `mg_movimenti`.`idintervento`);
UPDATE `mg_articoli_interventi` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `mg_articoli_interventi`.`idintervento`);
UPDATE `my_impianti_interventi` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `my_impianti_interventi`.`idintervento`);
UPDATE `my_impianto_componenti` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `my_impianto_componenti`.`idintervento`);
UPDATE `my_componenti_interventi` SET `id_intervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `my_componenti_interventi`.`id_intervento`);

ALTER TABLE `co_ordiniservizio` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `co_preventivi_interventi` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `co_righe_contratti` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `co_righe_documenti` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `in_righe_interventi` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `mg_movimenti` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `mg_articoli_interventi` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `my_impianti_interventi` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `my_impianto_componenti` CHANGE `idintervento` `idintervento` varchar(25);
ALTER TABLE `my_componenti_interventi` CHANGE `id_intervento` `id_intervento` varchar(25);

UPDATE `co_ordiniservizio` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `co_preventivi_interventi` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `co_righe_contratti` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `co_righe_documenti` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `in_righe_interventi` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `mg_movimenti` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `mg_articoli_interventi` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `my_impianti_interventi` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `my_impianto_componenti` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = '';
UPDATE `my_componenti_interventi` SET `id_intervento` = NULL WHERE `id_intervento` = 0 OR `id_intervento` = '';

ALTER TABLE `co_ordiniservizio` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `co_preventivi_interventi` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `co_righe_contratti` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `co_righe_documenti` CHANGE `idintervento` `idintervento` int(11);
ALTER TABLE `in_righe_interventi` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `mg_movimenti` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `mg_articoli_interventi` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `my_impianti_interventi` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `my_impianto_componenti` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;
ALTER TABLE `my_componenti_interventi` CHANGE `id_intervento` `id_intervento` int(11), ADD FOREIGN KEY (`id_intervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in my_componenti_interventi
ALTER TABLE `my_componenti_interventi` CHANGE `id_componente` `id_componente` int(11) NOT NULL, ADD FOREIGN KEY (`id_componente`) REFERENCES `my_impianto_componenti`(`id`) ON DELETE CASCADE;

-- Aggiornamento dei filtri per i gruppo di utenti
UPDATE `zz_group_module` SET `clause` = ' AND in_interventi.id IN (SELECT idintervento FROM in_interventi_tecnici WHERE idintervento=in_interventi.id AND idtecnico=|idtecnico|)' WHERE `id` = 1;
UPDATE `zz_group_module` SET `clause` = ' AND an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.idanagrafica=an_anagrafiche.idanagrafica AND idtecnico=|idtecnico|)' WHERE `id` = 2;

-- Eliminazione tabelle inutilizzate
DROP TABLE IF EXISTS `mk_allegati`;
DROP TABLE IF EXISTS `mk_attivita`;
DROP TABLE IF EXISTS `mk_email`;
DROP TABLE IF EXISTS `mk_statoattivita`;
DROP TABLE IF EXISTS `mk_tipoattivita`;
DROP TABLE IF EXISTS `dt_automezzi_tagliandi`;
DROP TABLE IF EXISTS `co_contratti_interventi`;

-- RELEASE 2.2.1 [NON UFFICIALE] --
-- Aggiunta del campo desc_iva anche per Preventivi, Ddt e Ordini
ALTER TABLE `dt_righe_ddt` ADD `desc_iva` varchar(255) NOT NULL AFTER `idiva`;
ALTER TABLE `co_righe_preventivi` ADD `desc_iva` varchar(255) NOT NULL AFTER `idiva`;
ALTER TABLE `or_righe_ordini` ADD `desc_iva` varchar(255) NOT NULL AFTER `idiva`;
ALTER TABLE `co_righe2_contratti` ADD `desc_iva` varchar(255) NOT NULL AFTER `idiva`;

-- Fix per l'ordinamento delle righe in Preventivi, Ddt e Ordini
ALTER TABLE `co_righe_preventivi` ADD `order` tinyint(11) NOT NULL AFTER `qta`;
ALTER TABLE `dt_righe_ddt` ADD `order` tinyint(11) NOT NULL AFTER `qta_evasa`;
ALTER TABLE `or_righe_ordini` ADD `order` tinyint(11) NOT NULL AFTER `qta_evasa`;
ALTER TABLE `co_righe2_contratti` ADD `order` tinyint(11) NOT NULL AFTER `qta`;
ALTER TABLE `co_righe_documenti` CHANGE `ordine` `order` int(11) NOT NULL;

-- Aggiungo idconto anche per le righe delle fatture e allineamento (copia idconto nelle righe delle fatture, solo per i conti di entrata  e uscita)
ALTER TABLE `co_righe_documenti` ADD `idconto` int(11) NOT NULL AFTER `idautomezzo`;
UPDATE `co_righe_documenti` SET `idconto` = (SELECT `idconto` FROM `co_documenti` WHERE `id` = `co_righe_documenti`.`iddocumento`);

-- 2016-12-16
-- Dicitura fissa a fondo fattura
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Dicitura fissa fattura', 'Ai sensi del D.Lgs. 196/2003 Vi informiamo che i Vs. dati saranno utilizzati esclusivamente per i fini connessi ai rapporti commerciali tra di noi in essere. Contributo CONAI assolto ove dovuto - Vi preghiamo di controllare i Vs. dati anagrafici, la P. IVA e il Cod. Fiscale. Non ci riteniamo responsabili di eventuali errori.', 'textarea', '1', 'Fatturazione');

-- 2016-12-20
-- Aggiunto peso lordo e volume (per ddt e fatture accompagnatorie)
ALTER TABLE `mg_articoli` ADD `peso_lordo` decimal(12, 4) NOT NULL AFTER `gg_garanzia`, ADD `volume` decimal(12, 4) NOT NULL AFTER `peso_lordo`;

-- 2016-02-15
-- Aggiunta sconto percentuale e unitario su fatture e righe, ddt e righe, ordini e righe, preventivi e righe, contratti e righe
ALTER TABLE `co_documenti` ADD `sconto_globale` decimal(12, 4) NOT NULL;
ALTER TABLE `co_documenti` ADD `tipo_sconto_globale` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_preventivi` ADD `sconto_globale` decimal(12, 4) NOT NULL;
ALTER TABLE `co_preventivi` ADD `tipo_sconto_globale` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_contratti` ADD `sconto_globale` decimal(12, 4) NOT NULL;
ALTER TABLE `co_contratti` ADD `tipo_sconto_globale` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT';
ALTER TABLE `or_ordini` ADD `sconto_globale` decimal(12, 4) NOT NULL;
ALTER TABLE `or_ordini` ADD `tipo_sconto_globale` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT';
ALTER TABLE `dt_ddt` ADD `sconto_globale` decimal(12, 4) NOT NULL;
ALTER TABLE `dt_ddt` ADD `tipo_sconto_globale` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT';
ALTER TABLE `in_interventi` ADD `sconto_globale` decimal(12, 4) NOT NULL;
ALTER TABLE `in_interventi` ADD `tipo_sconto_globale` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT';

ALTER TABLE `co_righe_documenti` ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`;
ALTER TABLE `co_righe_documenti`ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `co_righe_documenti` ADD `sconto_globale` boolean NOT NULL DEFAULT 0 AFTER `tipo_sconto`;

ALTER TABLE `co_righe_preventivi` ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`;
ALTER TABLE `co_righe_preventivi` ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `co_righe_preventivi` ADD `sconto_globale` boolean NOT NULL DEFAULT 0 AFTER `tipo_sconto`;

ALTER TABLE `co_righe2_contratti` ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`;
ALTER TABLE `co_righe2_contratti` ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `co_righe2_contratti` ADD `sconto_globale` boolean NOT NULL DEFAULT 0 AFTER `tipo_sconto`;

ALTER TABLE `or_righe_ordini` ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`;
ALTER TABLE `or_righe_ordini` ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `or_righe_ordini` ADD `sconto_globale` boolean NOT NULL DEFAULT 0 AFTER `tipo_sconto`;

ALTER TABLE `dt_righe_ddt` ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`;
ALTER TABLE `dt_righe_ddt` ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `dt_righe_ddt` ADD `sconto_globale` boolean NOT NULL DEFAULT 0 AFTER `tipo_sconto`;


ALTER TABLE `in_righe_interventi` ADD `sconto` decimal(12, 4) NOT NULL, ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`, ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `mg_articoli_interventi` ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`, ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`;
ALTER TABLE `in_interventi_tecnici`
    ADD `sconto` decimal(12, 4) NOT NULL, ADD `sconto_unitario` decimal(12, 4) NOT NULL AFTER `sconto`, ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `sconto_unitario`,
    ADD `scontokm` decimal(12, 4) NOT NULL AFTER `tipo_sconto`, ADD `scontokm_unitario` decimal(12, 4) NOT NULL AFTER `scontokm`, ADD `tipo_scontokm` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `scontokm_unitario`;

-- Inizializzo a vuoto il valore per le impostazioni Percentuale ritenuta d'acconto e Percentuale rivalsa INPS
UPDATE `zz_settings` SET `valore` = '' WHERE `zz_settings`.`nome` = "Percentuale ritenuta d'acconto";
UPDATE `zz_settings` SET `valore` = '' WHERE `zz_settings`.`nome` = "Percentuale rivalsa INPS";

-- RELEASE 2.3 --
-- Aggiunta tabelle nascoste per l'API
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Tabelle escluse per la sincronizzazione API automatica', 'zz_users,zz_permissions,zz_semaphores,zz_tokens,updates', 'string', '0', 'API'),
('Lunghezza pagine per API', '200', 'integer', '0', 'API');

--
-- Struttura della tabella `zz_views`
--

CREATE TABLE IF NOT EXISTS `zz_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_module` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `query` text DEFAULT NULL,
  `order` tinyint(11) NOT NULL,
  `search` tinyint(1) NOT NULL DEFAULT '1',
  `slow` tinyint(1) NOT NULL DEFAULT '0',
  `search_inside` varchar(255) DEFAULT NULL,
  `order_by` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `summable` tinyint(1) NOT NULL DEFAULT '0',
  `default` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

--
-- Dump dei dati per la tabella `zz_views`
--

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'id', 'an_anagrafiche.idanagrafica', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Ragione sociale', 'ragione_sociale', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Tipologia', 'GROUP_CONCAT(an_tipianagrafiche.descrizione SEPARATOR '', '')', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Città', 'citta', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Telefono', 'telefono', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'color_Rel.', 'an_relazioni.colore', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'color_title_Rel.', 'an_relazioni.descrizione', 7, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'id', 'in_interventi.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Numero', 'in_interventi.codice', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Ragione sociale', 'ragione_sociale', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Data inizio', 'MIN(orario_inizio)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Data fine', 'MAX(orario_fine)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), '_bg_', '(SELECT colore FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento)', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Stato', '(SELECT descrizione FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento)', 7, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Tipo', '(SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento)', 8, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), '_print_', '''pdfgen.php?ptype=interventi&idintervento=$id$&mode=single''', 9, 0, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'idanagrafica', 'in_interventi.idanagrafica', 10, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'orario_inizio', 'orario_inizio', 11, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'data_richiesta', 'data_richiesta', 12, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'orario_fine', 'orario_fine', 13, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di anagrafiche'), 'id', 'idtipoanagrafica', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di anagrafiche'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'id', 'idtipointervento', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Codice', 'idtipointervento', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Descrizione', 'descrizione', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Costo orario', 'costo_orario', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Costo al km', 'costo_km', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Diritto di chiamata', 'costo_diritto_chiamata', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Costo orario tecnico', 'costo_orario_tecnico', 7, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Costo al km tecnico', 'costo_km_tecnico', 8, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Diritto di chiamata tecnico', 'costo_diritto_chiamata_tecnico', 9, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati di intervento'), 'id', 'idstatointervento', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati di intervento'), 'Codice', 'idstatointervento', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati di intervento'), 'Descrizione', 'descrizione', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stati di intervento'), 'color_Colore', 'colore', 4, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Numero', 'numero', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Nome', 'nome', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Cliente', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_preventivi.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'icon_Stato', '(SELECT icona FROM co_statipreventivi WHERE id=idstato)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'icon_title_Stato', '(SELECT descrizione FROM co_statipreventivi WHERE id=idstato)', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'data_bozza', 'data_bozza', 7, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'data_conclusione', 'data_conclusione', 8, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'id', 'co_documenti.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Numero', 'IF(numero_esterno='''', numero, numero_esterno)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Data', 'data', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Ragione sociale', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_documenti.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Totale', '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + bollo + iva_rivalsainps', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'icon_Stato', '(SELECT icona FROM co_statidocumento WHERE id=idstatodocumento)', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'icon_title_Stato', '(SELECT descrizione FROM co_statidocumento WHERE id=idstatodocumento)', 7, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'dir', 'dir', 9, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'id', 'co_documenti.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Numero', 'IF(numero_esterno='''', numero, numero_esterno)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Data', 'data', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Ragione sociale', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_documenti.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Totale', '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento ) + bollo + iva_rivalsainps', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'icon_Stato', '(SELECT icona FROM co_statidocumento WHERE id=idstatodocumento)', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'icon_title_Stato', '(SELECT descrizione FROM co_statidocumento WHERE id=idstatodocumento)', 7, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'dir', 'dir', 9, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'id', 'co_movimenti.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Data', 'data', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Causale', 'co_movimenti.descrizione', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Controparte', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica= (SELECT idanagrafica FROM co_documenti WHERE id=iddocumento))', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Conto avere', 'GROUP_CONCAT(CASE WHEN totale>0 THEN co_pianodeiconti3.descrizione ELSE NULL END)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Dare', 'SUM(IF(totale > 0, ABS(totale), 0))', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Avere', 'SUM(IF(totale < 0, ABS(totale), 0))', 7, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Prima nota'), 'Conto dare', 'GROUP_CONCAT(CASE WHEN totale<0 THEN co_pianodeiconti3.descrizione ELSE NULL END)', 8, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'id', 'co_scadenziario.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Anagrafica', 'ragione_sociale', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Tipo di pagamento', 'co_pagamenti.descrizione', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Data emissione', 'CONCAT(co_tipidocumento.descrizione, CONCAT('' numero '', IF(numero_esterno<>'''', numero_esterno, numero)))', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Data scadenza', 'scadenza', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Importo', 'da_pagare', 6, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Pagato', 'pagato', 7, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), '_bg_', 'IF(scadenza<NOW(), ''#ff7777'', '''')', 8, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'da_pagare', 'da_pagare', 9, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'descrizione', 'co_statidocumento.descrizione', 10, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'pagato_reale', 'pagato', 11, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Codice', 'codice', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Categoria', '(SELECT `nome` FROM `mg_categorie` WHERE `id` = `id_categoria`)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Sottocategoria', '(SELECT `nome` FROM `mg_categorie` WHERE `id` = `id_sottocategoria`)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Q.tà', 'CONCAT_WS('' '', REPLACE(REPLACE(REPLACE(FORMAT(qta, 2), '','', ''#''), ''.'', '',''), ''#'', ''.''), um)', 6, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Nome', 'nome', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Percentuale guadagno o sconto', 'prc_guadagno', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Listini'), 'Note', 'note', 4, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'Targa', 'targa', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'Nome', 'nome', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Automezzi'), 'Descrizione', 'descrizione', 4, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'id', 'or_ordini.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Numero', 'IF(numero_esterno='''', numero, numero_esterno)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Ragione sociale', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=or_ordini.idanagrafica)', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'icon_Stato', '(SELECT icona FROM or_statiordine WHERE id=idstatoordine)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'icon_title_Stato', '(SELECT descrizione FROM or_statiordine WHERE id=idstatoordine)', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Data', 'data', 7, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'dir', 'dir', 8, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'id', 'or_ordini.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Numero', 'IF(numero_esterno='''', numero, numero_esterno)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'Ragione sociale', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=or_ordini.idanagrafica)', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'icon_Stato', '(SELECT icona FROM or_statiordine WHERE id=idstatoordine)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'icon_title_Stato', '(SELECT descrizione FROM or_statiordine WHERE id=idstatoordine)', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore'), 'dir', 'dir', 7, 1, 0, 0, 1);


INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'id', 'dt_ddt.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Numero', 'IF(numero_esterno='''', numero, numero_esterno)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Data', 'data', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Cliente', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=dt_ddt.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'icon_Stato', '(SELECT icona FROM dt_statiddt WHERE id=idstatoddt)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'icon_title_Stato', '(SELECT descrizione FROM dt_statiddt WHERE id=idstatoddt)', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'dir', 'dir', 8, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'id', 'dt_ddt.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Numero', 'IF(numero_esterno='''', numero, numero_esterno)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Data', 'data', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'Cliente', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=dt_ddt.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'icon_Stato', '(SELECT icona FROM dt_statiddt WHERE id=idstatoddt)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'icon_title_Stato', '(SELECT descrizione FROM dt_statiddt WHERE id=idstatoddt)', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di acquisto'), 'dir', 'dir', 8, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Zone'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Zone'), 'Nome', 'nome', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Zone'), 'Descrizione', 'descrizione', 3, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'Matricola', 'matricola', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'Data', 'data', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'Cliente', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=my_impianti.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'Tecnico', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=my_impianti.idtecnico)', 5, 1, 0, 1, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Numero', 'numero', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Nome', 'nome', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Cliente', '(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica)', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'icon_Stato', '(SELECT icona FROM co_staticontratti WHERE id=idstato)', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'icon_title_Stato', '(SELECT descrizione FROM co_staticontratti WHERE id=idstato)', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'data_bozza', 'data_bozza', 7, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'data_conclusione', 'data_conclusione', 8, 1, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Voci di servizio'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Voci di servizio'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Voci di servizio'), 'Categoria', 'categoria', 3, 1, 0, 1, 1);

--
-- Struttura della tabella `zz_group_view`
--

CREATE TABLE IF NOT EXISTS `zz_group_view` (
  `id_gruppo` int(11) NOT NULL,
  `id_vista` int(11) NOT NULL,
  PRIMARY KEY (`id_gruppo`, `id_vista`),
  FOREIGN KEY (`id_gruppo`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_vista`) REFERENCES `zz_views`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Aggiornamento delle query di default dei vari moduli predefiniti
UPDATE `zz_modules` SET `options` = '' WHERE `name` = 'Acquisti';
UPDATE `zz_modules` SET `options` = 'custom' WHERE `name` = 'Aggiornamenti';
UPDATE `zz_modules` SET `options` = 'custom' WHERE `name` = 'Backup';
UPDATE `zz_modules` SET `options` = '' WHERE `name` = 'Contabilità';
UPDATE `zz_modules` SET `options` = 'custom' WHERE `name` = 'Dashboard';
UPDATE `zz_modules` SET `options` = 'custom' WHERE `name` = 'Gestione componenti';
UPDATE `zz_modules` SET `options` = '' WHERE `name` = 'Magazzino';
UPDATE `zz_modules` SET `options` = 'custom' WHERE `name` = 'Piano dei conti';
UPDATE `zz_modules` SET `options` = '' WHERE `name` = 'Vendite';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_vociservizio` WHERE 1=1 HAVING 2=2 ORDER BY `categoria`, `descrizione`' WHERE `name` = 'Voci di servizio';
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `an_zone` WHERE 1=1 HAVING 2=2 ORDER BY `id`' WHERE `name` = 'Zone';

-- Aggiunta di campi per le sessioni avanzate e il timeout relativo in editor.php
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Attiva notifica di presenza utenti sul record', '1', 'boolean', 1, 'Generali');
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Timeout notifica di presenza (minuti)', '15', 'integer', 1, 'Generali');

-- Aggiunta tabella per le sessioni avanazate
CREATE TABLE IF NOT EXISTS `zz_semaphores` (
  `id_utente` int(11) NOT NULL,
  `posizione` varchar(255) NOT NULL,
  `updated` datetime
) ENGINE=InnoDB;

-- Aggiornamento zz_modules
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `level`, `parent`, `default`, `enabled`) VALUES (NULL, 'Strumenti', '', '', '', 'fa fa-cog', '2.3', '2.3', '1', '0', 0, '1', '1');

ALTER TABLE `zz_modules` DROP `level`;

UPDATE `zz_modules` SET `options` = 'menu' WHERE `options` = '';

INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Viste', 'viste', 'SELECT |select| FROM `zz_modules` WHERE 1=1 HAVING 2=2 ORDER BY `name`, `title` ASC', '', 'fa fa-eye', '2.3', '2.3', '1', 1, '1', '0');

INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Utenti e permessi', 'utenti', 'SELECT |select| FROM `zz_groups` WHERE 1=1 HAVING 2=2 ORDER BY `id`, `nome` ASC', '', 'fa fa-lock', '2.3', '2.3', '1', 1, '1', '1');

INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Impostazioni', 'impostazioni', 'SELECT |select| FROM `zz_settings` WHERE 1=1 AND `editable` = 1 GROUP BY `sezione` HAVING 2=2 ORDER BY `sezione`', '', 'fa fa-th-list', '2.3', '2.3', '1', 1, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Viste' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Utenti e permessi' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Impostazioni' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Aggiornamenti' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Backup' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS ''id'', `ragione_sociale` AS ''descrizione'' FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `idtipoanagrafica` FROM `an_tipianagrafiche` WHERE `descrizione` = ''Azienda'') AND deleted=0' WHERE `zz_settings`.`nome` = 'Azienda predefinita';

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impostazioni'), 'Nome', 'sezione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impostazioni'), 'id', 'idimpostazione', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'id', 'id', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'Gruppo', 'nome', 1, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste'), 'Numero', 'id', 1, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste'), 'Icona', 'icon', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste'), 'Nome', 'title', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste'), 'id', 'id', 2, 1, 0, 0, 1);

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Impostazioni') AND `name` = 'Nome')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Impostazioni') AND `name` = 'id')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi') AND `name` = 'id')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi') AND `name` = 'Gruppo')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste') AND `name` = 'Numero')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste') AND `name` = 'Icona')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste') AND `name` = 'Nome')),
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Amministratori'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Viste') AND `name` = 'id'));

-- Eliminazione impostazioni inutilizzata
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Usa tabelle avanzate';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Righe per pagina';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato report';

-- Aggiunta tabelle per la gestione dei campi "minori""
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Tabelle', '', '', '', 'fa fa-table', '2.3', '2.3', '1', 1, '1', '1');
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'IVA', 'iva', 'SELECT |select| FROM `co_iva` WHERE 1=1 HAVING 2=2', '', 'fa fa-percent', '2.3', '2.3', '1', 1, '1', '1');
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Causali', 'causali', 'SELECT |select| FROM `dt_causalet` WHERE 1=1 HAVING 2=2', '', 'fa fa-commenting-o', '2.3', '2.3', '1', 1, '1', '1');
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Aspetto beni', 'beni', 'SELECT |select| FROM `dt_aspettobeni` WHERE 1=1 HAVING 2=2', '', 'fa fa-external-link', '2.3', '2.3', '1', 1, '1', '1');
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Unità di misura', 'misure', 'SELECT |select| FROM `mg_unitamisura` WHERE 1=1 HAVING 2=2', '', 'fa fa-external-link', '2.3', '2.3', '1', 1, '1', '1');
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Porto', 'porti', 'SELECT |select| FROM `dt_porto` WHERE 1=1 HAVING 2=2', '', 'fa fa-external-link', '2.3', '2.3', '1', 1, '1', '1');
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Pagamenti', 'pagamenti', 'SELECT |select| FROM `co_pagamenti` WHERE 1=1 GROUP BY `descrizione` HAVING 2=2', '', 'fa fa-usd', '2.3', '2.3', '1', 1, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Tabelle' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'IVA' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Causali' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Aspetto beni' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Unità di misura' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Porto' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Pagamenti' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Pagamenti'), 'id', 'id', 3, 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Pagamenti'), 'Rate', 'COUNT(descrizione)', 2, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Pagamenti'), 'Descrizione', 'descrizione', 1, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Porto'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Porto'), 'id', 'id', 1, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Unità di misura'), 'Valore', 'valore', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Unità di misura'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Aspetto beni'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Aspetto beni'), 'id', 'id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali'), 'id', 'id', 1, 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'IVA'), 'Indetraibile', 'indetraibile', 4, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'IVA'), 'Percentuale', 'percentuale', 3, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'IVA'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'IVA'), 'id', 'id', 1, 1, 0, 0, 0);

-- Modifica di an_anagrafiche per sostituire le nazioni con i corrispettivi nella tabella apposita
ALTER TABLE `an_anagrafiche` ADD `id_nazione` int(11) AFTER `nazione`, ADD FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni`(`id`);
UPDATE `an_anagrafiche` SET `id_nazione` = (SELECT `id` FROM `an_nazioni` WHERE `nome` = `nazione`);
ALTER TABLE `an_anagrafiche` DROP COLUMN `nazione`;

-- Aggiunta della colonna id negli impianti, e relative modifiche alle altre tabelle
ALTER TABLE `my_impianti` DROP PRIMARY KEY, ADD `id` int(11) PRIMARY KEY AUTO_INCREMENT FIRST;

ALTER TABLE `co_ordiniservizio` ADD `idimpianto` int(11);
UPDATE `co_ordiniservizio` SET `idimpianto` = (SELECT `id` FROM `my_impianti` WHERE `my_impianti`.`matricola` = `co_ordiniservizio`.`matricola`);
ALTER TABLE `co_ordiniservizio` DROP COLUMN `matricola`, ADD FOREIGN KEY (`idimpianto`) REFERENCES `my_impianti`(`id`) ON DELETE CASCADE;

ALTER TABLE `mg_articoli_interventi` ADD `idimpianto` int(11);
UPDATE `mg_articoli_interventi` SET `idimpianto` = (SELECT `id` FROM `my_impianti` WHERE `my_impianti`.`matricola` = `mg_articoli_interventi`.`matricola`);
ALTER TABLE `mg_articoli_interventi` DROP COLUMN `matricola`, ADD FOREIGN KEY (`idimpianto`) REFERENCES `my_impianti`(`id`) ON DELETE CASCADE;

ALTER TABLE `my_impianto_componenti` ADD `idimpianto` int(11);
UPDATE `my_impianto_componenti` SET `idimpianto` = (SELECT `id` FROM `my_impianti` WHERE `my_impianti`.`matricola` = `my_impianto_componenti`.`matricola`);
ALTER TABLE `my_impianto_componenti` DROP COLUMN `matricola`, ADD FOREIGN KEY (`idimpianto`) REFERENCES `my_impianti`(`id`) ON DELETE CASCADE;

ALTER TABLE `my_impianti_interventi` ADD `idimpianto` int(11);
UPDATE `my_impianti_interventi` SET `idimpianto` = (SELECT `id` FROM `my_impianti` WHERE `my_impianti`.`matricola` = `my_impianti_interventi`.`matricola`);
DELETE FROM `my_impianti_interventi` WHERE `idimpianto` IS NULL;
ALTER TABLE `my_impianti_interventi` DROP COLUMN `matricola`, ADD FOREIGN KEY (`idimpianto`) REFERENCES `my_impianti`(`id`) ON DELETE CASCADE;

ALTER TABLE `my_impianti_contratti` ADD `idimpianto` int(11), ADD FOREIGN KEY (`idimpianto`) REFERENCES `my_impianti`(`id`) ON DELETE CASCADE;
UPDATE `my_impianti_contratti` SET `idimpianto` = (SELECT `id` FROM `my_impianti` WHERE `my_impianti`.`matricola` = `my_impianti_contratti`.`matricola`);
ALTER TABLE `my_impianti_contratti` DROP COLUMN `matricola`;

-- Adattamento di co_pagamenti e co_movimenti
ALTER TABLE `co_pagamenti` CHANGE `num_giorni` `num_giorni` int(11) NOT NULL;
ALTER TABLE `co_movimenti` CHANGE `iddocumento` `iddocumento` int(11) NOT NULL;

-- Aggiornamento plugins di anagrafiche
UPDATE `zz_plugins` SET `script` = 'referenti.php' WHERE `zz_plugins`.`name` = 'Referenti';
UPDATE `zz_plugins` SET `script` = 'sedi.php' WHERE `zz_plugins`.`name` = 'Sedi';

-- Aggiornamento dei vari campi per le Viste
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'data' WHERE `zz_modules`.`name` IN ('Fatture di vendita', 'Fatture di acquisto', 'Prima nota', 'Ddt di acquisto', 'MyImpianti', 'Ordini cliente', 'Ddt di vendita') AND `zz_views`.`name` = 'Data';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'scadenza' WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Data scadenza';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'orario_fine' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Orario fine';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'orario_inizio' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Orario inizio';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search_inside` = 'color_title_Rel' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'color_Rel.';
UPDATE `zz_views` SET `search` = '0' WHERE `name` = '_print_';

-- Aggiornamento icona di default per i moduli senza icona specifica
UPDATE `zz_modules` SET `icon`='fa fa-angle-right' WHERE `icon`='fa fa-external-link';

-- Aggiunta di tabella e modulo per categorie
CREATE TABLE IF NOT EXISTS `mg_categorie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `colore` varchar(255) NOT NULL,
  `nota` varchar(1000) NOT NULL,
  `parent` int(11),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`parent`) REFERENCES `mg_categorie`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO `mg_categorie` (`nome`) SELECT `categoria` FROM `mg_articoli` GROUP BY `categoria`;
INSERT INTO `mg_categorie` (`nome`, `parent`) SELECT `subcategoria`, `mg_categorie`.`id` FROM `mg_articoli` INNER JOIN `mg_categorie` ON `mg_categorie`.`nome` = `mg_articoli`.`categoria` GROUP BY `categoria`,`subcategoria`;
ALTER TABLE `mg_articoli` ADD `id_categoria` int(11) NOT NULL, ADD `id_sottocategoria` int(11) NOT NULL;
UPDATE `mg_articoli` JOIN `mg_categorie` ON `mg_articoli`.`categoria` = `mg_categorie`.`nome` AND `mg_categorie`.`parent` IS NULL SET `mg_articoli`.`id_categoria` = `mg_categorie`.`id`;
UPDATE `mg_articoli` JOIN `mg_categorie` ON `mg_articoli`.`subcategoria` = `mg_categorie`.`nome` AND `mg_categorie`.`parent` != 0 SET `mg_articoli`.`id_sottocategoria` = `mg_categorie`.`id`;
ALTER TABLE `mg_articoli` DROP COLUMN `categoria`, DROP COLUMN `subcategoria`;

INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Categorie', 'categorie', 'SELECT |select| FROM `mg_categorie` WHERE 1=1 AND `parent` IS NULL HAVING 2=2', '', 'fa fa-briefcase', '2.3', '2.3', '1', 1, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Categorie' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie'), 'id', 'id', 3, 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Categorie'), 'Nome', 'nome', 2, 1, 0, 1, 0);

-- Fix della ricerca per tipologia su anagrafiche
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`search_inside` = 'idanagrafica IN(SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica IN (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione LIKE |search|))' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Tipo';
-- Aggiunta della descrizione negli Articoli
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `search_inside`, `order_by`, `enabled`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'Descrizione', 'descrizione', '1', '1', '0', '', '', '1', '0', '1');

-- Aggiornamento delle icone
UPDATE `co_statidocumento` SET `icona` = 'fa fa-check-circle text-success' WHERE `descrizione` = 'Pagato';
UPDATE `co_statidocumento` SET `icona` = 'fa fa-file-text-o text-muted' WHERE `descrizione` = 'Bozza';
UPDATE `co_statidocumento` SET `icona` = 'fa fa-clock-o text-info' WHERE `descrizione` = 'Emessa';
UPDATE `co_statidocumento` SET `icona` = 'fa fa-times text-danger' WHERE `descrizione` = 'Annullata';
UPDATE `co_statidocumento` SET `icona` = 'fa fa-dot-circle-o text-warning' WHERE `descrizione` = 'Parzialmente pagato';

UPDATE `or_statiordine` SET `icona` = 'fa fa-file-text-o text-muted' WHERE `descrizione` = 'Non evaso';
UPDATE `or_statiordine` SET `icona` = 'fa fa-check-circle text-success' WHERE `descrizione` = 'Evaso';
UPDATE `or_statiordine` SET `icona` = 'fa fa-gear text-warning' WHERE `descrizione` = 'Parzialmente evaso';

UPDATE `dt_statiddt` SET `icona` = 'fa fa-file-text-o text-muted' WHERE `descrizione` = 'Bozza';
UPDATE `dt_statiddt` SET `icona` = 'fa fa-clock-o text-info' WHERE `descrizione` = 'Evaso';
UPDATE `dt_statiddt` SET `icona` = 'fa fa-check-circle text-success' WHERE `descrizione` = 'Pagato';

UPDATE `co_staticontratti` SET `icona` = 'fa fa-file-text-o text-muted' WHERE `descrizione` = 'Bozza';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-clock-o text-warning' WHERE `descrizione` = 'In attesa di conferma';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-thumbs-up text-success' WHERE `descrizione` = 'Accettato';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-thumbs-down text-danger' WHERE `descrizione` = 'Rifiutato';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-gear text-warning' WHERE `descrizione` = 'In lavorazione';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-money text-primary' WHERE `descrizione` = 'In attesa di pagamento';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-check-circle text-success' WHERE `descrizione` = 'Pagato';
UPDATE `co_staticontratti` SET `icona` = 'fa fa-check text-success' WHERE `descrizione` = 'Concluso';

UPDATE `co_statipreventivi` SET `icona` = 'fa fa-file-text-o text-muted' WHERE `descrizione` = 'Bozza';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-clock-o text-warning' WHERE `descrizione` = 'In attesa di conferma';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-thumbs-up text-success' WHERE `descrizione` = 'Accettato';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-thumbs-down text-danger' WHERE `descrizione` = 'Rifiutato';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-gear text-warning' WHERE `descrizione` = 'In lavorazione';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-check text-success' WHERE `descrizione` = 'Concluso';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-check-circle text-success' WHERE `descrizione` = 'Pagato';
UPDATE `co_statipreventivi` SET `icona` = 'fa fa-money text-primary' WHERE `descrizione` = 'In attesa di pagamento';

-- Aggiunta sconto incondizionato sull'attività
ALTER TABLE `in_interventi` ADD `tipo_sconto` enum('UNT', 'PRC') NOT NULL DEFAULT 'UNT' AFTER `ora_sla`;

-- Aggiunta sconto in euro e percentuale su ore e km, e quantità ore nella riga tecnico-intervento
ALTER TABLE `in_interventi_tecnici` ADD `ore` decimal(12, 4) NOT NULL AFTER `orario_fine`;

-- Calcolo il campo ore degli interventi già inseriti
UPDATE `in_interventi_tecnici` SET `ore` = (TIMESTAMPDIFF(MINUTE, `orario_inizio`, `orario_fine`) / 60);

-- Aggiunta sconti e prezzo di acquisto su righe generiche attività
ALTER TABLE `in_righe_interventi` ADD `prezzo_acquisto` decimal(12, 4) NOT NULL AFTER `prezzo`;

-- Aggiunta sconto in percentuale, unità di misura (in copia negli articoli aggiunti negli interventi), prezzo di acquisto sugli articoli e sulle spese aggiuntive
ALTER TABLE `mg_articoli_interventi` ADD `um` varchar(20) NOT NULL AFTER `qta`, ADD `prezzo_acquisto` decimal(12, 4) NOT NULL AFTER `altro`;

-- Rinomino il prezzo finale delle righe generiche in prezzo_vendita per uniformare i campi
ALTER TABLE `in_righe_interventi` CHANGE `prezzo` `prezzo_vendita` decimal(12, 4) NOT NULL;

-- Aggiungo la tipologia di intervento alla riga del tecnico e copio le tipologie già inserite nel nuovo campo
ALTER TABLE `in_interventi_tecnici` ADD `idtipointervento` varchar(25) NOT NULL AFTER `idintervento`;
-- Fix per le sessioni di lavoro dei tecnici precedenti
UPDATE `in_interventi_tecnici` SET `idtipointervento` = (SELECT `idtipointervento` FROM `in_interventi` WHERE `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`);

-- Cambiato il campo dell'unità di misura in valore effettivo, togliendo il riferimento all'id. Conversione dei precedenti id in valori
ALTER TABLE `mg_articoli` ADD `um` varchar(20) NOT NULL AFTER `idum`;
UPDATE `mg_articoli` SET `um` = (SELECT `valore` FROM `mg_unitamisura` WHERE `id` = `mg_articoli`.`idum`);
ALTER TABLE `mg_articoli` DROP `idum`;

-- Aggiunte altre possibili ritenute d'acconto e iva
INSERT INTO `co_ritenutaacconto` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`) VALUES (NULL, 'Ritenuta d''acconto 10%', '10', '0.00', '0');
INSERT INTO `co_ritenutaacconto` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`) VALUES (NULL, 'Ritenuta d''acconto 4%', '4', '0.00', '0');
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `descrizione2`, `indetraibile`, `esente`) VALUES (NULL, 'Art. 17 comma 6 DPR 633/72 22%', '22', '', '0.00', '0');

-- Aggiunto modulo per gestire le ritenute d'acconto
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Ritenute acconto', 'ritenute', 'SELECT |select| FROM `co_ritenutaacconto` WHERE 1=1 HAVING 2=2', '', 'fa fa-percent', '2.3', '2.3', '1', 1, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Ritenute acconto' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute acconto'), 'Indetraibile', 'indetraibile', 4, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute acconto'), 'Percentuale', 'percentuale', 3, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute acconto'), 'Descrizione', 'descrizione', 2, 1, 0, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ritenute acconto'), 'id', 'id', 1, 1, 0, 0, 0);

-- Query mancanti per viste su MyImpianti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'idanagrafica', 'idanagrafica', 1, 0, 0, 0, 1);

-- Query mancanti per viste su Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'idanagrafica', 'idanagrafica', 1, 0, 0, 0, 1);

-- Aggiunta del campo per permettere la modifica delle Viste di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Modifica Viste di default', '0', 'boolean', 0, 'Generali');

-- Aggiunta del campo per permettere la modifica delle prima pagina di OSM
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES
('Prima pagina', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Dashboard'), 'query=SELECT id, title AS ''descrizione'' FROM zz_modules WHERE enabled = 1 AND options != '''' AND options != ''menu'' AND options IS NOT NULL ORDER BY `order` ASC', 1, 'Generali');

-- Aggiunta del campo idagente in Anagrafiche per la visione da parte degli agenti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'idagente', 'idagente', 9, 0, 0, 0, 1);

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) VALUES
((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Agenti'), (SELECT `id` FROM `zz_views` WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche') AND `name` = 'idagente'));

-- Aggiunta di chiavi esterne in zz_modules
ALTER TABLE `zz_modules` CHANGE `parent` `parent` int(11) NULL;
UPDATE `zz_modules` SET `parent` = NULL WHERE `parent` = 0;
ALTER TABLE `zz_modules` ADD FOREIGN KEY (`parent`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_widgets
ALTER TABLE `zz_widgets` ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_group_module
ALTER TABLE `zz_group_module` ADD FOREIGN KEY (`idmodule`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE, ADD FOREIGN KEY (`idgruppo`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_permissions
DELETE FROM `zz_permissions` WHERE `idmodule` NOT IN (SELECT `id` FROM `zz_modules`);
ALTER TABLE `zz_permissions` ADD FOREIGN KEY (`idmodule`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE, ADD FOREIGN KEY (`idgruppo`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_plugins
DELETE FROM `zz_plugins` WHERE `idmodule_to` NOT IN (SELECT `id` FROM `zz_modules`);
DELETE FROM `zz_plugins` WHERE `idmodule_from` NOT IN (SELECT `id` FROM `zz_modules`);
ALTER TABLE `zz_plugins` ADD FOREIGN KEY (`idmodule_from`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE, ADD FOREIGN KEY (`idmodule_to`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_users
ALTER TABLE `zz_users` CHANGE `idutente` `id` int(11) NOT NULL AUTO_INCREMENT, ADD FOREIGN KEY (`idgruppo`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_logs
ALTER TABLE `zz_logs` DROP `password`, CHANGE `idutente` `id_utente` int(11), CHANGE `timestamp` `timestamp` datetime;
UPDATE `zz_logs` SET `id_utente` = NULL WHERE `id_utente` = 0;
DELETE FROM `zz_logs` WHERE `id_utente` IS NOT NULL AND `id_utente` NOT IN (SELECT `id` FROM `zz_users`);
ALTER TABLE `zz_logs` ADD FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE;

-- Aggiunta di chiavi esterne in zz_semaphores
ALTER TABLE `zz_semaphores` ADD FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE;

-- Aggiunta della tabella per gestire le chiavi di accesso all'API
CREATE TABLE IF NOT EXISTS `zz_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_utente` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `descrizione` varchar(255),
  `enabled` boolean NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`id_utente`) REFERENCES `zz_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Modifica di an_sedi per sostituire le nazioni con i corrispettivi nella tabella apposita
ALTER TABLE `an_sedi` ADD `id_nazione` int(11) COMMENT 'Nazione' AFTER `nazione`, ADD FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni`(`id`);
UPDATE `an_sedi` SET `id_nazione` = `nazione` WHERE `nazione` IN (SELECT `id` FROM `an_nazioni`);
ALTER TABLE `an_sedi` DROP COLUMN `nazione`;

-- Aggiunta di chiavi esterne in my_impianto_componenti
ALTER TABLE `my_impianto_componenti` CHANGE `idsostituto` `idsostituto` int(11);
UPDATE `my_impianto_componenti` SET `idsostituto` = NULL WHERE `idsostituto` = 0;
-- PRIMA DI AGGIUNGERE LA CHIAVE ESTERNA: mi assicuro che non ci siano componenti collegati a componenti non più esistenti
DELETE `t1` FROM `my_impianto_componenti` `t1` INNER JOIN `my_impianto_componenti` `t2` ON `t1`.`id` = `t2`.`id` WHERE `t1`.`idsostituto` NOT IN (`t2`.`id`);
ALTER TABLE `my_impianto_componenti` ADD FOREIGN KEY (`idsostituto`) REFERENCES `my_impianto_componenti`(`id`) ON DELETE CASCADE;

-- Adeguamento degli id di zz_files per interventi
UPDATE `zz_files` SET `externalid` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`idintervento` = `externalid`) WHERE module = 'interventi' ;

-- Adeguamento degli id di zz_files per impianti
UPDATE `zz_files` SET `externalid` = (SELECT `id` FROM `my_impianti` WHERE `my_impianti`.`matricola` = `externalid`) WHERE module = 'myimpianti' ;

-- Adeguamento dei contenuti di zz_files
ALTER TABLE `zz_files` CHANGE `externalid` `id_record` int(11) NOT NULL, ADD `id_module` int(11) NOT NULL AFTER `filename`, ADD `original` varchar(255) NOT NULL AFTER `filename`;

-- Adeguamento delle fatture (zz_files)
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di vendita') WHERE (`module`= 'fatture di vendita' OR `module`= 'fatture')  AND `id_record` IN (SELECT `id` FROM `co_documenti` WHERE `idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata'));
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Fatture di acquisto') WHERE (`module`= 'fatture di acquisto' OR `module`= 'fatture') AND `id_record` IN (SELECT `id` FROM `co_documenti` WHERE `idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'uscita'));

-- Adeguamento dei ddt (zz_files)
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Ddt di vendita') WHERE (`module`= 'ddt di vendita' OR `module`= 'ddt')  AND `id_record` IN (SELECT `id` FROM `dt_ddt` WHERE `idtipoddt` IN (SELECT `id` FROM `dt_tipiddt` WHERE `dir` = 'entrata'));
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Ddt di acquisto') WHERE (`module`= 'ddt di acquisto' OR `module`= 'ddt') AND `id_record` IN (SELECT `id` FROM `dt_ddt` WHERE `idtipoddt` IN (SELECT `id` FROM `dt_tipiddt` WHERE `dir` = 'uscita'));

-- Adeguamento degli ordini (zz_files)
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Ordini cliente') WHERE (`module`= 'ordini cliente' OR `module`= 'ordini') AND `id_record` IN (SELECT `id` FROM `or_ordini` WHERE `idtipoordine` IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = 'entrata'));
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = 'Ordini fornitore') WHERE (`module`= 'ordini fornitore' OR `module`= 'ordini') AND `id_record` IN (SELECT `id` FROM `or_ordini` WHERE `idtipoordine` IN (SELECT `id` FROM `or_tipiordine` WHERE `dir` = 'uscita'));

-- Adeguamento resto dei moduli (zz_files)
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`name` = `zz_files`.`module`) WHERE `id_module` = 0 OR `id_module` IS NULL;
UPDATE `zz_files` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `zz_modules`.`directory` = `zz_files`.`module`) WHERE `id_module` = 0 OR `id_module` IS NULL;
ALTER TABLE `zz_files` DROP `module`;

-- Fix dei contenuti dei filtri per gruppi
UPDATE `zz_group_module` SET `clause` = IF(SUBSTRING(TRIM(`clause`), 1, 4) = 'AND ', SUBSTRING(TRIM(`clause`), 4), TRIM(`clause`));

-- Aggiunta dei campi position e default in zz_group_module
ALTER TABLE `zz_group_module` ADD `default` boolean NOT NULL DEFAULT 0 AFTER `clause`, ADD `enabled` boolean NOT NULL DEFAULT 1 AFTER `clause`, ADD `position` enum('WHR', 'HVN') NOT NULL DEFAULT 'WHR' AFTER `clause`;
UPDATE `zz_group_module` SET `default` = 1;

-- Fix per lo spostamento di pdfgen.php
UPDATE `zz_widgets` SET `more_link` = REPLACE(TRIM(`more_link`), 'templates/pdfgen.php', 'pdfgen.php');

-- Nuova struttura per i plugins
ALTER TABLE `zz_plugins` ADD `title` varchar(255) NOT NULL AFTER `name`,
ADD `directory` varchar(50) NOT NULL AFTER `script`, ADD `options` text AFTER `script`, ADD `options2` text AFTER `script`, ADD `version` varchar(15) NOT NULL AFTER `script`, ADD `compatibility` varchar(1000) NOT NULL AFTER `script`, ADD `order` int(11) NOT NULL AFTER `script`, ADD `default` boolean NOT NULL DEFAULT 0 AFTER `script`, ADD `enabled` boolean NOT NULL DEFAULT 1 AFTER `script`;

UPDATE `zz_plugins` SET `name` = 'Serial' WHERE `name` = 'Lotti';
UPDATE `zz_plugins` SET `title` = `name` WHERE `title` = '';

-- Nuova struttura per i plugin Sedi e Referenti in Anagrafiche
UPDATE `zz_plugins` SET `script` = '', `options` = '	{ "main_query": [	{	"type": "table", "fields": "Nome, Indirizzo, Città, CAP, Provincia, Referente",	"query": "SELECT an_sedi.id, an_sedi.nomesede AS Nome, an_sedi.indirizzo AS Indirizzo, an_sedi.citta AS Città, an_sedi.cap AS CAP, an_sedi.provincia AS Provincia, an_referenti.nome AS Referente FROM an_sedi LEFT OUTER JOIN an_referenti ON idsede = an_sedi.id WHERE 1=1 AND an_sedi.idanagrafica=|idanagrafica| HAVING 2=2 ORDER BY an_sedi.id DESC"}	]}', `directory` = 'sedi', `version` = '2.3', `compatibility` = '2.*' WHERE `name` = 'Sedi';
UPDATE `zz_plugins` SET `script` = '', `options` = '	{ "main_query": [	{	"type": "table", "fields": "Nominativo, Mansione, Telefono, Indirizzo email, Sede",	"query": "SELECT an_referenti.id, an_referenti.nome AS Nominativo, mansione AS Mansione, an_referenti.telefono AS Telefono, an_referenti.email AS ''Indirizzo email'', an_sedi.nomesede AS Sede FROM an_referenti LEFT OUTER JOIN an_sedi ON idsede = an_sedi.id WHERE 1=1 AND an_referenti.idanagrafica=|idanagrafica| HAVING 2=2 ORDER BY an_referenti.id DESC"}	]}', `directory` = 'referenti', `version` = '2.3', `compatibility` = '2.*' WHERE `name` = 'Referenti';

-- Cleanup della tabella zz_settings
DELETE FROM `zz_settings` WHERE (`idimpostazione` = 33 AND `nome` = 'osmcloud_username') OR (`idimpostazione` = 34 AND `nome` = 'osmcloud_password') OR (`idimpostazione` = 3 AND `nome` = 'max_idintervento') OR (`idimpostazione` = 30 AND `nome` = 'Numero di mesi prima da cui iniziare a visualizzare gli interventi') OR (`idimpostazione` = 35 AND `nome` = 'osm_installed');

-- Modifica degli stati dei contratti
ALTER TABLE `co_staticontratti` DROP `completato`, DROP `annullato`, ADD `fatturabile` boolean NOT NULL AFTER `icona`, ADD `pianificabile` boolean NOT NULL AFTER `icona`;

UPDATE `co_staticontratti` SET `fatturabile` = 1 WHERE `descrizione` IN ('Pagato', 'Accettato', 'In lavorazione', 'In attesa di conferma', 'In attesa di pagamento', 'Concluso');
UPDATE `co_staticontratti` SET `pianificabile` = 1 WHERE `descrizione` IN ('Pagato', 'Accettato', 'In lavorazione', 'In attesa di pagamento');

-- Aggiornamento delle impostazioni riguardanti le cifre decimali
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Cifre decimali per quantità', '2', 'list[1,2,3,4]', 1, 'Generali');
UPDATE `zz_settings` SET `nome` = 'Cifre decimali per importi' WHERE `nome` = 'Cifre decimali';

-- Generalizzazione nella gestione dei seriali
ALTER TABLE `mg_articoli` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`;
ALTER TABLE `co_righe_documenti` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`;
ALTER TABLE `dt_righe_ddt` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`;
ALTER TABLE `mg_articoli_interventi` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`;
ALTER TABLE `or_righe_ordini` ADD `abilita_serial` boolean NOT NULL DEFAULT '0' AFTER `um`;

-- Aggiunto modulo per visualizzare i movimenti di magazzino
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Movimenti', '', 'SELECT |select| FROM `mg_movimenti` JOIN `mg_articoli` ON `mg_articoli`.id = `mg_movimenti`.`idarticolo` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.3', '2.3', '1', 1, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Movimenti' AND `t2`.`name` = 'Magazzino') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'Articolo', 'IF(mg_articoli.descrizione != '''', CONCAT(mg_articoli.codice, '' - '', mg_articoli.descrizione), mg_articoli.codice)', 2, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'Data', 'mg_movimenti.created_at', 5, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'Quantità', 'mg_movimenti.qta', 4, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'Descrizione', 'movimento', 3, 1, 0, 1, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'id', 'mg_movimenti.id', 1, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), '_link_module_', '(SELECT `id` FROM `zz_modules` WHERE `name` = ''Articoli'')', 6, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), '_link_record_', 'mg_movimenti.idarticolo', 7, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), '_link_hash_', 'CONCAT(''tab_'', (SELECT `id` FROM `zz_plugins` WHERE `name` = ''Movimenti'' AND idmodule_to = (SELECT `id` FROM `zz_modules` WHERE `name` = ''Articoli'')))', 7, 1, 0, 0, 1);

-- Aggiunta del campo di dicitura fissa per la fatturazione
ALTER TABLE `co_iva` ADD `dicitura` varchar(255);

-- Miglioramento della gestione dei pagamenti predefiniti
ALTER TABLE `an_anagrafiche` CHANGE `idpagamento` `idpagamento_vendite` int(11), ADD `idpagamento_acquisti` int(11) AFTER `idpagamento_vendite`;
UPDATE `an_anagrafiche` SET `idpagamento_vendite` = NULL WHERE `idpagamento_vendite` = 0;
UPDATE `an_anagrafiche` SET `idpagamento_acquisti` = `idpagamento_vendite` WHERE `idpagamento_vendite` IS NOT NULL;

-- Miglioramento della gestione dei listini predefiniti
ALTER TABLE `an_anagrafiche` CHANGE `idlistino` `idlistino_vendite` int(11), ADD `idlistino_acquisti` int(11) AFTER `idlistino_vendite`;
UPDATE `an_anagrafiche` SET `idlistino_vendite` = NULL WHERE `idlistino_vendite` = 0;
UPDATE `an_anagrafiche` SET `idlistino_acquisti` = `idlistino_vendite` WHERE `idlistino_vendite` IS NOT NULL;

-- Miglioramento della gestione dell'iva predefinita
ALTER TABLE `an_anagrafiche` CHANGE `idiva` `idiva_vendite` int(11), ADD `idiva_acquisti` int(11) AFTER `idiva_vendite`;
UPDATE `an_anagrafiche` SET `idiva_vendite` = NULL WHERE `idiva_vendite` = 0;
UPDATE `an_anagrafiche` SET `idiva_acquisti` = `idiva_vendite` WHERE `idiva_vendite` IS NOT NULL;

-- Rimozione data_sla e ora_sla
ALTER TABLE `in_interventi` DROP `data_sla`, DROP `ora_sla`;

-- Creazione del campo format per la tabella zz_views
ALTER TABLE `zz_views` ADD `format` boolean NOT NULL DEFAULT 0 AFTER `slow`;
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`format` = 1 WHERE 
(`zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` IN('Importo', 'Pagato', 'Data scadenza')) OR 
(`zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` IN ('Totale', 'Data')) OR
(`zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` IN ('Totale', 'Data')) OR
(`zz_modules`.`name` = 'Tipi di intervento' AND `zz_views`.`name`  IN ('Costo orario', 'Costo al km', 'Diritto di chiamata',  'Costo orario tecnico','Costo al km tecnico', 'Diritto di chiamata tecnico')) OR
(`zz_modules`.`name` = 'Interventi' AND `zz_views`.`name`  IN ('Data inizio', 'Data fine')) OR
(`zz_modules`.`name` = 'Prima nota' AND `zz_views`.`name`  IN('Data', 'Dare', 'Avere')) OR 
(`zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Data' ) OR
(`zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Data' ) OR
(`zz_modules`.`name` = 'MyImpianti' AND `zz_views`.`name` = 'Data' ) OR
(`zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'Data' ) OR
(`zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` IN ('Data', 'Quantità'));

-- Disabilitazione dei plugin instabili
UPDATE `zz_plugins` SET `enabled` = 0 WHERE `name` = 'Pianificazione fatturazione' OR `name` = 'Pianificazione ordini di servizio';

-- Fix del tipo di alcune impostazioni
UPDATE `zz_settings` SET `tipo` = 'decimal' WHERE `nome` = 'Soglia minima per l''applicazione della marca da bollo' OR `nome` = 'Importo marca da bollo';
UPDATE `zz_settings` SET `valore` = '82' WHERE `nome` = 'Tipo di pagamento predefinito' AND `valore` = '20';

-- Fix per le date in varie tabelle
ALTER TABLE `co_documenti` CHANGE `data` `data` datetime;
ALTER TABLE `or_ordini` CHANGE `data` `data` datetime;
ALTER TABLE `dt_ddt` CHANGE `data` `data` datetime;

ALTER TABLE `co_preventivi` CHANGE `data_bozza` `data_bozza` datetime, CHANGE `data_accettazione` `data_accettazione` datetime, CHANGE `data_rifiuto` `data_rifiuto` datetime, CHANGE `data_conclusione` `data_conclusione` datetime, CHANGE `data_pagamento` `data_pagamento` datetime;
ALTER TABLE `co_contratti` CHANGE `data_bozza` `data_bozza` datetime, CHANGE `data_accettazione` `data_accettazione` datetime, CHANGE `data_rifiuto` `data_rifiuto` datetime, CHANGE `data_conclusione` `data_conclusione` datetime;

ALTER TABLE `co_movimenti` CHANGE `data` `data` datetime, CHANGE `data_documento` `data_documento` datetime;
ALTER TABLE `co_ordiniservizio` CHANGE `data_scadenza` `data_scadenza` datetime;
ALTER TABLE `co_ordiniservizio_pianificazionefatture` CHANGE `data_scadenza` `data_scadenza` datetime;
ALTER TABLE `co_righe_contratti` CHANGE `data_richiesta` `data_richiesta` datetime;
ALTER TABLE `co_righe_preventivi` CHANGE `data_evasione` `data_evasione` datetime;
ALTER TABLE `co_scadenziario` CHANGE `data_emissione` `data_emissione` datetime, CHANGE `scadenza` `scadenza` datetime, CHANGE `data_pagamento` `data_pagamento` datetime;
ALTER TABLE `dt_automezzi_tecnici` CHANGE `data_inizio` `data_inizio` datetime, CHANGE `data_fine` `data_fine` datetime;
ALTER TABLE `my_impianto_componenti` CHANGE `data` `data` datetime, CHANGE `data_sostituzione` `data_sostituzione` datetime;
ALTER TABLE `or_righe_ordini` CHANGE `data_evasione` `data_evasione` datetime;

UPDATE `co_documenti` SET `data` = NULL WHERE `data` = '0000-00-00 00:00:00';
UPDATE `or_ordini` SET `data` = NULL WHERE `data` = '0000-00-00 00:00:00';
UPDATE `dt_ddt` SET `data` = NULL WHERE `data` = '0000-00-00 00:00:00';

UPDATE `co_preventivi` SET `data_bozza` = NULL WHERE `data_bozza` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_accettazione` = NULL WHERE `data_accettazione` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_rifiuto` = NULL WHERE `data_rifiuto` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_conclusione` = NULL WHERE `data_conclusione` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_pagamento` = NULL WHERE `data_pagamento` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_bozza` = NULL WHERE `data_bozza` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_accettazione` = NULL WHERE `data_accettazione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_rifiuto` = NULL WHERE `data_rifiuto` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_conclusione` = NULL WHERE `data_conclusione` = '0000-00-00 00:00:00';

UPDATE `co_movimenti` SET `data` = NULL WHERE `data` = '0000-00-00 00:00:00';
UPDATE `co_movimenti` SET `data_documento` = NULL WHERE `data_documento` = '0000-00-00 00:00:00';
UPDATE `co_ordiniservizio` SET `data_scadenza` = NULL WHERE `data_scadenza` = '0000-00-00 00:00:00';
UPDATE `co_ordiniservizio_pianificazionefatture` SET `data_scadenza` = NULL WHERE `data_scadenza` = '0000-00-00 00:00:00';
UPDATE `co_righe_contratti` SET `data_richiesta` = NULL WHERE `data_richiesta` = '0000-00-00 00:00:00';
UPDATE `co_righe_preventivi` SET `data_evasione` = NULL WHERE `data_evasione` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `data_emissione` = NULL WHERE `data_emissione` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `scadenza` = NULL WHERE `scadenza` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `data_pagamento` = NULL WHERE `data_pagamento` = '0000-00-00 00:00:00';
UPDATE `dt_automezzi_tecnici` SET `data_inizio` = NULL WHERE `data_inizio` = '0000-00-00 00:00:00';
UPDATE `dt_automezzi_tecnici` SET `data_fine` = NULL WHERE `data_fine` = '0000-00-00 00:00:00';
UPDATE `my_impianto_componenti` SET `data` = NULL WHERE `data` = '0000-00-00 00:00:00';
UPDATE `my_impianto_componenti` SET `data_sostituzione` = NULL WHERE `data_sostituzione` = '0000-00-00 00:00:00';
UPDATE `or_righe_ordini` SET `data_evasione` = NULL WHERE `data_evasione` = '0000-00-00 00:00:00';

ALTER TABLE `co_documenti` CHANGE `data` `data` date;
ALTER TABLE `or_ordini` CHANGE `data` `data` date;
ALTER TABLE `dt_ddt` CHANGE `data` `data` date;

ALTER TABLE `co_preventivi` CHANGE `data_bozza` `data_bozza` date, CHANGE `data_accettazione` `data_accettazione` date, CHANGE `data_rifiuto` `data_rifiuto` date, CHANGE `data_conclusione` `data_conclusione` date, CHANGE `data_pagamento` `data_pagamento` date;
ALTER TABLE `co_contratti` CHANGE `data_bozza` `data_bozza` date, CHANGE `data_accettazione` `data_accettazione` date, CHANGE `data_rifiuto` `data_rifiuto` date, CHANGE `data_conclusione` `data_conclusione` date;

ALTER TABLE `co_movimenti` CHANGE `data` `data` date, CHANGE `data_documento` `data_documento` date;
ALTER TABLE `co_ordiniservizio` CHANGE `data_scadenza` `data_scadenza` date;
ALTER TABLE `co_ordiniservizio_pianificazionefatture` CHANGE `data_scadenza` `data_scadenza` date;
ALTER TABLE `co_righe_contratti` CHANGE `data_richiesta` `data_richiesta` date;
ALTER TABLE `co_righe_preventivi` CHANGE `data_evasione` `data_evasione` date;
ALTER TABLE `co_scadenziario` CHANGE `data_emissione` `data_emissione` date, CHANGE `scadenza` `scadenza` date, CHANGE `data_pagamento` `data_pagamento` date;
ALTER TABLE `dt_automezzi_tecnici` CHANGE `data_inizio` `data_inizio` date, CHANGE `data_fine` `data_fine` date;
ALTER TABLE `my_impianto_componenti` CHANGE `data` `data` date, CHANGE `data_sostituzione` `data_sostituzione` date;
ALTER TABLE `or_righe_ordini` CHANGE `data_evasione` `data_evasione` date;

-- Fix per i serial number
ALTER TABLE `mg_prodotti` ADD `id_riga_documento` int(11), ADD FOREIGN KEY (`id_riga_documento`) REFERENCES `co_righe_documenti`(`id`) ON DELETE CASCADE, ADD `id_riga_ordine` int(11), ADD FOREIGN KEY (`id_riga_ordine`) REFERENCES `or_righe_ordini`(`id`) ON DELETE CASCADE, ADD `id_riga_ddt` int(11), ADD FOREIGN KEY (`id_riga_ddt`) REFERENCES `dt_righe_ddt`(`id`) ON DELETE CASCADE, ADD `id_riga_intervento` int(11), ADD FOREIGN KEY (`id_riga_intervento`) REFERENCES `mg_articoli_interventi`(`id`) ON DELETE CASCADE, ADD `dir` enum('entrata', 'uscita') DEFAULT 'uscita', CHANGE `idarticolo` `id_articolo` int(11), ADD FOREIGN KEY (`id_articolo`) REFERENCES `mg_articoli`(`id`) ON DELETE SET NULL, CHANGE `serial` `serial` varchar(50), CHANGE `lotto` `lotto` varchar(50), CHANGE `altro` `altro` varchar(50);

INSERT INTO `mg_prodotti` (`id_riga_documento`, `dir`, `id_articolo`, `serial`, `lotto`, `altro`) SELECT `id`, (SELECT `dir` FROM `co_tipidocumento` JOIN `co_documenti` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`), IF(`idarticolo` IN (SELECT `id` FROM `mg_articoli`), `idarticolo`, NULL), `serial`, `lotto`, `altro` FROM `co_righe_documenti`;

INSERT INTO `mg_prodotti` (`id_riga_ordine`, `dir`, `id_articolo`, `serial`, `lotto`, `altro`) SELECT `id`, (SELECT `dir` FROM `or_tipiordine` JOIN `or_ordini` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine` WHERE `or_ordini`.`id` = `or_righe_ordini`.`idordine`), IF(`idarticolo` IN (SELECT `id` FROM `mg_articoli`), `idarticolo`, NULL), `serial`, `lotto`, `altro` FROM `or_righe_ordini`;

INSERT INTO `mg_prodotti` (`id_riga_ddt`, `dir`, `id_articolo`, `serial`, `lotto`, `altro`) SELECT `id`, (SELECT `dir` FROM `dt_tipiddt` JOIN `dt_ddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt` WHERE `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`), IF(`idarticolo` IN (SELECT `id` FROM `mg_articoli`), `idarticolo`, NULL), `serial`, `lotto`, `altro` FROM `dt_righe_ddt`;

INSERT INTO `mg_prodotti` (`id_riga_intervento`, `dir`, `id_articolo`, `serial`, `lotto`, `altro`) SELECT `id`, 'entrata', IF(`idarticolo` IN (SELECT `id` FROM `mg_articoli`), `idarticolo`, NULL), `serial`, `lotto`, `altro` FROM `mg_articoli_interventi`;

UPDATE `mg_prodotti` SET `serial` = NULL WHERE `serial` = '';
UPDATE `mg_prodotti` SET `lotto` = NULL WHERE `lotto` = '';
UPDATE `mg_prodotti` SET `altro` = NULL WHERE `altro` = '';

DELETE FROM `mg_prodotti` WHERE `serial` IS NULL AND `lotto` IS NULL AND `altro` IS NULL;

ALTER TABLE `co_righe_documenti` DROP `serial`, DROP `altro`, DROP `lotto`;
ALTER TABLE `mg_articoli_interventi` DROP `serial`, DROP `altro`, DROP `lotto`;
ALTER TABLE `dt_righe_ddt` DROP `serial`, DROP `altro`, DROP `lotto`;
ALTER TABLE `or_righe_ordini` DROP `serial`, DROP `altro`, DROP `lotto`;
ALTER TABLE `co_righe_preventivi` DROP `serial`, DROP `altro`, DROP `lotto`;

UPDATE `mg_articoli` SET `abilita_serial` = 1 WHERE `id` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `co_righe_documenti` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `dt_righe_ddt` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `mg_articoli_interventi` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `or_righe_ordini` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);

-- Rimozione del campo descrizione_articolo (inutilizzato) da mg_movimenti
ALTER TABLE `mg_movimenti` DROP `descrizione_articolo`;

-- Rimozione sconto/magg. per i preventivi
UPDATE `co_righe_preventivi` SET `sconto_unitario` = `prc_guadagno`, `tipo_sconto` = 'PRC', `sconto` = `prc_guadagno` * `qta` WHERE `prc_guadagno` != 0;
ALTER TABLE `co_righe_preventivi` DROP `prc_guadagno`;

-- Rimozione del campo descrizione2 da co_iva e aggiunta delle diciture fisse in fattura (per la stampa)
ALTER TABLE `co_iva` DROP `descrizione2`;
UPDATE `co_iva` SET `dicitura` = 'Senza addebito iva ex art. 74 comma 8-9 del DPR 633/72' WHERE `descrizione` = 'Esente art. 74';
UPDATE `co_iva` SET `dicitura` = 'Operazione soggetta a reverse charge ex art. 17, comma 6, DPR 633/72' WHERE `descrizione` = 'Art. 17 comma 6 DPR 633/72' OR `descrizione` = 'Art. 17 comma 6 DPR 633/72 4%' OR `descrizione` = 'Art. 17 comma 6 DPR 633/72 10%' OR `descrizione` = 'Art. 17 comma 6 DPR 633/72 20%' OR `descrizione` = 'Art. 17 comma 6 DPR 633/72 22%';

-- Aggiunta campi in co_pagamenti per la selezione del conto di default
ALTER TABLE `co_pagamenti` ADD `idconto_vendite` int(11), ADD `idconto_acquisti` int(11);

-- Aggiunta del modulo Stampe contabili
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Stampe contabili', 'stampe_contabili', 'custom', '', 'fa fa-angle-right', '2.3', '2.3', '1', NULL, '1', '1');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Stampe contabili' AND `t2`.`name` = 'Contabilità') SET `t1`.`parent` = `t2`.`id`;

-- Aggiunta del campo per introdurre l'help nei widget
ALTER TABLE `zz_widgets` ADD `help` varchar(255);

-- ALTER TABLE `my_componenti_interventi` ADD PRIMARY KEY (`id_intervento`, `id_componente`);
-- ALTER TABLE `my_impianti_interventi` ADD PRIMARY KEY (`idintervento`, `idimpianto`);
-- ALTER TABLE `co_righe_documenti`ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE;

-- Aggiunta delle mappe Google
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Google Maps API key', '', 'string', '1', 'Generali');

ALTER TABLE `an_anagrafiche` ADD `gaddress` varchar(255), ADD `lat` float(10, 6), ADD `lng` float(10, 6);
ALTER TABLE `an_sedi` ADD `gaddress` varchar(255), ADD `lat` float(10, 6), ADD `lng` float(10, 6);

-- Aggiunta del modulo Statistiche
INSERT INTO `zz_modules` (`id`, `name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Statistiche', 'statistiche', 'custom', '', 'fa fa-bar-chart', '2.3', '2.3', '1', NULL, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN (SELECT MAX(`order`) AS `order` FROM `zz_modules`) `t2` ON (`t1`.`name` = 'Statistiche') SET `t1`.`order` = `t2`.`order`+1;
UPDATE `zz_modules` `t1` INNER JOIN (SELECT MAX(`order`) AS `order` FROM `zz_modules`) `t2` ON (`t1`.`name` = 'Strumenti') SET `t1`.`order` = `t2`.`order`+1;

-- Impostazione dei titoli di default
UPDATE `zz_modules` SET `title` = `name` WHERE `title` = '';

-- Nuova struttura per i plugin Ddt del cliente e Impianti del cliente in Anagrafiche
UPDATE `zz_plugins` SET `script` = '', `options` = '	{ "main_query": [	{	"type": "table", "fields": "Numero, Data, Descrizione, Qtà",	"query": "SELECT dt_ddt.id, (SELECT `id` FROM `zz_modules` WHERE `name` = ''Ddt di vendita'') AS _link_module_, dt_ddt.id AS _link_record_, IF(dt_ddt.numero_esterno = '''', dt_ddt.numero, dt_ddt.numero_esterno) AS Numero, DATE_FORMAT(dt_ddt.data, ''%d/%m/%Y'') AS Data, dt_righe_ddt.descrizione AS `Descrizione`, CONCAT(REPLACE(REPLACE(REPLACE(FORMAT(dt_righe_ddt.qta, 2), '','', ''#''), ''.'', '',''), ''#'', ''.''), '' '', dt_righe_ddt.um) AS `Qtà` FROM dt_ddt JOIN dt_righe_ddt ON dt_ddt.id=dt_righe_ddt.idddt WHERE dt_ddt.idanagrafica=|idanagrafica| HAVING 2=2 ORDER BY dt_ddt.id DESC"}	]}', `directory` = '', `version` = '2.3', `compatibility` = '2.*' WHERE `name` = 'Ddt del cliente';
UPDATE `zz_plugins` SET `script` = '', `options` = '	{ "main_query": [	{	"type": "table", "fields": "Matricola, Nome, Data, Descrizione",	"query": "SELECT id, (SELECT `id` FROM `zz_modules` WHERE `name` = ''MyImpianti'') AS _link_module_, id AS _link_record_, matricola AS Matricola, nome AS Nome, DATE_FORMAT(data, ''%d/%m/%Y'') AS Data, descrizione AS Descrizione FROM my_impianti WHERE idanagrafica=|idanagrafica| HAVING 2=2 ORDER BY id DESC"}	]}', `directory` = '', `version` = '2.3', `compatibility` = '2.*' WHERE `name` = 'Impianti del cliente';

-- Aggiunta del supporto alla sincronizzazione interventi
ALTER TABLE `in_interventi_tecnici` ADD `uid` VARCHAR(255) NOT NULL AFTER `prezzo_dirittochiamata_tecnico`, ADD `summary` VARCHAR(255) NOT NULL AFTER `uid`;
ALTER TABLE `in_interventi` ADD `deleted` TINYINT NOT NULL DEFAULT '0' AFTER `data_invio`;

-- Fix nella conversione dei listini precedenti
UPDATE `mg_listini` SET `prc_guadagno` = - `prc_guadagno`;

-- Aggiunta pagamento di default "Bonifico bancario"
INSERT INTO `co_pagamenti` (`id`, `descrizione`, `giorno`, `num_giorni`, `prc`, `idconto_vendite`, `idconto_acquisti`) VALUES (NULL, 'Bonifico bancario', '0', '10', '100', NULL, NULL);

-- Per Dashboard e Articoli i widgets vanno in alto
UPDATE `zz_widgets` SET `location` = 'controller_top' WHERE `zz_widgets`.`id_module` = (SELECT id FROM zz_modules WHERE name = 'Dashboard') OR `zz_widgets`.`id_module` = (SELECT id FROM zz_modules WHERE name = 'Articoli');

-- Disabilito widgets 'Ordini di servizio da impostare' e 'Rate contrattuali'
UPDATE `zz_widgets` SET `enabled` = '0' WHERE `zz_widgets`.`name` = 'Ordini di servizio da impostare';

-- Aggiunta articolo su contratti
ALTER TABLE `co_righe2_contratti` ADD `idarticolo` INT NOT NULL AFTER `idcontratto`;

-- Campo per identificare le righe descrittive documenti/ddt/preventivi/contratti/ordini
ALTER TABLE `co_righe_documenti` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idcontratto`;
ALTER TABLE `dt_righe_ddt` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idarticolo`;
ALTER TABLE `co_righe_preventivi` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idarticolo`;
ALTER TABLE `co_righe2_contratti` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idarticolo`;
ALTER TABLE `or_righe_ordini` ADD `is_descrizione` TINYINT(1) NOT NULL AFTER `idarticolo`;

-- Aggiunta flag "servizio" su articolo
ALTER TABLE `mg_articoli` ADD `servizio` TINYINT(1) NOT NULL AFTER `id_sottocategoria`;

-- Aggiunto lo stato ddt "Parzialmente fatturato" e cambiato lo stato "Pagato" in "Fatturato"
UPDATE `dt_statiddt` SET `descrizione` = 'Fatturato' WHERE `dt_statiddt`.`descrizione` = 'Pagato';
INSERT INTO `dt_statiddt` (`id`, `descrizione`, `icona`) VALUES (NULL, 'Parzialmente fatturato', 'fa fa-clock-o text-warning');
