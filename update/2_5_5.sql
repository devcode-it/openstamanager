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
(NULL, 'Gestore mappa', 'Google Maps', 'list[Google Maps, OpenStreetMap]', '1', 'Generale', NULL);

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Geolocalizzazione automatica'), 'Geolocalizzazione automatica', '');

INSERT INTO `zz_settings_lang` (`id`, `id_lang`, `id_record`, `title`, `help`) VALUES (NULL, '1', (SELECT `zz_settings`.`id` FROM `zz_settings` WHERE `zz_settings`.`nome` = 'Gestore mappa'), 'Gestore mappa', '');