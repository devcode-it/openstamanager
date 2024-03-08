-- Allineo per i movimenti relativi alle fatture di vendita, la data del movimento con la data del documento
UPDATE `co_movimenti` SET `co_movimenti`.`data` = `co_movimenti`.`data_documento` WHERE `iddocumento` IN (SELECT `co_documenti`.`id` FROM `co_documenti` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` WHERE `co_tipidocumento`.`dir` = 'entrata' ) AND `primanota` = 0;

-- Allineo per le fatture di vendita, la data_competenza con data emissione del documento
UPDATE `co_documenti` SET `co_documenti`.`data_competenza` = `co_documenti`.`data`  WHERE `co_documenti`.`idtipodocumento` IN (SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` WHERE `co_tipidocumento`.`dir` = 'entrata');

-- Elimino data_documento per co_documenti
ALTER TABLE `co_movimenti` DROP `data_documento`;

-- Allineamento idarticolo nelle tabelle delle righe
ALTER TABLE `co_righe_documenti` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_documenti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_preventivi` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_preventivi` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_contratti` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_contratti` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `dt_righe_ddt` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `dt_righe_ddt` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `or_righe_ordini` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `or_righe_ordini` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `or_righe_ordini` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `or_righe_ordini` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

ALTER TABLE `co_righe_promemoria` CHANGE `idarticolo` `idarticolo` INT(11) NULL;
UPDATE `co_righe_promemoria` SET `idarticolo` = NULL WHERE `idarticolo` = 0;

