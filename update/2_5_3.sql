-- Fix plugin Statistiche vendita
UPDATE `zz_plugins` SET `options` = '{\"main_query\": [{\"type\": \"table\", \"fields\": \"Articolo, Q.tà, Percentuale tot., Totale\", \"query\": \"SELECT (SELECT `id` FROM `zz_modules` WHERE `name` = ''Articoli'') AS _link_module_, mg_articoli.id AS _link_record_, ROUND(SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)),2) AS `Q.tà`, ROUND((SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) * 100 / (SELECT SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir=''entrata'' )),2) AS ''Percentuale tot.'', ROUND(SUM(IF(reversed=1, -(co_righe_documenti.subtotale - co_righe_documenti.sconto), (co_righe_documenti.subtotale - co_righe_documenti.sconto))),2) AS Totale, mg_articoli.id, CONCAT(mg_articoli.codice,'' - '',mg_articoli_lang.title) AS Articolo FROM co_documenti INNER JOIN co_statidocumento ON co_statidocumento.id = co_documenti.idstatodocumento INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id LEFT JOIN co_statidocumento_lang ON  (co_statidocumento.id = co_statidocumento_lang.id_record AND co_statidocumento_lang.id_lang = 1) INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo LEFT JOIN mg_articoli_lang ON (mg_articoli.id = mg_articoli_lang.id_record AND mg_articoli_lang.id_lang = 1) WHERE 1=1 AND co_tipidocumento.dir=''entrata'' AND (co_statidocumento_lang.title = ''Pagato'' OR co_statidocumento_lang.title = ''Parzialmente pagato'' OR co_statidocumento_lang.title = ''Emessa'' ) GROUP BY co_righe_documenti.idarticolo, mg_articoli_lang.title HAVING 2=2 ORDER BY SUM(IF(reversed=1, -co_righe_documenti.qta, co_righe_documenti.qta)) DESC\"}]}' WHERE `zz_plugins`.`name` = 'Statistiche vendita';

-- Allineamento vista Fatture di vendita
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_documenti`
    LEFT JOIN (SELECT SUM(`totale`) AS `totale`, `iddocumento` FROM `co_movimenti`  WHERE `totale` > 0 AND `primanota` = 1 GROUP BY `iddocumento`) AS `primanota` ON `primanota`.`iddocumento` = `co_documenti`.`id`
    LEFT JOIN `an_anagrafiche` ON `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND co_tipidocumento_lang.|lang|)
    LEFT JOIN (SELECT `iddocumento`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM((`subtotale` - `sconto` + `rivalsainps`) * `co_iva`.`percentuale` / 100) AS `iva` FROM `co_righe_documenti` LEFT JOIN `co_iva` ON `co_iva`.`id` = `co_righe_documenti`.`idiva` GROUP BY `iddocumento`) AS `righe` ON `co_documenti`.`id` = `righe`.`iddocumento`
    LEFT JOIN (SELECT `co_banche`.`id`, CONCAT(`co_banche`.`nome`, ' - ', `co_banche`.`iban`) AS `descrizione` FROM `co_banche` GROUP BY `co_banche`.`id`) AS `banche` ON `banche`.`id` =`co_documenti`.`id_banca_azienda`
	LEFT JOIN `co_statidocumento` ON `co_documenti`.`idstatodocumento` = `co_statidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.|lang|)
    LEFT JOIN `fe_stati_documento` ON `co_documenti`.`codice_stato_fe` = `fe_stati_documento`.`codice`
    LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento`.`codice` = `fe_stati_documento_lang`.`id_record` AND `fe_stati_documento_lang`.|lang|)
    LEFT JOIN `co_ritenuta_contributi` ON `co_documenti`.`id_ritenuta_contributi` = `co_ritenuta_contributi`.`id`
    LEFT JOIN (SELECT COUNT(id) as `emails`, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN(SELECT `id` FROM `zz_modules` WHERE name = 'Fatture di vendita') AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `co_documenti`.`id`
	LEFT JOIN `co_pagamenti` ON `co_documenti`.`idpagamento` = `co_pagamenti`.`id`
    LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND co_pagamenti_lang.|lang|)
    LEFT JOIN (SELECT `numero_esterno`, `id_segment`, `idtipodocumento`, `data` FROM `co_documenti` WHERE `co_documenti`.`idtipodocumento` IN( SELECT `id` FROM `co_tipidocumento` WHERE `dir` = 'entrata') AND `numero_esterno` != ''  |date_period(`co_documenti`.`data`)|   GROUP BY `id_segment`, `numero_esterno`, `idtipodocumento`HAVING COUNT(`numero_esterno`) > 1) dup ON `co_documenti`.`numero_esterno` = `dup`.`numero_esterno` AND `dup`.`id_segment` = `co_documenti`.`id_segment` AND `dup`.`idtipodocumento` = `co_documenti`.`idtipodocumento`
