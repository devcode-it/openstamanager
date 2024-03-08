-- Miglioramento hooks
ALTER TABLE `zz_hooks` ADD `enabled` boolean NOT NULL DEFAULT 1, ADD `id_module` int(11) NOT NULL;
UPDATE `zz_hooks` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') WHERE `name` = 'Ricevute';
UPDATE `zz_hooks` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') WHERE `name` = 'Fatture';
ALTER TABLE `zz_hooks` ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES
(NULL, 'Aggiornamenti', 'Modules\\Aggiornamenti\\UpdateHook', '7 day', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Aggiornamenti'));

--
-- Aggiunta nuovi campi per tracciamento sedi
--
ALTER TABLE `mg_movimenti` ADD `idsede_azienda` INT NOT NULL AFTER `idautomezzo`, ADD `idsede_controparte` INT NOT NULL AFTER `idsede_azienda`;

--
-- Creazione plugin Giacenze
--
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Giacenze', 'Giacenze', (SELECT id FROM zz_modules WHERE name='Articoli'), (SELECT id FROM zz_modules WHERE name='Articoli'), 'tab', 'articoli.giacenze.php', '1', '0', '0', '', '', NULL, NULL, '', '');

-- Aggiornamento nomi dei campi sede per ddt
ALTER TABLE `dt_ddt` CHANGE `idsede` `idsede_partenza` INT NOT NULL;
ALTER TABLE `dt_ddt` ADD `idsede_destinazione` INT NOT NULL AFTER `idsede_partenza`;

-- Aggiornamento idsede_destinazione per i ddt di vendita
UPDATE `dt_ddt` SET `idsede_destinazione`=`idsede_partenza` WHERE `idtipoddt` IN (SELECT id FROM dt_tipiddt WHERE dir="entrata");
UPDATE `dt_ddt` SET `idsede_partenza`=0 WHERE `idtipoddt` IN (SELECT id FROM dt_tipiddt WHERE dir="entrata");
UPDATE `dt_ddt` SET `idsede_destinazione`=0 WHERE `idtipoddt` IN (SELECT id FROM dt_tipiddt WHERE dir="uscita");

-- Aggiornamento nomi dei campi sede per co_documenti
ALTER TABLE `co_documenti` ADD `idsede_destinazione` INT NOT NULL AFTER `idsede`;
ALTER TABLE `co_documenti` CHANGE `idsede` `idsede_partenza` INT(11) NOT NULL;

-- Aggiornamento idsede su co_documenti come ddt
UPDATE `co_documenti` SET `idsede_destinazione`=`idsede_partenza` WHERE `idtipodocumento` IN (SELECT id FROM co_tipidocumento WHERE dir="entrata");
UPDATE `co_documenti` SET `idsede_partenza`=0 WHERE `idtipodocumento` IN (SELECT id FROM co_tipidocumento WHERE dir="entrata");
UPDATE `co_documenti` SET `idsede_destinazione`=0 WHERE `idtipodocumento` IN (SELECT id FROM co_tipidocumento WHERE dir="uscita");

-- Rinomino idsede in in_interventi in idsede_destinazione
ALTER TABLE `in_interventi` CHANGE `idsede` `idsede_destinazione` INT(11) NOT NULL;

-- Creazione idsede_partenza in_interventi
ALTER TABLE `in_interventi` ADD `idsede_partenza` INT NOT NULL AFTER `prezzo_ore_unitario`;

-- Aggiunta sede destinazione ddt uscita
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ('0', (SELECT id FROM zz_modules WHERE name='Ddt di vendita'), 'Sede destinazione', 'IF(`dt_ddt`.`idsede_destinazione`=0, \'Sede legale\',CONCAT_WS(\' - \', sedi_destinazione.nomesede,sedi_destinazione.citta))', '5', '1', '0', '0', '', '', '1', '0', '0');

-- Aggiunta sede destinazione ddt in entrata
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ('0', (SELECT id FROM zz_modules WHERE name='Ddt di acquisto'), 'Sede destinazione', 'IF(`dt_ddt`.`idsede_destinazione`=0, \'Sede legale\',CONCAT_WS(\' - \', sedi_destinazione.nomesede,sedi_destinazione.citta))', '5', '1', '0', '1', '', '', '1', '0', '0');

-- Update sede -> sede partenza ddt in uscita
UPDATE zz_views SET query='IF(dt_ddt.idsede_partenza=0, "Sede legale",CONCAT_WS(" - ", sedi.nomesede,sedi.citta))', name='Sede partenza' WHERE id_module=(SELECT id FROM zz_modules WHERE name="Ddt di vendita") AND name= "sede";

-- Update sede -> sede partenza ddt in entrata
UPDATE zz_views SET query='IF(`dt_ddt`.`idsede_partenza`=0, "Sede legale",CONCAT_WS(" - ", sedi.nomesede,sedi.citta))', name="Sede partenza" WHERE id_module=(SELECT id FROM zz_modules WHERE name="Ddt di acquisto") AND name= "sede";

-- Aggiornamento idsede_destinazione sui movimenti degli articoli inseriti sulle attività
UPDATE `mg_movimenti` SET `idsede_controparte` = (SELECT `idsede_destinazione` FROM `in_interventi` WHERE `in_interventi`.`id` = `mg_movimenti`.`idintervento`) WHERE `idintervento` IS NOT NULL;

-- Aggiorno idsede_azienda e controparte per ddt
UPDATE `mg_movimenti` SET `idsede_controparte` = (SELECT `idsede_destinazione` FROM `dt_ddt` WHERE `dt_ddt`.`id` = `mg_movimenti`.`idddt`) WHERE `idddt`!=0;
UPDATE `mg_movimenti` SET `idsede_azienda` = (SELECT `idsede_partenza` FROM `dt_ddt` WHERE `dt_ddt`.`id` = `mg_movimenti`.`idddt`) WHERE `idddt`!=0;

-- Aggiorno idsede_azienda e controparte per documenti
UPDATE `mg_movimenti` SET `idsede_controparte` = (SELECT `idsede_destinazione` FROM `co_documenti` WHERE `co_documenti`.`id` = `mg_movimenti`.`iddocumento`) WHERE `iddocumento`!=0;
UPDATE `mg_movimenti` SET `idsede_azienda` = (SELECT `idsede_destinazione` FROM `co_documenti` WHERE `co_documenti`.`id` = `mg_movimenti`.`iddocumento`) WHERE `iddocumento`!=0;

-- Relazione fra utente e una o più sedi
CREATE TABLE `zz_user_sedi` (
  `id_user` int(11) NOT NULL,
  `idsede` int(11) NOT NULL
) ENGINE=InnoDB;

-- Sistemo colonna Nome, Descrizione - Modelli prima nota
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'CONCAT_WS(co_movimenti_modelli.nome, co_movimenti_modelli.descrizione)' WHERE `zz_modules`.`name` = 'Modelli prima nota' AND `zz_views`.`name` = 'Nome';
UPDATE `co_movimenti_modelli` SET `nome` = `descrizione` WHERE `nome` = '';

-- Rimuovo le interruzioni di riga per descrizioni vuote
-- UPDATE `in_interventi` SET `descrizione` = REPLACE(`descrizione`, '\n', '') where `descrizione` LIKE '%\n';

-- Aggiunto tabella co_tipi_scadenze
CREATE TABLE `co_tipi_scadenze` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descrizione` varchar(255) NOT NULL,
  `can_delete` tinyint(1) NOT NULL DEFAULT '1',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `co_tipi_scadenze` (`id`, `nome`, `descrizione`, `can_delete`) VALUES
(1, 'f24', 'F24', 0),
(2, 'generico', 'Scadenze generiche', 0);

-- Aggiunto modulo per gestire i tipi di scadenze
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Tipi scadenze', 'Tipi scadenze', 'tipi_scadenze', 'SELECT |select| FROM `co_tipi_scadenze` WHERE 1=1 HAVING 2=2', '', 'fa fa-calendar', '2.4.10', '2.4.10', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Tabelle'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi scadenze'), 'Descrizione', 'descrizione', 3, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi scadenze'), 'Nome', 'nome', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi scadenze'), 'id', 'id', 1, 1, 0, 0, 0);

-- Plugin rinnovi per Contratti
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Rinnovi', 'Rinnovi', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'tab', '', 1, 0, 0, '', '', NULL, 'custom', 'rinnovi_contratti', '');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tecnici e tariffe'), '_bg_', 'colore', 3, 1, 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tecnici e tariffe'), 'Nome', 'ragione_sociale', 2, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tecnici e tariffe'), 'id', 'idanagrafica', 1, 1, 0, 0, 0);

-- Correzione primary key
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_2`;
ALTER TABLE `in_tipiintervento` DROP PRIMARY KEY;
ALTER TABLE `in_tipiintervento` CHANGE `idtipointervento` `codice` VARCHAR(25) NOT NULL, ADD `idtipointervento` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT;

UPDATE `in_interventi` INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`codice` SET `in_interventi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `in_interventi` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

ALTER TABLE `in_statiintervento` DROP PRIMARY KEY;
ALTER TABLE `in_statiintervento` CHANGE `idstatointervento` `codice` VARCHAR(25) NOT NULL, ADD `idstatointervento` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT;

UPDATE `in_interventi` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`codice` SET `in_interventi`.`idstatointervento` = `in_statiintervento`.`idstatointervento`;
UPDATE `in_interventi`  SET `idstatointervento` = (SELECT `idstatointervento` FROM `in_statiintervento` LIMIT 1) WHERE `idstatointervento` NOT IN (SELECT `idstatointervento` FROM `in_statiintervento`);
ALTER TABLE `in_interventi` CHANGE `idstatointervento` `idstatointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idstatointervento`) REFERENCES `in_statiintervento`(`idstatointervento`);

UPDATE `an_anagrafiche` INNER JOIN `in_tipiintervento` ON `an_anagrafiche`.`idtipointervento_default` = `in_tipiintervento`.`codice` SET `an_anagrafiche`.`idtipointervento_default` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `an_anagrafiche` CHANGE `idtipointervento_default` `idtipointervento_default` varchar(25);
UPDATE `an_anagrafiche` SET `idtipointervento_default` = NULL WHERE `idtipointervento_default` NOT IN (SELECT `idtipointervento` FROM `in_tipiintervento`);
ALTER TABLE `an_anagrafiche` CHANGE `idtipointervento_default` `idtipointervento_default` INT(11), ADD FOREIGN KEY (`idtipointervento_default`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `co_contratti_tipiintervento`.`idtipointervento` = `in_tipiintervento`.`codice` SET `co_contratti_tipiintervento`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
DELETE FROM `co_contratti_tipiintervento` WHERE `idtipointervento` NOT IN (SELECT `idtipointervento` FROM `in_tipiintervento`);
ALTER TABLE `co_contratti_tipiintervento` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `co_preventivi` INNER JOIN `in_tipiintervento` ON `co_preventivi`.`idtipointervento` = `in_tipiintervento`.`codice` SET `co_preventivi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
UPDATE `co_preventivi`  SET `idtipointervento` = (SELECT `idtipointervento` FROM `in_tipiintervento` LIMIT 1) WHERE `idtipointervento` NOT IN (SELECT `idtipointervento` FROM `in_tipiintervento`);
ALTER TABLE `co_preventivi` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `co_promemoria` INNER JOIN `in_tipiintervento` ON `co_promemoria`.`idtipointervento` = `in_tipiintervento`.`codice` SET `co_promemoria`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `co_promemoria` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `in_interventi_tecnici` INNER JOIN `in_tipiintervento` ON `in_interventi_tecnici`.`idtipointervento` = `in_tipiintervento`.`codice` SET `in_interventi_tecnici`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
UPDATE `in_interventi_tecnici`  SET `idtipointervento` = (SELECT `idtipointervento` FROM `in_tipiintervento` LIMIT 1) WHERE `idtipointervento` NOT IN (SELECT `idtipointervento` FROM `in_tipiintervento`);
ALTER TABLE `in_interventi_tecnici` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `in_tariffe` INNER JOIN `in_tipiintervento` ON `in_tariffe`.`idtipointervento` = `in_tipiintervento`.`codice` SET `in_tariffe`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
DELETE FROM `in_tariffe` WHERE `idtipointervento` NOT IN (SELECT `idtipointervento` FROM `in_tipiintervento`);
ALTER TABLE `in_tariffe` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'IF(`dup`.`numero_esterno` IS NULL, '''', ''red'')' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = '_bg_';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.idanagrafica' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'idanagrafica';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'IF(co_documenti.numero_esterno='''', co_documenti.numero, co_documenti.numero_esterno)', `order_by` ='CAST(co_documenti.numero_esterno AS UNSIGNED)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Numero';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_documenti.data' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Data';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Ragione sociale';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'co_statidocumento.descrizione' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '`fe_stati_documento`.`icon`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_FE';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = '`fe_stati_documento`.`descrizione`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_title_FE';
-- Impostazione per la lunghezza delle pagine Datatables
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Lunghezza in pagine del buffer Datatables', '10', 'decimal', 0, 'Generali', 1);

-- Plugin Statistiche per Articoli
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Statistiche', 'Statistiche', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), 'tab', '', 1, 0, 0, '', '', NULL, 'custom', 'statistiche_articoli', '');

-- Aggiunta nome file per le stampe
ALTER TABLE `zz_prints` ADD `filename` VARCHAR(255) NOT NULL AFTER `title`;
UPDATE `zz_prints` SET `filename` = 'Fattura num. {numero} del {data}' WHERE `name` = 'Fattura di vendita';
UPDATE `zz_prints` SET `filename` = 'Fatturato'  WHERE `name` = 'Fatturato';
UPDATE `zz_prints` SET `filename` = 'DDT num. {numero} del {data}' WHERE `name` = 'Ddt di vendita (senza costi)';
UPDATE `zz_prints` SET `filename` = 'DDT num. {numero} del {data}' WHERE `name` = 'Ddt di vendita';
UPDATE `zz_prints` SET `filename` = 'Ordine fornitore num. {numero} del {data}' WHERE `name` = 'Ordine fornitore (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Ordine cliente num. {numero} del {data}' WHERE `name` = 'Ordine cliente (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Ordine fornitore num. {numero} del {data}' WHERE `name` = 'Ordine fornitore';
UPDATE `zz_prints` SET `filename` = 'Ordine cliente num. {numero} del {data}' WHERE `name` = 'Ordine cliente';
UPDATE `zz_prints` SET `filename` = 'Ordine cliente num. {numero} del {data}' WHERE `name` = 'Ordine cliente';
UPDATE `zz_prints` SET `filename` = 'Contratto num. {numero} del {data}' WHERE `name` = 'Contratto (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Contratto num. {numero} del {data}' WHERE `name` = 'Contratto';
UPDATE `zz_prints` SET `filename` = 'Intervento num. {numero} del {data}' WHERE `name` = 'Intervento (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Intervento num. {numero} del {data}' WHERE `name` = 'Intervento';
UPDATE `zz_prints` SET `filename` = 'Preventivo num. {numero} del {data}' WHERE `name` = 'Preventivo (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Preventivo num. {numero} del {data}' WHERE `name` = 'Preventivo';
UPDATE `zz_prints` SET `filename` = 'Registro IVA {tipo}' WHERE `name` = 'Registro IVA';
UPDATE `zz_prints` SET `filename` = 'Scadenzario' WHERE `name` = 'Scadenzario';
UPDATE `zz_prints` SET `filename` = 'Spesometro' WHERE `name` = 'Spesometro';
UPDATE `zz_prints` SET `filename` = 'Mastrino' WHERE `name` = 'Mastrino';
UPDATE `zz_prints` SET `filename` = 'Inventario' WHERE `name` = 'Inventario magazzino';
UPDATE `zz_prints` SET `filename` = 'Riepilogo interventi' WHERE `name` = 'Riepilogo interventi';
UPDATE `zz_prints` SET `filename` = 'Consuntivo contratto num. {numero} del {data}' WHERE `name` = 'Consuntivo contratto (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Consuntivo contratto num. {numero} del {data}' WHERE `name` = 'Consuntivo contratto';
UPDATE `zz_prints` SET `filename` = 'Consuntivo preventivo num. {numero} del {data}' WHERE `name` = 'Consuntivo preventivo (senza costi)';
UPDATE `zz_prints` SET `filename` = 'Consuntivo preventivo num. {numero} del {data}' WHERE `name` = 'Consuntivo preventivo';

-- Aggiornamento Statistiche delle Anagrafiche
UPDATE `zz_plugins` SET `options` = 'custom', `directory` = 'statistiche_anagrafiche', `script` = '' WHERE `name` = 'Statistiche' AND `idmodule_from` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche');

-- Fix sconti globali
UPDATE `co_righe_documenti` SET `sconto` = -`subtotale`, `sconto_unitario` = -`subtotale` WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `co_righe_preventivi` SET `sconto` = -`subtotale`, `sconto_unitario` = -`subtotale` WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `co_righe_contratti` SET `sconto` = -`subtotale`, `sconto_unitario` = -`subtotale` WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `or_righe_ordini` SET `sconto` = -`subtotale`, `sconto_unitario` = -`subtotale` WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `dt_righe_ddt` SET `sconto` = -`subtotale`, `sconto_unitario` = -`subtotale` WHERE `is_sconto` = 1 AND `subtotale` != 0;

UPDATE `co_righe_documenti` SET `subtotale` = 0 WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `co_righe_preventivi` SET `subtotale` = 0 WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `co_righe_contratti` SET `subtotale` = 0 WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `or_righe_ordini` SET `subtotale` = 0 WHERE `is_sconto` = 1 AND `subtotale` != 0;
UPDATE `dt_righe_ddt` SET `subtotale` = 0 WHERE `is_sconto` = 1 AND `subtotale` != 0;
