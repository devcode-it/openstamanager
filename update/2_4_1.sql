ALTER TABLE `co_contratti` ADD `idsede` INT NOT NULL AFTER `idanagrafica`;

-- Imposto conto cassa per contanti e rimesse
UPDATE `co_pagamenti` SET `idconto_vendite` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Cassa'), `idconto_acquisti` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Cassa')  WHERE `co_pagamenti`.`descrizione` = 'Contanti' OR `co_pagamenti`.`descrizione` LIKE 'Rimessa %';

-- Imposto conto banca per tutti i bonifici e ri.ba.
UPDATE `co_pagamenti` SET `idconto_vendite` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C'), `idconto_acquisti` = (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C')  WHERE `co_pagamenti`.`descrizione` LIKE 'Bonifico %' OR `co_pagamenti`.`descrizione` LIKE 'Ri.Ba. %';

-- Indirizzo PEC
ALTER TABLE `an_anagrafiche` ADD `pec` VARCHAR(255) NOT NULL AFTER `email`;

-- ISO 3166-1 alpha-2 code per nazioni
ALTER TABLE `an_nazioni` ADD `iso2` VARCHAR(2) NOT NULL AFTER `nome`;

-- ISO 2 per ITALIA (https://it.wikipedia.org/wiki/ISO_3166-1_alpha-2)
UPDATE `an_nazioni` SET `iso2` = 'IT' WHERE `an_nazioni`.`nome` = 'ITALIA';

-- Aggiunto name per i filtri
ALTER TABLE `zz_group_module` ADD `name` VARCHAR(255) NOT NULL AFTER `idmodule`;

UPDATE `zz_group_module` SET `name` = 'Mostra interventi ai tecnici coinvolti' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Tecnici') AND `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');
UPDATE `zz_group_module` SET `name` = 'Mostra interventi ai clienti coinvolti' WHERE `zz_group_module`.`idgruppo` = (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti') AND `zz_group_module`.`idmodule` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi');

-- Abilito plugin Pianificazione fatturazione in contratti
UPDATE `zz_plugins` SET `enabled` = '1' WHERE `zz_plugins`.`name` = 'Pianificazione fatturazione';

-- Abilito widget Rate contrattuali in dashboard
UPDATE `zz_widgets` SET `enabled` = '1' WHERE `zz_widgets`.`name` = 'Rate contrattuali';

-- Help text per i plugins
ALTER TABLE `zz_plugins` ADD `help` VARCHAR(255) NOT NULL AFTER `directory`;

-- Help text per plugin Ddt del cliente
UPDATE `zz_plugins` SET `help` = 'Righe ddt del cliente. I ddt senza righe non saranno visualizzati.' WHERE `zz_plugins`.`name` = 'Ddt del cliente';

-- Creazione tabella per modelli primanota
CREATE TABLE IF NOT EXISTS `co_movimenti_modelli` (
  `id` int(11) NOT NULL,
  `idmastrino` int(11) NOT NULL,
  `descrizione` text NOT NULL,
  `idconto` int(11) NOT NULL
);

ALTER TABLE `co_movimenti_modelli` ADD PRIMARY KEY (`id`);

ALTER TABLE `co_movimenti_modelli` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- Modulo modelli prima nota
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Modelli prima nota', 'Modelli prima nota', 'modelli_primanota', 'SELECT |select| FROM `co_movimenti_modelli` WHERE 1=1 GROUP BY `idmastrino` HAVING 2=2', '', 'fa fa-angle-right', '2.4.1', '2.4.1', '1', NULL, '1', '1');

UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Modelli prima nota' AND `t2`.`name` = 'Tabelle') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `enabled`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name='Modelli prima nota'), 'id', 'co_movimenti_modelli.id', '0', '1', '0', '0', NULL, NULL, '0', '0', '1');
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `enabled`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name='Modelli prima nota'), 'Causale predefinita', 'co_movimenti_modelli.descrizione', '1', '1', '0', '0', NULL, NULL, '1', '0', '1');

