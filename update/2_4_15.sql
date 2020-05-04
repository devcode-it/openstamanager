-- Aggiunta del campo per permettere la modifica delle Viste di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Modifica Viste di default', '0', 'boolean', 0, 'Generali');

-- Retrofix
UPDATE `mg_articoli` SET `id_categoria` = NULL WHERE `codice` = 'DELETED';

-- Ottimizzazione query attività
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM (`in_interventi` INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`) LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento` LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede,\'<br>\',an_sedi.telefono,\'<br>\',an_sedi.cellulare,\'<br>\',an_sedi.citta,\' - \', an_sedi.indirizzo) AS info FROM an_sedi) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione LEFT JOIN (SELECT co_righe_documenti.idintervento, CONCAT(\'Fatt. \', co_documenti.numero_esterno,\' del \', DATE_FORMAT(co_documenti.data, \'%d/%m/%Y\')) AS info FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento) AS fattura ON fattura.idintervento = in_interventi.id WHERE 1=1 |date_period(`orario_inizio`,`data_richiesta`)| GROUP BY `in_interventi`.`id` HAVING 2=2 ORDER BY IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `name` = 'Interventi';

-- Aggiunta colonna Rif. fattura per attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Rif. fattura', 'fattura.info', 17, 1, 0, 0, 1);

-- Aggiunta indice per idintervento in co_righe_documenti
ALTER TABLE `co_righe_documenti` ADD INDEX(`idintervento`);

-- Ore preavviso rinnovo con decimali per frazioni ore
ALTER TABLE `co_contratti` CHANGE `ore_preavviso_rinnovo` `ore_preavviso_rinnovo` DECIMAL(15,6) NULL DEFAULT NULL; 

-- Canale aggiornamenti stable/pre-release
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Abilita canale pre-release per aggiornamenti', '1', 'boolean', '1', 'Generali', NULL, "Consente di recuperare dal canale di pre-release gli aggiornamenti NON stabili del gestionale.");

-- Per gli articoli con id_categoria e id_sottocategoria a 0 imposto il valore NULL (permesso dalla 2.4.14)
UPDATE `mg_articoli` SET `id_categoria` = NULL WHERE `mg_articoli`.`id_categoria` = 0; 
UPDATE `mg_articoli` SET `id_sottocategoria` = NULL WHERE `mg_articoli`.`id_sottocategoria` = 0; 

-- Allineamento title stampe
UPDATE `zz_prints` SET `title` = 'Ddt in uscita (senza prezzi)' WHERE `zz_prints`.`name` = 'Ddt di vendita (senza costi)';
UPDATE `zz_prints` SET `title` = 'Ddt in uscita' WHERE `zz_prints`.`name` = 'Ddt di vendita';

-- Rimozione stampe ordini di servizio a db
DELETE FROM `zz_prints` WHERE `zz_prints`.`name` = 'Ordine di servizio (senza costi)';
DELETE FROM `zz_prints` WHERE `zz_prints`.`name` = 'Ordine di servizio';