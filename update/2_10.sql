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
UPDATE `in_interventi` SET `id_contratto` = null WHERE `id_contratto` = 0;
UPDATE `in_interventi` SET `id_ordine` = null WHERE `id_ordine` = 0;
UPDATE `in_interventi` SET `idcontratto` = null WHERE `idcontratto` = 0;

UPDATE `in_interventi_tecnici` SET `id_tipo` = null WHERE `id_tipo` = 0;

ALTER TABLE `co_preventivi` CHANGE `idpagamento` `idpagamento` INT NULL DEFAULT NULL;
UPDATE `co_preventivi` SET `idpagamento` = null WHERE `idpagamento` = 0;
ALTER TABLE `co_preventivi` ADD CONSTRAINT `co_preventivi_ibfk_3` FOREIGN KEY (`idpagamento`) REFERENCES `co_pagamenti`(`id`) ON DELETE SET NULL;