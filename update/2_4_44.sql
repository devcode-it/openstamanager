-- Fix query vista Segmenti
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM
    `zz_segments`
    INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_segments`.`id_module`
    LEFT JOIN (SELECT GROUP_CONCAT(`zz_groups`.`nome` ORDER BY `zz_groups`.`nome`  SEPARATOR ', ') AS `gruppi`, `zz_group_segment`.`id_segment` FROM `zz_group_segment` INNER JOIN `zz_groups` ON `zz_groups`.`id` = `zz_group_segment`.`id_gruppo` GROUP BY  `zz_group_segment`.`id_segment`) AS `t` ON `t`.`id_segment` = `zz_segments`.`id`
WHERE
    1=1
HAVING
    2=2
ORDER BY `zz_segments`.`name`,
    `zz_segments`.`id_module`" WHERE `name` = 'Segmenti';

-- Aggiunta segmento Articoli disponibili
INSERT INTO `zz_segments` (`id`, `id_module`, `name`, `clause`, `position`, `pattern`, `note`, `dicitura_fissa`, `predefined`, `predefined_accredito`, `predefined_addebito`, `autofatture`, `is_sezionale`, `is_fiscale`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = "Articoli"), 'Disponibili', 'qta-IFNULL(a.qta_impegnata,0)', 'WHR', '####', '', '', '0', '0', '0', '0', '0', '0'); 

-- Aggiunta colonna stampa in vista moduli
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), '_print_', '''Preventivo''', 12, 0, 0, 0, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente'), '_print_', '''Ordine cliente''', 13, 0, 0, 0, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Ddt di vendita'), '_print_', '''Ddt di vendita''', 14, 0, 0, 0, 1);
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), '_print_', '''Fattura di vendita''', 14, 0, 0, 0, 1);

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `visible`, `default`) VALUES((SELECT `id` FROM `zz_modules` WHERE `name` = 'Fatture di vendita'), 'Prima nota', '`primanota`.`totale`', 15, 1, 0, 1, 0, 1);

-- Fix query Pagamenti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_pagamenti`
	LEFT JOIN(SELECT `fe_modalita_pagamento`.`codice`, CONCAT(`fe_modalita_pagamento`.`codice`, ' - ', `fe_modalita_pagamento`.`descrizione`) AS tipo FROM `fe_modalita_pagamento`) AS pagamenti ON `pagamenti`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe`
WHERE
    1=1
GROUP BY
    `co_pagamenti`.`id`
HAVING
    2=2" WHERE `name` = 'Pagamenti';

-- Fix vista Stampe
UPDATE `zz_prints` SET `options` = '{\"pricing\": true, \"last-page-footer\": true, \"hide-codice\": true, \"images\": true}' WHERE `zz_prints`.`name` = "Ordine cliente (senza codici)";
UPDATE `zz_prints` SET `options` = '{\"pricing\":true, \"hide-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (senza totali)"; 
UPDATE `zz_prints` SET `options` = '{\"pricing\":false, \"show-only-total\":true, \"images\": true}' WHERE `zz_prints`.`name` = "Preventivo (solo totale)";

-- Aggiunto deleted_at in tipi intervento e relazioni
ALTER TABLE `in_tipiintervento` ADD `deleted_at` TIMESTAMP NULL AFTER `tempo_standard`; 
ALTER TABLE `an_relazioni` ADD `deleted_at` TIMESTAMP NULL AFTER `is_bloccata`; 

UPDATE `zz_modules` SET `options` = 'SELECT \r\n|select|\r\nFROM\r\n    `an_anagrafiche`\r\nLEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`\r\nLEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`\r\nLEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`\r\nLEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nomesede SEPARATOR \', \') AS nomi FROM `an_sedi` GROUP BY idanagrafica) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`\r\nLEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nome SEPARATOR \', \') AS nomi FROM `an_referenti` GROUP BY idanagrafica) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`\r\nLEFT JOIN (SELECT `co_pagamenti`.`descrizione`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti`)AS pagvendita ON IF(`an_anagrafiche`.`idpagamento_vendite`>0,`an_anagrafiche`.`idpagamento_vendite`= `pagvendita`.`id`,\'\')\r\nLEFT JOIN (SELECT `co_pagamenti`.`descrizione`AS nome, `co_pagamenti`.`id` FROM `co_pagamenti`)AS pagacquisto ON IF(`an_anagrafiche`.`idpagamento_acquisti`>0,`an_anagrafiche`.`idpagamento_acquisti`= `pagacquisto`.`id`,\'\')\r\nWHERE\r\n    1=1 AND `an_anagrafiche`.`deleted_at` IS NULL\r\nGROUP BY\r\n    `an_anagrafiche`.`idanagrafica`, `pagvendita`.`nome`, `pagacquisto`.`nome`\r\nHAVING\r\n    2=2\r\nORDER BY\r\n    TRIM(`ragione_sociale`)' WHERE `zz_modules`.`name` = 'Anagrafiche';

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `in_tipiintervento` WHERE 1=1 AND deleted_at IS NULL HAVING 2=2' WHERE `zz_modules`.`name` = 'Tipi di intervento';
UPDATE `zz_modules` SET `options` = 'SELECT |select|\r\nFROM `an_relazioni`\r\nWHERE 1=1 AND deleted_at IS NULL\r\nHAVING 2=2\r\nORDER BY `an_relazioni`.`created_at` DESC' WHERE `zz_modules`.`name` = 'Relazioni';  