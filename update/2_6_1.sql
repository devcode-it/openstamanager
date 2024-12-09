-- Allineamento vista Contratti
UPDATE `zz_modules` SET `options` = "
SELECT
    |select|
FROM
    `co_contratti`
    LEFT JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS `agente` ON `co_contratti`.`idagente` = `agente`.`idanagrafica`
    LEFT JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND |lang|)
    LEFT JOIN (SELECT `idcontratto`, SUM(`subtotale` - `sconto`) AS `totale_imponibile`, SUM(`subtotale` - `sconto` + `iva`) AS `totale` FROM `co_righe_contratti` GROUP BY `idcontratto`) AS righe ON `co_contratti`.`id` = `righe`.`idcontratto`
    LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(matricola, IF(nome != '', CONCAT(' - ', nome), '')) SEPARATOR '<br>') AS descrizione, my_impianti_contratti.idcontratto FROM my_impianti INNER JOIN my_impianti_contratti ON my_impianti.id = my_impianti_contratti.idimpianto GROUP BY my_impianti_contratti.idcontratto) AS impianti ON impianti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT um, SUM(qta) AS somma, idcontratto FROM co_righe_contratti GROUP BY um, idcontratto) AS orecontratti ON orecontratti.um = 'ore' AND orecontratti.idcontratto = co_contratti.id
    LEFT JOIN (SELECT in_interventi.id_contratto, SUM(ore) AS sommatecnici FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento = in_interventi.id GROUP BY in_interventi.id_contratto) AS tecnici ON tecnici.id_contratto = co_contratti.id
    LEFT JOIN `co_categorie_contratti` ON `co_contratti`.`id_categoria` = `co_categorie_contratti`.`id`
    LEFT JOIN `co_categorie_contratti_lang` ON (`co_categorie_contratti`.`id` = `co_categorie_contratti_lang`.`id_record` AND `co_categorie_contratti_lang`.|lang|)
    LEFT JOIN `co_categorie_contratti` AS sottocategorie ON `co_contratti`.`id_sottocategoria` = `sottocategorie`.`id`
    LEFT JOIN `co_categorie_contratti_lang` AS sottocategorie_lang ON (`sottocategorie`.`id` = `sottocategorie_lang`.`id_record` AND `sottocategorie_lang`.|lang|)
WHERE
    1=1 |segment(`co_contratti`.`id_segment`)| |date_period(custom,'|period_start|' >= `data_bozza` AND '|period_start|' <= `data_conclusione`,'|period_end|' >= `data_bozza` AND '|period_end|' <= `data_conclusione`,`data_bozza` >= '|period_start|' AND `data_bozza` <= '|period_end|',`data_conclusione` >= '|period_start|' AND `data_conclusione` <= '|period_end|',`data_bozza` >= '|period_start|' AND `data_conclusione` = NULL)|
HAVING 
    2=2" WHERE `name` = 'Contratti';

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Categoria', '`co_categorie_contratti_lang`.`title`', 16, 1, 0, 0, 0, '', '', 0, 0, 0, 0),
(@id_module, 'Sottocategoria', '`co_categorie_contratti_lang`.`title`', 17, 1, 0, 0, 0, '', '', 0, 0, 0, 0);

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Categoria' AND `zz_views`.`id_module` = @id_module), 'Categoria'),
(1, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Sottocategoria' AND `zz_views`.`id_module` = @id_module), 'Sottocategoria'),
(2, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Categoria' AND `zz_views`.`id_module` = @id_module), 'Category'),
(2, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Sottocategoria' AND `zz_views`.`id_module` = @id_module), 'Subcategory');

-- Miglioria impostazione Condizioni generali di fornitura
UPDATE `zz_settings_lang` SET `help` = 'Quanto qui definito verrà proposto come condizione di fornitura per tutti i contratti' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura contratti') AND `zz_settings_lang`.`id_lang` = 1;
UPDATE `zz_settings_lang` SET `help` = 'This text will be proposed as a supply condition for all contracts.' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura contratti') AND `zz_settings_lang`.`id_lang` = 2;
UPDATE `zz_settings_lang` SET `help` = 'Quanto qui definito verrà proposto come condizione di fornitura per tutti i preventivi' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura preventivi') AND `zz_settings_lang`.`id_lang` = 1;
UPDATE `zz_settings_lang` SET `help` = 'This text will be proposed as a supply condition for all quotations.' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura preventivi') AND `zz_settings_lang`.`id_lang` = 2;
  
-- Allineamento modulo Marchi
ALTER TABLE `mg_marchi` ADD `immagine` VARCHAR(255) NOT NULL;

-- Spostamento Adattatori di archiviazione in Strumenti
SET @id_strumenti = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Strumenti');
UPDATE `zz_modules` SET `parent` = @id_strumenti WHERE `name` = 'Adattatori di archiviazione';