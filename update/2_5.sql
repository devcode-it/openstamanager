-- Standardizzazione tabelle

-- Separazione sedi dalle anagrafiche
ALTER TABLE `an_anagrafiche` ADD `id_sede_legale` int(11), ADD FOREIGN KEY (`id_sede_legale`) REFERENCES `an_sedi`(`id`) ON DELETE SET NULL;
INSERT INTO `an_sedi` (`nomesede`, `indirizzo`, `indirizzo2`, `citta`, `cap`, `provincia`, `km`, `id_nazione`, `telefono`, `fax`, `cellulare`, `email`, `idanagrafica`, `idzona`, `gaddress`, `lat`, `lng`) SELECT 'Sede legale', `indirizzo`, `indirizzo2`, `citta`, `cap`, `provincia`, `km`, `id_nazione`, `telefono`, `fax`, `cellulare`, `email`, `idanagrafica`, `idzona`, `gaddress`, `lat`, `lng` FROM `an_anagrafiche`;

UPDATE `an_anagrafiche` SET `id_sede_legale` = (SELECT `id` FROM `an_sedi` WHERE `an_sedi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` AND `an_sedi`.`nomesede` = 'Sede legale' LIMIT 1);

ALTER TABLE `an_anagrafiche` DROP FOREIGN KEY `an_anagrafiche_ibfk_1`, DROP `indirizzo`, DROP `indirizzo2`, DROP `citta`, DROP `cap`, DROP `provincia`, DROP `km`, DROP `id_nazione`, DROP `telefono`, DROP `fax`, DROP `cellulare`, DROP `email`, DROP `idzona`, DROP `gaddress`, DROP `lat`, DROP `lng`;

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `an_anagrafiche` LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id` LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` INNER JOIN `an_sedi` ON `an_sedi`.`id`=`an_anagrafiche`.`id_sede_legale` LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id` = `an_tipianagrafiche_anagrafiche`.`id_tipo_anagrafica` WHERE 1=1 AND `deleted_at` IS NULL GROUP BY `an_anagrafiche`.`idanagrafica` HAVING 2=2 ORDER BY TRIM(`ragione_sociale`)' WHERE `name` = 'Anagrafiche';

--  TODO: definire cluausole ON DELETE

-- TIPI
-- Foreign keys an_tipianagrafiche
ALTER TABLE `an_tipianagrafiche_anagrafiche` DROP FOREIGN KEY `an_tipianagrafiche_anagrafiche_ibfk_1`;

ALTER TABLE `an_tipianagrafiche` CHANGE `idtipoanagrafica` `id` int(11) NOT NULL AUTO_INCREMENT;
UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'an_tipianagrafiche.idtipoanagrafica', 'an_tipianagrafiche.id');

ALTER TABLE `an_tipianagrafiche_anagrafiche` CHANGE `idtipoanagrafica` `id_tipo_anagrafica` int(11);
DELETE FROM `an_tipianagrafiche_anagrafiche` WHERE `id_tipo_anagrafica` NOT IN (SELECT `id` FROM `an_tipianagrafiche`);
ALTER TABLE `an_tipianagrafiche_anagrafiche` ADD FOREIGN KEY (`id_tipo_anagrafica`) REFERENCES `an_tipianagrafiche`(`id`) ON DELETE CASCADE;

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `an_anagrafiche` LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id` LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`id_tipo_anagrafica` LEFT JOIN `an_sedi` ON `an_sedi`.`id`=`an_anagrafiche`.`id_sede_legale` WHERE 1=1 AND `deleted_at` IS NULL GROUP BY `an_anagrafiche`.`idanagrafica` HAVING 2=2 ORDER BY TRIM(`ragione_sociale`)' WHERE `name` = 'Anagrafiche';
UPDATE `zz_views` SET `query` = 'an_anagrafiche.codice_destinatario' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche') AND `query` = 'codice_destinatario';

UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'an_tipianagrafiche_anagrafiche.idtipoanagrafica', 'an_tipianagrafiche_anagrafiche.id_tipo_anagrafica');

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS ''id'', `ragione_sociale` AS ''descrizione'' FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `id_tipo_anagrafica` = (SELECT `id` FROM `an_tipianagrafiche` WHERE `descrizione` = ''Azienda'') AND deleted_at IS NULL' WHERE `zz_settings`.`nome` = 'Azienda predefinita';

UPDATE `zz_views` SET `query` = 'id' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di anagrafiche') AND `name` = 'id';
UPDATE `zz_views` SET `search_inside` = 'idanagrafica IN(SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE id_tipo_anagrafica IN (SELECT id FROM an_tipianagrafiche WHERE descrizione LIKE |search|))' WHERE `zz_views`.`id` = 3;

-- Foreign keys in_tipiintervento
ALTER TABLE `in_interventi` DROP FOREIGN KEY `in_interventi_ibfk_2`;

ALTER TABLE `in_tipiintervento` CHANGE `idtipointervento` `id` VARCHAR(25) NOT NULL;

ALTER TABLE `an_anagrafiche` CHANGE `idtipointervento_default` `id_tipo_intervento_default` VARCHAR(25);
UPDATE `an_anagrafiche` SET `id_tipo_intervento_default` = NULL WHERE `id_tipo_intervento_default` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `an_anagrafiche` ADD FOREIGN KEY (`id_tipo_intervento_default`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

ALTER TABLE `co_preventivi` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25);
UPDATE `co_preventivi` SET `id_tipo_intervento` = NULL WHERE `id_tipo_intervento` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `co_preventivi` ADD FOREIGN KEY (`id_tipo_intervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

ALTER TABLE `co_promemoria` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25);
UPDATE `co_promemoria` SET `id_tipo_intervento` = NULL WHERE `id_tipo_intervento` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `co_promemoria` ADD FOREIGN KEY (`id_tipo_intervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

ALTER TABLE `in_interventi` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25);
UPDATE `in_interventi` SET `id_tipo_intervento` = NULL WHERE `id_tipo_intervento` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `in_interventi` ADD FOREIGN KEY (`id_tipo_intervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

ALTER TABLE `in_interventi_tecnici` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25);
UPDATE `in_interventi_tecnici` SET `id_tipo_intervento` = NULL WHERE `id_tipo_intervento` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `in_interventi_tecnici` ADD FOREIGN KEY (`id_tipo_intervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

ALTER TABLE `in_tariffe` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25);
DELETE FROM `in_tariffe` WHERE `id_tipo_intervento` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `in_tariffe` ADD FOREIGN KEY (`id_tipo_intervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

ALTER TABLE `co_contratti_tipiintervento` CHANGE `idtipointervento` `id_tipo_intervento` VARCHAR(25);
DELETE FROM `co_contratti_tipiintervento` WHERE `id_tipo_intervento` NOT IN (SELECT `id` FROM `in_tipiintervento`);
ALTER TABLE `co_contratti_tipiintervento` ADD FOREIGN KEY (`id_tipo_intervento`) REFERENCES `in_tipiintervento`(`id`) ON DELETE CASCADE;

UPDATE `zz_views` SET `query` = '(SELECT descrizione FROM in_tipiintervento WHERE in_tipiintervento.id=in_interventi.id_tipo_intervento)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = 'Tipo';

-- Foreign keys or_tipiordine
ALTER TABLE `or_tipiordine` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `or_ordini` CHANGE `idtipoordine` `id_tipo_ordine` int(11);
DELETE FROM `or_ordini` WHERE `id_tipo_ordine` NOT IN (SELECT `id` FROM `or_tipiordine`);
ALTER TABLE `or_ordini` ADD FOREIGN KEY (`id_tipo_ordine`) REFERENCES `or_tipiordine`(`id`) ON DELETE CASCADE;

UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idtipoordine', 'id_tipo_ordine');
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'idtipoordine', 'id_tipo_ordine'), `options2` = REPLACE(`options2`, 'idtipoordine', 'id_tipo_ordine');


-- Foreign keys dt_tipiddt
ALTER TABLE `dt_tipiddt` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dt_ddt` CHANGE `idtipoddt` `id_tipo_ddt` int(11);
UPDATE `dt_ddt` SET `id_tipo_ddt` = NULL WHERE `id_tipo_ddt` NOT IN (SELECT `id` FROM `dt_tipiddt`);
ALTER TABLE `dt_ddt` ADD FOREIGN KEY (`id_tipo_ddt`) REFERENCES `dt_tipiddt`(`id`) ON DELETE CASCADE;

UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idtipoddt', 'id_tipo_ddt');

-- Foreign keys co_tipidocumento
ALTER TABLE `co_tipidocumento` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_documenti` CHANGE `idtipodocumento` `id_tipo_documento` int(11) NOT NULL;
UPDATE `co_documenti` SET `id_tipo_documento` = NULL WHERE `id_tipo_documento` NOT IN (SELECT `id` FROM `co_tipidocumento`);
ALTER TABLE `co_documenti` ADD FOREIGN KEY (`id_tipo_documento`) REFERENCES `co_tipidocumento`(`id`) ON DELETE CASCADE;

UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idtipodocumento', 'id_tipo_documento');
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'idtipodocumento', 'id_tipo_documento'), `options2` = REPLACE(`options2`, 'idtipodocumento', 'id_tipo_documento');
UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'idtipodocumento', 'id_tipo_documento');

-- STATI
-- Foreign keys co_staticontratti
ALTER TABLE `co_staticontratti` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_contratti` CHANGE `idstato` `id_stato` int(11);
UPDATE `co_contratti` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `co_staticontratti`);
ALTER TABLE `co_contratti` ADD FOREIGN KEY (`id_stato`) REFERENCES `co_staticontratti`(`id`) ON DELETE CASCADE;

