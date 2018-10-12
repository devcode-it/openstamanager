-- Foreign keys an_tipianagrafiche
ALTER TABLE `an_tipianagrafiche_anagrafiche` DROP FOREIGN KEY `an_tipianagrafiche_anagrafiche_ibfk_1`;

ALTER TABLE `an_tipianagrafiche` CHANGE `idtipoanagrafica` `id` int(11) NOT NULL AUTO_INCREMENT;

DELETE FROM `an_tipianagrafiche_anagrafiche` WHERE `idtipoanagrafica` NOT IN (SELECT `id` FROM `an_tipianagrafiche`);
ALTER TABLE `an_tipianagrafiche_anagrafiche` ADD FOREIGN KEY (`idtipoanagrafica`) REFERENCES `an_tipianagrafiche`(`id`) ON DELETE CASCADE;

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `an_anagrafiche` LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id` LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` WHERE 1=1 AND `deleted_at` IS NULL GROUP BY `an_anagrafiche`.`idanagrafica` HAVING 2=2 ORDER BY TRIM(`ragione_sociale`)' WHERE `name` = 'Anagrafiche';

UPDATE `zz_views` SET `query` = 'id' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di anagrafiche') AND `name` = 'id';

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS ''id'', `ragione_sociale` AS ''descrizione'' FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `id` FROM `an_tipianagrafiche` WHERE `descrizione` = ''Azienda'') AND deleted_at IS NULL' WHERE `zz_settings`.`nome` = 'Azienda predefinita';

UPDATE `zz_views` SET `search_inside` = 'idanagrafica IN(SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica IN (SELECT id FROM an_tipianagrafiche WHERE descrizione LIKE |search|))' WHERE `zz_views`.`id` = 3;

-- Foreign keys co_staticontratti TODO: ON DELETE
ALTER TABLE `co_staticontratti` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_contratti` CHANGE `idstato` `id_stato` int(11);
UPDATE `co_contratti` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `co_staticontratti`);
ALTER TABLE `co_contratti` ADD FOREIGN KEY (`id_stato`) REFERENCES `co_staticontratti`(`id`) ON DELETE CASCADE;

-- Foreign keys co_statipreventivi TODO: ON DELETE
ALTER TABLE `co_statipreventivi` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_preventivi` CHANGE `idstato` `id_stato` int(11);
UPDATE `co_preventivi` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `co_statipreventivi`);
ALTER TABLE `co_preventivi` ADD FOREIGN KEY (`id_stato`) REFERENCES `co_statipreventivi`(`id`) ON DELETE CASCADE;

-- Foreign keys dt_statiddt TODO: ON DELETE
ALTER TABLE `dt_statiddt` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `dt_ddt` CHANGE `idstatoddt` `id_stato` int(11);
UPDATE `dt_ddt` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `dt_statiddt`);
ALTER TABLE `dt_ddt` ADD FOREIGN KEY (`id_stato`) REFERENCES `dt_statiddt`(`id`) ON DELETE CASCADE;

-- Foreign keys co_statidocumento TODO: ON DELETE
ALTER TABLE `co_statidocumento` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `co_documenti` CHANGE `idstatodocumento` `id_stato` int(11);
UPDATE `co_documenti` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `co_statidocumento`);
ALTER TABLE `co_documenti` ADD FOREIGN KEY (`id_stato`) REFERENCES `co_statidocumento`(`id`) ON DELETE CASCADE;

-- Foreign keys in_statiintervento TODO: ON DELETE
ALTER TABLE `in_statiintervento` CHANGE `idstatointervento` `id` VARCHAR(10) NOT NULL;

ALTER TABLE `in_interventi` CHANGE `idstatointervento` `id_stato` VARCHAR(10);
UPDATE `in_interventi` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `in_statiintervento`);
ALTER TABLE `in_interventi` ADD FOREIGN KEY (`id_stato`) REFERENCES `in_statiintervento`(`id`) ON DELETE CASCADE;

-- Foreign keys or_statiordine
ALTER TABLE `or_statiordine` CHANGE `id` `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `or_ordini` CHANGE `idstatoordine` `id_stato` int(11);
UPDATE `or_ordini` SET `id_stato` = NULL WHERE `id_stato` NOT IN (SELECT `id` FROM `or_statiordine`);
ALTER TABLE `or_ordini` ADD FOREIGN KEY (`id_stato`) REFERENCES `or_statiordine`(`id`) ON DELETE CASCADE;

-- Fix vari
UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'an_tipianagrafiche.idtipoanagrafica', 'an_tipianagrafiche.id');
UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'idstatodocumento', 'id_stato');

UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatointervento', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatodocumento', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatoordine', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstatoddt', 'id_stato');
UPDATE `zz_views` SET `query` = REPLACE(`query`, 'idstato', 'id_stato');

UPDATE `zz_views` SET `query` = '(SELECT descrizione FROM in_statiintervento WHERE id=in_interventi.id_stato)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = 'Stato';
UPDATE `zz_views` SET `query` = '(SELECT colore FROM in_statiintervento WHERE id=in_interventi.id_stato)' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi') AND `name` = '_bg_';

UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato, co_contratti.id, DATEDIFF( data_conclusione, NOW() ) AS giorni_rimanenti FROM co_contratti WHERE id_stato IN(SELECT id FROM co_staticontratti WHERE fatturabile = 1) AND rinnovabile=1 AND NOW() > DATE_ADD( data_conclusione, INTERVAL - ABS(giorni_preavviso_rinnovo) DAY) AND YEAR(data_conclusione) > 1970 HAVING ISNULL((SELECT id FROM co_contratti contratti WHERE contratti.idcontratto_prev=co_contratti.id )) ORDER BY giorni_rimanenti ASC' WHERE `zz_widgets`.`name` = 'Contratti in scadenza';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_promemoria WHERE idcontratto IN( SELECT id FROM co_contratti WHERE id_stato IN (SELECT id FROM co_staticontratti WHERE pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Interventi da pianificare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_ordiniservizio WHERE idcontratto IN( SELECT id FROM co_contratti WHERE id_stato IN(SELECT id FROM co_staticontratti WHERE pianificabile = 1)) AND idintervento IS NULL' WHERE `zz_widgets`.`name` = 'Ordini di servizio da impostare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_ordiniservizio_pianificazionefatture WHERE idcontratto IN( SELECT id FROM co_contratti WHERE id_stato IN(SELECT id FROM co_staticontratti WHERE descrizione IN("Bozza", "Accettato", "In lavorazione", "In attesa di pagamento")) ) AND co_ordiniservizio_pianificazionefatture.iddocumento=0' WHERE `zz_widgets`.`name` = 'Rate contrattuali';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM in_interventi WHERE id NOT IN (SELECT idintervento FROM in_interventi_tecnici) AND id_stato IN (SELECT id FROM in_statiintervento WHERE completato = 0)' WHERE `zz_widgets`.`name` = 'Attività da pianificare';
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM co_preventivi WHERE id_stato = (SELECT id FROM co_statipreventivi WHERE descrizione="In lavorazione")' WHERE `zz_widgets`.`name` = 'Preventivi in lavorazione';
