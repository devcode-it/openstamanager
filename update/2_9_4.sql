-- Fix: rendere il capitale sociale opzionale (NULL se non specificato)
ALTER TABLE `an_anagrafiche`
    MODIFY `capitale_sociale` DECIMAL(15,6) NULL DEFAULT NULL;

-- Aggiunta {tipo} come variabile per l'impostazione "Descrizione personalizzata in fatturazione"
UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variabili utilizzabili: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 1;

UPDATE `zz_settings` INNER JOIN `zz_settings_lang` ON `zz_settings`.`id` = `zz_settings_lang`.`id_record` SET `zz_settings_lang`.`help` = "Variables availables: {email} {numero} {ragione_sociale} {richiesta} {descrizione} {data} {data richiesta} {data fine intervento} {id_anagrafica} {stato} {tipo}" WHERE `zz_settings`.`nome` = 'Descrizione personalizzata in fatturazione' AND `id_lang` = 2;

-- Data inizio e fine competenza per le righe
ALTER TABLE `co_righe_contratti` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `dt_righe_ddt` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `co_righe_documenti` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `co_righe_preventivi` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `in_righe_interventi` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `or_righe_ordini` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;
ALTER TABLE `co_righe_promemoria` ADD `data_inizio_competenza` DATE NULL, ADD `data_fine_competenza` DATE NULL;

-- Impostazione format = 1 per tutti i campi data nella tabella zz_views
UPDATE `zz_views` SET `format` = 1 WHERE (`name` LIKE '%data%' OR `name` LIKE '%Data%');

UPDATE `an_anagrafiche` SET `idiva_vendite` = null WHERE `idiva_vendite` = 0;
UPDATE `an_anagrafiche` SET `idiva_acquisti` = null WHERE `idiva_acquisti` = 0;
UPDATE `an_anagrafiche` SET `idpagamento_vendite` = null WHERE `idpagamento_vendite` = 0;
UPDATE `an_anagrafiche` SET `idpagamento_acquisti` = null WHERE `idpagamento_acquisti` = 0;
UPDATE `an_anagrafiche` SET `id_nazione` = null WHERE `id_nazione` = 0;
UPDATE `an_anagrafiche` SET `id_piano_sconto_vendite` = null WHERE `id_piano_sconto_vendite` = 0;
UPDATE `an_anagrafiche` SET `id_piano_sconto_acquisti` = null WHERE `id_piano_sconto_acquisti` = 0;
UPDATE `an_anagrafiche` SET `id_ritenuta_acconto_vendite` = null WHERE `id_ritenuta_acconto_vendite` = 0;
UPDATE `an_anagrafiche` SET `id_ritenuta_acconto_acquisti` = null WHERE `id_ritenuta_acconto_acquisti` = 0;
UPDATE `an_anagrafiche` SET `idbanca_vendite` = null WHERE `idbanca_vendite` = 0;
UPDATE `an_anagrafiche` SET `idbanca_acquisti` = null WHERE `idbanca_acquisti` = 0;
UPDATE `an_anagrafiche` SET `id_provenienza` = null WHERE `id_provenienza` = 0;
UPDATE `an_anagrafiche` SET `idtipointervento_default` = null WHERE `idtipointervento_default` = 0;
UPDATE `an_anagrafiche` SET `id_dichiarazione_intento_default` = null WHERE `id_dichiarazione_intento_default` = 0;
UPDATE `an_anagrafiche` SET `capitale_sociale` = null WHERE `capitale_sociale` = 0;
UPDATE `an_anagrafiche` SET `codicerea` = null WHERE `codicerea` = '';
UPDATE `an_anagrafiche` SET `riferimento_amministrazione` = null WHERE `riferimento_amministrazione` = '';
UPDATE `an_anagrafiche` SET `n_alboartigiani` = null WHERE `n_alboartigiani` = '';
UPDATE `an_anagrafiche` SET `gaddress` = null WHERE `gaddress` = '';
UPDATE `an_anagrafiche` SET `lat` = null WHERE `lat` = 0;
UPDATE `an_anagrafiche` SET `lng` = null WHERE `lng` = 0;
UPDATE `an_anagrafiche` SET `codice_destinatario` = null WHERE `codice_destinatario` = '';
UPDATE `an_anagrafiche` SET `enable_newsletter` = null WHERE `enable_newsletter` = 0;

