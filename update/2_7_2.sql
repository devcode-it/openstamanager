ALTER TABLE `co_pianodeiconti3` CHANGE `descrizione` `descrizione` VARCHAR(255) NOT NULL;

INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Residuo contratto', "IF((righe.totale_imponibile - spesacontratto.somma) != 0, righe.totale_imponibile - spesacontratto.somma, '')", '20', '1', '0', '0', '0', '', '', '1', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Residuo contratto' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), 'Residuo contratto'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Residuo contratto' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), 'Contract Residual');


-- Aggiunta colonna Note interne in Preventivi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), 'Note interne', "`co_preventivi`.`informazioniaggiuntive`", '18', '1', '0', '0', '0', '', '', '0', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi')), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi')), 'Notes');

-- Aggiunta colonna Note interne in Contratti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti'), 'Note interne', "`co_contratti`.`informazioniaggiuntive`", '18', '1', '0', '0', '0', '', '', '0', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), 'Note interne'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = 'Note interne' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Contratti')), 'Notes');

-- Aggiunta colonna _bg_ in Articoli
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli'), '_bg_', "IF(giacenze.stato_giacenza!=0, IF(giacenze.stato_giacenza>0, '#CCFFCC', '#ec5353'), '')", '16', '1', '0', '0', '0', '', '', '0', '0', '0');

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `name` = '_bg_' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')), '_bg_'),
(2, (SELECT `id` FROM `zz_views` WHERE `name` = '_bg_' AND `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli')), '_bg_');

UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `query` = '`mg_articoli`.`qta`' WHERE `zz_views`.`name` =  'Q.tà' AND `zz_modules`.`name` = 'Articoli';
UPDATE `zz_views` LEFT JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `query` = '`mg_articoli`.`qta`-IFNULL(a.qta_impegnata, 0)' WHERE `zz_views`.`name` =  'Q.tà disponibile' AND `zz_modules`.`name` = 'Articoli';

UPDATE `an_anagrafiche` SET `capitale_sociale` = 0 WHERE `capitale_sociale` NOT REGEXP '^[0-9]{1,15}(\\.[0-9]{1,6})?$';
ALTER TABLE `an_anagrafiche` CHANGE `capitale_sociale` `capitale_sociale` DECIMAL(15,6) NOT NULL DEFAULT 0;