-- Allineamento vista Utenti e Permessi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select| 
FROM
    `zz_groups`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` WHERE `zz_users`. `enabled` = 1 GROUP BY `idgruppo`) AS utenti_abilitati ON `zz_groups`.`id`=`utenti_abilitati`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_tokens`.`id`) AS num FROM `zz_users` INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente` WHERE `zz_tokens`. `enabled` = 1 GROUP BY `idgruppo`) AS api_abilitate ON `zz_groups`.`id`=`api_abilitate`.`idgruppo`
    LEFT JOIN (SELECT `zz_modules_lang`.`title`, `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)) AS `module` ON `module`.`id`=`zz_groups`.`id_module_start`
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `id`, `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi';

-- Geolocalizzazione automatica
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`) VALUES 
(NULL, 'Geolocalizzazione automatica', '1', 'boolean', '1', 'Anagrafiche', NULL),
(NULL, 'Gestore mappa', 'OpenStreetMap', 'list[Google Maps,OpenStreetMap]', '1', 'Generali', NULL);

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Geolocalizzazione automatica'), 'Geolocalizzazione automatica', '');

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Gestore mappa'), 'Gestore mappa', '');

-- Fix widget statistiche
UPDATE `zz_widgets` SET `class` = 'col-md-6' WHERE `zz_widgets`.`name` = "Spazio utilizzato"; 

-- Gestione tipi destinatari e autocompletamenti destinatari nelle mail in uscita
ALTER TABLE `em_templates` 
  ADD `type` varchar(5) NOT NULL DEFAULT 'a' AFTER `note_aggiuntive`, 
  ADD `indirizzi_proposti` TINYINT NOT NULL DEFAULT '0' AFTER `type`; 

-- Aggiunta visualizzazione satellite in mappa
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES 
(NULL, 'Tile server satellite', 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', 'string', '1', 'Generali');

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES 
(NULL, '1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Tile server satellite'), 'Tile server satellite', ''), 
(NULL, '2', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Tile server satellite'), 'Satellite tile server', '');

-- Aggiunto flag Attivo in Template
ALTER TABLE `em_templates` ADD `enabled` BOOLEAN NOT NULL DEFAULT TRUE AFTER `note_aggiuntive`;