-- Widget per stampa calendario
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help` ) VALUES (NULL, 'Stampa calendario', 'print', '1', 'controller_top', 'col-md-12', NULL, '#4ccc4c', 'fa fa-print', '', './modules/dashboard/widgets/stampa_calendario.dashboard.php', 'popup', '', 'Stampa calendario', '1', '7', NULL);

-- Stampa calendario
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `main`, `default`, `enabled`) VALUES (NULL, '1', '1', 'Stampa calendario', 'Stampa calendario', 'dashboard', '', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- impianti per pianificazione contratti
ALTER TABLE `co_righe_contratti` ADD `idimpianti` VARCHAR(255) NOT NULL AFTER `idsede`;

-- Struttura della tabella `co_righe_contratti_materiali`
CREATE TABLE IF NOT EXISTS `co_righe_contratti_materiali` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `descrizione` varchar(255) NOT NULL,
  `qta` float(12,4) NOT NULL,
  `um` varchar(25) NOT NULL,
  `prezzo_vendita` decimal(12,4) NOT NULL,
  `prezzo_acquisto` decimal(12,4) NOT NULL,
  `idiva` int(11) NOT NULL,
  `desc_iva` varchar(255) NOT NULL,
  `iva` decimal(12,4) NOT NULL,
  `id_riga_contratto` int(11) DEFAULT NULL,
  `sconto` decimal(12,4) NOT NULL,
  `sconto_unitario` decimal(12,4) NOT NULL,
  `tipo_sconto` enum('UNT','PRC') NOT NULL DEFAULT 'UNT',
  PRIMARY KEY (`id`),
  KEY `id_riga_contratto` (`id_riga_contratto`)
);

-- Struttura della tabella `co_righe_contratti_articoli`
CREATE TABLE IF NOT EXISTS `co_righe_contratti_articoli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idarticolo` int(11) NOT NULL,
  `id_riga_contratto` int(11) DEFAULT NULL,
  `descrizione` varchar(255) NOT NULL,
  `prezzo_acquisto` decimal(12,4) NOT NULL,
  `prezzo_vendita` decimal(12,4) NOT NULL,
  `sconto` decimal(12,4) NOT NULL,
  `sconto_unitario` decimal(12,4) NOT NULL,
  `tipo_sconto` enum('UNT','PRC') NOT NULL DEFAULT 'UNT',
  `idiva` int(11) NOT NULL,
  `desc_iva` varchar(255) NOT NULL,
  `iva` decimal(12,4) NOT NULL,
  `idautomezzo` int(11) NOT NULL,
  `qta` decimal(10,2) NOT NULL,
  `um` varchar(20) NOT NULL,
  `abilita_serial` tinyint(1) NOT NULL DEFAULT '0',
  `idimpianto` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_riga_contratto` (`id_riga_contratto`),
  KEY `idimpianto` (`idimpianto`)
);

-- Aggiunto campo data su movimenti articoli
ALTER TABLE `mg_movimenti` ADD `data` DATE NOT NULL AFTER `movimento`;

-- Campo per identificare i movimenti manuali
ALTER TABLE `mg_movimenti` ADD `manuale` TINYINT(1) NOT NULL AFTER `data`;

-- Aggiunta possibilità di selezionare anche i conti in 620 Costi diversi negli acquisti
UPDATE `co_pianodeiconti2` SET `dir` = 'uscita' WHERE `co_pianodeiconti2`.`descrizione` = 'Costi diversi';

-- Supporto al valore NULL per uid e summary in in_interventi_tecnici
ALTER TABLE `in_interventi_tecnici` CHANGE `uid` `uid` VARCHAR(255), CHANGE `summary` `summary` VARCHAR(255);
UPDATE `in_interventi_tecnici` SET `uid` = NULL WHERE `uid` = '';
UPDATE `in_interventi_tecnici` SET `summary` = NULL WHERE `summary` = '';
ALTER TABLE `in_interventi_tecnici` CHANGE `uid` `uid` int(11);

