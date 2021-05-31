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