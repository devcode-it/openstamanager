-- Fix query viste Utenti e permessi
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `zz_groups` 
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`
WHERE 
    1=1
HAVING 
    2=2 
ORDER BY 
    `id`, 
    `nome` ASC" WHERE `name` = 'Utenti e permessi';


-- Aggiunta campo Pagamento predefinito in vista Anagrafiche
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
('2', 'Pagamento cliente', '`pagvendita`.`nome`', '15', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
('2', 'Pagamento fornitore', '`pagacquisto`.`nome`', '16', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

UPDATE `zz_modules` SET `options` = "SELECT 
|select|
FROM
    `an_anagrafiche`
LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nomesede SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY idanagrafica) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nome SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY idanagrafica) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`
LEFT JOIN (SELECT `co_pagamenti`.`descrizione`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti`)AS pagvendita ON IF(`an_anagrafiche`.`idpagamento_vendite`>0,`an_anagrafiche`.`idpagamento_vendite`= `pagvendita`.`id`,'')
LEFT JOIN (SELECT `co_pagamenti`.`descrizione`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti`)AS pagacquisto ON IF(`an_anagrafiche`.`idpagamento_acquisti`>0,`an_anagrafiche`.`idpagamento_acquisti`= `pagacquisto`.`id`,'')
WHERE
    1=1 AND `deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`, `pagvendita`.`nome`, `pagacquisto`.`nome`
HAVING
    2=2
ORDER BY
    TRIM(`ragione_sociale`)" WHERE `name` = 'Anagrafiche';

-- Aggiunta descrizione codice natura N7
SELECT @codice := MAX(CAST(codice AS UNSIGNED))+1 FROM co_iva WHERE deleted_at IS NULL;
INSERT INTO `co_iva` (`id`, `descrizione`, `percentuale`, `indetraibile`, `esente`, `dicitura`, `codice_natura_fe`, `deleted_at`, `codice`, `esigibilita`, `default`) VALUES (NULL, 'Regime OSS, D.Lgs. 83/2021', '0.00', '0.00', '1', NULL, 'N7', NULL, @codice, 'I', '1'); 

-- Aggiunta campo agente in Preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
('13', 'Agente', '`agente`.`nome`', '11', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

UPDATE `zz_modules` SET `options` = "SELECT 
|select|
FROM 
    `co_preventivi`
    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT JOIN (
        SELECT `idpreventivo`,
            SUM(`subtotale` - `sconto`) AS `totale_imponibile`,
            SUM(`subtotale` - `sconto` + `iva`) AS `totale`
        FROM `co_righe_preventivi`
        GROUP BY `idpreventivo`
    ) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
    LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`)AS agente ON `agente`.`idanagrafica`=`co_preventivi`.`idagente`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR ', ') AS info, co_righe_documenti.original_document_id AS idpreventivo FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type='Modules\\Preventivi\\Preventivo' GROUP BY idpreventivo) AS fattura ON fattura.idpreventivo = co_preventivi.id
WHERE 
    1=1 |segment(`co_preventivi`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = '0000-00-00')| AND default_revision = 1
GROUP BY 
    `co_preventivi`.`id`
HAVING 
    2=2
ORDER BY 
    `co_preventivi`.`id` DESC" WHERE `name` = 'Preventivi';

-- Aggiunta colonna Inviato in DDT in uscita
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
('26', 'icon_Inviato', "IF(`email`.`id_email` IS NOT NULL, 'fa fa-envelope text-success', '')", '13', '1', '0', '0', '0', NULL, NULL, '0', '0', '0');

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
    LEFT JOIN (SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
    LEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_operations`.`op` = 'send-email' GROUP BY `zz_operations`.`id_record`) AS `email` ON `email`.`id_record` = `dt_ddt`.`id`
WHERE
    1=1 AND `dir` = 'entrata' AND (`data` BETWEEN '2023-01-01' AND '2023-12-31 23:59:59')
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC,
    `dt_ddt`.created_at DESC" WHERE `name` = 'Ddt di vendita';


