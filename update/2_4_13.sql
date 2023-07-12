-- Fix data registrazione e data competenza non settate
UPDATE `co_documenti` SET `data_registrazione` = `data` WHERE `data_registrazione` IS NULL;
UPDATE `co_documenti` SET `data_competenza` = `data_registrazione` WHERE `data_competenza` IS NULL;

-- Data ora trasporto per ddt
ALTER TABLE `dt_ddt` ADD `data_ora_trasporto` DATETIME NULL DEFAULT NULL AFTER `data`;

-- Impostazione "Filigrana stampe"
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES (NULL, 'Filigrana stampe', '', 'string', '0', 'Generali');

-- Per elenco coda di invio aggiungo colonna Modulo (legata al template) e Destinatario
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Modulo', '(SELECT zz_modules.title FROM zz_modules WHERE zz_modules.id = em_templates.id_module)', 3, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Stato email'), 'Destinatari', '(SELECT GROUP_CONCAT(address SEPARATOR "<br>") FROM em_email_receiver WHERE em_email_receiver.id_email = em_emails.id)', 1, 1, 0, 1, 1, 0);

-- Modulo Relazioni
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`) VALUES (NULL, 'Relazioni', 'Relazioni', 'relazioni_anagrafiche', 'SELECT |select|
FROM `an_relazioni`
WHERE 1=1 
HAVING 2=2
ORDER BY `an_relazioni`.`created_at` DESC', '', 'fa fa-angle-right ', '2.4.13', '2.*', '1', (SELECT `id` FROM `zz_modules` t WHERE t.`name` = 'Anagrafiche'), '1', '1');

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `default`, `visible`, `format`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Relazioni'), 'id', 'an_relazioni.id', 1, 0, 0, 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Relazioni'), 'Descrizione', 'an_relazioni.descrizione', 2, 1, 0, 1, 1, 0),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Relazioni'), 'Colore', 'an_relazioni.colore', 3, 1, 0, 1, 1, 0);

-- Ripristino modulo pianificazione fatturazione
INSERT INTO `zz_plugins` (`id`, `name`, `title`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES
(NULL, 'Pianificazione fatturazione', 'Pianificazione fatturazione', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'tab', 'contratti.fatturaordiniservizio.php', 1, 0, 0, '', '', NULL, NULL, '', '');

-- Aggiunta campo note nello scadenzario --
ALTER TABLE `co_scadenziario` ADD `note` VARCHAR(255) DEFAULT NULL AFTER `data_pagamento`; 

-- Aggiunta note in vista scadenzario --
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `zz_modules`.`name`='Scadenzario' ), 'Note', 'co_scadenziario.note', '5', '1', '0', '0', '', '', '0', '0', '0');

UPDATE `zz_settings` SET `nome` = 'Ora inizio sul calendario' WHERE `zz_settings`.`nome` = 'Inizio orario lavorativo';
UPDATE `zz_settings` SET `nome` = 'Ora fine sul calendario' WHERE `zz_settings`.`nome` = 'Fine orario lavorativo';
UPDATE `zz_settings` SET `nome` = 'Formato codice attività' WHERE `zz_settings`.`nome` = 'Formato codice intervento';

-- Inizio orario lavorativo
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `help`) VALUES (NULL, 'Inizio orario lavorativo', '08:00:00', 'time', '1', 'Interventi', 'Inizio dell''orario lavorativo standard.');

-- Fine orario lavorativo
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `help`) VALUES (NULL, 'Fine orario lavorativo', '18:00:00', 'time', '1', 'Interventi', 'Fine dell''orario lavorativo standard.');

-- Giorni lavorativi
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`,`help`) VALUES (NULL, 'Giorni lavorativi', 'Lunedì,Martedì,Mercoledì,Giovedì,Venerdì', 'multiple[Lunedì,Martedì,Mercoledì,Giovedì,Venerdì,Sabato,Domenica]', '1', 'Interventi', '');

ALTER TABLE `zz_settings` CHANGE `help` `help` TEXT;