WHERE
    1=1 AND `dir` = 'entrata' |segment(`co_documenti`.`id_segment`)| |date_period(`co_documenti`.`data`)|
HAVING
    2=2
ORDER BY
    `co_documenti`.`data` DESC,
    CAST(`co_documenti`.`numero_esterno` AS UNSIGNED) DESC" WHERE `zz_modules`.`name` = 'Fatture di vendita';

-- Fix plugin Impianti del cliente
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [{\"type\": \"table\", \"fields\": \"Matricola, Nome, Data, Descrizione\", \"query\": \"SELECT id, (SELECT `id` FROM `zz_modules` WHERE `name` = ''Impianti'') AS _link_module_, id AS _link_record_, matricola AS Matricola, nome AS Nome, DATE_FORMAT(data, ''%d/%m/%Y'') AS Data, descrizione AS Descrizione FROM my_impianti WHERE idanagrafica=|id_parent| HAVING 2=2\"}]}' WHERE `zz_plugins`.`name` = 'Impianti del cliente';

UPDATE `zz_modules` SET `name` = 'Ddt in uscita' WHERE `zz_modules`.`name` = 'Ddt di vendita'; 
UPDATE `zz_modules` SET `name` = 'Ddt in entrata' WHERE `zz_modules`.`name` = 'Ddt di acquisto'; 

-- Fix plugin Contratti del cliente
UPDATE `zz_plugins` SET `options` = '{ \"main_query\": [ { \"type\": \"table\", \"fields\": \"Numero, Nome, Cliente, Totale, Stato, Predefinito\", \"query\": \"SELECT `co_contratti`.`id`, `numero` AS Numero, `co_contratti`.`nome` AS Nome, `an_anagrafiche`.`ragione_sociale` AS Cliente, FORMAT(`righe`.`totale_imponibile`,2) AS Totale, `co_staticontratti_lang`.`title` AS Stato, IF(`co_contratti`.`predefined`=1, ''SÌ'', ''NO'') AS Predefinito FROM `co_contratti` LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = ''Lingua'')) LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto` ) AS righe ON `co_contratti`.`id` =`righe`.`idcontratto` WHERE 1=1 AND `co_contratti`.`idanagrafica`=|id_parent| GROUP BY `co_contratti`.`id` HAVING 2=2 ORDER BY `co_contratti`.`id` ASC\"} ]}' WHERE `zz_plugins`.`name` = 'Contratti del cliente';

-- Fix vista sottocategorie in Articoli
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = '`sottocategorie_lang`.`title`' WHERE `zz_modules`.`name` = 'Articoli' AND `zz_views`.`name` = 'Sottocategoria';

-- Gestione solleciti automatici
INSERT INTO `em_templates` (`id`, `id_module`, `name`, `icon`, `tipo_reply_to`, `reply_to`, `cc`, `bcc`, `read_notify`, `predefined`, `note_aggiuntive`, `deleted_at`, `id_account`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Scadenzario'), 'Secondo sollecito di pagamento', 'fa fa-envelope', '', '', '', '', '0', '1', '', NULL, '1', NULL);
INSERT INTO `em_templates_lang` (`id`, `id_lang`, `id_record`, `title`, `subject`, `body`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `em_templates` WHERE `name`='Secondo sollecito di pagamento'), 'Secondo sollecito di pagamento', 'Sollecito di pagamento fattura {numero}', '', NULL);

INSERT INTO `em_templates` (`id`, `id_module`, `name`, `icon`, `tipo_reply_to`, `reply_to`, `cc`, `bcc`, `read_notify`, `predefined`, `note_aggiuntive`, `deleted_at`, `id_account`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Scadenzario'), 'Terzo sollecito di pagamento', 'fa fa-envelope', '', '', '', '', '0', '1', '', NULL, '1', NULL);
INSERT INTO `em_templates_lang` (`id`, `id_lang`, `id_record`, `title`, `subject`, `body`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `em_templates` WHERE `name`='Terzo sollecito di pagamento'), 'Terzo sollecito di pagamento', 'Sollecito di pagamento fattura {numero}', '', NULL);

INSERT INTO `em_templates` (`id`, `id_module`, `name`, `icon`, `tipo_reply_to`, `reply_to`, `cc`, `bcc`, `read_notify`, `predefined`, `note_aggiuntive`, `deleted_at`, `id_account`, `created_at`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name`='Scadenzario'), 'Notifica interna sollecito di pagamento', 'fa fa-envelope', '', '', '', '', '0', '1', '', NULL, '1', NULL);
INSERT INTO `em_templates_lang` (`id`, `id_lang`, `id_record`, `title`, `subject`, `body`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `em_templates` WHERE `name`='Notifica interna sollecito di pagamento'), 'Notifica interna sollecito di pagamento', 'Notifica interna sollecito di pagamento fattura {numero}', '', NULL);

UPDATE `zz_settings` SET `nome` = 'Template email primo sollecito' WHERE `zz_settings`.`nome` = 'Template email invio sollecito'; 
UPDATE `zz_settings_lang` SET `title` = 'Template email primo sollecito' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome`='Template email primo sollecito'); 

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`) VALUES (NULL, 'Template email secondo sollecito', (SELECT `id` FROM `em_templates` WHERE `name`='Secondo sollecito di pagamento'), 'query=SELECT `em_templates`.`id`, `name` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \"Lingua\"))', '1', 'Scadenzario', NULL, '3');
INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `zz_settings` WHERE `nome`='Template email secondo sollecito'), 'Template email secondo sollecito', '', NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`) VALUES (NULL, 'Template email terzo sollecito', (SELECT `id` FROM `em_templates` WHERE `name`='Terzo sollecito di pagamento'), 'query=SELECT `em_templates`.`id`, `name` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \"Lingua\"))', '1', 'Scadenzario', NULL, '4');
INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `zz_settings` WHERE `nome`='Template email terzo sollecito'), 'Template email terzo sollecito', '', NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`) VALUES (NULL, 'Template email mancato pagamento dopo i solleciti', (SELECT `id` FROM `em_templates` WHERE `name`='Notifica interna sollecito di pagamento'), 'query=SELECT `em_templates`.`id`, `name` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates_lang`.`id_record` = `em_templates`.`id` AND `em_templates_lang`.`id_lang` = (SELECT `valore` FROM `zz_settings` WHERE `nome` = \"Lingua\"))', '1', 'Scadenzario', NULL, '4');
INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `zz_settings` WHERE `nome`='Template email mancato pagamento dopo i solleciti'), 'Template email mancato pagamento dopo i solleciti', '', NULL);

INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `created_at`, `order`) VALUES (NULL, 'Indirizzo email mancato pagamento dopo i solleciti', '', 'string', '1', 'Scadenzario', NULL, '4');
INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`, `created_at`) VALUES (NULL, (SELECT `valore` FROM `zz_settings` WHERE `nome` = 'Lingua'), (SELECT `id` FROM `zz_settings` WHERE `nome`='Indirizzo email mancato pagamento dopo i solleciti'), 'Indirizzo email mancato pagamento dopo i solleciti', '', NULL);
