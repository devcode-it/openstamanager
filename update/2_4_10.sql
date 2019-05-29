--
-- Aggiunta nuovi campi per tracciamento sedi 
--
ALTER TABLE `mg_movimenti` ADD `idsede_azienda` INT NOT NULL AFTER `idautomezzo`, ADD `idsede_controparte` INT NOT NULL AFTER `idsede_azienda`;

--
-- Creazione plugin Giacenze
--
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Giacenze', 'Giacenze', (SELECT id FROM zz_modules WHERE name='Articoli'), (SELECT id FROM zz_modules WHERE name='Articoli'), 'tab', 'articoli.giacenze.php', '1', '0', '0', '', '', NULL, NULL, '', '');

-- Aggiornamento nomi dei campi sede per ddt
ALTER TABLE `dt_ddt` CHANGE `idsede` `idsede_partenza` INT(11) NOT NULL;
ALTER TABLE `dt_ddt` ADD `idsede_destinazione` INT NULL AFTER `idsede_partenza`;

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
UPDATE `mg_movimenti` SET `idsede_controparte` (SELECT `idsede_destinazione` FROM `dt_ddt` WHERE `dt_ddt`.`id` = `mg_movimenti`.`idddt`) WHERE `idddt`!=0;
UPDATE `mg_movimenti` SET `idsede_azienda` (SELECT `idsede_partenza` FROM `dt_ddt` WHERE `dt_ddt`.`id` = `mg_movimenti`.`idddt`) WHERE `idddt`!=0;

-- Aggiorno idsede_azienda e controparte per documenti
UPDATE `mg_movimenti` SET `idsede_controparte` (SELECT `idsede_destinazione` FROM `co_documenti` WHERE `co_documenti`.`id` = `mg_movimenti`.`iddocumento`) WHERE `iddocumento`!=0;
UPDATE `mg_movimenti` SET `idsede_azienda` (SELECT `idsede_destinazione` FROM `co_documenti` WHERE `co_documenti`.`id` = `mg_movimenti`.`iddocumento`) WHERE `iddocumento`!=0;