-- Foreign keys co_statipreventivi
ALTER TABLE `co_statipreventivi` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_preventivi` CHANGE `idstato` `id_stato` int(11);
UPDATE `co_preventivi` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `co_statipreventivi`);
ALTER TABLE `co_preventivi` ADD FOREIGN KEY (`id_stato`) REFERENCES `co_statipreventivi`(`id`) ON DELETE CASCADE;

-- Foreign keys dt_statiddt
ALTER TABLE `dt_statiddt` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dt_ddt` CHANGE `idstatoddt` `id_stato` int(11);
UPDATE `dt_ddt` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `dt_statiddt`);
ALTER TABLE `dt_ddt` ADD FOREIGN KEY (`id_stato`) REFERENCES `dt_statiddt`(`id`) ON DELETE CASCADE;

-- Foreign keys co_statidocumento
ALTER TABLE `co_statidocumento` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_documenti` CHANGE `idstatodocumento` `id_stato` int(11);
UPDATE `co_documenti` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `co_statidocumento`);
ALTER TABLE `co_documenti` ADD FOREIGN KEY (`id_stato`) REFERENCES `co_statidocumento`(`id`) ON DELETE CASCADE;

-- Foreign keys in_statiintervento
ALTER TABLE `in_statiintervento` CHANGE `idstatointervento` `id` VARCHAR(10) NOT NULL;

ALTER TABLE `in_interventi` CHANGE `idstatointervento` `id_stato` VARCHAR(10);
UPDATE `in_interventi` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `in_statiintervento`);
ALTER TABLE `in_interventi` ADD FOREIGN KEY (`id_stato`) REFERENCES `in_statiintervento`(`id`) ON DELETE CASCADE;

-- Foreign keys or_statiordine
ALTER TABLE `or_statiordine` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `or_ordini` CHANGE `idstatoordine` `id_stato` int(11);
UPDATE `or_ordini` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `or_statiordine`);
ALTER TABLE `or_ordini` ADD FOREIGN KEY (`id_stato`) REFERENCES `or_statiordine`(`id`) ON DELETE CASCADE;

UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'idtipoddt', 'id_tipo_ddt'), `options2` = REPLACE(`options2`, 'idtipoddt', 'id_tipo_ddt');

-- Fix vari per gli stati
UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'idstatodocumento', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatointervento', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatodocumento', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatoordine', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatoddt', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstato', 'id_stato');

UPDATE `zz_views` SET `query` = '(SELECT descrizione FROM in_statiintervento WHERE id=in_interventi.id_stato)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = 'Stato';
UPDATE `zz_views` SET `query` = '(SELECT colore FROM in_statiintervento WHERE id=in_interventi.id_stato)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = '_bg_';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato, co_contratti.id, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti FROM co_contratti WHERE id_stato IN(SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970 AND ISNULL((SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev=co_contratti.id )) ORDER BY giorni_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_promemoria WHERE idcontratto IN( SELECT id FROM co_contratti WHERE id_stato IN (SELECT id FROM co_staticontratti WHERE pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Interventi da pianificare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_ordiniservizio WHERE idcontratto IN( SELECT id FROM co_contratti WHERE id_stato IN(SELECT id FROM co_staticontratti WHERE pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Ordini di servizio da impostare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_ordiniservizio_pianificazionefatture WHERE idcontratto IN( SELECT id FROM co_contratti WHERE id_stato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND co_ordiniservizio_pianificazionefatture.iddocumento=0' WHERE `zz_widgets`.`name` = 'Rate contrattuali';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE id NOT IN (SELECT idintervento FROM in_interventi_tecnici) AND id_stato IN (SELECT id FROM in_statiintervento WHERE completato = 0)' WHERE `zz_widgets`.`name` = 'Attività da pianificare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE id_stato = (SELECT id FROM co_statipreventivi WHERE descrizione="In lavorazione")' WHERE `zz_widgets`.`name` = 'Preventivi in lavorazione';

-- Fix contenuti delle date (NULL al posto di 0000-00-00)
ALTER TABLE `mg_movimenti` CHANGE `data` `data` date;
ALTER TABLE `my_impianti` CHANGE `data` `data` date;
ALTER TABLE `an_anagrafiche` CHANGE `data_nascita` `data_nascita` date;
ALTER TABLE `in_interventi` CHANGE `data_richiesta` `data_richiesta` DATETIME;
ALTER TABLE `in_interventi` CHANGE `firma_data` `firma_data` DATETIME;

UPDATE `mg_movimenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `my_impianti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `an_anagrafiche` SET `data_nascita` = NULL WHERE `data_nascita` = '0000-00-00' OR `data_nascita` = '0000-00-00 00:00:00';
UPDATE `in_interventi` SET `data_richiesta` = NULL WHERE `data_richiesta` = '0000-00-00' OR `data_richiesta` = '0000-00-00 00:00:00';
UPDATE `in_interventi` SET `firma_data` = NULL WHERE `firma_data` = '0000-00-00' OR `firma_data` = '0000-00-00 00:00:00';
UPDATE `updates` SET `id` = NULL WHERE `id` = '0000-00-00' OR `id` = '0000-00-00 00:00:00';
UPDATE `in_interventi_tecnici` SET `orario_fine` = NULL WHERE `orario_fine` = '0000-00-00' OR `orario_fine` = '0000-00-00 00:00:00';
UPDATE `in_interventi_tecnici` SET `orario_inizio` = NULL WHERE `orario_inizio` = '0000-00-00' OR `orario_inizio` = '0000-00-00 00:00:00';
UPDATE `updates` SET `script` = NULL WHERE `script` = '0000-00-00' OR `script` = '0000-00-00 00:00:00';
UPDATE `updates` SET `sql` = NULL WHERE `sql` = '0000-00-00' OR `sql` = '0000-00-00 00:00:00';
UPDATE `updates` SET `version` = NULL WHERE `version` = '0000-00-00' OR `version` = '0000-00-00 00:00:00';
UPDATE `or_ordini` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `co_documenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `zz_documenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `co_movimenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `my_impianto_componenti` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `dt_ddt` SET `data` = NULL WHERE `data` = '0000-00-00' OR `data` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_accettazione` = NULL WHERE `data_accettazione` = '0000-00-00' OR `data_accettazione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_accettazione` = NULL WHERE `data_accettazione` = '0000-00-00' OR `data_accettazione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_bozza` = NULL WHERE `data_bozza` = '0000-00-00' OR `data_bozza` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_bozza` = NULL WHERE `data_bozza` = '0000-00-00' OR `data_bozza` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_conclusione` = NULL WHERE `data_conclusione` = '0000-00-00' OR `data_conclusione` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_conclusione` = NULL WHERE `data_conclusione` = '0000-00-00' OR `data_conclusione` = '0000-00-00 00:00:00';
UPDATE `co_movimenti` SET `data_documento` = NULL WHERE `data_documento` = '0000-00-00' OR `data_documento` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `data_emissione` = NULL WHERE `data_emissione` = '0000-00-00' OR `data_emissione` = '0000-00-00 00:00:00';
UPDATE `co_righe_preventivi` SET `data_evasione` = NULL WHERE `data_evasione` = '0000-00-00' OR `data_evasione` = '0000-00-00 00:00:00';
UPDATE `or_righe_ordini` SET `data_evasione` = NULL WHERE `data_evasione` = '0000-00-00' OR `data_evasione` = '0000-00-00 00:00:00';
UPDATE `dt_automezzi_tecnici` SET `data_fine` = NULL WHERE `data_fine` = '0000-00-00' OR `data_fine` = '0000-00-00 00:00:00';
UPDATE `dt_automezzi_tecnici` SET `data_inizio` = NULL WHERE `data_inizio` = '0000-00-00' OR `data_inizio` = '0000-00-00 00:00:00';
UPDATE `in_interventi` SET `data_invio` = NULL WHERE `data_invio` = '0000-00-00' OR `data_invio` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_pagamento` = NULL WHERE `data_pagamento` = '0000-00-00' OR `data_pagamento` = '0000-00-00 00:00:00';
UPDATE `co_scadenziario` SET `data_pagamento` = NULL WHERE `data_pagamento` = '0000-00-00' OR `data_pagamento` = '0000-00-00 00:00:00';
UPDATE `co_promemoria` SET `data_richiesta` = NULL WHERE `data_richiesta` = '0000-00-00' OR `data_richiesta` = '0000-00-00 00:00:00';
UPDATE `co_preventivi` SET `data_rifiuto` = NULL WHERE `data_rifiuto` = '0000-00-00' OR `data_rifiuto` = '0000-00-00 00:00:00';
UPDATE `co_contratti` SET `data_rifiuto` = NULL WHERE `data_rifiuto` = '0000-00-00' OR `data_rifiuto` = '0000-00-00 00:00:00';
UPDATE `co_ordiniservizio` SET `data_scadenza` = NULL WHERE `data_scadenza` = '0000-00-00' OR `data_scadenza` = '0000-00-00 00:00:00';
UPDATE `co_ordiniservizio_pianificazionefatture` SET `data_scadenza` = NULL WHERE `data_scadenza` = '0000-00-00' OR `data_scadenza` = '0000-00-00 00:00:00';
UPDATE `my_impianto_componenti` SET `data_sostituzione` = NULL WHERE `data_sostituzione` = '0000-00-00' OR `data_sostituzione` = '0000-00-00 00:00:00';

-- Permessi avanzati
ALTER TABLE `zz_permissions` DROP FOREIGN KEY `zz_permissions_ibfk_1`, DROP FOREIGN KEY`zz_permissions_ibfk_2`;
ALTER TABLE `zz_permissions` CHANGE `idmodule` `external_id` int(11), CHANGE `idgruppo` `group_id` int(11), CHANGE `permessi` `permission_level` enum('-', 'r', 'rw'), ADD `permission_type` varchar(255);
ALTER TABLE `zz_permissions` ADD FOREIGN KEY (`group_id`) REFERENCES `zz_groups`(`id`) ON DELETE CASCADE;
UPDATE `zz_permissions` SET `permission_type` = 'Models\Module';
DELETE FROM `zz_permissions` WHERE `permission_level` = '-';
