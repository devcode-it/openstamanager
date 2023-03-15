-- Aggiunto modulo Listini clienti
RENAME TABLE `mg_listini` TO `mg_piani_sconto`;
ALTER TABLE `an_anagrafiche` CHANGE `idlistino_acquisti` `id_piano_sconto_acquisti` INT(11) NULL DEFAULT NULL; 
ALTER TABLE `an_anagrafiche` CHANGE `idlistino_vendite` `id_piano_sconto_vendite` INT(11) NULL DEFAULT NULL; 
ALTER TABLE `an_anagrafiche` ADD `id_listino` INT NOT NULL AFTER `id_piano_sconto_acquisti`; 

CREATE TABLE `mg_listini` ( `id` INT NOT NULL AUTO_INCREMENT , `nome` VARCHAR(255) NOT NULL , `data_attivazione` DATE NULL , `data_scadenza_predefinita` DATE NULL , `is_sempre_visibile` BOOLEAN NOT NULL , `attivo` BOOLEAN NOT NULL , `note` TEXT NOT NULL , PRIMARY KEY (`id`)); 

CREATE TABLE `mg_listini_articoli` ( `id` INT NOT NULL AUTO_INCREMENT , `id_listino` INT NOT NULL, `id_articolo` INT NOT NULL , `data_scadenza` DATE NOT NULL , `prezzo_unitario` DECIMAL(15,6) NOT NULL , `prezzo_unitario_ivato` DECIMAL(15,6) NOT NULL , `sconto_percentuale` DECIMAL(15,6) NOT NULL , `dir` VARCHAR(20) NOT NULL , PRIMARY KEY (`id`)); 

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Listini cliente', 'Listini cliente', 'listini_cliente', 'SELECT |select| FROM `mg_listini` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.*', '2.*', '2', (SELECT `id` FROM `zz_modules` AS `t` WHERE `t`.`name`='Magazzino'), '1', '1', '0', '0');

INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'id', 'id', 1, 1, 0, 0, 0, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Nome', 'nome', 2, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Data attivazione', 'data_attivazione', 3, 1, 0, 1, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Articoli', '(SELECT COUNT(id) FROM mg_listini_articoli WHERE id_listino=mg_listini.id)', 4, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Anagrafiche', '(SELECT COUNT(idanagrafica) FROM an_anagrafiche WHERE id_listino=mg_listini.id)', 5, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Ultima modifica', '(SELECT username FROM zz_users WHERE id=(SELECT id_utente FROM zz_operations WHERE id_module=(SELECT id FROM zz_modules WHERE name=\'Listini cliente\') AND id_record=mg_listini.id ORDER BY id DESC LIMIT 0,1))', 6, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Sempre visibile', 'IF(is_sempre_visibile=0,\'NO\',\'SÌ\')', 7, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Attivo', 'IF(attivo=0,\'NO\',\'SÌ\')', 7, 1, 0, 0, 0, '', '', 1, 0, 1);


UPDATE `zz_plugins` SET `title` = 'Netto clienti', `name` = 'Netto Clienti' WHERE `zz_plugins`.`name` = 'Listino Clienti'; 

ALTER TABLE `mg_articoli` ADD `minimo_vendita` DECIMAL(15,6) NOT NULL AFTER `prezzo_vendita_ivato`; 
ALTER TABLE `mg_articoli` ADD `minimo_vendita_ivato` DECIMAL(15,6) NOT NULL AFTER `minimo_vendita`; 

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Bloccare i prezzi inferiori al minimo di vendita', '0', 'boolean', '1', 'Fatturazione', NULL, NULL);

-- Aggiunto task invio mail
INSERT INTO `zz_tasks` (`id`, `name`, `class`, `expression`, `next_execution_at`, `last_executed_at`) VALUES (NULL, 'Invio automatico mail', 'Modules\\Emails\\EmailTask', '*/1 * * * *', NULL, NULL);

-- Ottimizzazione query vista anagrafiche
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'sedi.nomi' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Sedi';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.citta' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Città';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.codice_destinatario' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Codice destinatario';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.telefono' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Telefono';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'referenti.nomi' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Referenti';

UPDATE `zz_modules` SET `options` = "SELECT 
|select|
FROM
    `an_anagrafiche`
LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nomesede SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY idanagrafica) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nome SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY idanagrafica) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`
WHERE
    1=1 AND `deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`
HAVING
    2=2
ORDER BY
    TRIM(`ragione_sociale`)" WHERE `name` = 'Anagrafiche';

-- Creazione modelli prima nota per liquidazione salari e stipendi
SELECT @numero := MAX(CAST(numero AS UNSIGNED))+10 FROM co_pianodeiconti3 WHERE idpianodeiconti2 = '8';
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES 
(NULL, LPAD(@numero, 6, '0'), 'Personale c/Retribuzioni', '8', '', '100.00');

SELECT @numero := MAX(CAST(numero AS UNSIGNED))+10 FROM co_pianodeiconti3 WHERE idpianodeiconti2 = '8';
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES 
(NULL, LPAD(@numero, 6, '0'), 'INPS c/Competenza', '8', '', '100.00');

SELECT @numero := MAX(CAST(numero AS UNSIGNED))+10 FROM co_pianodeiconti3 WHERE idpianodeiconti2 = '5';
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES 
(NULL, LPAD(@numero, 6, '0'), 'Erario c/Ritenute dipendenti', '5', '', '100.00'); 

SELECT @idmastrino := MAX(idmastrino)+1 FROM co_movimenti_modelli;
INSERT INTO `co_movimenti_modelli` (`id`, `idmastrino`, `nome`, `descrizione`, `idconto`, `totale`) VALUES
(NULL, @idmastrino, 'Liquidazione salari e stipendi', 'Liquidazione retribuzione relativa al mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Costi salari e stipendi' LIMIT 0,1), '0.0'),
(NULL, @idmastrino, 'Liquidazione salari e stipendi', 'Liquidazione retribuzione relativa al mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'INPS c/Competenza' LIMIT 0,1), '0.0'),
(NULL, @idmastrino, 'Liquidazione salari e stipendi', 'Liquidazione retribuzione relativa al mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Personale c/Retribuzioni' LIMIT 0,1), '0.0');

SELECT @idmastrino := MAX(idmastrino)+1 FROM co_movimenti_modelli;
INSERT INTO `co_movimenti_modelli` (`id`, `idmastrino`, `nome`, `descrizione`, `idconto`, `totale`) VALUES
(NULL, @idmastrino, 'Pagamento salari e stipendi', 'Pagamento ai dipendenti delle retribuzioni nette del mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Personale c/Retribuzioni' LIMIT 0,1), '0.0'),
(NULL, @idmastrino, 'Pagamento salari e stipendi', 'Pagamento ai dipendenti delle retribuzioni nette del mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'INPS c/Competenza' LIMIT 0,1), '0.0'),
(NULL, @idmastrino, 'Pagamento salari e stipendi', 'Pagamento ai dipendenti delle retribuzioni nette del mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Erario c/Ritenute dipendenti' LIMIT 0,1), '0.0'),
(NULL, @idmastrino, 'Pagamento salari e stipendi', 'Pagamento ai dipendenti delle retribuzioni nette del mese di ...', (SELECT id FROM co_pianodeiconti3 WHERE descrizione = 'Banca C/C' LIMIT 0,1), '0.0');


-- Ottimizzazione query vista prima nota
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'co_documenti.numero_esterno' WHERE `zz_modules`.`name` = 'Prima nota' AND `zz_views`.`name` = 'Rif. fattura';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Prima nota' AND `zz_views`.`name` = 'Controparte';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'co_movimenti.data' WHERE `zz_modules`.`name` = 'Prima nota' AND `zz_views`.`name` = 'Data';

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM
    `co_movimenti`
INNER JOIN `co_pianodeiconti3` ON `co_movimenti`.`idconto` = `co_pianodeiconti3`.`id`
LEFT JOIN `co_documenti` ON `co_documenti`.`id` = `co_movimenti`.`iddocumento`
LEFT JOIN `an_anagrafiche` ON `co_movimenti`.`id_anagrafica` = `an_anagrafiche`.`idanagrafica`
WHERE
    1=1 AND `primanota` = 1  |date_period(`co_movimenti`.`data`)|
GROUP BY
    `idmastrino`,
    `primanota`,
    `co_movimenti`.`data`,
    `numero_esterno`,
    `co_movimenti`.`descrizione`,
    `an_anagrafiche`.`ragione_sociale`
HAVING
    2=2
ORDER BY
    `co_movimenti`.`data`
DESC" WHERE `name` = 'Prima nota';

-- Ottimizzazione query vista ordini cliente
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'or_statiordine.icona' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'or_statiordine.descrizione' WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
	`or_ordini`
    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`
    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN (
SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR ', ') AS info, co_righe_documenti.original_document_id AS idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type='Modules\\Ordini\\Ordine' GROUP BY idordine
) AS fattura ON fattura.idordine = or_ordini.id
LEFT JOIN (
SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`
FROM `zz_operations`
INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`
INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`
INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`
WHERE `zz_modules`.`name` = 'Ordini cliente' AND `zz_operations`.`op` = 'send-email'
GROUP BY `zz_operations`.`id_record`
) AS `email` ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1 AND `dir` = 'entrata'  |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY 
	`data` DESC, 
    CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Ordini cliente';

-- Ottimizzazione query vista ordini fornitore
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'or_statiordine.icona' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'or_statiordine.descrizione' WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
	`or_ordini`
    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`
    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN (
SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR ', ') AS info, co_righe_documenti.original_document_id AS idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type='Modules\\Ordini\\Ordine' GROUP BY idordine
) AS fattura ON fattura.idordine = or_ordini.id
LEFT JOIN (
SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`
FROM `zz_operations`
INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`
INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`
INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`
WHERE `zz_modules`.`name` = 'Ordini fornitore' AND `zz_operations`.`op` = 'send-email'
GROUP BY `zz_operations`.`id_record`
) AS `email` ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1 AND `dir` = 'uscita' |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY 
	`data` DESC, 
    CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Ordini fornitore';

-- Ottimizzazione query vista ddt uscita
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'dt_statiddt.icona' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'dt_statiddt.descrizione' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `dt_ddt`
LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
LEFT JOIN(
    SELECT `idddt`,
        SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
        SUM(`subtotale` - `sconto` + `iva`) AS `totale`
    FROM
        `dt_righe_ddt`
    GROUP BY
        `idddt`
) AS righe
ON
    `dt_ddt`.`id` = `righe`.`idddt`
LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    
WHERE
    1=1 AND `dir` = 'entrata' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.created_at DESC" WHERE `name` = 'Ddt di vendita';


-- Ottimizzazione query vista ddt entrata
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'dt_statiddt.icona' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'icon_Stato';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'dt_statiddt.descrizione' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'icon_title_Stato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `dt_ddt`
LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
LEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
LEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`
LEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
LEFT JOIN(
    SELECT `idddt`,
        SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
        SUM(`subtotale` - `sconto` + `iva`) AS `totale`
    FROM
        `dt_righe_ddt`
    GROUP BY
        `idddt`
) AS righe
ON
    `dt_ddt`.`id` = `righe`.`idddt`
LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    
WHERE
    1=1 AND `dir` = 'uscita' |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.created_at DESC" WHERE `name` = 'Ddt di acquisto';


-- Ottimizzazione query vista impianti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'my_impianti.id' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'my_impianti.idanagrafica' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'idanagrafica';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'my_impianti.nome' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'clienti.ragione_sociale' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Cliente';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = "IF(my_impianti.idsede > 0, sede.info, CONCAT('', IF (clienti.telefono!='',CONCAT(clienti.telefono,'<br>'),''), IF(clienti.cellulare!='', CONCAT(clienti.cellulare,'<br>'),''),IF(clienti.citta!='',clienti.citta,''),IF(clienti.indirizzo!='',CONCAT(' - ',clienti.indirizzo),'')))" WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Sede';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'tecnici.ragione_sociale' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Tecnico';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'my_impianti_categorie.nome' WHERE `zz_modules`.`name` = 'Impianti' AND `zz_views`.`name` = 'Categoria';
UPDATE `zz_modules` SET `options` = "SELECT
    |select| 
FROM
    `my_impianti`
    LEFT JOIN `an_anagrafiche` AS clienti ON `clienti`.`idanagrafica` = `my_impianti`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS tecnici ON `tecnici`.`idanagrafica` = `my_impianti`.`idtecnico` 
    LEFT JOIN `my_impianti_categorie` ON `my_impianti_categorie`.`id` = `my_impianti`.`id_categoria`
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi
) AS sede ON sede.id = my_impianti.idsede
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `matricola`" WHERE `name` = 'Impianti';