UPDATE `an_sedi` SET `id_nazione` = null WHERE `id_nazione` = 0;

UPDATE `co_documenti` SET `id_banca_azienda` = null WHERE `id_banca_azienda` = 0;
UPDATE `co_documenti` SET `id_banca_controparte` = null WHERE `id_banca_controparte` = 0;

UPDATE `co_movimenti` SET `id_anagrafica` = null WHERE `id_anagrafica` = 0;

UPDATE `co_scadenziario` SET `id_banca_azienda` = null WHERE `id_banca_azienda` = 0;
UPDATE `co_scadenziario` SET `id_banca_controparte` = null WHERE `id_banca_controparte` = 0;

UPDATE `zz_files` SET `id_module` = null WHERE `id_module` = 0;
UPDATE `zz_files` SET `id_plugin` = null WHERE `id_plugin` = 0;

UPDATE `zz_otp_tokens` SET `id_utente` = null WHERE `id_utente` = 0;

UPDATE `an_sedi` SET `nome` = null WHERE `nome` = '';
UPDATE `an_sedi` SET `descrizione` = null WHERE `descrizione` = '';
UPDATE `an_sedi` SET `targa` = null WHERE `targa` = '';

UPDATE `co_preventivi` SET `idporto` = null WHERE `idporto` = 0;
UPDATE `co_preventivi` SET `idpagamento` = null WHERE `idpagamento` = 0;

UPDATE `or_ordini` SET `idspedizione` = null WHERE `idspedizione` = 0;
UPDATE `or_ordini` SET `idporto` = null WHERE `idporto` = 0;
UPDATE `or_ordini` SET `idvettore` = null WHERE `idvettore` = 0;
UPDATE `or_ordini` SET `idrivalsainps` = null WHERE `idrivalsainps` = 0;
UPDATE `or_ordini` SET `idritenutaacconto` = null WHERE `idritenutaacconto` = 0;

UPDATE `dt_ddt` SET `idspedizione` = null WHERE `idspedizione` = 0;
UPDATE `dt_ddt` SET `idcausalet` = null WHERE `idcausalet` = 0;
UPDATE `dt_ddt` SET `idvettore` = null WHERE `idvettore` = 0;
UPDATE `dt_ddt` SET `idporto` = null WHERE `idporto` = 0;
UPDATE `dt_ddt` SET `idaspettobeni` = null WHERE `idaspettobeni` = 0;
UPDATE `dt_ddt` SET `idrivalsainps` = null WHERE `idrivalsainps` = 0;
UPDATE `dt_ddt` SET `idritenutaacconto` = null WHERE `idritenutaacconto` = 0;

UPDATE `co_contratti` SET `id_categoria` = null WHERE `id_categoria` = 0;
UPDATE `co_contratti` SET `id_sottocategoria` = null WHERE `id_sottocategoria` = 0;

UPDATE `in_interventi` SET `idclientefinale` = null WHERE `idclientefinale` = 0;
UPDATE `in_interventi` SET `id_preventivo` = null WHERE `id_preventivo` = 0;
UPDATE `in_interventi` SET `id_ordine` = null WHERE `id_ordine` = 0;

ALTER TABLE `co_preventivi` CHANGE `idpagamento` `idpagamento` INT NULL DEFAULT NULL;
UPDATE `co_preventivi` SET `idpagamento` = null WHERE `idpagamento` NOT IN (SELECT `id` FROM `co_pagamenti`);
ALTER TABLE `co_preventivi` ADD CONSTRAINT `co_preventivi_ibfk_3` FOREIGN KEY (`idpagamento`) REFERENCES `co_pagamenti`(`id`) ON DELETE SET NULL;

