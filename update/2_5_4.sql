
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