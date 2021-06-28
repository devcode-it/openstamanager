-- Colonna n. protocollo per fatture di acquisto
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Fatture di acquisto'), 'N. Prot.', 'co_documenti.numero', 1, 1, 0, 0, '', '', 0, 0, 0);
 
-- Formattazione colonna data modulo Ordini fornitore
UPDATE `zz_views` SET `format`=1 WHERE `zz_views`.`name`='Data' AND `zz_views`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore');

-- Colonna stato per newsletter
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Newsletter'), 'Stato', 'IF(em_newsletters.state = \'DEV\', \'Bozza\', IF(em_newsletters.state = \'WAIT\', \'Invio in corso\', \'Completata\'))', 4, 1, 0, 0, '', '', 1, 0, 0);

-- Colonna destinatari per newsletter
INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Newsletter'), 'Destinatari', '(SELECT COUNT(*) FROM `em_newsletter_anagrafica` WHERE `em_newsletter_anagrafica`.`id_newsletter` = `em_newsletters`.`id`)', 5, 1, 0, 0, '', '', 1, 0, 0);

-- Aggiorno colonna completato per newsletter
UPDATE `zz_views` SET `query` = 'IF(completed_at IS NULL, \'No\', CONCAT(\'Sì \', \'(\', DATE_FORMAT(completed_at, \'%d/%m/%Y %H:%i:%s\' ), \')\'))', `order` = 6 WHERE `zz_views`.`id_module` = (SELECT `id` FROM `zz_modules` WHERE name='Newsletter') AND `name` = 'Completato'; 

-- Visualizza informazioni aggiuntive sul calendario
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Visualizza informazioni aggiuntive sul calendario', '0', 'boolean', '1', 'Dashboard', '1', 'Visualizza sul calendario il box Tutto il giorno dove possono essere presenti informazioni aggiuntve'); 

-- Rinominate stampe ordini fornitore
UPDATE `zz_prints` SET `title` = 'Richiesta di offerta (RdO)' WHERE `zz_prints`.`name` = 'Ordine fornitore (senza costi)';
UPDATE `zz_prints` SET `title` = 'Richiesta di acquisto (RdA)' WHERE `zz_prints`.`name` = 'Ordine fornitore';

-- Aggiunta impostazione formato ore in stampa intervento
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `updated_at`, `order`, `help`) VALUES (NULL, 'Formato ore in stampa', 'Decimale', 'list[Decimale,Sessantesimi]', '1', 'Attività', NOW(), NOW(), '1', '');

-- Aggiunta plugin allegati dell'anagrafica
INSERT INTO `zz_plugins` (`name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES ('Allegati', 'Allegati', (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Anagrafiche'), 'tab', 'allegati.php', '1', '0', '0', '', '', NULL, NULL, '', '');

-- Aggiunta idsede nelle righe dell'intervento
ALTER TABLE `in_righe_interventi` ADD `idsede_partenza` INT NOT NULL AFTER `id_dettaglio_fornitore`;


-- Aggiunto nuovo plugin Componenti e disabilitato quello precedente
UPDATE `zz_plugins` SET `name`='Componenti ini', `title`='Componenti ini' WHERE `name`='Componenti';
UPDATE `zz_plugins` SET `enabled`=0 WHERE `name`='Componenti ini';

INSERT INTO `zz_plugins` ( `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`, `created_at`, `updated_at`) VALUES ('Componenti', 'Componenti', (SELECT `id` FROM `zz_modules` WHERE name='Impianti'), (SELECT `id` FROM `zz_modules` WHERE name='Impianti'), 'tab', '', '1', '0', '0', '', '', NULL, 'custom', 'componenti', '', NOW(), NOW());

CREATE TABLE `my_componenti_articoli` ( `id` INT NOT NULL AUTO_INCREMENT, `id_impianto` INT NOT NULL , `id_articolo` INT NOT NULL , `pre_id_articolo` INT NOT NULL, `note` TEXT NOT NULL , `data_registrazione` DATE NULL , `data_installazione` DATE NULL , `data_disinstallazione` DATE NULL , `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`));

-- Aggiunta vista referente in modulo attività
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Interventi' ), 'Referente', '(SELECT an_referenti.nome FROM an_referenti WHERE an_referenti.id=in_interventi.idreferente)', '7', '1', '0', '0', '', '', '1', '0', '0');

-- Aggiunta vista scaduto in scadenzario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Scadenzario' ), 'Scaduto', 'IF(pagato = da_pagare, \'NO\', IF(data_concordata IS NOT NULL AND data_concordata > NOW(), \'NO\', IF(scadenza < NOW(), \'SÌ\', \'NO\')))', 14, 1, 0, 0, '', '', 1, 0, 0);

INSERT INTO `zz_group_view` (`id_gruppo`, `id_vista`) (SELECT `zz_groups`.`id`, `zz_views`.`id` FROM `zz_groups`, `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` WHERE `zz_modules`.`name` = 'Scadenzario' AND `zz_views`.`name` = 'Scaduto');