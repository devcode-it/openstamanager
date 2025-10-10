-- Ordini cliente
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `or_ordini`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS agente ON `or_ordini`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`) > 0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine` = `righe_da_evadere`.`idordine`
    INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT \'Fattura \', `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`, `co_righe_documenti`.`original_document_id` AS `idordine` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = \'ModulesOrdiniOrdine\' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`idordine` = `or_ordini`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT \'DDT \', `dt_ddt`.`numero_esterno` SEPARATOR \', \') AS `info`, `dt_righe_ddt`.`original_document_id` AS `idddt` FROM `dt_ddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt` WHERE `original_document_type` = \'ModulesOrdiniOrdine\' GROUP BY `original_document_id`) AS `ddt` ON `ddt`.`idddt` = `or_ordini`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ordini cliente\') AND `zz_operations`.`op` = \'send-email\' GROUP BY `em_emails`.`id_record`) AS email ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1
    |segment(`or_ordini`.`id_segment`)|
    AND `dir` = \'entrata\'
    |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini cliente';

-- Ordini fornitore
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `or_ordini`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`) > 0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine` = `righe_da_evadere`.`idordine`
    INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT co_documenti.numero_esterno SEPARATOR \', \') AS info, co_righe_documenti.original_document_id AS idordine FROM co_documenti INNER JOIN co_righe_documenti ON co_documenti.id = co_righe_documenti.iddocumento WHERE original_document_type = \'Modules\\\\Ordini\\\\Ordine\' GROUP BY idordine, original_document_id) AS fattura ON fattura.idordine = or_ordini.id
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ordini fornitore\') AND `zz_operations`.`op` = \'send-email\' GROUP BY `em_emails`.`id_record`) AS email ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1
    |segment(`or_ordini`.`id_segment`)|
    AND `dir` = \'uscita\'
    |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC, CAST(`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Ordini fornitore';

-- Ddt in uscita
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `dt_ddt`
    LEFT JOIN `an_anagrafiche` ON `dt_ddt`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
    LEFT JOIN `dt_causalet` ON `dt_ddt`.`idcausalet` = `dt_causalet`.`id`
    LEFT JOIN `dt_causalet_lang` ON (`dt_causalet_lang`.`id_record` = `dt_causalet`.`id` AND `dt_causalet_lang`.|lang|)
    LEFT JOIN `dt_spedizione` ON `dt_ddt`.`idspedizione` = `dt_spedizione`.`id`
    LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione_lang`.`id_record` = `dt_spedizione`.`id` AND `dt_spedizione_lang`.|lang|)
    LEFT JOIN `an_anagrafiche` AS `vettori` ON `dt_ddt`.`idvettore` = `vettori`.`idanagrafica`
    LEFT JOIN `an_sedi` AS `sedi` ON `dt_ddt`.`idsede_partenza` = `sedi`.`id`
    LEFT JOIN `an_sedi` AS `sedi_destinazione` ON `dt_ddt`.`idsede_destinazione` = `sedi_destinazione`.`id`
    LEFT JOIN (SELECT `idddt`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `dt_righe_ddt` GROUP BY `idddt`) AS righe ON `dt_ddt`.`id` = `righe`.`idddt`
    LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
    LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt_lang`.`id_record` = `dt_statiddt`.`id` AND `dt_statiddt_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT \'Fattura \', `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`, `co_righe_documenti`.`original_document_id` AS `idddt` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = \'Modules\\\\DDT\\\\DDT\' GROUP BY `original_document_id`) AS `fattura` ON `fattura`.`idddt` = `dt_ddt`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Ddt in uscita\') AND `zz_operations`.`op` = \'send-email\' GROUP BY `id_record`) AS `email` ON `email`.`id_record` = `dt_ddt`.`id`
WHERE
    1=1
    |segment(`dt_ddt`.`id_segment`)|
    AND `dir` = \'entrata\'
    |date_period(`data`)|
HAVING
    2=2
