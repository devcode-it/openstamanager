-- Aggiunta segmento Articoli disponibili
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = "Articoli"), 'Disponibili', 'qta-IFNULL(a.qta_impegnata,0)', 'WHR', '####', '', '', '0', '0', '0', '0', '0', '0'); 

-- Aggiunta colonna stampa in vista moduli
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), '_print_', '''Preventivo''', 12, 0, 0, 0, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), '_print_', '''Ordine cliente''', 13, 0, 0, 0, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), '_print_', '''Ddt di vendita''', 14, 0, 0, 0, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), '_print_', '''Fattura di vendita''', 14, 0, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Prima nota', '`primanota`.`totale`', 15, 1, 0, 1, 0, 1);

-- Fix vista Stampe
UPDATE `zz_prints` SET `options` = '{\"pricing\": true, \"last-page-footer\": true, \"hide-codice\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Ordine cliente (senza codici)";
UPDATE `zz_prints` SET `options` = '{\"pricing\":true, \"hide-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (senza totali)"; 
UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"show-only-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (solo totale)";

-- Aggiunto deleted_at in tipi intervento e relazioni
ALTER TABLE `in_tipiintervento` ADD `deleted_at` TIMESTAMP NULL AFTER `tempo_standard`; 
ALTER TABLE `an_relazioni` ADD `deleted_at` TIMESTAMP NULL AFTER `is_bloccata`; 
