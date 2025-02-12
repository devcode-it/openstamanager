-- Aggiunto flag non conteggiare e campo note in tipologie interventi
ALTER TABLE `in_tipiintervento` ADD `non_conteggiare` TINYINT NOT NULL;
ALTER TABLE `in_tipiintervento` ADD `note` TEXT NOT NULL;

-- Aggiunto tipo attività in contratti
ALTER TABLE `co_contratti` ADD `idtipointervento` INT NOT NULL;

-- Aggiunta colonna Zone in Anagrafiche
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Zone', 'an_zone.nome', '18', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Zone' AND `id_module` = @id_module), 'Zone'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Zone' AND `id_module` = @id_module), 'Zone');

-- Allineamento vista Anagrafiche
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `an_anagrafiche`
    LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
    LEFT JOIN `an_relazioni_lang` ON (`an_relazioni`.`id` = `an_relazioni_lang`.`id_record` AND `an_relazioni_lang`.|lang|)
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.|lang|)
    LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(`nomesede` SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY `idanagrafica`) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`
    LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(`nome` SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY `idanagrafica`) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`
    LEFT JOIN (SELECT `co_pagamenti_lang`.`title`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|))AS pagvendita ON IF(`an_anagrafiche`.`idpagamento_vendite`>0,`an_anagrafiche`.`idpagamento_vendite`= `pagvendita`.`id`,'')
    LEFT JOIN (SELECT `co_pagamenti_lang`.`title` AS nome, `co_pagamenti`.`id` FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|))AS pagacquisto ON IF(`an_anagrafiche`.`idpagamento_acquisti`>0,`an_anagrafiche`.`idpagamento_acquisti`= `pagacquisto`.`id`,'')
    LEFT JOIN `an_zone` ON `an_anagrafiche`.`idzona`=`an_zone`.`id`
WHERE
    1=1 AND `an_anagrafiche`.`deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`, `pagvendita`.`nome`, `pagacquisto`.`nome`
HAVING
    2=2
ORDER BY
    TRIM(`ragione_sociale`)" WHERE `name` = 'Anagrafiche';

-- Aggiunta colonna Zone in Attività
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Interventi';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Zone', 'an_zone.nome', '18', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Interventi';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Zone' AND `id_module` = @id_module), 'Zone'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Zone' AND `id_module` = @id_module), 'Zone');

