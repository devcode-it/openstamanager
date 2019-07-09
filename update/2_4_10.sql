-- Fix colonna Totale per Fatture
UPDATE `zz_views` SET `query` = '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + iva_rivalsainps' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') AND name = 'Totale';
UPDATE `zz_views` SET `query` = '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + iva_rivalsainps' WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') AND name = 'Totale';

-- Fix widget Contratti in scadenza per mostrare i contratti con ore in esaurimento
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato,
       ((SELECT SUM(co_righe_contratti.qta) FROM co_righe_contratti WHERE co_righe_contratti.um=\'ore\' AND co_righe_contratti.idcontratto=co_contratti.id) - IFNULL( (SELECT SUM(in_interventi_tecnici.ore) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id_contratto=co_contratti.id AND in_interventi.idstatointervento IN (SELECT in_statiintervento.idstatointervento FROM in_statiintervento WHERE in_statiintervento.completato = 1)), 0) ) AS ore_rimanenti,
       DATEDIFF(data_conclusione, NOW()) AS giorni_rimanenti,
       data_conclusione,
       ore_preavviso_rinnovo,
       giorni_preavviso_rinnovo,
       (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=co_contratti.idanagrafica) AS ragione_sociale
FROM co_contratti WHERE
        idstato IN (SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND
        rinnovabile = 1 AND
        YEAR(data_conclusione) > 1970 AND
        (SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev = co_contratti.id) IS NULL
HAVING (ore_rimanenti < ore_preavviso_rinnovo OR DATEDIFF(data_conclusione, NOW()) < ABS(giorni_preavviso_rinnovo))
ORDER BY giorni_rimanenti ASC, ore_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';

-- Miglioramento hooks
ALTER TABLE `zz_hooks` ADD `enabled` boolean NOT NULL DEFAULT 1, ADD `id_module` int(11) NOT NULL;
UPDATE `zz_hooks` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') WHERE `name` = 'Ricevute';
UPDATE `zz_hooks` SET `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di acquisto') WHERE `name` = 'Fatture';
ALTER TABLE `zz_hooks` ADD FOREIGN KEY (`id_module`) REFERENCES `zz_modules`(`id`) ON DELETE CASCADE;

INSERT INTO `zz_hooks` (`id`, `name`, `class`, `frequency`, `id_module`) VALUES
(NULL, 'Ricevute', 'Modules\\Aggiornamenti\\UpdateHook', '7 day', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Aggiornamenti'));

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


UPDATE `zz_modules` SET `options`='SELECT |select| FROM (((((((`dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`) LEFT OUTER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`) LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`) LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`) LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`) LEFT OUTER JOIN `an_anagrafiche` AS `destinatari` ON `dt_ddt`.`idanagrafica` = `destinatari`.`idanagrafica`) LEFT OUTER JOIN an_sedi AS sedi ON `dt_ddt`.`idsede_partenza` = `sedi`.`id`) LEFT OUTER JOIN an_sedi AS sedi_destinazione ON dt_ddt.idsede_destinazione = sedi_destinazione.id  WHERE 1=1 AND `dir` = ''entrata'' |date_period(`data`)| GROUP BY dt_ddt.id HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE name="Ddt di vendita";

UPDATE `zz_modules` SET `options`='SELECT |select| FROM (((((((`dt_ddt` INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`) LEFT OUTER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt`) LEFT OUTER JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`) LEFT OUTER JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`) LEFT OUTER JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`) LEFT OUTER JOIN `an_anagrafiche` AS `destinatari` ON `dt_ddt`.`idanagrafica` = `destinatari`.`idanagrafica`) LEFT OUTER JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`) LEFT OUTER JOIN an_sedi AS sedi_destinazione ON dt_ddt.idsede_destinazione = sedi_destinazione.id WHERE 1=1 AND `dir` = ''uscita'' |date_period(`data`)| GROUP BY dt_ddt.id HAVING 2=2 ORDER BY `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC,`dt_ddt`.created_at DESC' WHERE name="Ddt di acquisto";

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

-- Sistemo vista per icon_Inviata modulo Fatture di vendita
UPDATE `zz_views` SET `query` = 'IF(`email`.`name` IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_views`.`name` = 'icon_Inviata' AND `id_module` =  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') ;

-- Sistemo vista per icon_title_Inviata modulo Fatture di vendita
UPDATE `zz_views` SET `query` = '`email`.`name`' WHERE `zz_views`.`name` = 'icon_title_Inviata' AND `id_module` =  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita') ;

-- Relazione fra utente e una o più sedi
CREATE TABLE `zz_user_sedi` (
  `id_user` int(11) NOT NULL,
  `idsede` int(11) NOT NULL
) ENGINE=InnoDB;

-- Sistemo colonna Nome, Descrizione - Modelli prima nota
UPDATE `zz_views` SET `query` = 'CONCAT_WS(co_movimenti_modelli.nome, co_movimenti_modelli.descrizione)' WHERE `zz_views`.`name` = 'Nome' AND `id_module` =  (SELECT `id` FROM `zz_modules` WHERE `name` = 'Modelli prima nota');

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

