-- Modificato plugin per la gestione del contratto predefinito
UPDATE `zz_plugins` SET `script` = '', `directory` = 'contratti_anagrafiche' WHERE `zz_plugins`.`name` = 'Contratti del cliente';

ALTER TABLE `co_contratti` ADD `predefined` BOOLEAN NOT NULL AFTER `condizioni_fornitura`;

-- Aggiunto select Agenda per impostazione Dashboard
UPDATE `zz_settings` SET `tipo` = 'list[mese,settimana,giorno,agenda]' WHERE `zz_settings`.`nome` = 'Vista dashboard'; 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Impianti', 'impianti.descrizione', '16', '1', '0', '0', '1', NULL, NULL, '0', '0', '1'); 

-- Allineamento totali dichiaranzione d'intento
UPDATE `co_dichiarazioni_intento` INNER JOIN (SELECT `co_documenti`.`id_dichiarazione_intento`, SUM(IF(`co_tipidocumento`.`reversed`=1, (-(`co_righe_documenti`.`subtotale`-`co_righe_documenti`.`sconto`)), (`co_righe_documenti`.`subtotale`-`co_righe_documenti`.`sconto`))) AS `totale` FROM `co_righe_documenti` INNER JOIN `co_documenti` ON `co_documenti`.`id`=`co_righe_documenti`.`iddocumento` INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id`=`co_documenti`.`idtipodocumento` INNER JOIN `co_iva` ON `co_iva`.`id`=`co_righe_documenti`.`idiva` WHERE `co_iva`.`codice_natura_fe`='N3.5' GROUP BY `co_documenti`.`id_dichiarazione_intento`) AS `righe` ON `righe`.`id_dichiarazione_intento`=`co_dichiarazioni_intento`.`id` SET `co_dichiarazioni_intento`.`totale`=`righe`.`totale`;