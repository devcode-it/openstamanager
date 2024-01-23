-- Rimozione flag inutilizzato
ALTER TABLE `or_statiordine` DROP `annullato`;

-- Aggiunta flag "impegnato" sugli stati ordine
ALTER TABLE `or_statiordine` ADD `impegnato` BOOLEAN NOT NULL DEFAULT FALSE AFTER `icona`;
UPDATE `or_statiordine` SET `impegnato` = 1 WHERE `descrizione` IN('Evaso', 'Parzialmente evaso', 'Accettato', 'Parzialmente fatturato', 'Fatturato');

-- Aggiunta risorse API per creazione e modifica Articoli
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'v1', 'create', 'articolo', 'Modules\\Articoli\\API\\v1\\Articoli', '1'),
(NULL, 'v1', 'update', 'articolo', 'Modules\\Articoli\\API\\v1\\Articoli', '1');

-- Fix visualizzazione attività in dashboard
UPDATE `zz_segments` SET `clause` = '(orario_inizio BETWEEN \'|period_start|\' AND \'|period_end|\' OR orario_fine BETWEEN \'|period_start|\' AND \'|period_end|\')' WHERE `zz_segments`.`name` = 'Attività';

-- Aumentato limite per campo note in scheda anagrafica
ALTER TABLE `an_anagrafiche` CHANGE `note` `note` TEXT NOT NULL;

-- Aggiunta risorsa APi per revisione applicazione
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES
(NULL, 'app-v1', 'retrieve', 'revisione', 'API\\App\\v1\\Revisione', '1');

-- Cambiato title al plugin prezzi specifici
UPDATE `zz_plugins` SET `title` = 'Prezzi di listino' WHERE `zz_plugins`.`name` = 'Prezzi specifici articolo';

-- Impostati stati fatturabili in ddt e ordini
ALTER TABLE `or_statiordine` ADD `is_fatturabile` TINYINT(1) NOT NULL AFTER `completato`;
ALTER TABLE `dt_statiddt` ADD `is_fatturabile` TINYINT(1) NOT NULL AFTER `completato`;

UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Evaso';
UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Parzialmente evaso';
UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Parzialmente fatturato';
UPDATE `or_statiordine` SET `is_fatturabile` = '1' WHERE `or_statiordine`.`descrizione` = 'Accettato';
UPDATE `dt_statiddt` SET `is_fatturabile` = '1' WHERE `dt_statiddt`.`descrizione` = 'Evaso';
UPDATE `dt_statiddt` SET `is_fatturabile` = '1' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente evaso';
UPDATE `dt_statiddt` SET `is_fatturabile` = '1' WHERE `dt_statiddt`.`descrizione` = 'Parzialmente fatturato';

-- Aggiunta colonna um in Movimenti di magazzino
UPDATE `zz_views` SET `query` = 'mg_movimenti.qta' WHERE `zz_views`.`name` = 'Quantità';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Movimenti'), 'Um', 'mg_articoli.um', 5, 1, 0, 0, '', '', 0, 0, 0);

-- Fix campo iva per Sconti di versioni precedenti
UPDATE `co_righe_documenti` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `co_righe_preventivi` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `co_righe_contratti` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `dt_righe_ddt` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `or_righe_ordini` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `co_righe_promemoria` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;
UPDATE `in_righe_interventi` SET `iva` = ABS(`iva`) * IF(`sconto` > 0, -1, 1) WHERE `is_sconto` = 1;

-- Aumentato il campo descrizione in articoli da varchar a text
ALTER TABLE `mg_articoli` CHANGE `descrizione` `descrizione` TEXT NOT NULL;

-- Set a NULL le date dei contratti vuote
UPDATE `co_contratti` SET `data_bozza`=NULL WHERE `data_bozza`=0000-00-00;
UPDATE `co_contratti` SET `data_accettazione`=NULL WHERE `data_accettazione`=0000-00-00;
UPDATE `co_contratti` SET `data_rifiuto`=NULL WHERE `data_rifiuto`=0000-00-00;
UPDATE `co_contratti` SET `data_conclusione`=NULL WHERE `data_conclusione`=0000-00-00;

-- Aggiunto sconto finale in preventivi
ALTER TABLE `co_preventivi` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `garanzia`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Aggiunto sconto finale in ordini
ALTER TABLE `or_ordini` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `numero_cliente`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Aggiunto sconto finale in ddt
ALTER TABLE `dt_ddt` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `num_item`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Aggiunto sconto finale in contratti
ALTER TABLE `co_contratti` ADD `sconto_finale` DECIMAL(17,8) NOT NULL AFTER `num_item`, ADD `sconto_finale_percentuale` DECIMAL(17,8) NOT NULL AFTER `sconto_finale`;

-- Set a NULL le date dei preventivi vuote
UPDATE `co_preventivi` SET `data_bozza`=NULL WHERE `data_bozza`=0000-00-00;
UPDATE `co_preventivi` SET `data_accettazione`=NULL WHERE `data_accettazione`=0000-00-00;
UPDATE `co_preventivi` SET `data_rifiuto`=NULL WHERE `data_rifiuto`=0000-00-00;
UPDATE `co_preventivi` SET `data_conclusione`=NULL WHERE `data_conclusione`=0000-00-00;