-- Ottimizzazione query vista Movimenti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'zz_modules.id' WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = '_link_module_';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'page.link' WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = '_link_hash_';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = "IF(`mg_movimenti`.`reference_type` = 'Modules\\\\Fatture\\\\Fattura', fattura.nomi, IF(`mg_movimenti`.`reference_type` = 'Modules\\\\DDT\\\\DDT', ddt.nomi, IF(`mg_movimenti`.`reference_type` = 'Modules\\\\Interventi\\\\Intervento', intervento.nomi, '')))" WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = 'Anagrafica';
UPDATE `zz_modules` SET `options` = "SELECT
    |select| 
FROM
     `mg_movimenti`
	INNER JOIN `mg_articoli` ON `mg_articoli`.id = `mg_movimenti`.`idarticolo`
	LEFT JOIN `an_sedi` ON `mg_movimenti`.`idsede` = `an_sedi`.`id`
	LEFT JOIN `zz_modules` ON `zz_modules`.`name` = 'Articoli'
	LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `co_documenti`.`id`, `ragione_sociale` AS nomi FROM `co_documenti` LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` GROUP BY `idanagrafica`, `co_documenti`.`id`) AS fattura ON `fattura`.`id`= `mg_movimenti`.`reference_id`
	LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `dt_ddt`.`id`, `ragione_sociale` AS nomi FROM `dt_ddt` LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` GROUP BY `idanagrafica`, `dt_ddt`.`id`) AS ddt ON `ddt`.`id`= `mg_movimenti`.`reference_id`
	LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `in_interventi`.`id`, `ragione_sociale` AS nomi FROM `in_interventi` LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` GROUP BY `idanagrafica`, `in_interventi`.`id`) AS intervento ON `intervento`.`id`= `mg_movimenti`.`reference_id`
    LEFT JOIN (SELECT CONCAT('tab_', `zz_plugins`.`id`) AS link FROM `zz_plugins` INNER JOIN `zz_modules` ON `zz_plugins`.`idmodule_to` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Articoli' AND `zz_plugins`.`name` = 'Movimenti') AS page ON `mg_movimenti`.`id` != ''
WHERE
    1=1 AND mg_articoli.deleted_at IS NULL
HAVING
    2=2
ORDER BY
    mg_movimenti.data DESC,
    mg_movimenti.created_at DESC" WHERE `name` = 'Movimenti';


-- Ottimizzazione query vista Template email
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates`.`id`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates`.`id`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = '#';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates`.`NAME`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates`.`SUBJECT`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Oggetto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = ' `zz_modules`.`name`' WHERE `zz_modules`.`name` = 'Template email' AND `zz_views`.`name` = 'Modulo';

UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `em_templates`
    LEFT JOIN `zz_modules` on `zz_modules`.`id` = `em_templates`.`id_module`
WHERE
    1=1 AND `deleted_at` IS NULL
HAVING
    2=2
ORDER BY
    `zz_modules`.`name`" WHERE `name` = 'Template email';


-- Ottimizzazione query vista Campi personalizzati
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_fields`.`id`' WHERE `zz_modules`.`name` = 'Campi personalizzati' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_modules`.`NAME`' WHERE `zz_modules`.`name` = 'Campi personalizzati' AND `zz_views`.`name` = 'Modulo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_plugins`.`NAME`' WHERE `zz_modules`.`name` = 'Campi personalizzati' AND `zz_views`.`name` = 'Plugin';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_fields`.`NAME`' WHERE `zz_modules`.`name` = 'Campi personalizzati' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `zz_fields`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_fields`.`id_module`
    LEFT JOIN `zz_plugins` ON `zz_plugins`.`id` = `zz_fields`.`id_module`
WHERE
    1=1
HAVING
    2=2" WHERE `name` = 'Campi personalizzati';


-- Ottimizzazione query vista Segmenti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_segments`.`id`' WHERE `zz_modules`.`name` = 'Segmenti' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_segments`.`NAME`' WHERE `zz_modules`.`name` = 'Segmenti' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_modules`.`NAME`' WHERE `zz_modules`.`name` = 'Segmenti' AND `zz_views`.`name` = 'Modulo';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `zz_segments`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `zz_segments`.`id_module`
WHERE
    1=1
HAVING
    2=2
ORDER BY `zz_segments`.`NAME`,
    `zz_segments`.`id_module`" WHERE `name` = 'Segmenti';

    

