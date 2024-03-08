-- Aggiunta importazione impianti
INSERT INTO `zz_imports` (`name`, `class`) VALUES ('Impianti', 'Modules\\Impianti\\Import\\CSV');

-- Aggiunta importazione attività
INSERT INTO `zz_imports` (`name`, `class`) VALUES ('Attività', 'Modules\\Interventi\\Import\\CSV');

ALTER TABLE `my_impianti_categorie` ADD `parent` INT NULL DEFAULT NULL; 
ALTER TABLE `my_impianti` ADD `id_sottocategoria` INT NULL DEFAULT NULL AFTER `id_categoria`; 
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `my_impianti_categorie` WHERE 1=1 AND parent IS NULL HAVING 2=2' WHERE `zz_modules`.`name` = 'Categorie impianti'; 

-- Aggiornamento vista Impianti
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Impianti'), 'Sottocategoria', 'sub.nome', '7', '1', '0', '0', '0', '', '', '1', '0', '0');
UPDATE `zz_modules` SET `options` = "SELECT
    |select| 
FROM
    `my_impianti`
    LEFT JOIN `an_anagrafiche` AS clienti ON `clienti`.`idanagrafica` = `my_impianti`.`idanagrafica`
    LEFT JOIN `an_anagrafiche` AS tecnici ON `tecnici`.`idanagrafica` = `my_impianti`.`idtecnico` 
    LEFT JOIN `my_impianti_categorie` ON `my_impianti_categorie`.`id` = `my_impianti`.`id_categoria`
    LEFT JOIN `my_impianti_categorie` as sub ON sub.`id` = `my_impianti`.`id_sottocategoria`
    LEFT JOIN (SELECT an_sedi.id, CONCAT(an_sedi.nomesede, '<br />',IF(an_sedi.telefono!='',CONCAT(an_sedi.telefono,'<br />'),''),IF(an_sedi.cellulare!='',CONCAT(an_sedi.cellulare,'<br />'),''),an_sedi.citta,IF(an_sedi.indirizzo!='',CONCAT(' - ',an_sedi.indirizzo),'')) AS info FROM an_sedi
) AS sede ON sede.id = my_impianti.idsede
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `matricola`" WHERE `name` = 'Impianti';

-- Serial in Contratti
ALTER TABLE `mg_prodotti` ADD `id_riga_contratto` INT NULL AFTER `id_riga_intervento`;
ALTER TABLE `mg_prodotti` ADD FOREIGN KEY (`id_riga_contratto`) REFERENCES `co_righe_contratti`(`id`) ON DELETE CASCADE;

-- Aggiunta stampa preventivo (solo totale imponibile)
INSERT INTO `zz_prints` (`id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`, `available_options`) VALUES ((SELECT `id` FROM `zz_modules` WHERE `name` = 'Preventivi'), '1', 'Preventivo(solo totale imponibile)', 'Preventivo (solo totale imponibile)', 'Preventivo num. {numero} del {data} rev {revisione}', 'preventivi', 'idpreventivo', '{\"pricing\": false, \"last-page-footer\": true, \"images\": true, \"no-iva\":true, \"show-only-total\":true }', 'fa fa-print', '', '', '0', '0', '1', '1', '{\"pricing\":\"Visualizzare i prezzi\", \"hide-total\": \"Nascondere i totali delle righe\", \"show-only-total\": \"Visualizzare solo i totali del documento\", \"hide-header\": \"Nascondere intestazione\", \"hide-footer\": \"Nascondere footer\", \"last-page-footer\": \"Visualizzare footer solo su ultima pagina\", \"hide-item-number\": \"Nascondere i codici degli articoli\"}');

-- Aggiunta indice per ricerca su files più rapida
ALTER TABLE `zz_files` ADD INDEX(`id_record`);

ALTER TABLE `co_scadenziario` ADD `id_pagamento` INT NOT NULL;
ALTER TABLE `co_scadenziario` ADD `id_banca_azienda` INT NULL;
ALTER TABLE `co_scadenziario` ADD `id_banca_controparte` INT NULL;   

-- Aggiunta nuovi campi in sedi per gestione automezzi
ALTER TABLE `an_sedi`
    ADD `nome` VARCHAR(225) NULL DEFAULT NULL ,
    ADD `descrizione` VARCHAR(225) NULL DEFAULT NULL AFTER `nome` ,
    ADD `targa` VARCHAR(225) NULL DEFAULT NULL AFTER `descrizione` ,
    ADD `is_automezzo` TINYINT NOT NULL DEFAULT '0' AFTER `targa` ;


-- Creazione modulo Automezzi
INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Automezzi', 'Automezzi', 'automezzi', 'SELECT |select| FROM an_sedi INNER JOIN zz_settings ON (zz_settings.valore=an_sedi.idanagrafica AND zz_settings.nome=\'Azienda predefinita\' ) WHERE 1=1 AND an_sedi.is_automezzo=1 HAVING 2=2 ORDER BY nomesede ASC', '', 'fa fa-angle-right', '1.0', '2.4.16', '100', (SELECT `t`.`id` FROM `zz_modules` AS `t` WHERE `t`.`name` = 'Magazzino'), '0', '1', '0', '0');