-- Allineamento vista Fatture di acquisto
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.|lang|)
    LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.|lang|)
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`nome`, ' - ', `iban`) AS `descrizione` FROM `co_banche`) AS `banche` ON `banche`.`id` = `co_documenti`.`id_banca_azienda`
    LEFT JOIN (SELECT `iddocumento`, GROUP_CONCAT(`co_pianodeiconti3`.`descrizione`) AS `descrizione` FROM `co_righe_documenti` INNER JOIN `co_pianodeiconti3` ON `co_pianodeiconti3`.`id` = `co_righe_documenti`.`idconto` GROUP BY iddocumento) AS `conti` ON `conti`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`iva`) AS `iva` FROM `co_righe_documenti` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT COUNT(`d`.`id`) AS `conteggio`, IF(`d`.`numero_esterno` = '', `d`.`numero`, `d`.`numero_esterno`) AS `numero_documento`, `d`.`idanagrafica` AS `anagrafica`, `d`.`id_segment`, YEAR(`d`.`data`) AS `anno` FROM `co_documenti` AS `d`
    LEFT JOIN `co_tipidocumento` AS `d_tipo` ON `d`.`idtipodocumento` = `d_tipo`.`id` WHERE 1=1 AND `d_tipo`.`dir` = 'uscita' AND('|period_start|' <= `d`.`data` AND '|period_end|' >= `d`.`data` OR '|period_start|' <= `d`.`data_competenza` AND '|period_end|' >= `d`.`data_competenza`) GROUP BY `d`.`id_segment`, `numero_documento`, `d`.`idanagrafica`, YEAR(`d`.`data`)) AS `d` ON (`d`.`numero_documento` = IF(`co_documenti`.`numero_esterno` = '',`co_documenti`.`numero`,`co_documenti`.`numero_esterno`) AND `d`.`anagrafica` = `co_documenti`.`idanagrafica` AND `d`.`id_segment` = `co_documenti`.`id_segment` AND `d`.`anno` = YEAR(`co_documenti`.`data`))
WHERE
    1=1
AND
    `dir` = 'uscita' |segment(`co_documenti`.`id_segment`)| |date_period(custom, '|period_start|' <= `co_documenti`.`data` AND '|period_end|' >= `co_documenti`.`data`, '|period_start|' <= `co_documenti`.`data_competenza` AND '|period_end|' >= `co_documenti`.`data_competenza` )|
GROUP BY
    `co_documenti`.`id`, `d`.`conteggio`
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC, CAST(IF(`co_documenti`.`numero` = '', `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED) DESC" WHERE `name` = 'Fatture di acquisto';

-- Ordinamento vista Modelli prima nota
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `co_movimenti_modelli` WHERE 1=1 GROUP BY `idmastrino` HAVING 2=2 ORDER BY `co_movimenti_modelli`.`nome`' WHERE `zz_modules`.`name` = 'Modelli prima nota';

-- Ordinamento vista IVA
UPDATE `zz_modules` SET `options` = '\nSELECT\n |select|\nFROM\n `co_iva`\n LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND |lang|)\nWHERE\n 1=1 AND `deleted_at` IS NULL\nHAVING\n 2=2 \nORDER BY\n `co_iva_lang`.`title`' WHERE `zz_modules`.`name` = 'Iva';

-- Fix per stampe contabili
ALTER TABLE `co_stampecontabili` CHANGE `dir` `dir` VARCHAR(255) NULL;
ALTER TABLE `co_stampecontabili` CHANGE `id_sezionale` `id_sezionale` INT NULL;
UPDATE `co_stampecontabili` SET `dir` = NULL WHERE `dir` = '';
UPDATE `co_stampecontabili` SET `id_sezionale` = NULL WHERE `id_sezionale` = 0;