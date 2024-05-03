-- Aggiunta tabella in_tags
CREATE TABLE `in_tags` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL
);

ALTER TABLE `in_tags`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `in_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- Aggiunta modulo Tags
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES ('Tags', 'tags', 'SELECT |select| FROM `in_tags` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.5.2', '2.5.2', '2', (SELECT `id` FROM `zz_modules` as b WHERE `name` = 'Interventi'), '1', '1', '1', '0');
INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`) VALUES ('1', (SELECT MAX(id) FROM `zz_modules`), 'Tags');

-- Aggiunta viste Tags
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags'), 'id', 'id', 1),
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Tags'), 'Nome','name', 1);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`)-1 FROM `zz_views` ), 'id'),
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Nome');

-- Aggiunta tabella in_interventi_tags
CREATE TABLE `in_interventi_tags` (
  `id` int NOT NULL,
  `id_intervento` int NOT NULL,
  `id_tag`int NOT NULL
);

ALTER TABLE `in_interventi_tags`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `in_interventi_tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- Allineamento vista Attività
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `in_interventi`
    LEFT JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `idintervento`, SUM(`prezzo_unitario`*`qta`-`sconto`) AS `ricavo_righe`, SUM(`costo_unitario`*`qta`) AS `costo_righe` FROM `in_righe_interventi` GROUP BY `idintervento`) AS `righe` ON `righe`.`idintervento` = `in_interventi`.`id`
    INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
    LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.|lang|)
    LEFT JOIN `an_referenti` ON `in_interventi`.`idreferente` = `an_referenti`.`id`
    LEFT JOIN (SELECT `an_sedi`.`id`, CONCAT(`an_sedi`.`nomesede`, '<br />',IF(`an_sedi`.`telefono`!='',CONCAT(`an_sedi`.`telefono`,'<br />'),''),IF(`an_sedi`.`cellulare`!='',CONCAT(`an_sedi`.`cellulare`,'<br />'),''),`an_sedi`.`citta`,IF(`an_sedi`.`indirizzo`!='',CONCAT(' - ',`an_sedi`.`indirizzo`),'')) AS `info` FROM `an_sedi`) AS `sede_destinazione` ON `sede_destinazione`.`id` = `in_interventi`.`idsede_destinazione`
    LEFT JOIN (SELECT GROUP_CONCAT(DISTINCT `co_documenti`.`numero_esterno` SEPARATOR ', ') AS `info`, `co_righe_documenti`.`original_document_id` AS `idintervento` FROM `co_documenti` INNER JOIN `co_righe_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento` WHERE `original_document_type` = 'Modules\\Interventi\\Intervento' GROUP BY `idintervento`, `original_document_id`) AS `fattura` ON `fattura`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tecnici_assegnati`.`id_intervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici_assegnati` ON `in_interventi_tecnici_assegnati`.`id_tecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `id_intervento`) AS `tecnici_assegnati` ON `in_interventi`.`id` = `tecnici_assegnati`.`id_intervento`
    LEFT JOIN (SELECT `in_interventi_tecnici`.`idintervento`, GROUP_CONCAT( DISTINCT `ragione_sociale` SEPARATOR ', ') AS `nomi` FROM `an_anagrafiche` INNER JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica` GROUP BY `idintervento`) AS `tecnici` ON `in_interventi`.`id` = `tecnici`.`idintervento`
    LEFT JOIN (SELECT COUNT(`id`) as emails, `em_emails`.`id_record` FROM `em_emails` INNER JOIN `zz_operations` ON `zz_operations`.`id_email` = `em_emails`.`id` WHERE `id_module` IN (SELECT `zz_modules`.`id` FROM `zz_modules` WHERE `name` = 'Interventi' AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AND `zz_operations`.`op` = 'send-email' GROUP BY `em_emails`.`id_record`) AS `email` ON `email`.`id_record` = `in_interventi`.`id`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != '', CONCAT(' - ', `nome`), '')) SEPARATOR '<br />') AS `descrizione`, `my_impianti_interventi`.`idintervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto` GROUP BY `my_impianti_interventi`.`idintervento`) AS `impianti` ON `impianti`.`idintervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `co_contratti`.`id`, CONCAT(`co_contratti`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_contratti`) AS `contratto` ON `contratto`.`id` = `in_interventi`.`id_contratto`
    LEFT JOIN (SELECT `co_preventivi`.`id`, CONCAT(`co_preventivi`.`numero`, ' del ', DATE_FORMAT(`data_bozza`, '%d/%m/%Y')) AS `info` FROM `co_preventivi`) AS `preventivo` ON `preventivo`.`id` = `in_interventi`.`id_preventivo`
    LEFT JOIN (SELECT `or_ordini`.`id`, CONCAT(`or_ordini`.`numero`, ' del ', DATE_FORMAT(`data`, '%d/%m/%Y')) AS `info` FROM `or_ordini`) AS `ordine` ON `ordine`.`id` = `in_interventi`.`id_ordine`
    INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.|lang|)
    LEFT JOIN (SELECT GROUP_CONCAT(' ', `zz_files`.`name`) as name, `zz_files`.`id_record` FROM `zz_files` INNER JOIN `zz_modules` ON `zz_files`.`id_module` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|) WHERE `zz_modules`.`name` = 'Interventi' GROUP BY id_record) AS files ON `files`.`id_record` = `in_interventi`.`id`
    LEFT JOIN `in_interventi_tags` ON `in_interventi_tags`.`id_intervento` = `in_interventi`.`id`
    LEFT JOIN (SELECT `in_interventi_tags`.`id_intervento`, GROUP_CONCAT( DISTINCT `name` SEPARATOR ', ') AS `nomi` FROM `in_tags` INNER JOIN `in_interventi_tags` ON `in_interventi_tags`.`id_tag` = `in_tags`.`id`) AS `tags` ON `in_interventi`.`id` = `tags`.`id_intervento`
WHERE 
    1=1 |segment(`in_interventi`.`id_segment`)| |date_period(`orario_inizio`,`data_richiesta`)|
GROUP BY 
    `in_interventi`.`id`
HAVING 
    2=2
ORDER BY 
    IFNULL(`orario_fine`, `data_richiesta`) DESC" WHERE `zz_modules`.`name` = 'Interventi';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(`files`.`name` != '', `files`.`name`, 'No')' WHERE `zz_modules`.`name` = 'Interventi' AND `zz_views`.`name` = 'Allegati';

-- Aggiunta colonna Tags in Attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Tags', 'tags.nomi', 10);
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT MAX(`id`) FROM `zz_views` ), 'Tags');