-- Ottimizzazione query vista Fatture di vendita
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`banche`.`descrizione`' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Banca';
UPDATE `zz_modules` SET `options` = "SELECT
	|select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN( SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS descrizione FROM `co_banche` GROUP BY `co_banche`.`id`) AS banche ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN( SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_operations`.`op` = 'send-email' GROUP BY `zz_operations`.`id_record`, zz_operations.id_email) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
	LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
	LEFT JOIN( SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN( SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata') AND( `co_documenti`.`data` BETWEEN '2022-01-01' AND '2022-12-31 23:59:59') AND `numero_esterno` != '' GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento`, `data` HAVING COUNT(`numero_esterno`) > 1) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento` AND `dup`.`data` = `co_documenti`.`data`
WHERE
    1=1 AND `dir` = 'entrata' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `name` = 'Fatture di vendita';


-- Ottimizzazione query vista Attività
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`an_referenti`.`nome`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Referente';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_statiintervento`.`colore`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = '_bg_';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`in_tipiintervento`.`descrizione`' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Tipo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'tecnici_assegnati.nomi' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Tecnici assegnati';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'tecnici.nomi' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Tecnici';
UPDATE `zz_modules` SET `options` = "SELECT 
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT idintervento, SUM(prezzo_unitario*qta-sconto) AS ricavo_righe, SUM(costo_unitario*qta) AS costo_righe FROM `in_righe_interventi` GROUP BY idintervento) AS righe ON righe.`idintervento` = `in_interventi`.`id`
    LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`
    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR ', ') AS info, co_righe_documenti.original_document_id AS idintervento FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type = 'Modules\\\\Interventi\\\\Intervento' GROUP BY idintervento) AS fattura ON fattura.idintervento = in_interventi.id
	LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS nomi FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS tecnici_assegnati ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento` 
   	LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS nomi FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS tecnici ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Interventi' AND `zz_operations`.`op` = 'send-email' GROUP BY `zz_operations`.`id_record`) AS email ON email.id_record=in_interventi.id
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br />') AS descrizione, my_impianti_interventi.idintervento FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id = my_impianti_interventi.idimpianto GROUP BY my_impianti_interventi.idintervento) AS impianti ON impianti.idintervento = in_interventi.id
    LEFT JOIN (SELECT co_contratti.id, CONCAT(co_contratti.numero, ' del ', DATE_FORMAT(data_bozza, '%d/%m/%Y')) AS info FROM co_contratti) AS contratto ON contratto.id = in_interventi.id_contratto
    LEFT JOIN (SELECT co_preventivi.id, CONCAT(co_preventivi.numero, ' del ', DATE_FORMAT(data_bozza, '%d/%m/%Y')) AS info FROM co_preventivi) AS preventivo ON preventivo.id = in_interventi.id_preventivo
    LEFT JOIN (SELECT or_ordini.id, CONCAT(or_ordini.numero, ' del ', DATE_FORMAT(data, '%d/%m/%Y')) AS info FROM or_ordini) AS ordine ON ordine.id = in_interventi.id_ordine
    LEFT JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`
WHERE 
    1=1  |date_period(`orario_inizio`,`data_richiesta`)|
GROUP BY 
    `in_interventi`.`id`
HAVING 
    2=2
ORDER BY 
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `name` = 'Interventi';


-- Ottimizzazione query vista Pagamenti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`pagamenti`.`tipo`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Codice pagamento';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `co_pagamenti`
	LEFT JOIN(SELECT `fe_modalita_pagamento`.`codice`, CONCAT(`fe_modalita_pagamento`.`codice`, ' - ', `fe_modalita_pagamento`.`descrizione`) AS tipo FROM `fe_modalita_pagamento`) AS pagamenti ON `pagamenti`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe`   
WHERE
    1=1
GROUP BY
    `descrizione`, `id`
HAVING
    2=2" WHERE `name` = 'Pagamenti';


-- Ottimizzazione query vista Fatture di acquisto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'banche.descrizione' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Banca';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'conti.descrizione' WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Conto';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `co_documenti`
LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
LEFT JOIN (SELECT co_banche.id, CONCAT(`nome`, ' - ', `iban`) AS descrizione FROM `co_banche`) AS banche ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
LEFT JOIN (SELECT iddocumento, CONCAT(co_pianodeiconti3.descrizione) AS descrizione FROM co_righe_documenti INNER JOIN co_pianodeiconti3 ON co_pianodeiconti3.id = co_righe_documenti.idconto) AS conti ON conti.iddocumento = co_documenti.id
LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS righe ON `co_documenti`.`id` = `righe`.`iddocumento`
LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`idanagrafica` AS `anagrafica`, `id_segment` FROM `co_documenti` AS `d`
LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `id_segment`, `numero_documento`, `d`.`idanagrafica`) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`idanagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment`)
WHERE 
    1=1 
AND 
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)||date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    co_documenti.id
HAVING 
    2=2
ORDER BY 
    `co_documenti`.`data` DESC,
    CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = 'Fatture di acquisto';

-- Ottimizzazione query vista Checklists
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_checklists`.`id`' WHERE `zz_modules`.`name` = 'Checklists' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_checklists`.`NAME`' WHERE `zz_modules`.`name` = 'Checklists' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_modules`.`NAME`' WHERE `zz_modules`.`name` = 'Checklists' AND `zz_views`.`name` = 'Modulo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_plugins`.`NAME`' WHERE `zz_modules`.`name` = 'Checklists' AND `zz_views`.`name` = 'Plugin';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `zz_checklists`
    LEFT JOIN `zz_modules` ON `zz_checklists`.`id_module` = `zz_modules`.`id`
    LEFT JOIN `zz_plugins` ON `zz_checklists`.`id_plugin`=`zz_plugins`.`id`
WHERE
    1=1
HAVING
    2=2" WHERE `name` = 'Checklists';


