-- Fix nome colonna 'Cliente' ddt vendita e acquisto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`name` = 'Ragione sociale' WHERE `zz_modules`.`name` = 'Ddt di vendita' AND `zz_views`.`name` = 'Cliente';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`name` = 'Ragione sociale' WHERE `zz_modules`.`name` = 'Ddt di acquisto' AND `zz_views`.`name` = 'Cliente';
-- Rinomino colonna Rel. in Relazione
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`name` = 'color_Relazione', `search_inside` = 'color_title_Relazione' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'color_Rel.';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`name` = 'color_title_Relazione' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'color_title_Rel.';
-- Rinomino colonna Tipologia in Tipo
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`name` = 'Tipo' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Tipologia';
-- Nuova colonna Tipologia
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche'), 'Tipologia', 'tipo', 3, 1, 0, 0, NULL, NULL, 1, 0, 0);

-- Fix dei widget con nuova colonna Tipo
UPDATE `zz_widgets` SET `more_link` = 'if($(\'#th_Tipo input\').val()!= \'Tecnico\'){ $(\'#th_Tipo input\').val(\'Tecnico\').trigger(\'keyup\');} else reset(\'Tipo\');' WHERE `zz_widgets`.`name` = 'Numero di tecnici';

UPDATE `zz_widgets` SET `more_link` = 'if($(\'#th_Tipo input\').val()!= \'Cliente\'){ $(\'#th_Tipo input\').val(\'Cliente\').trigger(\'keyup\');} else reset(\'Tipo\');' WHERE `zz_widgets`.`name` = 'Numero di clienti';

UPDATE `zz_widgets` SET `more_link` = 'if($(\'#th_Tipo input\').val()!= \'Fornitore\'){ $(\'#th_Tipo input\').val(\'Fornitore\').trigger(\'keyup\');} else reset(\'Tipo\');' WHERE `zz_widgets`.`name` = 'Numero di fornitori';

UPDATE `zz_widgets` SET `more_link` = 'if($(\'#th_Tipo input\').val()!= \'Agente\'){ $(\'#th_Tipo input\').val(\'Agente\').trigger(\'keyup\');} else reset(\'Tipo\');' WHERE `zz_widgets`.`name` = 'Numero di agenti';

UPDATE `zz_widgets` SET `more_link` = 'if($(\'#th_Tipo input\').val()!= \'Vettore\'){ $(\'#th_Tipo input\').val(\'Vettore\').trigger(\'keyup\');} else reset(\'Tipo\');' WHERE `zz_widgets`.`name` = 'Numero di vettori';

UPDATE `zz_widgets` SET `more_link` = 'reset(\'Tipo\');' WHERE `zz_widgets`.`name` = 'Tutte le anagrafiche';

-- Fix del campo codice_xml
ALTER TABLE `co_documenti` CHANGE `codice_xml` `progressivo_invio` VARCHAR(255);

-- Aggiunto codice cig e codice cup per fatture e ordini
ALTER TABLE `or_ordini` ADD `codice_cig` VARCHAR(15) AFTER `tipo_sconto_globale`, ADD `codice_cup` VARCHAR(15) AFTER `codice_cig`, ADD `id_documento_fe` VARCHAR(20) AFTER `codice_cup`;
ALTER TABLE `co_righe_documenti` ADD `data_inizio_periodo` date, ADD `data_fine_periodo` date, ADD `riferimento_amministrazione` VARCHAR(20), ADD `tipo_cessione_prestazione` enum('SC', 'PR', 'AB', 'AC') DEFAULT NULL;

ALTER TABLE `co_documenti` ADD `tipo_resa` VARCHAR(3);

-- Colonna nome impianto
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'MyImpianti'), 'Nome', 'nome', 2, 1, 0, 0, NULL, NULL, 1, 0, 1);

-- Colonna causale predefinita
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Causali'), 'Predefinita', 'IF(predefined, ''Sì'', ''No'')', 2, 1, 0, 0, NULL, NULL, 0, 0, 0);

-- Colonna porto predefinita
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Porto'), 'Predefinita', 'IF(predefined, ''Sì'', ''No'')', 2, 1, 0, 0, NULL, NULL, 0, 0, 0);

-- Nascondo colonna id inutile per porto
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`visible` = 0 WHERE `zz_modules`.`name` = 'Porto' AND `zz_views`.`name` = 'id';
-- Colonna tipi di spedizione predefinita
INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default` ) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di spedizione'), 'Predefinita', 'IF(predefined, ''Sì'', ''No'')', 2, 1, 0, 0, NULL, NULL, 0, 0, 0);

-- Fix plugin
UPDATE `zz_plugins` SET `directory` = 'exportFE' WHERE `idmodule_to` = (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di vendita') AND `name` = 'Fatturazione Elettronica';
UPDATE `zz_plugins` SET `directory` = 'importFE' WHERE `idmodule_to` = (SELECT `id` FROM `zz_modules` WHERE `name`='Fatture di acquisto') AND `name` = 'Fatturazione Elettronica';

-- Codice destinatario sedi
ALTER TABLE `an_sedi` ADD `codice_destinatario` varchar(7);
