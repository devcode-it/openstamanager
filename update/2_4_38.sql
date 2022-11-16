-- Aggiunto modulo Listini clienti
RENAME TABLE `mg_listini` TO `mg_piani_sconto`;
ALTER TABLE `an_anagrafiche` CHANGE `idlistino_acquisti` `id_piano_sconto_acquisti` INT(11) NULL DEFAULT NULL; 
ALTER TABLE `an_anagrafiche` CHANGE `idlistino_vendite` `id_piano_sconto_vendite` INT(11) NULL DEFAULT NULL; 
ALTER TABLE `an_anagrafiche` ADD `id_listino` INT NOT NULL AFTER `id_piano_sconto_acquisti`; 

CREATE TABLE `mg_listini` ( `id` INT NOT NULL AUTO_INCREMENT , `nome` VARCHAR(255) NOT NULL , `data_attivazione` DATE NULL , `data_scadenza_predefinita` DATE NULL , `is_sempre_visibile` BOOLEAN NOT NULL , `note` TEXT NOT NULL , `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)); 

CREATE TABLE `mg_listini_articoli` ( `id` INT NOT NULL AUTO_INCREMENT , `id_listino` INT NOT NULL, `id_articolo` INT NOT NULL , `data_scadenza` DATE NOT NULL , `prezzo_unitario` DECIMAL(15,6) NOT NULL , `prezzo_unitario_ivato` DECIMAL(15,6) NOT NULL , `sconto_percentuale` DECIMAL(15,6) NOT NULL , `dir` VARCHAR(20) NOT NULL , `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , `updated_at` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`)); 

INSERT INTO `zz_modules` (`id`, `name`, `title`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES (NULL, 'Listini cliente', 'Listini cliente', 'listini_cliente', 'SELECT |select| FROM `mg_listini` WHERE 1=1 HAVING 2=2', '', 'fa fa-angle-right', '2.*', '2.*', '2', (SELECT `id` FROM `zz_modules` AS `t` WHERE `t`.`name`='Magazzino'), '1', '1', '0', '0');

INSERT INTO `zz_views` ( `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'id', 'id', 1, 1, 0, 0, 0, '', '', 0, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Nome', 'nome', 2, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Data attivazione', 'data_attivazione', 3, 1, 0, 1, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Articoli', '(SELECT COUNT(id) FROM mg_listini_articoli WHERE id_listino=mg_listini.id)', 4, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Anagrafiche', '(SELECT COUNT(idanagrafica) FROM an_anagrafiche WHERE id_listino=mg_listini.id)', 5, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Ultima modifica', '(SELECT username FROM zz_users WHERE id=(SELECT id_utente FROM zz_operations WHERE id_module=(SELECT id FROM zz_modules WHERE name=\'Listini cliente\') AND id_record=mg_listini.id ORDER BY id DESC LIMIT 0,1))', 6, 1, 0, 0, 0, '', '', 1, 0, 1),
((SELECT `id` FROM `zz_modules` WHERE name='Listini cliente'), 'Sempre visibile', 'IF(is_sempre_visibile=0,\'NO\',\'SÌ\')', 7, 1, 0, 0, 0, '', '', 1, 0, 1);

UPDATE `zz_plugins` SET `title` = 'Netto clienti', `name` = 'Netto Clienti' WHERE `zz_plugins`.`name` = 'Listino Clienti'; 

ALTER TABLE `mg_articoli` ADD `minimo_vendita` DECIMAL(15,6) NOT NULL AFTER `prezzo_vendita_ivato`; 
ALTER TABLE `mg_articoli` ADD `minimo_vendita_ivato` DECIMAL(15,6) NOT NULL AFTER `minimo_vendita`; 

-- Aggiunto task invio mail
INSERT INTO `zz_tasks` (`id`, `name`, `class`, `expression`, `next_execution_at`, `last_executed_at`) VALUES (NULL, 'Invio automatico mail', 'Modules\\Emails\\EmailTask', '*/1 * * * *', NULL, NULL);

-- Ottimizzazione query vista anagrafiche
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'sedi.nomi' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Sedi';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.citta' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Città';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.codice_destinatario' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Codice destinatario';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'an_anagrafiche.telefono' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Telefono';
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module` = `zz_modules`.`id` SET `zz_views`.`query` = 'referenti.nomi' WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Referenti';

UPDATE `zz_modules` SET `options` = "SELECT 
|select|
FROM
    `an_anagrafiche`
LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id`
LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nomesede SEPARATOR ', ') AS nomi FROM `an_sedi` GROUP BY idanagrafica) AS sedi ON `an_anagrafiche`.`idanagrafica`= `sedi`.`idanagrafica`
LEFT JOIN (SELECT `idanagrafica`, GROUP_CONCAT(nome SEPARATOR ', ') AS nomi FROM `an_referenti` GROUP BY idanagrafica) AS referenti ON `an_anagrafiche`.`idanagrafica` =`referenti`.`idanagrafica`
WHERE
    1=1 AND `deleted_at` IS NULL
GROUP BY
    `an_anagrafiche`.`idanagrafica`
HAVING
    2=2
ORDER BY
    TRIM(`ragione_sociale`)" WHERE `name` = 'Anagrafiche';