-- Aggiorno campo 'Data' in 'Data movimento'
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`name` = 'Data movimento', `zz_views`.`order` = 6 WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = 'Data';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'CONCAT(mg_movimenti.qta,'' '', (SELECT um FROM mg_articoli WHERE id = mg_movimenti.idarticolo) )' WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = 'Quantità';
-- Allineo anche il modulo movimenti con il nuovo campo data
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'Data', 'mg_movimenti.data', 5, 1, 0, 1, 1, 1);

-- Aggiungo colonna impianti per i contratti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Impianti', '(SELECT IF(nome = '''', GROUP_CONCAT(matricola SEPARATOR ''<br>''), GROUP_CONCAT(matricola, '' - '', nome SEPARATOR ''<br>'')) FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto WHERE my_impianti_contratti.idcontratto = co_contratti.id)', 4, 1, 0, 0, 0, 1);

-- Tempo standard per attività
ALTER TABLE `in_tipiintervento` ADD `tempo_standard` DECIMAL(10,2)  NULL AFTER `costo_diritto_chiamata_tecnico`;

-- Rinomino Interventi da pianificare in Promemoria contratti da pianificare
UPDATE `zz_widgets` SET `text` = 'Promemoria contratti da pianificare' WHERE `zz_widgets`.`name` = 'Interventi da pianificare';

-- Aggiunta impostazioni per cambio stato automatici
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Cambia automaticamente stato ddt fatturati', '1', 'boolean', '1', 'Ddt');
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Cambia automaticamente stato ordini fatturati', '1', 'boolean', '1', 'Ordini');

-- Flag completato per disabilitare la modifica dei campi nei ddt
ALTER TABLE `dt_statiddt` ADD `completato` TINYINT(1) NOT NULL AFTER `icona`;
UPDATE `dt_statiddt` SET `completato` = '1' WHERE `dt_statiddt`.`descrizione` IN( 'Fatturato', 'Parzialmente fatturato', 'Evaso' );

-- Aggiungo stato parzialmente evaso
INSERT INTO `dt_statiddt` (`id`, `descrizione`, `icona`, `completato`) VALUES (NULL, 'Parzialmente evaso', 'fa fa-clock-o text-info', '1');

-- Modifico le icone degli stati ddt
UPDATE `dt_statiddt` SET `icona` = 'fa fa-truck text-success' WHERE `dt_statiddt`.`descrizione` = 'Evaso';
UPDATE `dt_statiddt` SET `icona` = 'fa fa-truck text-warning' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente evaso';
UPDATE `dt_statiddt` SET `icona` = 'fa fa-file-text-o text-success' WHERE `dt_statiddt`.`descrizione` = 'Fatturato';
UPDATE `dt_statiddt` SET `icona` = 'fa fa-file-text-o text-warning' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente fatturato';

-- Modifico gli stati dell'ordine
UPDATE `or_statiordine` SET `descrizione` = 'Bozza' WHERE `or_statiordine`.`descrizione` = 'Non evaso';

-- Modifico le icone degli stati ordini
UPDATE `or_statiordine` SET `icona` = 'fa fa-truck text-success' WHERE `or_statiordine`.`descrizione` = 'Evaso';

-- Inserisco due nuovi stati ordine
INSERT INTO `or_statiordine` (`id`, `descrizione`, `annullato`, `icona`) VALUES
(NULL, 'Parzialmente fatturato', 0, 'fa fa-file-text-o text-warning'),
(NULL, 'Fatturato', 0, 'fa fa-file-text-o text-success');

-- Flag completato per disabilitare la modifica dei campi negli ordini
ALTER TABLE `or_statiordine` ADD `completato` TINYINT(1) NOT NULL AFTER `icona`;
UPDATE `or_statiordine` SET `completato` = '1' WHERE `or_statiordine`.`id` IN( 2, 3, 4, 5 );

-- Campi per note aggiuntive in ddt e ordini
ALTER TABLE `or_ordini` ADD `note_aggiuntive` TEXT NOT NULL AFTER `note`;
ALTER TABLE `dt_ddt` ADD `note_aggiuntive` TEXT NOT NULL AFTER `note`;

-- Fix id_plugin (zz_files)
UPDATE `zz_files` SET `id_plugin` = NULL WHERE `id_plugin` = 0;

-- Fix id_module (zz_files)
ALTER TABLE `zz_files` CHANGE `id_module` `id_module` INT(11) NULL;
UPDATE `zz_files` SET `id_module` = NULL WHERE `id_module` = 0;

-- Totali fatture, sommabile
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`summable` = 1 WHERE `zz_modules`.`name` IN ('Fatture di acquisto', 'Fatture di vendita') AND `zz_views`.`name` = 'Totale';
-- Fix serial, lotti, altro a 0 o null
DELETE FROM `mg_prodotti` WHERE (`serial` IS NULL OR `serial`='0') AND (`lotto` IS NULL OR `lotto`='0') AND (`altro` IS NULL OR `altro`='0');

UPDATE `mg_articoli` SET `abilita_serial` = 1 WHERE `id` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `co_righe_documenti` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `dt_righe_ddt` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `mg_articoli_interventi` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `or_righe_ordini` SET `abilita_serial` = 1 WHERE `idarticolo` IN (SELECT `id_articolo` FROM `mg_prodotti`);

UPDATE `mg_articoli` SET `abilita_serial` = 0 WHERE `id` NOT IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `co_righe_documenti` SET `abilita_serial` = 0 WHERE `idarticolo` NOT IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `dt_righe_ddt` SET `abilita_serial` = 0 WHERE `idarticolo` NOT IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `mg_articoli_interventi` SET `abilita_serial` = 0 WHERE `idarticolo` NOT IN (SELECT `id_articolo` FROM `mg_prodotti`);
UPDATE `or_righe_ordini` SET `abilita_serial` = 0 WHERE `idarticolo` NOT IN (SELECT `id_articolo` FROM `mg_prodotti`);

-- Aggiungo colonna idanagrafica per i preventivi (colonna necessaria per i permessi)
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'idanagrafica', 'co_preventivi.idanagrafica', 0, 0, 0, 0, 0, 1);

-- Fix name, title e order stampa ordine fornitore senza costi
UPDATE `zz_prints` SET `name` = 'Ordine fornitore (senza costi)', `title` = 'Ordine fornitore (senza costi)', `order` = 1 WHERE `zz_prints`.`name` = 'Ordine fornitore' AND  options = '{"pricing":false}' AND `zz_prints`.`id_module` =  (SELECT id FROM zz_modules WHERE name='Ordini fornitore') ;
-- Stampa ordine fornitore con costi costi
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `main`, `default`, `enabled`) VALUES (NULL, (SELECT id FROM zz_modules WHERE name='Ordini fornitore'), '1', 'Ordine fornitore', 'Ordine fornitore', 'ordini', 'idordine', '{"pricing":true}', 'fa fa-print', '', '', '0', '1', '1', '1');
-- Default invio ordini al fornitore con i prezzi
UPDATE `zz_email_print` SET `id_print` = (SELECT id FROM zz_prints WHERE name = 'Ordine fornitore') WHERE `zz_email_print`.`id_email` = (SELECT id FROM zz_emails WHERE id_module =  (SELECT id FROM zz_modules WHERE name='Ordini fornitore'));

-- Ordino stampa ordine cliente senza costi
UPDATE `zz_prints` SET `order` = 1 WHERE `zz_prints`.`name` = 'Ordine cliente (senza costi)' AND  options = '{"pricing":false}' AND `zz_prints`.`id_module` =  (SELECT id FROM zz_modules WHERE name='Ordini cliente');

-- Rimuovo impostazioni non più utilizzate
DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sugli ordini';
DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sui contratti';
DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sui ddt';
DELETE FROM `zz_settings` WHERE `nome` = 'Visualizza i costi sulle stampe degli interventi';
DELETE FROM `zz_settings` WHERE `nome` = 'Stampa i prezzi sui preventivi';

-- Lo stato 'In programmazione' non può essere eliminato/modificato
UPDATE `in_statiintervento` SET `can_delete` = '0' WHERE `in_statiintervento`.`idstatointervento` = 'WIP';

-- Campi Importo e Pagato dello Scadenzario sommabili
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`summable` = 1 WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` IN ('Importo', 'Pagato');
-- Collego il preventivo alla riga dell'ordine
ALTER TABLE `or_righe_ordini` ADD `idpreventivo` INT(11) NOT NULL AFTER `idarticolo`;

-- Fix foreign keys (2.4)
ALTER TABLE `zz_emails`
ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
ADD FOREIGN KEY (`id_smtp`) REFERENCES `zz_smtp`(`id`) ON DELETE CASCADE;

ALTER TABLE `zz_email_print`
ADD FOREIGN KEY (`id_email`) REFERENCES `zz_emails`(`id`) ON DELETE CASCADE,
ADD FOREIGN KEY (`id_print`) REFERENCES `zz_prints`(`id`) ON DELETE CASCADE;

ALTER TABLE `zz_fields`
ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE,
ADD FOREIGN KEY (`id_plugin`) REFERENCES `zz_plugins`(`id`) ON DELETE CASCADE;

ALTER TABLE `zz_field_record`
ADD FOREIGN KEY (`id_field`) REFERENCES `zz_fields`(`id`) ON DELETE CASCADE;

ALTER TABLE `zz_prints`
ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

-- Widget per attività senza nessun tecnico assegnato
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES (NULL, 'Attività da pianificare', 'stats', (SELECT id FROM zz_modules WHERE name = 'Dashboard'), 'controller_top', 'col-md-3', 'SELECT COUNT(id) AS dato FROM in_interventi WHERE id NOT IN (SELECT idintervento FROM in_interventi_tecnici) AND idstatointervento IN (SELECT idstatointervento FROM in_statiintervento WHERE completato = 0) ', '#6dab3c', 'fa fa-cogs', '', './modules/interventi/widgets/interventi.pianificazionedashboard.interventi.php', 'popup', '', 'Promemoria attività da pianificare', 1, '0', NULL);

-- Impostazione "Tempo di attesa ricerche"
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Tempo di attesa ricerche in secondi', '2', 'integer', '0', 'Generali');

-- Rimozione IVA duplicata
DELETE FROM `co_iva` WHERE `id` = 31;
UPDATE `an_anagrafiche` SET `idiva_acquisti` = 75 WHERE `idiva_acquisti` = 31;
UPDATE `an_anagrafiche` SET `idiva_vendite` = 75 WHERE `idiva_vendite` = 31;
UPDATE `co_preventivi` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `co_righe2_contratti` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `co_righe_contratti_articoli` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `co_righe_contratti_materiali` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `co_righe_documenti` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `co_righe_documenti` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `dt_ddt` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `dt_righe_ddt` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `in_righe_interventi` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `mg_articoli` SET `idiva_vendita` = 75 WHERE `idiva_vendita` = 31;
UPDATE `mg_articoli_interventi` SET `idiva` = 75 WHERE `idiva` = 31;
UPDATE `or_righe_ordini` SET `idiva` = 75 WHERE `idiva` = 31;

-- Rimozione idtipointervento da co_contratti
ALTER TABLE `co_contratti` DROP `idtipointervento`;

-- Ridenominazione tabelle
ALTER TABLE `co_righe_contratti` RENAME `co_contratti_promemoria`;
ALTER TABLE `co_righe2_contratti` RENAME `co_righe_contratti`;

-- Ordine per le Impostazioni
ALTER TABLE `zz_settings` ADD `order` int(11);
UPDATE `zz_settings` SET `order` = 1 WHERE `nome` = 'Azienda predefinita';
UPDATE `zz_settings` SET `order` = 2 WHERE `nome` = 'Nascondere la barra sinistra di default';
UPDATE `zz_settings` SET `order` = 3 WHERE `nome` = 'Backup automatico';
UPDATE `zz_settings` SET `order` = 4 WHERE `nome` = 'Numero di backup da mantenere';
UPDATE `zz_settings` SET `order` = 5 WHERE `nome` = 'Vista dashboard';
UPDATE `zz_settings` SET `order` = 6 WHERE `nome` = 'Utilizzare i tooltip sul calendario';
UPDATE `zz_settings` SET `order` = 7 WHERE `nome` = 'Visualizzare la domenica sul calendario';
UPDATE `zz_settings` SET `order` = 8 WHERE `nome` = 'Abilitare orario lavorativo';
UPDATE `zz_settings` SET `order` = 9 WHERE `nome` = 'Cifre decimali per importi';
UPDATE `zz_settings` SET `order` = 10 WHERE `nome` = 'Cifre decimali per quantità';
UPDATE `zz_settings` SET `order` = 11 WHERE `nome` = 'Prima pagina';
UPDATE `zz_settings` SET `order` = 12 WHERE `nome` = 'Google Maps API key';
UPDATE `zz_settings` SET `order` = 13 WHERE `nome` = 'Attiva notifica di presenza utenti sul record';
UPDATE `zz_settings` SET `order` = 14 WHERE `nome` = 'Timeout notifica di presenza (minuti)';
UPDATE `zz_settings` SET `order` = 15 WHERE `nome` = 'apilayer API key for Email';
UPDATE `zz_settings` SET `order` = 16 WHERE `nome` = 'apilayer API key for VAT number';
UPDATE `zz_settings` SET `order` = 17 WHERE `nome` = 'CSS Personalizzato';

-- Fix tipo del campo order
ALTER TABLE `co_righe_preventivi` CHANGE `order` `order` int(11) NOT NULL;
ALTER TABLE `dt_righe_ddt` CHANGE `order` `order` int(11) NOT NULL;
ALTER TABLE `or_righe_ordini` CHANGE `order` `order` int(11) NOT NULL;
ALTER TABLE `co_righe_contratti` CHANGE `order` `order` int(11) NOT NULL;

-- Impostazione "Logo stampe"
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Logo stampe', '', 'string', '0', 'Generali');

-- Categorie zz_files
ALTER TABLE `zz_files` ADD `category` varchar(100) AFTER `original`;

-- Impostazione "Abilita esportazione Excel e PDF"
INSERT INTO `zz_settings` (`idimpostazione`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Abilita esportazione Excel e PDF', '0', 'boolean', '1', 'Generali', 18);

-- Rimozione idtipoanagrafica da zz_users
ALTER TABLE `zz_users` DROP `idtipoanagrafica`;

-- Introduzione controllo duplicazione della numerazione Fatture di vendita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), '_bg_', 'IF((SELECT COUNT(t.numero_esterno) FROM co_documenti AS t WHERE t.numero_esterno = co_documenti.numero_esterno AND t.id_segment = co_documenti.id_segment  AND idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir = ''entrata'') AND t.data >= ''|period_start|'' AND t.data <= ''|period_end|'') > 1, ''red'', '''')', 0, 0, 0, 0, 1);

-- Aggiunto supporto a Note di accredito e addebito
ALTER TABLE `co_documenti` ADD `ref_documento` int(11) AFTER `idagente`, ADD FOREIGN KEY (`ref_documento`) REFERENCES `co_documenti`(`id`) ON DELETE CASCADE;
ALTER TABLE `co_righe_documenti` ADD `qta_evasa` decimal(12,4) NOT NULL AFTER `qta`, ADD `ref_riga_documento` int(11) AFTER `idcontratto`, ADD FOREIGN KEY (`ref_riga_documento`) REFERENCES `co_righe_documenti`(`id`) ON DELETE CASCADE;

ALTER TABLE `co_tipidocumento` ADD `reversed` BOOLEAN NOT NULL DEFAULT FALSE AFTER `dir`;
UPDATE `co_tipidocumento` SET `reversed` = 1 WHERE `descrizione` = 'Nota di accredito';

-- Fix id_sottocategoria in mg_articoli
ALTER TABLE `mg_articoli` CHANGE `id_sottocategoria` `id_sottocategoria` int(11);

-- Immagini articoli come allegati
ALTER TABLE `mg_articoli` CHANGE `immagine01` `immagine` varchar(255);
UPDATE `mg_articoli` SET `immagine` = NULL WHERE `immagine` = '';
INSERT INTO `zz_files` (`id_module`, `id_record`, `nome`, `filename`, `original`) SELECT (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), `id`, 'Immagine', `immagine`, `immagine` FROM `mg_articoli` WHERE `immagine` IS NOT NULL;

-- Immagini impianto come allegati
ALTER TABLE `my_impianti` CHANGE `immagine` `immagine` varchar(255);
UPDATE `my_impianti` SET `immagine` = NULL WHERE `immagine` = '';
INSERT INTO `zz_files` (`id_module`, `id_record`, `nome`, `filename`, `original`) SELECT (SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), `id`, 'Immagine', `immagine`, `immagine` FROM `my_impianti` WHERE `immagine` IS NOT NULL;


-- Introduzione del tipo documento nelle tabelle Fatture
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Tipo', 'co_tipidocumento.descrizione', 4, 1, 0, 1, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `enabled`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto'), 'Tipo',
'co_tipidocumento.descrizione', 4, 1, 0, 1, 1);

