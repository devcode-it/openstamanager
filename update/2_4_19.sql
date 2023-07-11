-- Aggiunta impostazione conto anticipi
INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES (NULL, '000011', 'Anticipo fornitori', '8', '', '100.00');

INSERT INTO `co_pianodeiconti3` (`id`, `numero`, `descrizione`, `idpianodeiconti2`, `dir`, `percentuale_deducibile`) VALUES (NULL, '000011', 'Anticipo clienti', '2', '', '100.00');

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto anticipo clienti', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`='Anticipo clienti'), 'query=SELECT id, CONCAT_WS('' - '', numero, descrizione) AS descrizione FROM co_pianodeiconti3', '1', 'Fatturazione', NULL, NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Conto anticipo fornitori', (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`='Anticipo fornitori'), 'query=SELECT id, CONCAT_WS('' - '', numero, descrizione) AS descrizione FROM co_pianodeiconti3', '1', 'Fatturazione', NULL, NULL);

-- Allineamento tipo di campo con NULL se non valorizzato
ALTER TABLE `co_movimenti` CHANGE `idanagrafica` `id_anagrafica` INT(11) NULL;

-- Rimozione valori a 0
UPDATE `co_movimenti` SET `id_anagrafica` = NULL WHERE `id_anagrafica` = 0;

-- Spostamento conti transitori su stato patrimoniale
UPDATE `co_pianodeiconti2` SET `idpianodeiconti1` = 1 WHERE `descrizione` = 'Conti transitori';

-- Aggiunta quantità multipla
ALTER TABLE `mg_articoli` ADD `qta_multipla` DECIMAL(15,6) NOT NULL DEFAULT '0' AFTER `threshold_qta`;

-- Correzione riferimenti Articoli Interventi per Fatture
UPDATE `co_righe_documenti` SET `co_righe_documenti`.`original_id` = (SELECT `id` FROM `in_righe_interventi` WHERE `co_righe_documenti`.`original_id` = `in_righe_interventi`.`old_id`) WHERE `co_righe_documenti`.`original_type` = 'Modules\\Interventi\\Components\\Articolo';

--
-- Aggiornamento dati Interventi per Fatture
--
-- Collegamento Articoli
UPDATE `co_righe_documenti` INNER JOIN `in_righe_interventi` ON `co_righe_documenti`.`idintervento` = `in_righe_interventi`.`idintervento` AND `co_righe_documenti`.`descrizione` = `in_righe_interventi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `in_righe_interventi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `in_righe_interventi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Interventi\\Components\\Articolo' WHERE `co_righe_documenti`.`idarticolo` != 0 AND `co_righe_documenti`.`original_type` IS NULL;

-- Collegamento Sconti
UPDATE `co_righe_documenti` INNER JOIN `in_righe_interventi` ON `co_righe_documenti`.`idintervento` = `in_righe_interventi`.`idintervento` AND `co_righe_documenti`.`descrizione` = `in_righe_interventi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `in_righe_interventi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `in_righe_interventi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Interventi\\Components\\Sconto' WHERE `co_righe_documenti`.`is_sconto` != 0 AND `co_righe_documenti`.`original_type` IS NULL;

-- Collegamento Descrizioni
UPDATE `co_righe_documenti` INNER JOIN `in_righe_interventi` ON `co_righe_documenti`.`idintervento` = `in_righe_interventi`.`idintervento` AND `co_righe_documenti`.`descrizione` = `in_righe_interventi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `in_righe_interventi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `in_righe_interventi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Interventi\\Components\\Descrizione' WHERE `co_righe_documenti`.`is_descrizione` != 0 AND `co_righe_documenti`.`original_type` IS NULL;

-- Collegamento Righe
UPDATE `co_righe_documenti` INNER JOIN `in_righe_interventi` ON `co_righe_documenti`.`idintervento` = `in_righe_interventi`.`idintervento` AND `co_righe_documenti`.`descrizione` = `in_righe_interventi`.`descrizione` AND `co_righe_documenti`.`idarticolo` = `in_righe_interventi`.`idarticolo` SET `co_righe_documenti`.`original_id` = `in_righe_interventi`.`id`, `co_righe_documenti`.`original_type` = 'Modules\\Interventi\\Components\\Riga' WHERE `co_righe_documenti`.`original_id` IS NULL AND `co_righe_documenti`.`original_type` IS NULL;

-- Aggiunta colonna qta_evasa per Interventi
ALTER TABLE `in_righe_interventi` ADD `qta_evasa` decimal(15, 6) NOT NULL AFTER `qta`;

-- Allineo campo qta_evasa (appena aggiunto) con la qta delle righe interventi inseriti in fattura
UPDATE `in_righe_interventi` SET `in_righe_interventi`.`qta_evasa` = `in_righe_interventi`.`qta` WHERE  `in_righe_interventi`.`idintervento` IN (
    SELECT DISTINCT( `idintervento`) FROM `co_righe_documenti` WHERE `idintervento` IS NOT NULL
);

-- Aggiornamento date vuote su movimenti
UPDATE `mg_movimenti` SET `data`=`created_at` WHERE `data` IS NULL;

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Movimenti'), 'Sede', 'IF( mg_movimenti.idsede_azienda=0, ''Sede legale'', an_sedi.nomesede )', '4', '1', '0', '0', NULL, NULL, '1', '0', '1');

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`summable` = 1 WHERE `zz_modules`.`name` = 'Movimenti' AND `zz_views`.`name` = 'Quantità';