-- Ottimizzazione query vista Contratti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'orecontratti.somma - IFNULL(tecnici.sommatecnici, 0)' WHERE `zz_modules`.`name` = 'Contratti' AND `zz_views`.`name` = 'Ore rimanenti';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN ( SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN ( SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br>') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN( SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id 
    LEFT JOIN( SELECT in_interventi.id_contratto, idintervento, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id GROUP BY in_interventi.id_contratto, idintervento) AS tecnici ON tecnici.id_contratto = co_contratti.id
WHERE
    1=1
    |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = '0000-00-00')|
HAVING 
    2=2" WHERE `name` = 'Contratti';


-- Ottimizzazione query vista Newsletter
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_newsletters`.`id`' WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_newsletters`.`NAME`' WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Nome';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates`.`NAME`' WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Template';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`totale`' WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Destinatari';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = "IF(completed_at IS NULL OR em_newsletters.state='DEV', 'No', CONCAT('Sì ', '(', DATE_FORMAT(completed_at, '%d/%m/%Y %H:%i:%s'),')'))" WHERE `zz_modules`.`name` = 'Newsletter' AND `zz_views`.`name` = 'Completato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `em_newsletters` 
    LEFT JOIN `em_templates` ON `em_newsletters`.`id_template` = `em_templates`.`id`
    LEFT JOIN (SELECT `id_newsletter`, COUNT(*) AS totale FROM `em_newsletter_receiver` GROUP BY  `id_newsletter`) AS riceventi ON `riceventi`.`id_newsletter` = `em_newsletters`.`id`
WHERE 
    1=1 AND `em_newsletters`.`deleted_at` IS NULL
HAVING 
    2=2" WHERE `name` = 'Newsletter';


-- Ottimizzazione query vista Coda di invio
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_emails`.`id`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`destinatari`.`nomi`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Destinatari';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_emails`.`subject`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Oggetto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_emails`.`content`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Contenuto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_templates`.`name`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Template';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_modules`.`title`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Modulo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_emails`.`sent_at`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Data invio';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`em_emails`.`failed_at`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Ultimo tentativo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_users`.`username`' WHERE `zz_modules`.`name` = 'Stato email' AND `zz_views`.`name` = 'Utente';
UPDATE `zz_modules` SET `options` = "SELECT 
    |select|
FROM 
    `em_emails`
    LEFT JOIN `em_templates` ON `em_templates`.`id` = `em_emails`.`id_template`
    INNER JOIN `zz_users` ON `zz_users`.`id` = `em_emails`.`created_by`
    LEFT JOIN (SELECT `id_email`, GROUP_CONCAT(`address` SEPARATOR '<br>') as `nomi` FROM `em_email_receiver` GROUP BY `id_email`)AS `destinatari` ON `destinatari`.`id_email` = `em_emails`.`id`
    LEFT JOIN `zz_modules` ON `zz_modules`.`id` = `em_templates`.`id_module`
WHERE 
    1=1 
AND 
    (`em_emails`.`created_at` BETWEEN '|period_start|' AND '|period_end|' OR `em_emails`.`sent_at` IS NULL)
HAVING 
    2=2
ORDER BY 
    `em_emails`.`created_at` DESC" WHERE `name` = 'Stato email';


-- Ottimizzazione query vista Giacenze sedi
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'categoria.nome' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Categoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'sottocategoria.nome' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Sottocategoria';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.ragione_sociale' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Fornitore';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(co_iva.percentuale IS NOT NULL, (mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * co_iva.percentuale / 100), mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * iva.perc / 100)' WHERE `zz_modules`.`name` = 'Giacenze sedi' AND `zz_views`.`name` = 'Prezzo vendita ivato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT JOIN (SELECT SUM(qta - qta_evasa) AS qta_impegnata, idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id WHERE idstatoordine IN(SELECT id FROM or_statiordine WHERE completato = 0) GROUP BY idarticolo) ordini ON ordini.idarticolo = mg_articoli.id
    LEFT JOIN (SELECT `idarticolo`, `idsede`, SUM(`qta`) AS `qta` FROM `mg_movimenti` WHERE `idsede` = '' GROUP BY `idarticolo`, `idsede`) movimenti ON `mg_articoli`.`id` = `movimenti`.`idarticolo`
    LEFT JOIN (SELECT id, nome AS nome FROM mg_categorie)AS categoria ON categoria.id= mg_articoli.id_categoria
    LEFT JOIN (SELECT id, nome AS nome FROM mg_categorie)AS sottocategoria ON sottocategoria.id=mg_articoli.id_sottocategoria
	LEFT JOIN (SELECT co_iva.percentuale AS perc, co_iva.id, zz_settings.nome FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore)AS iva ON iva.nome= 'Iva predefinita' 
WHERE 
    1=1 AND `mg_articoli`.`deleted_at` IS NULL 
HAVING
    2=2 AND `Q.tà` > 0
ORDER BY
    `descrizione`" WHERE `name` = 'Giacenze sedi';


-- Ottimizzazione query vista Listini
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'zz_modules.id' WHERE `zz_modules`.`name` = 'Listini' AND `zz_views`.`name` = '_link_module_';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'fornitore.codice' WHERE `zz_modules`.`name` = 'Listini' AND `zz_views`.`name` = 'Codice';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'fornitore.barcode' WHERE `zz_modules`.`name` = 'Listini' AND `zz_views`.`name` = 'Barcode';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    mg_prezzi_articoli
    INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = mg_prezzi_articoli.id_anagrafica
    INNER JOIN mg_articoli ON mg_articoli.id = mg_prezzi_articoli.id_articolo
    LEFT JOIN mg_categorie AS categoria ON mg_articoli.id_categoria = categoria.id
    LEFT JOIN mg_categorie AS sottocategoria ON mg_articoli.id_sottocategoria = sottocategoria.id
    LEFT JOIN zz_modules ON zz_modules.NAME= 'Articoli'
    LEFT JOIN (SELECT codice_fornitore AS codice, id_articolo, id_fornitore, barcode_fornitore AS barcode, deleted_at FROM mg_fornitore_articolo) AS fornitore ON mg_prezzi_articoli.id_articolo= fornitore.id_articolo AND mg_prezzi_articoli.id_anagrafica=fornitore.id_fornitore AND fornitore.deleted_at IS NULL
WHERE
    1=1 AND mg_articoli.deleted_at IS NULL AND an_anagrafiche.deleted_at IS NULL
HAVING
    2=2
ORDER BY
    an_anagrafiche.ragione_sociale" WHERE `name` = 'Listini';

-- Aggiunta tabella permessi segmenti
CREATE TABLE `zz_group_segment` ( `id_gruppo` INT NOT NULL , `id_segment` INT NOT NULL ); 

-- Aggiunti segmenti nei documenti
ALTER TABLE `zz_segments` ADD `is_sezionale` TINYINT(1) NOT NULL AFTER `autofatture`; 

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Preventivi'); 
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Preventivi'), 'Standard preventivi', '1=1', 'WHR', (SELECT `valore` FROM `zz_settings` WHERE `nome`='Formato codice preventivi'), '', '', '1', '0', '0', '0', '1', '0');

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Contratti'); 
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Contratti'), 'Standard contratti', '1=1', 'WHR', (SELECT `valore` FROM `zz_settings` WHERE `nome`='Formato codice contratti'), '', '', '1', '0', '0', '0', '1', '0');

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Ddt di acquisto'); 
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Ddt di acquisto'), 'Standard ddt in entrata', '1=1', 'WHR', '#', '', '', '1', '0', '0', '0', '1', '0');

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Ddt di vendita'); 
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Ddt di vendita'), 'Standard ddt in uscita', '1=1', 'WHR', (SELECT `valore` FROM `zz_settings` WHERE `nome`='Formato numero secondario ddt'), '', '', '1', '0', '0', '0', '1', '0');

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'); 
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'), 'Standard ordini cliente', '1=1', 'WHR', (SELECT `valore` FROM `zz_settings` WHERE `nome`='Formato numero secondario ordine'), '', '', '1', '0', '0', '0', '1', '0');

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'); 
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'), 'Standard ordini fornitore', '1=1', 'WHR', '#', '', '', '1', '0', '0', '0', '1', '0');

UPDATE `zz_segments` SET `predefined` = '0' WHERE `zz_segments`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Interventi'); 
UPDATE `zz_segments` SET `predefined` = '1', `is_sezionale` = '1', `name` = 'Standard attività', `pattern` = (SELECT `valore` FROM `zz_settings` WHERE `nome`='Formato codice attività') WHERE `zz_segments`.`name` = 'Tutti' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Interventi'); 

UPDATE `zz_segments` SET `is_sezionale` = '1' WHERE `zz_segments`.`id_module` IN(SELECT `id` FROM `zz_modules` WHERE `name` IN('Fatture di vendita', 'Fatture di acquisto')); 

-- Aggiunto campo id_segment nei documenti
ALTER TABLE `co_contratti` ADD `id_segment` INT NOT NULL; 
ALTER TABLE `dt_ddt` ADD `id_segment` INT NOT NULL; 
ALTER TABLE `co_preventivi` ADD `id_segment` INT NOT NULL; 
ALTER TABLE `or_ordini` ADD `id_segment` INT NOT NULL AFTER; 
ALTER TABLE `in_interventi` ADD `id_segment` INT NOT NULL AFTER;

-- Allineamento id_segment nei record già creati
UPDATE `co_contratti` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard contratti"); 
UPDATE `dt_ddt` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard ddt in entrata") WHERE `idtipoddt` = (SELECT `id` FROM `dt_tipiddt` WHERE `descrizione` = "Ddt in entrata"); 
UPDATE `dt_ddt` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard ddt in uscita") WHERE `idtipoddt` = (SELECT `id` FROM `dt_tipiddt` WHERE `descrizione` = "Ddt in uscita"); 
UPDATE `co_preventivi` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard preventivi"); 
UPDATE `in_interventi` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard attività"); 
UPDATE `or_ordini` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard ordini cliente") WHERE `idtipoordine` = (SELECT `id` FROM `or_tipiordine` WHERE `descrizione` = "Ordine cliente"); 
UPDATE `or_ordini` SET `id_segment` = (SELECT `id` FROM `zz_segments` WHERE `name` = "Standard ordini fornitore") WHERE `idtipoordine` = (SELECT `id` FROM `or_tipiordine` WHERE `descrizione` = "Ordine fornitore"); 

-- Aggiunto controllo id_segment in options moduli
UPDATE `zz_modules` SET `options` = 'SELECT \n    |select|\nFROM\n    `in_interventi`\n    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`\n    LEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`\n    LEFT JOIN (SELECT idintervento, SUM(prezzo_unitario*qta-sconto) AS ricavo_righe, SUM(costo_unitario*qta) AS costo_righe FROM `in_righe_interventi` GROUP BY idintervento) AS righe ON righe.`idintervento` = `in_interventi`.`id`\n    LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`\n    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`\n    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, \'<br />\',IF(an_sedi.telefono!=\'\',CONCAT(an_sedi.telefono,\'<br />\'),\'\'),IF(an_sedi.cellulare!=\'\',CONCAT(an_sedi.cellulare,\'<br />\'),\'\'),an_sedi.citta,IF(an_sedi.indirizzo!=\'\',CONCAT(\' - \',an_sedi.indirizzo),\'\')) AS info FROM an_sedi) AS sede_destinazione ON sede_destinazione.id = in_interventi.idsede_destinazione\n    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \', \') AS info, co_righe_documenti.original_document_id AS idintervento FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type = \'Modules\\\\Interventi\\\\Intervento\' GROUP BY idintervento) AS fattura ON fattura.idintervento = in_interventi.id\n	LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR \', \') AS nomi FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS tecnici_assegnati ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento` \n   	LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR \', \') AS nomi FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS tecnici ON `in_interventi`.`id` = `tecnici`.`idintervento`\n    LEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = \'Interventi\' AND `zz_operations`.`op` = \'send-email\' GROUP BY `zz_operations`.`id_record`) AS email ON email.id_record=in_interventi.id\n    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != \'\', CONCAT(\' - \', nome), \'\')) SEPARATOR \'<br />\') AS descrizione, my_impianti_interventi.idintervento FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id = my_impianti_interventi.idimpianto GROUP BY my_impianti_interventi.idintervento) AS impianti ON impianti.idintervento = in_interventi.id\n    LEFT JOIN (SELECT co_contratti.id, CONCAT(co_contratti.numero, \' del \', DATE_FORMAT(data_bozza, \'%d/%m/%Y\')) AS info FROM co_contratti) AS contratto ON contratto.id = in_interventi.id_contratto\n    LEFT JOIN (SELECT co_preventivi.id, CONCAT(co_preventivi.numero, \' del \', DATE_FORMAT(data_bozza, \'%d/%m/%Y\')) AS info FROM co_preventivi) AS preventivo ON preventivo.id = in_interventi.id_preventivo\n    LEFT JOIN (SELECT or_ordini.id, CONCAT(or_ordini.numero, \' del \', DATE_FORMAT(data, \'%d/%m/%Y\')) AS info FROM or_ordini) AS ordine ON ordine.id = in_interventi.id_ordine\n    LEFT JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`idtipointervento`\nWHERE \n    1=1 |segment(`in_interventi`.`id_segment`)| |date_period(`orario_inizio`,`data_richiesta`)|\nGROUP BY \n    `in_interventi`.`id`\nHAVING \n    2=2\nORDER BY \n    IFNULL(`orario_fine`, `data_richiesta`) DESC' WHERE `zz_modules`.`name` = 'Interventi';

UPDATE `zz_modules` SET `options` = 'SELECT |select|\nFROM `co_preventivi`\n    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`\n    LEFT JOIN (\n        SELECT `idpreventivo`,\n            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\n            SUM(`subtotale` - `sconto` + `iva`) AS `totale`\n        FROM `co_righe_preventivi`\n        GROUP BY `idpreventivo`\n    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`\n\nLEFT JOIN (SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \", \") AS info, co_righe_documenti.original_document_id AS idpreventivo FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type=\'Modules\\\\Preventivi\\\\Preventivo\' GROUP BY idpreventivo) AS fattura ON fattura.idpreventivo = co_preventivi.id\nWHERE 1=1 |segment(`co_preventivi`.`id_segment`)| |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = \'0000-00-00\')| AND default_revision = 1\nGROUP BY `co_preventivi`.`id`\nHAVING 2=2\nORDER BY `co_preventivi`.`id` DESC ' WHERE `zz_modules`.`name` = 'Preventivi';

UPDATE `zz_modules` SET `options` = 'SELECT\n    |select|\nFROM\n	`or_ordini`\n    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\n    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\n    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\n    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`\n    LEFT JOIN (\nSELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \', \') AS info, co_righe_documenti.original_document_id AS idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type=\'Modules\\Ordini\\Ordine\' GROUP BY idordine\n) AS fattura ON fattura.idordine = or_ordini.id\nLEFT JOIN (\nSELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\nFROM `zz_operations`\nINNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\nINNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\nINNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`\nWHERE `zz_modules`.`name` = \'Ordini cliente\' AND `zz_operations`.`op` = \'send-email\'\nGROUP BY `zz_operations`.`id_record`\n) AS `email` ON `email`.`id_record` = `or_ordini`.`id`\nWHERE\n    1=1 |segment(`or_ordini`.`id_segment`)| AND `dir` = \'entrata\'  |date_period(`or_ordini`.`data`)|\nHAVING\n    2=2\nORDER BY \n	`data` DESC, \n    CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';

UPDATE `zz_modules` SET `options` = 'SELECT\n    |select|\nFROM\n	`or_ordini`\n    LEFT JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`\n    LEFT JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`\n    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`\n    LEFT JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`\n    LEFT JOIN (\nSELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \', \') AS info, co_righe_documenti.original_document_id AS idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type=\'Modules\\Ordini\\Ordine\' GROUP BY idordine\n) AS fattura ON fattura.idordine = or_ordini.id\nLEFT JOIN (\nSELECT `zz_operations`.`id_email`, `zz_operations`.`id_record`\nFROM `zz_operations`\nINNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id`\nINNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id`\nINNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id`\nWHERE `zz_modules`.`name` = \'Ordini fornitore\' AND `zz_operations`.`op` = \'send-email\'\nGROUP BY `zz_operations`.`id_record`\n) AS `email` ON `email`.`id_record` = `or_ordini`.`id`\nWHERE\n    1=1 |segment(`or_ordini`.`id_segment`)| AND `dir` = \'uscita\' |date_period(`or_ordini`.`data`)|\nHAVING\n    2=2\nORDER BY \n	`data` DESC, \n    CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';

UPDATE `zz_modules` SET `options` = 'SELECT\n    |select|\nFROM\n    `dt_ddt`\nLEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`\nLEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`\nLEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`\nLEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`\nLEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`\nLEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`\nLEFT JOIN(\n    SELECT `idddt`,\n        SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\n        SUM(`subtotale` - `sconto` + `iva`) AS `totale`\n    FROM\n        `dt_righe_ddt`\n    GROUP BY\n        `idddt`\n) AS righe\nON\n    `dt_ddt`.`id` = `righe`.`idddt`\nLEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    \nWHERE\n    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = \'entrata\' |date_period(`data`)|\nHAVING\n    2=2\nORDER BY\n    `data` DESC,\n    CAST(`numero_esterno` AS UNSIGNED) DESC,\n    `dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di vendita';

UPDATE `zz_modules` SET `options` = 'SELECT\n    |select|\nFROM\n    `dt_ddt`\nLEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\nLEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`\nLEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`\nLEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`\nLEFT JOIN `an_anagrafiche` `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`\nLEFT JOIN `an_sedi` AS sedi ON `dt_ddt`.`idsede_partenza` = sedi.`id`\nLEFT JOIN `an_sedi` AS `sedi_destinazione`ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`\nLEFT JOIN(\n    SELECT `idddt`,\n        SUM(`subtotale` - `sconto`) AS `totale_imponibile`,\n        SUM(`subtotale` - `sconto` + `iva`) AS `totale`\n    FROM\n        `dt_righe_ddt`\n    GROUP BY\n        `idddt`\n) AS righe\nON\n    `dt_ddt`.`id` = `righe`.`idddt`\nLEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`    \nWHERE\n    1=1 |segment(`dt_ddt`.`id_segment`)| AND `dir` = \'uscita\' |date_period(`data`)|\nHAVING\n    2=2\nORDER BY\n    `data` DESC,\n    CAST(`numero_esterno` AS UNSIGNED) DESC,\n    `dt_ddt`.created_at DESC' WHERE `zz_modules`.`name` = 'Ddt di acquisto';

UPDATE `zz_modules` SET `options` = 'SELECT\n    |select|\nFROM\n    `co_contratti`\n    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\n    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`\n    LEFT JOIN ( SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`\n    LEFT JOIN ( SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != \'\', CONCAT(\' - \', nome), \'\')) SEPARATOR \'<br>\') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id\n    LEFT JOIN( SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = \'ore\' AND orecontratti.idcontratto = co_contratti.id \n    LEFT JOIN( SELECT in_interventi.id_contratto, idintervento, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id GROUP BY in_interventi.id_contratto, idintervento) AS tecnici ON tecnici.id_contratto = co_contratti.id\nWHERE\n    1=1 |segment(`co_contratti`.`id_segment`)|\n    |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = \'0000-00-00\')|\nHAVING \n    2=2' WHERE `zz_modules`.`name` = 'Contratti';

-- Eliminazione impostazioni maschere
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato numero secondario ddt';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato numero secondario ordine';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato codice attività';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato codice preventivi';
DELETE FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Formato codice contratti';

-- Aggiunta campi provvigione su righe promemoria
ALTER TABLE `co_righe_promemoria` ADD `provvigione` DECIMAL(15,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(15,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(15,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM('UNT','PRC') NOT NULL DEFAULT 'UNT' AFTER `provvigione_percentuale`; 

-- Ottimizzazione query vista Stampe
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'zz_modules.NAME' WHERE `zz_modules`.`name` = 'Stampe' AND `zz_views`.`name` = 'Modulo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'zz_prints.id' WHERE `zz_modules`.`name` = 'Stampe' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'zz_prints.title' WHERE `zz_modules`.`name` = 'Stampe' AND `zz_views`.`name` = 'Titolo';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
 FROM 
    `zz_prints`
    LEFT JOIN zz_modules ON zz_modules.id = zz_prints.id_module
WHERE 
    1=1 
AND 
    zz_prints.enabled=1 
HAVING 
    2=2" WHERE `name` = 'Stampe';


-- Ottimizzazione query vista Articoli
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF( co_iva.percentuale IS NOT NULL, (mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * co_iva.percentuale / 100), mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita*iva.perc/100)' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Prezzo vendita ivato';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `mg_articoli`
    LEFT JOIN an_anagrafiche ON mg_articoli.id_fornitore = an_anagrafiche.idanagrafica
    LEFT JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
    LEFT JOIN (SELECT SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS qta_impegnata, or_righe_ordini.idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id INNER JOIN or_statiordine ON or_ordini.idstatoordine = or_statiordine.id WHERE or_tipiordine.dir = 'entrata' AND or_righe_ordini.confermato = 1 AND or_statiordine.impegnato = 1 GROUP BY idarticolo) a ON a.idarticolo = mg_articoli.id
    LEFT JOIN (SELECT SUM(or_righe_ordini.qta - or_righe_ordini.qta_evasa) AS qta_ordinata, or_righe_ordini.idarticolo FROM or_righe_ordini INNER JOIN or_ordini ON or_righe_ordini.idordine = or_ordini.id INNER JOIN or_tipiordine ON or_ordini.idtipoordine = or_tipiordine.id INNER JOIN or_statiordine ON or_ordini.idstatoordine = or_statiordine.id WHERE or_tipiordine.dir = 'uscita' AND or_righe_ordini.confermato = 1 AND or_statiordine.impegnato = 1
    GROUP BY idarticolo) ordini_fornitore ON ordini_fornitore.idarticolo = mg_articoli.id
    LEFT JOIN mg_categorie ON mg_articoli.id_categoria = mg_categorie.id
    LEFT JOIN mg_categorie AS sottocategorie ON mg_articoli.id_sottocategoria = sottocategorie.id
    LEFT JOIN (SELECT co_iva.percentuale AS perc, co_iva.id, zz_settings.nome FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore)AS iva ON iva.nome= 'Iva predefinita' 
WHERE
    1=1 AND(`mg_articoli`.`deleted_at`) IS NULL
HAVING
    2=2
ORDER BY
    `mg_articoli`.`descrizione`" WHERE `name` = 'Articoli';


-- Ottimizzazione query vista Utenti e permessi
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`nome`' WHERE `zz_modules`.`name` = 'Utenti e permessi' AND `zz_views`.`name` = 'Gruppo';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`zz_groups`.`id`' WHERE `zz_modules`.`name` = 'Utenti e permessi' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`utenti`.`num`' WHERE `zz_modules`.`name` = 'Utenti e permessi' AND `zz_views`.`name` = 'N. utenti';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `zz_groups` 
    LEFT JOIN (SELECT `zz_users`.`id`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `id`) AS utenti ON `zz_groups`.`id`=`utenti`.`id`
WHERE 
    1=1
HAVING 
    2=2 
ORDER BY 
    `id`, 
    `nome` ASC" WHERE `name` = 'Utenti e permessi';


-- Ottimizzazione query vista Listini cliente
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_listini`.`id`' WHERE `zz_modules`.`name` = 'Listini cliente' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`mg_listini`.`id`' WHERE `zz_modules`.`name` = 'Listini cliente' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`articoli`.`num`' WHERE `zz_modules`.`name` = 'Listini cliente' AND `zz_views`.`name` = 'Articoli';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`anagrafiche`.`num`' WHERE `zz_modules`.`name` = 'Listini cliente' AND `zz_views`.`name` = 'Anagrafiche';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`utente`.`username`' WHERE `zz_modules`.`name` = 'Listini cliente' AND `zz_views`.`name` = 'Ultima modifica';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `mg_listini`
    LEFT JOIN (SELECT `mg_listini_articoli`.`id_listino`, COUNT(`id_listino`) AS num FROM `mg_listini_articoli` GROUP BY `id_listino`) AS articoli ON `mg_listini`.`id`=`articoli`.`id_listino`
    LEFT JOIN (SELECT `an_anagrafiche`.`id_listino`, COUNT(`id_listino`) AS num FROM `an_anagrafiche` GROUP BY `id_listino`) AS anagrafiche ON `mg_listini`.`id`=`anagrafiche`.`id_listino`
    LEFT JOIN (SELECT `zz_users`.`id`, `zz_users`.`username` FROM `zz_users` INNER JOIN (SELECT `id_utente`, `id_record` FROM `zz_operations` INNER JOIN `zz_modules` ON `zz_modules`.`NAME` = 'Listini cliente' ORDER BY `id_utente` DESC LIMIT 0, 1) AS `id`) AS `utente` ON `utente`.`id` = `mg_listini`.`id`
WHERE 
    1=1 
HAVING 
    2=2" WHERE `name` = 'Listini cliente';


-- Allineamento query vista Piani di sconto/maggiorazione
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `mg_piani_sconto` 
WHERE 
    1=1
HAVING 
    2=2 
ORDER BY 
    `nome`" WHERE `name` = 'Piani di sconto/maggiorazione';

-- Aggiunti widget listini clienti
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES (NULL, 'Listini attivi', 'stats', (SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'controller_top', 'col-md-6', 'SELECT COUNT(mg_listini.id) AS dato FROM mg_listini WHERE 1=1 AND attivo=1 HAVING 2=2', '#4ccc4c', 'fa fa-check', '', '', 'javascript', '', 'Listini attivi', '1', '1', NULL);

INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES (NULL, 'Listini scaduti', 'stats', (SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'controller_top', 'col-md-6', 'SELECT COUNT(mg_listini.id) AS dato FROM mg_listini WHERE 1=1 AND attivo=0 HAVING 2=2', '#c62f2a', 'fa fa-times', '', '', 'javascript', '', 'Listini scaduti', '1', '2', NULL);