--Viste modulo automezzi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), 'id', 'an_sedi.id', 0, 1, 0, 0, '', '', 0, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), 'Targa', 'an_sedi.targa', 2, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), 'Nome', 'an_sedi.nome', 3, 1, 0, 0, '', '', 1, 0, 0),
((SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), 'Descrizione', 'an_sedi.descrizione', 4, 1, 0, 0, '', '', 1, 0, 0);

-- Tabella per associazione automezzi-tecnici
CREATE TABLE IF NOT EXISTS `an_sedi_tecnici` (
    `id` INT NOT NULL AUTO_INCREMENT , 
    `idsede` INT NOT NULL ,  
    `idtecnico` INT NOT NULL ,  
    `data_inizio` DATE NULL DEFAULT NULL,
    `data_fine` DATE NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
); 

-- Aggiunta indici
ALTER TABLE `an_sedi_tecnici` 
    ADD INDEX(`idsede`),
    ADD INDEX(`idtecnico`);

ALTER TABLE `an_sedi_tecnici` ADD CONSTRAINT `an_sedi_tecnici_ibfk_1` FOREIGN KEY (`idsede`) REFERENCES `an_sedi`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION; 

ALTER TABLE `an_sedi_tecnici` ADD CONSTRAINT `an_sedi_tecnici_ibfk_2` FOREIGN KEY (`idtecnico`) REFERENCES `an_anagrafiche`(`idanagrafica`) ON DELETE CASCADE ON UPDATE NO ACTION; 

-- Aggiunta stampe automezzi
INSERT INTO `zz_prints` (`id`, `id_module`, `is_record`, `name`, `title`, `filename`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `default`, `enabled`) VALUES 
(NULL, (SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), '0', 'Giacenza automezzi', 'Giacenza automezzi', 'giacenza automezzo', 'automezzi_inventario', '', '', '', '', '', '1', '0', '0', '1'),
(NULL, (SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), '0', 'Giacenza odierna automezzi', 'Giacenza odierna automezzi', 'giacenza automezzo', 'automezzi_carico', '', '', '', '', '', '1', '0', '0', '1');

-- Widget modulo automezzi
INSERT INTO `zz_widgets` (`id`, `name`, `type`, `id_module`, `location`, `class`, `query`, `bgcolor`, `icon`, `print_link`, `more_link`, `more_link_type`, `php_include`, `text`, `enabled`, `order`, `help`) VALUES 
(NULL, 'Stampa carico odierno', 'print', (SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), 'controller_top', 'col-md-4', '', '#37a02d', 'fa fa-print', '', 'var data_carico = prompt(\'Data del carico da stampare?\', moment(new Date()).format(\'DD/MM/YYYY\'));\r\nif ( data_carico != null){ \r\nwindow.open(\'pdfgen.php?id_print=54&search_targa=\'+$(\'#th_Targa input\').val()+\'&search_nome=\'+$(\'#th_Nome input\').val()+\'&data_carico=\'+data_carico); \r\n}', 'javascript', '', 'Stampa carico odierno', '1', '2', NULL), 
(NULL, 'Stampa giacenza', 'print', (SELECT `id` FROM `zz_modules` WHERE name='Automezzi'), 'controller_top', 'col-md-4', '', '#45a9f1', 'fa fa-truck', '', 'if( confirm(\'Stampare la giacenza attuale sugli automezzi?\') ){ window.open(\'pdfgen.php?id_print=53&search_targa=\'+$(\'#th_Targa input\').val()+\'&search_nome=\'+$(\'#th_Nome input\').val()); }', 'javascript', '', 'Stampa giacenza', '1', '1', NULL);

-- Aggiunta flag rappresentante fiscale per sede
ALTER TABLE `an_sedi` ADD `is_rappresentante_fiscale` BOOLEAN NULL DEFAULT FALSE; 

-- Aggiunte impostazioni per definire il numero di decimali in stampa
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Cifre decimali per importi in stampa", '2', 'list[0,1,2,3,4,5]', 1, 'Generali', '35', 'Definisce il numero di decimali per gli importi nei template di stampa');
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Cifre decimali per quantità in stampa", '0', 'list[0,1,2,3,4,5]', 1, 'Generali', '36', 'Definisce il numero di decimali per le quantità nei template di stampa');
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES ("Cifre decimali per totali in stampa", '2', 'list[0,1,2]', 1, 'Generali', '37', 'Definisce il numero di decimali per i totali nei template di stampa');

UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'IF(emails IS NOT NULL, \'fa fa-envelope text-success\', \'\')' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'icon_Inviata';