ORDER BY
    `data` DESC,
    CAST(`numero_esterno` AS UNSIGNED) DESC, `dt_ddt`.`created_at` DESC' WHERE `zz_modules`.`name` = 'Ddt in uscita';

-- Miglioramento query per modulo Utenti e permessi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `zz_groups`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_users`.`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id` = `utenti`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_users`.`id`) AS num FROM `zz_users` WHERE `zz_users`.`enabled` = 1 GROUP BY `idgruppo`) AS utenti_abilitati ON `zz_groups`.`id` = `utenti_abilitati`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_tokens`.`id`) AS num FROM `zz_users` INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente` WHERE `zz_tokens`.`enabled` = 1 GROUP BY `idgruppo`) AS api_abilitate ON `zz_groups`.`id` = `api_abilitate`.`idgruppo`
    LEFT JOIN (SELECT `zz_modules_lang`.`title`, `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)) AS `module` ON `module`.`id` = `zz_groups`.`id_module_start`
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `id`, `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi';

    -- Correzione riferimento per colonna email inviata nei moduli
-- Preventivi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `co_preventivi`
    LEFT JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato` = `co_statipreventivi`.`id`
    LEFT JOIN `co_statipreventivi_lang` ON (`co_statipreventivi`.`id` = `co_statipreventivi_lang`.`id_record` AND co_statipreventivi_lang.id_lang = |lang|)
    LEFT JOIN (SELECT `idpreventivo`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_preventivi` GROUP BY `idpreventivo`) AS righe ON `co_preventivi`.`id` = `righe`.`idpreventivo`
    LEFT JOIN (SELECT `an_anagrafiche`.`idanagrafica`, `an_anagrafiche`.`ragione_sociale` AS nome FROM `an_anagrafiche`) AS agente ON `agente`.`idanagrafica` = `co_preventivi`.`idagente`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR \', \') AS `info`, `co_righe_documenti`.`original_document_id` AS `idpreventivo` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = \'ModulesPreventiviPreventivo\' GROUP BY `idpreventivo`, `original_document_id`) AS `fattura` ON `fattura`.`idpreventivo` = `co_preventivi`.`id`
    LEFT JOIN (SELECT COUNT(em_emails.id) AS emails, em_emails.id_record FROM em_emails INNER JOIN zz_operations ON zz_operations.id_email = em_emails.id WHERE id_module IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Preventivi\') AND `zz_operations`.`op` = \'send-email\' GROUP BY em_emails.id_record) AS `email` ON `email`.`id_record` = `co_preventivi`.`id`
WHERE
    1=1
    |segment(`co_preventivi`.`id_segment`)|
    |date_period(custom,\'|period_start|\' >= `data_bozza` AND \'|period_start|\' <= `data_conclusione`,\'|period_end|\' >= `data_bozza` AND \'|period_end|\' <= `data_conclusione`,`data_bozza` >= \'|period_start|\' AND `data_bozza` <= \'|period_end|\',`data_conclusione` >= \'|period_start|\' AND `data_conclusione` <= \'|period_end|\',`data_bozza` >= \'|period_start|\' AND `data_conclusione` = NULL)|
    AND `default_revision` = 1
GROUP BY
    `co_preventivi`.`id`,
    `fattura`.`info`
HAVING
    2=2
ORDER BY
    `co_preventivi`.`data_bozza` DESC, `numero` ASC' WHERE `zz_modules`.`name` = 'Preventivi';

-- Fatture di vendita
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento` FROM `co_movimenti` WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND co_tipidocumento_lang.|lang|)
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, \' - \', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record` AND `fe_stati_documento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(`em_emails`.`id`) AS `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = \'Fatture di vendita\') AND `zz_operations`.`op` = \'send-email\' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND co_pagamenti_lang.|lang|)
    LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN (SELECT `id` FROM `co_tipidocumento` WHERE `dir` = \'entrata\') AND `numero_esterno` != \'\' |date_period(`co_documenti`.`data`)| GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento` HAVING COUNT(`numero_esterno`) > 1) AS dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1=1
    AND `dir` = \'entrata\'
    |segment(`co_documenti`.`id_segment`)|
    |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC' WHERE `zz_modules`.`name` = 'Fatture di vendita';

-- Aggiunta {tipo} come variabile per l'impostazione "Descrizione personalizzata in fatturazione"
UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variabili utilizzabili: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 1;

UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variables availables: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 2;


UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `query` = "IF(giacenze.stato_giacenza = 0, '', IF(giacenze.stato_giacenza > 0, '#CCFFCC', '#ec5353'))" WHERE `zz_views`.`name` = '_bg_' AND `zz_modules`.`name` = 'Articoli';

-- fix: invio via mail token otp
ALTER TABLE `em_emails` CHANGE `created_by` `created_by` INT(11) NULL;

-- fix: dimensioni QR Code
UPDATE `zz_prints` SET `options` = '{\"width\": 40, \"height\": 30, \"format\": [40, 30], \"margins\": {\"top\": 1,\"bottom\": 0,\"left\": 0,\"right\": 0}}' WHERE `zz_prints`.`id` = 56;


-- Anagrafiche: ottimizzazione query 
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `an_anagrafiche`
    LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
    LEFT JOIN `an_relazioni_lang` ON (`an_relazioni_lang`.`id_record` = `an_relazioni`.`id` AND `an_relazioni_lang`.|lang|)
    LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.|lang|)
    LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(`nomesede` SEPARATOR \', \') AS nomi FROM `an_sedi` GROUP BY `idanagrafica`) AS sedi ON `an_anagrafiche`.`idanagrafica` = `sedi`.`idanagrafica`
    LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(`nome` SEPARATOR \', \') AS nomi FROM `an_referenti` GROUP BY `idanagrafica`) AS referenti ON `an_anagrafiche`.`idanagrafica` = `referenti`.`idanagrafica`
    LEFT JOIN (
        SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS `nome`
        FROM `co_pagamenti`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    ) AS pagvendita ON `an_anagrafiche`.`idpagamento_vendite` = `pagvendita`.`id`
    LEFT JOIN (
        SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS `nome`
        FROM `co_pagamenti`
        LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti_lang`.`id_record` = `co_pagamenti`.`id` AND `co_pagamenti_lang`.|lang|)
    ) AS pagacquisto ON `an_anagrafiche`.`idpagamento_acquisti` = `pagacquisto`.`id`
    LEFT JOIN `an_zone` ON `an_anagrafiche`.`idzona` = `an_zone`.`id`
WHERE
    1=1
    AND `an_anagrafiche`.`deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`, `pagvendita`.`nome`, `pagacquisto`.`nome`
HAVING
    2=2
ORDER BY
    `ragione_sociale`' WHERE `zz_modules`.`name` = 'Anagrafiche';

-- Anagrafiche: colonna Zone con nome - descrizione
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id`
SET `zz_views`.`query` = 'CONCAT_WS('' - '', an_zone.nome, an_zone.descrizione)'
WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Zone';

-- Marche: parent = 0 diventa NULL
ALTER TABLE `zz_marche` CHANGE `parent` `parent` INT NULL DEFAULT NULL;
UPDATE `zz_marche` SET `parent` = NULL WHERE `parent` = 0;
UPDATE `mg_articoli` SET `id_marca` = NULL WHERE `id_marca` = 0;

UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `zz_marche`
WHERE
    1=1
    AND
    `parent` IS NULL
HAVING
    2=2
ORDER BY
    `name`' WHERE `zz_modules`.`name` = 'Marche';

-- Indici per categoria e sottocategoria
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_categoria` (`id_categoria`);
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_sottocategoria` (`id_sottocategoria`);

-- Indici per marca e modello
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_marca` (`id_marca`);
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_modello` (`id_modello`);

-- Indice per iva di vendita
ALTER TABLE `mg_articoli` ADD INDEX(`idiva_vendita`);

-- Rimozione indice doppio
ALTER TABLE `mg_articoli` DROP INDEX `id_combinazione`;