-- Fix problemi integrità db se si aggiorna (o si è aggiornato in passato) da una versione precedente alla 2.4.28
ALTER TABLE `an_mansioni` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `an_mansioni` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `em_mansioni_template` CHANGE `created_at` `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `em_mansioni_template` CHANGE `updated_at` `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Fix query vista Pagamenti
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`pagamenti`.`tipo`' WHERE `zz_modules`.`name` = 'Pagamenti' AND `zz_views`.`name` = 'Codice pagamento';
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `co_pagamenti`
	LEFT JOIN(SELECT `fe_modalita_pagamento`.`codice`, CONCAT(`fe_modalita_pagamento`.`codice`, ' - ', `fe_modalita_pagamento`.`descrizione`) AS tipo FROM `fe_modalita_pagamento`) AS pagamenti ON `pagamenti`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe`
WHERE
    1=1
GROUP BY
    `descrizione`
HAVING
    2=2" WHERE `name` = 'Pagamenti';

-- Aggiunta impostazione Numero massimo widget per colonna
INSERT INTO zz_settings(nome, valore, tipo, editable, sezione) VALUES ('Numero massimo Widget per riga','6','list[1,2,3,4,6]','1','Generali');

-- Modifica widget Magazzino
UPDATE `zz_widgets` SET `query` = 'SELECT REPLACE(REPLACE(REPLACE(FORMAT(SUM(qta),2), ",", "#"), ".", ","), "#", ".") AS dato FROM mg_articoli WHERE qta>0 AND deleted_at IS NULL AND servizio=0 AND 1=1' WHERE `zz_widgets`.`name` = 'Articoli in magazzino';
UPDATE `zz_widgets` SET `text` = 'Unità' WHERE `zz_widgets`.`name` = 'Articoli in magazzino';
UPDATE `zz_widgets` SET `text` = 'Valore' WHERE `zz_widgets`.`name` = 'Valore magazzino';

-- Aggiunta colonna Banca azienda in Scadenzario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ('18', 'Banca azienda', '`banca`.`nome`', '18', '1', '0', '0', '0', NULL, NULL, '0', '0', '0'); 

UPDATE `zz_modules` SET `options` = "
SELECT
    |select| 
FROM 
    `co_scadenziario`
    LEFT JOIN `co_documenti` ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `co_banche`.`nome`AS nome, `co_banche`.`id` FROM `co_banche` INNER JOIN `co_documenti` ON `co_documenti`.`id_banca_azienda` = `co_banche`.`id`)AS banca ON `co_scadenziario`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_scadenziario`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN (SELECT `zz_operations`.`id_email`, `zz_operations`.`id_record` FROM `zz_operations` INNER JOIN `em_emails` ON `zz_operations`.`id_email` = `em_emails`.`id` INNER JOIN `em_templates` ON `em_emails`.`id_template` = `em_templates`.`id` INNER JOIN `zz_modules` ON `zz_operations`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_operations`.`op` = 'send-email' GROUP BY `zz_operations`.`id_record`) AS `email` ON `email`.`id_record` = `co_scadenziario`.`id`
WHERE 
    1=1 AND (`co_statidocumento`.`descrizione` IS NULL OR `co_statidocumento`.`descrizione` IN('Emessa','Parzialmente pagato','Pagato'))
HAVING
    2=2
ORDER BY 
    `scadenza` ASC" WHERE `name` = 'Scadenzario';

-- Aggiunta dichiarazione d'intento predefinita
ALTER TABLE `an_anagrafiche` ADD `id_dichiarazione_intento_default` INT NULL AFTER `idtipointervento_default`, ADD FOREIGN KEY (`id_dichiarazione_intento_default`) REFERENCES `co_dichiarazioni_intento`(`id`);

-- Aggiunta impostazione Movimentazione articoli da fatture di acquisto
INSERT INTO zz_settings(nome, valore, tipo, editable, sezione) VALUES ('Movimenta magazzino da fatture di acquisto','1','boolean','1','Fatturazione Elettronica');

-- Permetto valore null per numero_esterno di co_documenti
ALTER TABLE `co_documenti` CHANGE `numero_esterno` `numero_esterno` VARCHAR(100) NULL DEFAULT NULL; 

-- Aggiunta impostazione Posizione della valuta
INSERT INTO zz_settings(nome, valore, tipo, editable, sezione) VALUES ('Posizione del simbolo valuta','Dopo','list[Prima,Dopo]','1','Generali');

-- Miglioria segmenti scadenzario
UPDATE `zz_segments` SET `name` = 'Scadenzario completo' WHERE `zz_segments`.`name` = 'Scadenziaro completo'; 
UPDATE `zz_segments` SET `clause` = "(`co_scadenziario`.`scadenza` BETWEEN '|period_start|' AND '|period_end|' AND codice_tipo_documento_fe NOT IN ('TD16', 'TD17', 'TD18', 'TD19', 'TD20', 'TD21', 'TD22', 'TD23', 'TD26', 'TD27', 'TD28'))" WHERE `zz_segments`.`name` = 'Scadenzario completo';
INSERT INTO `zz_segments` (`id_module`, `name`, `clause`, `position`, `pattern`,`note`, `dicitura_fissa`,`predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario'), 'Scadenzario autofatture', "(codice_tipo_documento_fe IN ('TD16', 'TD17', 'TD18', 'TD19', 'TD20', 'TD21', 'TD22', 'TD23', 'TD26', 'TD27', 'TD28'))", 'WHR', '####', '', '', 0, 0, 0, 0, 0); 