-- Aggiunta risorse API dedicate all'applicazione
DELETE FROM `zz_api_resources` WHERE `version` = 'app-v1';
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
-- Login
(NULL, 'app-v1', 'create', 'login', 'API\\App\\v1\\Login', '1'),
-- Clienti
(NULL, 'app-v1', 'retrieve', 'clienti', 'API\\App\\v1\\Clienti', '1'),
(NULL, 'app-v1', 'retrieve', 'clienti-cleanup', 'API\\App\\v1\\Clienti', '1'),
(NULL, 'app-v1', 'retrieve', 'cliente', 'API\\App\\v1\\Clienti', '1'),
-- Tecnici
(NULL, 'app-v1', 'retrieve', 'tecnici', 'API\\App\\v1\\Tecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tecnici-cleanup', 'API\\App\\v1\\Tecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tecnico', 'API\\App\\v1\\Tecnici', '1'),
-- Sedi
(NULL, 'app-v1', 'retrieve', 'sedi', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'sedi-cleanup', 'API\\App\\v1\\Sedi', '1'),
(NULL, 'app-v1', 'retrieve', 'sede', 'API\\App\\v1\\Sedi', '1'),
-- Referenti
(NULL, 'app-v1', 'retrieve', 'referenti', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'referenti-cleanup', 'API\\App\\v1\\Referenti', '1'),
(NULL, 'app-v1', 'retrieve', 'referente', 'API\\App\\v1\\Referenti', '1'),
-- Impianti
(NULL, 'app-v1', 'retrieve', 'impianti', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianti-cleanup', 'API\\App\\v1\\Impianti', '1'),
(NULL, 'app-v1', 'retrieve', 'impianto', 'API\\App\\v1\\Impianti', '1'),
-- Stati degli interventi
(NULL, 'app-v1', 'retrieve', 'stati-intervento', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'stati-intervento-cleanup', 'API\\App\\v1\\StatiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'stato-intervento', 'API\\App\\v1\\StatiIntervento', '1'),
-- Tipi degli interventi
(NULL, 'app-v1', 'retrieve', 'tipi-intervento', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipi-intervento-cleanup', 'API\\App\\v1\\TipiIntervento', '1'),
(NULL, 'app-v1', 'retrieve', 'tipo-intervento', 'API\\App\\v1\\TipiIntervento', '1'),
-- Articoli
(NULL, 'app-v1', 'retrieve', 'articoli', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'articoli-cleanup', 'API\\App\\v1\\Articoli', '1'),
(NULL, 'app-v1', 'retrieve', 'articolo', 'API\\App\\v1\\Articoli', '1'),
-- Interventi
(NULL, 'app-v1', 'retrieve', 'interventi', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'interventi-cleanup', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'retrieve', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'create', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'update', 'intervento', 'API\\App\\v1\\Interventi', '1'),
(NULL, 'app-v1', 'delete', 'intervento', 'API\\App\\v1\\Interventi', '1'),
-- Sessioni degli interventi
(NULL, 'app-v1', 'retrieve', 'sessioni', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'sessioni-cleanup', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'delete', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'create', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
(NULL, 'app-v1', 'update', 'sessione', 'API\\App\\v1\\SessioniInterventi', '1'),
-- Righe degli interventi
(NULL, 'app-v1', 'retrieve', 'righe-interventi', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'righe-interventi-cleanup', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'create', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'update', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
(NULL, 'app-v1', 'delete', 'riga-intervento', 'API\\App\\v1\\RigheInterventi', '1'),
-- Aliquote IVA
(NULL, 'app-v1', 'retrieve', 'aliquote-iva', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquote-iva-cleanup', 'API\\App\\v1\\AliquoteIva', '1'),
(NULL, 'app-v1', 'retrieve', 'aliquota-iva', 'API\\App\\v1\\AliquoteIva', '1'),
-- Impostazioni (non modificabili)
(NULL, 'app-v1', 'retrieve', 'impostazioni', 'API\\App\\v1\\Impostazioni', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazioni-cleanup', 'API\\App\\v1\\Impostazioni', '1'),
(NULL, 'app-v1', 'retrieve', 'impostazione', 'API\\App\\v1\\Impostazioni', '1'),
-- Contratti
(NULL, 'app-v1', 'retrieve', 'contratti', 'API\\App\\v1\\Contratti', '1'),
(NULL, 'app-v1', 'retrieve', 'contratti-cleanup', 'API\\App\\v1\\Contratti', '1'),
(NULL, 'app-v1', 'retrieve', 'contratto', 'API\\App\\v1\\Contratti', '1'),
-- Preventivi
(NULL, 'app-v1', 'retrieve', 'preventivi', 'API\\App\\v1\\Preventivi', '1'),
(NULL, 'app-v1', 'retrieve', 'preventivi-cleanup', 'API\\App\\v1\\Preventivi', '1'),
(NULL, 'app-v1', 'retrieve', 'preventivo', 'API\\App\\v1\\Preventivi', '1'),
-- Tariffe dei tecnici
(NULL, 'app-v1', 'retrieve', 'tariffe-tecnici', 'API\\App\\v1\\TariffeTecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffe-tecnici-cleanup', 'API\\App\\v1\\TariffeTecnici', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffa-tecnico', 'API\\App\\v1\\TariffeTecnici', '1'),
-- Tariffe relative ai contratti
(NULL, 'app-v1', 'retrieve', 'tariffe-contratti', 'API\\App\\v1\\TariffeContratti', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffe-contratti-cleanup', 'API\\App\\v1\\TariffeContratti', '1'),
(NULL, 'app-v1', 'retrieve', 'tariffa-contratto', 'API\\App\\v1\\TariffeContratti', '1'),
-- Allegati
(NULL, 'app-v1', 'retrieve', 'allegati-interventi', 'API\\App\\v1\\AllegatiInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'allegati-interventi-cleanup', 'API\\App\\v1\\AllegatiInterventi', '1'),
(NULL, 'app-v1', 'retrieve', 'allegato-intervento', 'API\\App\\v1\\AllegatiInterventi', '1'),
(NULL, 'app-v1', 'create', 'allegato-intervento', 'API\\App\\v1\\AllegatiInterventi', '1'),
-- Email di rapportino intervento
(NULL, 'app-v1', 'retrieve', 'email-rapportino', 'API\\App\\v1\\RapportinoIntervento', '1'),
(NULL, 'app-v1', 'create', 'email-rapportino', 'API\\App\\v1\\RapportinoIntervento', '1');

-- Impostazioni relative all'applicazione
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Google Maps API key per Tecnici', '', 'string', '1', 'Applicazione', 1, ''),
(NULL, 'Mostra prezzi', '1', 'boolean', '1', 'Applicazione', 2, ''),
(NULL, 'Sincronizza solo i Clienti per cui il Tecnico ha lavorato in passato', '1', 'boolean', '1', 'Applicazione', 3, ''),
(NULL, 'Mesi per lo storico delle Attività', '6', 'integer', '1', 'Applicazione', 3, '');

-- Impostazioni relative gli stati delle Attività
UPDATE `zz_settings` SET `sezione` = 'Attività' WHERE `sezione` = 'Interventi';
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Stato dell''attività alla chiusura', (SELECT idstatointervento AS id FROM in_statiintervento WHERE codice = 'OK'), 'query=SELECT idstatointervento AS id, descrizione AS text FROM in_statiintervento WHERE is_completato = 1', '1', 'Attività', 1, 'Stato in cui spostare l''attitivà a seguito della chiusura'),
(NULL, 'Stato dell''attività dopo la firma', (SELECT idstatointervento AS id FROM in_statiintervento WHERE codice = 'OK'), 'query=SELECT idstatointervento AS id, descrizione AS text FROM in_statiintervento WHERE is_completato = 1', '1', 'Attività', 2, 'Stato in cui spostare l''attitivà dopo la firma del cliente');

-- Aggiunta risorsa per il download degli allegati
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES (NULL, 'v1', 'retrieve', 'allegato', 'API\\Common\\Allegato', '1');

-- Modifica di MyImpianti in Impianti
UPDATE `zz_modules` SET `name` = 'Impianti', `title` = IF(`title` = 'MyImpianti', 'Impianti', `title`), `directory` = 'impianti' WHERE `zz_modules`.`name` = 'MyImpianti';

-- Rimozione stato di Interventi "Chiamata" se inutilizzato
DELETE FROM `in_statiintervento` WHERE `codice` = 'CALL' AND `descrizione` = 'Chiamata' AND
   NOT EXISTS(SELECT `idstatointervento` FROM `in_interventi` WHERE `in_interventi`.`idstatointervento` = `in_statiintervento`.`idstatointervento`) ;

-- Rimozione aliquote iva non usate
UPDATE `co_iva` SET deleted_at = NOW() WHERE `descrizione` LIKE 'Scorporo%';

-- Modifica mg_causali_movimenti
ALTER TABLE `mg_causali_movimenti` ADD `tipo_movimento` ENUM('carico', 'scarico', 'spostamento') NOT NULL DEFAULT 'spostamento';
UPDATE `mg_causali_movimenti` SET `tipo_movimento` = 'carico' WHERE `movimento_carico` = 1;
UPDATE `mg_causali_movimenti` SET `tipo_movimento` = 'scarico' WHERE `movimento_carico` = 0;
ALTER TABLE `mg_causali_movimenti` DROP `movimento_carico`;

INSERT INTO `mg_causali_movimenti` (`id`, `nome`, `descrizione`, `tipo_movimento`) VALUES
(NULL, 'Spostamento', 'Spostamento manuale', 'spostamento');

-- Aggiunta tabella in_interventi_tecnici_assegnati per la gestione dei tecnici assegnati alle attività
CREATE TABLE IF NOT EXISTS `in_interventi_tecnici_assegnati` (
    `id_intervento` int(11) NOT NULL,
    `id_tecnico` int(11) NOT NULL,
    FOREIGN KEY (`id_intervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_tecnico`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Fix contenuti delle date (NULL al posto di 0000-00-00)
ALTER TABLE `mg_movimenti` CHANGE `data` `data` date;
ALTER TABLE `my_impianti` CHANGE `data` `data` date;
ALTER TABLE `an_anagrafiche` CHANGE `data_nascita` `data_nascita` date;
ALTER TABLE `in_interventi` CHANGE `data_richiesta` `data_richiesta` DATETIME;
ALTER TABLE `in_interventi` CHANGE `firma_data` `firma_data` DATETIME;

-- SET sql_mode = '';
UPDATE `mg_movimenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `my_impianti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `an_anagrafiche` SET `data_nascita` = NULL WHERE `data_nascita` = '0000-00-00' OR `data_nascita` = '0000-00-00 00:00:00';
UPDATE `in_interventi` SET `data_richiesta` = NULL WHERE `data_richiesta` = '0000-00-00' OR `data_richiesta` = '0000-00-00 00:00:00';
UPDATE `in_interventi` SET `firma_data` = NULL WHERE `firma_data` = '0000-00-00' OR `firma_data` = '0000-00-00 00:00:00';
UPDATE `updates` SET `id` = NULL WHERE `id` = '0000-00-00' OR `id` = '0000-00-00 00:00:00';
UPDATE `in_interventi_tecnici` SET `orario_fine` = NULL WHERE `orario_fine` = '0000-00-00' OR `orario_fine` = '0000-00-00 00:00:00';
UPDATE `in_interventi_tecnici` SET `orario_inizio` = NULL WHERE `orario_inizio` = '0000-00-00' OR `orario_inizio` = '0000-00-00 00:00:00';
UPDATE `or_ordini` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `co_documenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `do_documenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `co_movimenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `my_impianto_componenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `dt_ddt` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_accettazione` = NULL WHERE `data_accettazione` = '0000-00-00' OR `data_accettazione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_accettazione` = NULL WHERE `data_accettazione` = '0000-00-00' OR `data_accettazione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_bozza` = NULL WHERE `data_bozza` = '0000-00-00' OR `data_bozza` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_bozza` = NULL WHERE `data_bozza` = '0000-00-00' OR `data_bozza` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_conclusione` = NULL WHERE `data_conclusione` = '0000-00-00' OR `data_conclusione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_conclusione` = NULL WHERE `data_conclusione` = '0000-00-00' OR `data_conclusione` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `data_emissione` = NULL WHERE `data_emissione` = '0000-00-00' OR `data_emissione` = '0000-00-00 00:00:00';
UPDATE `or_righe_ordini` SET `data_evasione` = NULL WHERE `data_evasione` = '0000-00-00' OR `data_evasione` = '0000-00-00 00:00:00';
UPDATE `in_interventi` SET `data_invio` = NULL WHERE `data_invio` = '0000-00-00' OR `data_invio` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_pagamento` = NULL WHERE `data_pagamento` = '0000-00-00' OR `data_pagamento` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `data_pagamento` = NULL WHERE `data_pagamento` = '0000-00-00' OR `data_pagamento` = '0000-00-00 00:00:00';
UPDATE `co_promemoria` SET `data_richiesta` = NULL WHERE `data_richiesta` = '0000-00-00' OR `data_richiesta` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_rifiuto` = NULL WHERE `data_rifiuto` = '0000-00-00' OR `data_rifiuto` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_rifiuto` = NULL WHERE `data_rifiuto` = '0000-00-00' OR `data_rifiuto` = '0000-00-00 00:00:00';
UPDATE `my_impianto_componenti` SET `data_sostituzione` = NULL WHERE `data_sostituzione` = '0000-00-00' OR `data_sostituzione` = '0000-00-00 00:00:00';

-- Impostazioni per i tecnici assegnati delle Attività
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES
(NULL, 'Mostra promemoria attività ai soli Tecnici assegnati', '1', 'boolean', '1', 'Attività', 14),
(NULL, 'Espandi automaticamente la sezione "Dettagli aggiuntivi"', '0', 'boolean', '1', 'Attività', 15);

-- Modifica per introdurre il totale reddito per i Movimenti, sulla base del Conto relativo
ALTER TABLE `co_movimenti` ADD `totale_reddito` decimal(12, 6) NOT NULL DEFAULT 0;
ALTER TABLE `co_pianodeiconti3` ADD `percentuale_deducibile` decimal(5,2) NOT NULL DEFAULT 0;
UPDATE `co_movimenti` SET `totale_reddito` = `totale`;
UPDATE `co_pianodeiconti3` SET `percentuale_deducibile` = 100;