-- Aggiungo possibilità di vedere la descrizione per le scadenze generiche
UPDATE `zz_views` SET `query` = 'IF(an_anagrafiche.ragione_sociale IS NULL, co_scadenziario.descrizione, an_anagrafiche.ragione_sociale)' WHERE `zz_views`.`name` = 'Anagrafica' AND `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario');

-- Plugin rinnovi per Contratti
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Rinnovi', 'Rinnovi', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'tab', '', 1, 0, 0, '', '', NULL, 'custom', 'rinnovi_contratti', '');

-- Aggiornamento Tecnici e tariffe
UPDATE `zz_modules` SET `options` = '
SELECT |select| FROM an_anagrafiche WHERE idanagrafica IN (
    SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica IN (
        SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = ''Tecnico''
    )
) AND deleted_at IS NULL AND 1=1 HAVING 2=2 ORDER BY ragione_sociale' WHERE `name` = 'Tecnici e tariffe';

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
ALTER TABLE `in_interventi` CHANGE `idstatointervento` `idstatointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idstatointervento`) REFERENCES `in_statiintervento`(`idstatointervento`);

UPDATE `an_anagrafiche` INNER JOIN `in_tipiintervento` ON `an_anagrafiche`.`idtipointervento_default` = `in_tipiintervento`.`codice` SET `an_anagrafiche`.`idtipointervento_default` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `an_anagrafiche` CHANGE `idtipointervento_default` `idtipointervento_default` varchar(25);
UPDATE `an_anagrafiche` SET `idtipointervento_default` = NULL WHERE `idtipointervento_default` NOT IN (SELECT `idtipointervento` FROM `in_tipiintervento`);
ALTER TABLE `an_anagrafiche` CHANGE `idtipointervento_default` `idtipointervento_default` INT(11), ADD FOREIGN KEY (`idtipointervento_default`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `co_contratti_tipiintervento`.`idtipointervento` = `in_tipiintervento`.`codice` SET `co_contratti_tipiintervento`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `co_contratti_tipiintervento` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `co_preventivi` INNER JOIN `in_tipiintervento` ON `co_preventivi`.`idtipointervento` = `in_tipiintervento`.`codice` SET `co_preventivi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `co_preventivi` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `co_promemoria` INNER JOIN `in_tipiintervento` ON `co_promemoria`.`idtipointervento` = `in_tipiintervento`.`codice` SET `co_promemoria`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `co_promemoria` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `in_interventi_tecnici` INNER JOIN `in_tipiintervento` ON `in_interventi_tecnici`.`idtipointervento` = `in_tipiintervento`.`codice` SET `in_interventi_tecnici`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `in_interventi_tecnici` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

UPDATE `in_tariffe` INNER JOIN `in_tipiintervento` ON `in_tariffe`.`idtipointervento` = `in_tipiintervento`.`codice` SET `in_tariffe`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`;
ALTER TABLE `in_tariffe` CHANGE `idtipointervento` `idtipointervento` INT(11) NOT NULL, ADD FOREIGN KEY (`idtipointervento`) REFERENCES `in_tipiintervento`(`idtipointervento`);

-- Ottimizzazione query Fatture
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_documenti`
    INNER JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    INNER JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN (SELECT `numero_esterno`, `id_segment` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN(SELECT `id` FROM `co_tipidocumento` WHERE `dir` = ''entrata'') AND `co_documenti`.`data` >= ''2019-01-01'' AND `co_documenti`.`data` <= ''2019-12-31'' GROUP BY `id_segment`, `numero_esterno` HAVING COUNT(`numero_esterno`) > 1) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment`
    LEFT OUTER JOIN (SELECT `zz_emails`.`name`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `zz_emails` ON `zz_operations`.`id_email` = `zz_emails`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` AND `zz_modules`.`name` = ''Fatture di vendita'' AND `zz_operations`.`op` = ''send-email'' LIMIT 1) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
WHERE 1=1 AND `dir` = ''entrata'' |segment(co_documenti.id_segment)| AND `co_documenti`.`data` >= ''|period_start|'' AND `co_documenti`.`data` <= ''|period_end|''
HAVING 2=2
ORDER BY `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `name` = 'Fatture di vendita';

UPDATE `zz_views` SET `query` = 'IF(`dup`.`numero_esterno` IS NULL, '''', ''red'')' WHERE `name` = '_bg_' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = 'an_anagrafiche.idanagrafica' WHERE `name` = 'idanagrafica' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = 'IF(co_documenti.numero_esterno='''', co_documenti.numero, co_documenti.numero_esterno)', `order_by` ='CAST(co_documenti.numero_esterno AS UNSIGNED)' WHERE `name` = 'Numero' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = 'co_documenti.data' WHERE `name` = 'Data' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = 'an_anagrafiche.ragione_sociale' WHERE `name` = 'Ragione sociale' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = '(SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + co_documenti.iva_rivalsainps' WHERE `name` = 'Totale' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = 'co_statidocumento.icona' WHERE `name` = 'icon_Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = 'co_statidocumento.descrizione' WHERE `name` = 'icon_title_Stato' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = '`fe_stati_documento`.`icon`' WHERE `name` = 'icon_FE' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');
UPDATE `zz_views` SET `query` = '`fe_stati_documento`.`descrizione`' WHERE `name` = 'icon_title_FE' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita');

-- Impostazione per la lunghezza delle pagine Datatables
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES (NULL, 'Lunghezza in pagine del buffer Datatables', '10', 'decimal', 0, 'Generali', 1);
