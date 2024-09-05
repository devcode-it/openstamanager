
-- Aggiunta API per DDT
INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES 
(NULL, 'v1', 'delete', 'righe_ddt', 'Modules\\DDT\\API\\v1\\Righe', '1'), 
(NULL, 'v1', 'update', 'righe_ddt', 'Modules\\DDT\\API\\v1\\Righe', '1'), 
(NULL, 'v1', 'create', 'righe_ddt', 'Modules\\DDT\\API\\v1\\Righe', '1'), 
(NULL, 'v1', 'retrieve', 'righe_ddt', 'Modules\\DDT\\API\\v1\\Righe', '1'), 
(NULL, 'v1', 'delete', 'ddt', 'Modules\\DDT\\API\\v1\\DDTS', '1'), 
(NULL, 'v1', 'update', 'ddt', 'Modules\\DDT\\API\\v1\\DDTS', '1'), 
(NULL, 'v1', 'create', 'ddt', 'Modules\\DDT\\API\\v1\\DDTS', '1'), 
(NULL, 'v1', 'retrieve', 'ddt', 'Modules\\DDT\\API\\v1\\DDTS', '1');

-- Modifica API righe interventi
UPDATE `zz_api_resources` SET `resource` = 'righe_intervento', `class` = 'Modules\\Interventi\\API\\v1\\Righe' WHERE `zz_api_resources`.`resource` = 'articoli_intervento';
UPDATE `zz_api_resources` SET `resource` = 'riga_intervento', `class` = 'Modules\\Interventi\\API\\v1\\Righe' WHERE `zz_api_resources`.`resource` = 'articolo_intervento';

INSERT INTO `zz_api_resources` (`id`, `version`, `type`, `resource`, `class`, `enabled`) VALUES 
(NULL, 'v1', 'update', 'riga_intervento', 'Modules\\Interventi\\API\\v1\\Righe', '1'),
(NULL, 'v1', 'delete', 'riga_intervento', 'Modules\\Interventi\\API\\v1\\Righe', '1');

-- Gestione mappa in plugin attività
INSERT INTO `zz_plugins` (`id`, `name`, `idmodule_from`, `idmodule_to`, `position`, `script`, `enabled`, `default`, `order`, `compatibility`, `version`, `options2`, `options`, `directory`, `help`) VALUES (NULL, 'Mostra su mappa', (SELECT `id` FROM `zz_modules` WHERE `name`= 'Interventi'), (SELECT `id` FROM `zz_modules` WHERE `name`= 'Interventi'), 'tab_main', 'mappa.php', '1', '0', '0', '', '', NULL, NULL, '', ''); 

INSERT INTO `zz_plugins_lang` (`id`, `id_lang`, `id_record`, `title`) VALUES (NULL, '1', (SELECT `zz_plugins`.`id` FROM `zz_plugins` WHERE `zz_plugins`.`name`='Mostra su mappa'), 'Mostra su mappa');

-- Aggiunta colonna Agente in Ordini cliente
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Agente', 'agente.ragione_sociale', 15, 1, 0, 0, 0, '', '', 0, 0, 0, 0);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Agente' AND `id_module` = @id_module), 'Agente');

-- Allineamento vista Ordini cliente
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
	`or_ordini`
    INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine` = `or_tipiordine`.`id`
    INNER JOIN `an_anagrafiche` ON `or_ordini`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS agente ON `or_ordini`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN (SELECT `idordine`, SUM(`qta` - `qta_evasa`) AS `qta_da_evadere`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `or_righe_ordini` GROUP BY `idordine`) AS righe ON `or_ordini`.`id` = `righe`.`idordine`
    LEFT JOIN (SELECT `idordine`, MIN(`data_evasione`) AS `data_evasione` FROM `or_righe_ordini` WHERE (`qta` - `qta_evasa`)>0 GROUP BY `idordine`) AS `righe_da_evadere` ON `righe`.`idordine`=`righe_da_evadere`.`idordine`
    INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
    LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'Fattura ',`co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idordine` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type`='Modules\Ordini\Ordine' GROUP BY original_document_id) AS `fattura` ON `fattura`.`idordine` = `or_ordini`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT 'DDT ', `dt_ddt`.`numero_esterno` SEPARATOR ', ') AS `info`, `dt_righe_ddt`.`original_document_id` AS `idddt` FROM `dt_ddt` INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id`=`dt_righe_ddt`.`idddt` WHERE `original_document_type`='Modules\Ordini\Ordine' GROUP BY original_document_id) AS `ddt` ON `ddt`.`idddt`=`or_ordini`.`id`
    LEFT JOIN (SELECT COUNT(`id`) as emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `id` FROM `zz_modules` WHERE `name` = 'Ordini cliente' AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AND `zz_operations`.`op` = 'send-email' GROUP BY id_record) AS email ON `email`.`id_record` = `or_ordini`.`id`
WHERE
    1=1 |segment(`or_ordini`.`id_segment`)| AND `dir` = 'entrata'  |date_period(`or_ordini`.`data`)|
HAVING
    2=2
ORDER BY 
	`data` DESC, 
    CAST(`numero_esterno` AS UNSIGNED) DESC" WHERE `zz_modules`.`name` = 'Ordini cliente';