-- Aggiunta di alcuni filtri di base
INSERT INTO `zz_group_module` (`id`, `idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES
(NULL, (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), 'Mostra ddt di vendita ai clienti coinvolti', 'dt_ddt.idanagrafica=|idanagrafica|', 'WHR', '0', '1'),
(NULL, (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), 'Mostra ordini cliente ai clienti coinvolti', 'or_ordini.idanagrafica=|idanagrafica|', 'WHR', '0', '1'),
(NULL, (SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Mostra fatture di vendita ai clienti coinvolti', 'co_documenti.idanagrafica=|idanagrafica|', 'WHR', '0', '1');

-- Sostituzione deleted con deleted_at
ALTER TABLE `co_banche` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `co_banche` SET `deleted_at` = NOW() WHERE `deleted` = 1;
ALTER TABLE `co_banche` DROP `deleted`;

ALTER TABLE `an_anagrafiche` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `an_anagrafiche` SET `deleted_at` = NOW() WHERE `deleted` = 1;
ALTER TABLE `an_anagrafiche` DROP `deleted`;

ALTER TABLE `in_statiintervento` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `in_statiintervento` SET `deleted_at` = NOW() WHERE `deleted` = 1;
ALTER TABLE `in_statiintervento` DROP `deleted`;

ALTER TABLE `zz_emails` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `zz_emails` SET `deleted_at` = NOW() WHERE `deleted` = 1;
ALTER TABLE `zz_emails` DROP `deleted`;

ALTER TABLE `zz_smtp` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `zz_smtp` SET `deleted_at` = NOW() WHERE `deleted` = 1;
ALTER TABLE `zz_smtp` DROP `deleted`;

ALTER TABLE `in_interventi` ADD `deleted_at` timestamp NULL DEFAULT NULL;
UPDATE `in_interventi` SET `deleted_at` = NOW() WHERE `deleted` = 1;
ALTER TABLE `in_interventi` DROP `deleted`;

UPDATE `zz_widgets` SET `query` = REPLACE(
    REPLACE(
        REPLACE(`query`, 'deleted=0', '`deleted_at` IS NULL')
    , 'deleted = 0', '`deleted_at` IS NULL')
, '`deleted` = 0', '`deleted_at` IS NULL');
UPDATE `zz_group_module` SET `clause` = REPLACE(
    REPLACE(
        REPLACE(`clause`, 'deleted=0', '`deleted_at` IS NULL')
    , 'deleted = 0', '`deleted_at` IS NULL')
, '`deleted` = 0', '`deleted_at` IS NULL');
UPDATE `zz_settings` SET `tipo` = REPLACE(
    REPLACE(
        REPLACE(`tipo`, 'deleted=0', '`deleted_at` IS NULL')
    , 'deleted = 0', '`deleted_at` IS NULL')
, '`deleted` = 0', '`deleted_at` IS NULL');

UPDATE `zz_widgets` SET `query` = REPLACE(
    REPLACE(
        REPLACE(`query`, 'deleted=1', '`deleted_at` IS NOT NULL')
    , 'deleted = 1', '`deleted_at` IS NOT NULL')
, '`deleted` = 1', '`deleted_at` IS NOT NULL');
UPDATE `zz_group_module` SET `clause` = REPLACE(
    REPLACE(
        REPLACE(`clause`, 'deleted=1', '`deleted_at` IS NOT NULL')
    , 'deleted = 1', '`deleted_at` IS NOT NULL')
, '`deleted` = 1', '`deleted_at` IS NOT NULL');
UPDATE `zz_settings` SET `tipo` = REPLACE(
    REPLACE(
        REPLACE(`tipo`, 'deleted=1', '`deleted_at` IS NOT NULL')
    , 'deleted = 1', '`deleted_at` IS NOT NULL')
, '`deleted` = 1', '`deleted_at` IS NOT NULL');

-- Fix id delle Banche
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`enabled` = 0 WHERE `zz_modules`.`name` = 'Banche' AND `zz_views`.`name` = 'id';
-- Aggiunta campi per specificare se la riga importata è un import unico di pù righe
ALTER TABLE `co_righe_documenti` ADD `is_preventivo` TINYINT(1) NOT NULL AFTER `is_descrizione`, ADD `is_contratto` TINYINT(1) NOT NULL AFTER `is_preventivo`;

-- Aggiunta colonna 'Sede' per MyImpianti
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `enabled`, `summable`, `default` ) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'Sede', 'IF(my_impianti.idsede = 0, \'Sede legale\', (SELECT CONCAT_WS( '' - '', nomesede, citta ) AS descrizione FROM an_sedi WHERE id = my_impianti.idsede))', 4, 1, 0, 0, '', '', 0, 0, 0);

-- Aggiunta colonna 'Tempo standard' per Tipi di intervento
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `enabled`, `summable`, `default` ) VALUES
(NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di intervento'), 'Tempo standard', 'in_tipiintervento.tempo_standard', 10, 1, 0, 1, '', '', 0, 0, 0);

-- Disabilito temporaneamente le stampe degli ordini di servizio, plugins disabilitati
UPDATE `zz_prints` SET `enabled` = 0 WHERE `name` IN( 'Ordine di servizio', 'Ordine di servizio (senza costi)' );

-- Fix colonna delle stampe
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '\'Intervento\'' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = '_print_';
-- Flag per definire i segmenti di note di accredito e di addebito
ALTER TABLE `zz_segments` ADD `predefined_accredito` TINYINT(1) NOT NULL AFTER `predefined`, ADD `predefined_addebito` TINYINT(1) NOT NULL AFTER `predefined_accredito`;

-- Fattura di vendita senza intestazione (per carta intestata)
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `main`, `default`, `enabled`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE name='Fatture di vendita'), 1, 'Fattura di vendita (senza intestazione)', 'Fattura di vendita (senza intestazione)', 'fatture', 'iddocumento', '{"hide_header":true, "hide_footer":true}', 'fa fa-print', '', '', 0, 0, 1, 1);
UPDATE `zz_prints` SET `main` = '1' WHERE `name` = 'Fattura di vendita';

-- Innesto modulo per campi personalizzati
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Campi personalizzati', 'Campi personalizzati', 'custom_fields', 'SELECT |select| FROM `zz_fields` WHERE 1=1 HAVING 2=2', '', 'fa fa-list', '2.4.1', '2.4.1', '1', NULL, '1', '0');
UPDATE `zz_modules` `t1` INNER JOIN `zz_modules` `t2` ON (`t1`.`name` = 'Campi personalizzati' AND `t2`.`name` = 'Strumenti') SET `t1`.`parent` = `t2`.`id`;

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `enabled`, `summable`, `default`) VALUES
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Campi personalizzati'), 'id', 'id', 0, 0, 0, 0, 0, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Campi personalizzati'), 'Modulo', '(SELECT name FROM zz_modules WHERE zz_modules.id = zz_fields.id_module)', 0, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Campi personalizzati'), 'Plugin', '(SELECT name FROM zz_plugins WHERE zz_plugins.id = zz_fields.id_plugin)', 0, 1, 0, 0, 1, 0, 1),
(NULL,  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Campi personalizzati'), 'Nome', 'name', 0, 1, 0, 0, 1, 0, 1);