-- Allineamento vista Attività
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `idintervento`, SUM(`prezzo_unitario`*`qta`-`sconto`) AS `ricavo_righe`, SUM(`costo_unitario`*`qta`) AS `costo_righe` FROM `in_righe_interventi` GROUP BY `idintervento`) AS `righe` ON `righe`.`idintervento` = `in_interventi`.`id`
    INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
    LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.|lang|)
    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />',IF(`an_sedi`.`telefono`!='',CONCAT(`an_sedi`.`telefono`,'<br />'),''),IF(`an_sedi`.`cellulare`!='',CONCAT(`an_sedi`.`cellulare`,'<br />'),''),`an_sedi`.`citta`,IF(`an_sedi`.`indirizzo`!='',CONCAT(' - ',`an_sedi`.`indirizzo`),'')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `in_interventi`.`idsede_destinazione`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idintervento` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = 'Modules\\\\Interventi\\\\Intervento' GROUP BY `idintervento`, `original_document_id`) AS `fattura` ON `fattura`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS `tecnici_assegnati` ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS `tecnici` ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (SELECT COUNT(`id`) as emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `name` = 'Interventi' AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS `descrizione`, `my_impianti_interventi`.`idintervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto` GROUP BY `my_impianti_interventi`.`idintervento`) AS `impianti` ON `impianti`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `co_contratti`.`id`, CONCAT(`co_contratti`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_contratti`) AS `contratto` ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (SELECT `co_preventivi`.`id`, CONCAT(`co_preventivi`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_preventivi`) AS `preventivo` ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (SELECT `or_ordini`.`id`, CONCAT(`or_ordini`.`numero`, ' del ', DATE_FORMAT(`data`, '%d/%m/%Y')) AS `info` FROM `or_ordini`) AS `ordine` ON `ordine`.`id` = `in_interventi`.`id_ordine`
    INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(' ', `zz_files`.`name`) as name, `zz_files`.`id_record` FROM `zz_files` INNER JOIN `zz_modules` ON `zz_files`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) WHERE `zz_modules`.`name` = 'Interventi' GROUP BY id_record) AS files ON `files`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tags`.`id_intervento`, GROUP_CONCAT( DISTINCT `name` SEPARATOR ', ') AS `nomi` FROM `in_tags` INNER JOIN `in_interventi_tags` ON `in_interventi_tags`.`id_tag` = `in_tags`.`id` GROUP BY `in_interventi_tags`.`id_intervento`) AS `tags` ON `in_interventi`.`id` = `tags`.`id_intervento`
    LEFT JOIN `an_zone` ON `an_anagrafiche`.`idzona`=`an_zone`.`id`
WHERE 
    1=1 |segment(`in_interventi`.`id_segment`)| |date_period(`orario_inizio`,`data_richiesta`)|
GROUP BY 
    `in_interventi`.`id`
HAVING 
    2=2
ORDER BY 
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `zz_modules`.`name` = 'Interventi';

-- Aggiunte impostazioni per raggruppamento fatturazione massiva
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Raggruppamento fatturazione massiva ddt', '', 'list[cliente,sede]', '1', 'Ddt', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva ddt'), 'Raggruppamento fatturazione massiva ddt', ''), 
('2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva ddt'), 'Massive ddt billing grouping', '');

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Raggruppamento fatturazione massiva attività', '', 'list[cliente,sede]', '1', 'Attività', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva attività'), 'Raggruppamento fatturazione massiva attività', ''), 
('2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva attività'), 'Massive activities billing grouping', '');

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Raggruppamento fatturazione massiva contratti', '', 'list[cliente,sede]', '1', 'Contratti', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva contratti'), 'Raggruppamento fatturazione massiva contratti', ''), 
('2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva contratti'), 'Massive contracts billing grouping', '');

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Raggruppamento fatturazione massiva ordini', '', 'list[cliente,sede]', '1', 'Ordini', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva ordini'), 'Raggruppamento fatturazione massiva ordini', ''), 
('2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva ordini'), 'Massive orders billing grouping', '');

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Raggruppamento fatturazione massiva preventivi', '', 'list[cliente,sede]', '1', 'Preventivi', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva preventivi'), 'Raggruppamento fatturazione massiva preventivi', ''), 
('2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Raggruppamento fatturazione massiva preventivi'), 'Massive quotes billing grouping', '');

-- Aggiunta colonna Da rinnovare in Contratti
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Da rinnovare', "IF(rinnovabile,IF(DATEDIFF(data_conclusione,NOW()) BETWEEN 0 AND giorni_preavviso_rinnovo,'Sì',IF(DATEDIFF(data_conclusione,NOW()) <= 0,'Sì','No')),'No')", '19', '1', '0', '0', '0', '', '', '1', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Da rinnovare' AND `id_module` = @id_module), 'Da rinnovare'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Da rinnovare' AND `id_module` = @id_module), 'Renewable');

UPDATE `zz_views` SET `query` = "IF(`co_contratti`.`rinnovabile`=1, 'Sì', 'No')" WHERE `zz_views`.`name` = "Rinnovabile" AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = "Contratti");

-- Gestione spese d'incasso
ALTER TABLE `co_pagamenti` ADD `descrizione_incasso` TEXT NOT NULL, ADD `importo_fisso_incasso` DECIMAL(15,6) NOT NULL, ADD `importo_percentuale_incasso` DECIMAL(15,6) NOT NULL;

ALTER TABLE `co_documenti` ADD `id_riga_spese_incasso` INT NULL;

INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES
("Conto predefinito per le spese d'incasso", (SELECT `id` FROM `co_pianodeiconti3` WHERE `descrizione`='Ricavi vari'), "query=SELECT id, descrizione FROM co_pianodeiconti3 WHERE idpianodeiconti2=(SELECT id FROM co_pianodeiconti2 WHERE descrizione='Ricavi')", "1", "Fatturazione", NULL, 0);

SELECT @id_record := `id` FROM `zz_settings` WHERE `nome` = "Conto predefinito per le spese d'incasso";
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', @id_record, "Conto predefinito per le spese d'incasso", ''), 
('2', @id_record, 'Default account for collection costs', '');

-- Gestione meta title dei moduli
ALTER TABLE `zz_modules_lang` ADD `meta_title` VARCHAR(255) NOT NULL AFTER `title`;

-- Aggiunta colonna per gestione cartella allegati alternativa nei moduli
ALTER TABLE `zz_modules` ADD `attachments_directory` VARCHAR(255) NOT NULL DEFAULT '' AFTER `directory`; 

UPDATE `zz_modules` SET `attachments_directory` = `directory`;
UPDATE `zz_modules` SET `attachments_directory` = 'fatture/vendite' WHERE `zz_modules`.`name` = 'Fatture di vendita'; 

-- Aggiunta colonna per gestione cartella allegati alternativa nei plugin
ALTER TABLE `zz_plugins` ADD `attachments_directory` VARCHAR(255) NOT NULL DEFAULT '' AFTER `directory`; 

UPDATE `zz_plugins` SET `attachments_directory` = `directory`;

-- Aggiunta impostazione per generare nomi casuali agli allegati
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `is_user_setting`) VALUES ('Rendi casuale il nome dei file allegati', '1', 'boolean', '1', 'Generali', NULL, '0');
INSERT INTO `zz_settings_lang` (`id_lang`, `id_record`, `title`, `help`) VALUES 
('1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Rendi casuale il nome dei file allegati'), 'Rendi casuale il nome dei file allegati', ''), 
('2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Rendi casuale il nome dei file allegati'), 'Randomize attachments name', '');

-- Firma in stampa preventivi solo totali
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Preventivi';
UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"show-only-total\":true, \"images\": true, \"last-page-footer\": true }' WHERE `zz_prints`.`id_module` = @id_module AND `zz_prints`.`name` = 'Preventivo (solo totale)';

-- Rimozione campo deprecato
ALTER TABLE `mg_articoli` DROP `threshold_qta`;
DELETE FROM `zz_views` WHERE `zz_views`.`name` = '_bg_' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');

-- Agginta sede partenza in preventivi
ALTER TABLE `co_preventivi` CHANGE `idsede` `idsede_destinazione` INT NOT NULL; 
ALTER TABLE `co_preventivi` ADD `idsede_partenza` INT NOT NULL AFTER `idsede_destinazione`; 

-- Agginta sede partenza in contratti
ALTER TABLE `co_contratti` CHANGE `idsede` `idsede_destinazione` INT NOT NULL; 
ALTER TABLE `co_contratti` ADD `idsede_partenza` INT NOT NULL AFTER `idsede_destinazione`;

-- Agginta sede partenza in ordini
ALTER TABLE `or_ordini` CHANGE `idsede` `idsede_destinazione` INT NOT NULL; 
ALTER TABLE `or_ordini` ADD `idsede_partenza` INT NOT NULL AFTER `idsede_destinazione`; 

-- Aggiunta colonna Note interne in Fatture di vendita
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`co_documenti`.`note_aggiuntive`", '23', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Aggiunta colonna Note interne in DDT in entrata
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ddt in entrata';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`dt_ddt`.`note_aggiuntive`", '17', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ddt in entrata';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Aggiunta colonna Note interne in DDT in uscita
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ddt in uscita';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`dt_ddt`.`note_aggiuntive`", '19', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ddt in uscita';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Aggiunta colonna Note interne in Ordini fornitore
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`or_ordini`.`note_aggiuntive`", '15', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ordini fornitore';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Aggiunta colonna Note interne in Ordini cliente
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES 
(@id_module, 'Note interne', "`or_ordini`.`note_aggiuntive`", '17', '1', '0', '0', '0', '', '', '0', '0', '0');

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = @id_module), 'Notes');

-- Aggiunto sezionale in stampe definitive
ALTER TABLE `co_stampecontabili` ADD `id_sezionale` INT NOT NULL;

UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"last-page-footer\":true, \"show-only-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = 'Preventivo (solo totale)'; 