UPDATE `zz_settings` SET `help` = '<p>Impostare la maschera senza indicare l''anno per evitare il reset del contatore.</p><ul><li><b>####</b>: Numero progressivo del documento, con zeri non significativi per raggiungere il numero desiderato di caratteri</li><li><b>YYYY</b>: Anno corrente a 4 cifre</li><li><b>yy</b>: Anno corrente a 2 cifre</li></ul>' WHERE `zz_settings`.`nome` = 'Formato codice preventivi';

-- Fix nome hook Aggiornamenti
UPDATE `zz_hooks` SET `name` = 'Aggiornamenti' WHERE `class` = 'Modules\\Aggiornamenti\\UpdateHook';

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.codice' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Codice';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.id' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'id';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`query` = 'mg_articoli.descrizione' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Descrizione';
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Prezzo vendita ivato', 'IF( co_iva.percentuale IS NOT NULL, (mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita * co_iva.percentuale / 100), mg_articoli.prezzo_vendita + mg_articoli.prezzo_vendita*(SELECT co_iva.percentuale FROM co_iva INNER JOIN zz_settings ON co_iva.id=zz_settings.valore AND nome=\'Iva predefinita\')/100 )', 8, 1, 0, 1, '', '', 0, 0, 1),
(NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Q.tà impegnata', 'IFNULL(a.qta_impegnata, 0)', 10, 1, 0, 1, '', '', 0, 0, 1),
(NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Q.tà disponibile', 'qta-IFNULL(a.qta_impegnata, 0)', 11, 1, 0, 1, '', '', 0, 0, 1);

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order` = 9 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Q.tà';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`visible` = 1 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Fornitore';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`visible` = 1 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Prezzo di acquisto';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`visible` = 1 WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Prezzo di vendita';
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Articoli'), 'Barcode', 'mg_articoli.barcode', '2', '1', '0', '0', '', '', '1', '0', '1');

-- Aggiunto help per maschera codice attività
UPDATE `zz_settings` SET `help` = '<p>Impostare la maschera senza indicare l''anno per evitare il reset del contatore.</p><ul><li><b>####</b>: Numero progressivo dell''attività, con zeri non significativi per raggiungere il numero desiderato di caratteri</li><li><b>YYYY</b>: Anno corrente a 4 cifre</li><li><b>yy</b>: Anno corrente a 2 cifre</li></ul>' WHERE `zz_settings`.`nome` = 'Formato codice attività';

-- Rimosso stato completato dallo stato ordine Parzialmente evaso 
UPDATE `or_statiordine` SET `completato` = '0' WHERE `or_statiordine`.`descrizione` = 'Parzialmente evaso';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Fatture di vendita'), 'Imponibile', 'righe.imponibile', '5', '1', '0', '1', '', '', '1', '1', '1');
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order` = 6 WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Totale';

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT id FROM zz_modules WHERE `name`='Fatture di acquisto'), 'Imponibile', 'righe.imponibile', '5', '1', '0', '1', '', '', '1', '1', '1');
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order` = 6 WHERE `zz_modules`.`name` = 'Fatture di acquisto' AND `zz_views`.`name` = 'Totale';

UPDATE `zz_widgets` SET `help` = 'Crediti iva inclusa accumulati con i clienti durante tutti gli anni di attività.' WHERE `zz_widgets`.`name` = 'Crediti da clienti';
UPDATE `zz_widgets` SET `help` = 'Debiti iva inclusa accumulati con i fornitori durante tutti gli anni di attività.' WHERE `zz_widgets`.`name` = 'Debiti verso fornitori';

-- Fix relazione su modulo liste
ALTER TABLE `em_list_anagrafica` DROP FOREIGN KEY `em_list_anagrafica_ibfk_1`; ALTER TABLE `em_list_anagrafica` ADD CONSTRAINT `em_list_anagrafica_ibfk_1` FOREIGN KEY (`id_list`) REFERENCES `em_lists`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