-- Aggiunto filtro in attività per vedere interventi assegnati
INSERT INTO `zz_group_module` ( `idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES ((SELECT `id` FROM `zz_groups` WHERE `nome`='Tecnici'), (SELECT `id` FROM `zz_modules` WHERE name='Interventi'), 'Mostra interventi ai tecnici assegnati', 'in_interventi.id IN (SELECT id_intervento FROM in_interventi_tecnici_assegnati WHERE id_intervento=in_interventi.id AND id_tecnico=|id_anagrafica|)', 'WHR', 0, 1);

-- Aggiunta colonna Prev. evasione in Ordini
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'), 'icon_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'fa fa-clock-o text-info\', \'fa fa-warning text-danger\'), \'fa fa-check text-success\')', 8, 1, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore'), 'icon_title_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'In orario\', \'In ritardo\'), \'Consegnato\')', 9, 1, 0, 0, '', '', 0, 0, 0);

INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'), 'icon_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'fa fa-clock-o text-info\', \'fa fa-warning text-danger\'), \'fa fa-check text-success\')', 8, 1, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Ordini cliente'), 'icon_title_Prev. evasione', 'IF(righe.`qta_da_evadere` > 0,IF((righe_da_evadere.data_evasione>now() OR righe_da_evadere.data_evasione IS NULL), \'In orario\', \'In ritardo\'), \'Consegnato\')', 9, 1, 0, 0, '', '', 0, 0, 0);

-- Aggiunto campo ora evasione in ordini
ALTER TABLE `or_righe_ordini` ADD `ora_evasione` TIME NULL AFTER `data_evasione`;

-- Aggiunta indice sull'id preventivo per velocizzare il caricamento del rif. numero fattura in vista preventivi
ALTER TABLE `co_righe_documenti` ADD INDEX(`idpreventivo`);

-- Aggiunta stampa dettaglio anagrafica
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES ((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), '1', 'Dettaglio anagrafica', 'Dettaglio anagrafica', 'Anagrafica {codice} - {ragione_sociale}', 'anagrafiche', 'idanagrafica', '', 'fa fa-print', '', '', '0', '1', '1', '1');

-- Aggiunta stampa dati aziendali
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES ((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), '1', 'Dati aziendali', 'Dati aziendali', 'Dati aziendali {ragione_sociale}', 'azienda', 'idanagrafica', '', 'fa fa-print', '', '', '0', '0', '0', '1');

-- Correzione per segmenti con pagamenti RiBa per Scadenzario
UPDATE `zz_segments` SET `clause` = REPLACE(`clause`, 'co_pagamenti.riba=1', 'co_pagamenti.codice_modalita_pagamento_fe= ''MP12''');
ALTER TABLE `co_pagamenti` DROP `riba`;

-- Aggiunto filtro in contratti per i clienti
INSERT INTO `zz_group_module` (`idgruppo`, `idmodule`, `name`, `clause`, `position`, `enabled`, `default`) VALUES((SELECT `id` FROM `zz_groups` WHERE `nome` = 'Clienti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Mostra i contratti ai clienti coinvolti', 'co_contratti.idanagrafica=|id_anagrafica|', 'WHR', 1, 1);

-- Aggiunto campo descrizione revisione in preventivi
ALTER TABLE `co_preventivi` ADD `descrizione_revision` VARCHAR(255) NOT NULL AFTER `default_revision`;
UPDATE `zz_prints` SET `filename` = 'Preventivo num. {numero} del {data} rev {revisione}' WHERE `zz_prints`.`name` = 'Preventivo';

-- Aggiunti campi per componenti IBAN
ALTER TABLE `co_banche` ADD `branch_code` VARCHAR(20) NULL,
    ADD `bank_code` VARCHAR(20) NULL,
    ADD `account_number` VARCHAR(20) NULL,
    ADD `check_digits` VARCHAR(20) NULL,
    ADD `national_check_digits` VARCHAR(20) NULL,
    ADD `id_nazione` INT(11) NULL,
    ADD FOREIGN KEY (`id_nazione`) REFERENCES `an_nazioni`(`id`);

-- Messaggio Verifica numero intervento
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES
(NULL, 'Verifica numero intervento', '1', 'boolean', 1, 'Attività', 1, 'Visualizza il messaggio che verifica la continuità dei numeri per le attività');

-- Allineamento colore icona EC02
UPDATE `fe_stati_documento` SET `icon` = 'fa fa-times text-danger' WHERE `fe_stati_documento`.`codice` = 'EC02'; 

-- Impostata aliquota iva per dichiarazione d'intento se non presente
UPDATE `zz_settings` SET `valore` = IF(`valore` ='', (SELECT `id` FROM `co_iva` WHERE `descrizione`='Non imp. art. 8 c.1 lett. c DPR 633/1972'), `valore`) WHERE `nome`="Iva per lettere d'intento";

-- Ripristino Fattura di vendita come stampa predefinita
UPDATE `zz_prints` SET `predefined` = '0' WHERE `zz_prints`.`name` = 'Fattura elettronica di vendita'; 

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Modulo', '(SELECT `name` FROM `zz_modules` WHERE `zz_modules`.`id` = `zz_prints`.`id_module`)', 4, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Predefinita', 'zz_prints.predefined', 5, 1, 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stampe'), 'Ordine', 'zz_prints.order', 6, 1, 0, 0, 1);