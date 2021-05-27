-- Colonna n. protocollo per fatture di acquisto
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Fatture di acquisto'), 'N. Prot.', 'co_documenti.numero', 1, 1, 0, 0, '', '', 0, 0, 0);
 
-- Formattazione colonna data modulo Ordini fornitore
UPDATE `zz_views` SET `format`=1 WHERE `zz_views`.`name`='Data' AND `zz_views`.`id_module`=(SELECT `id` FROM `zz_modules` WHERE name='Ordini fornitore');