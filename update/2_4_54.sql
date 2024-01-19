-- Aggiunto colore in per gruppi utenti
ALTER TABLE `zz_groups` ADD `theme` VARCHAR(25) NULL AFTER `id_module_start`;

-- Aggiunta colonna modulo iniziale in utenti e permessi
UPDATE `zz_modules` SET `options` = 'SELECT\n |select|\nFROM \n `zz_groups` \n LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`\n LEFT JOIN (SELECT `zz_modules`.`title`, `zz_modules`.`id` FROM `zz_modules`) AS `module` ON `module`.`id`=`zz_groups`.`id_module_start`\nWHERE \n 1=1\nHAVING \n 2=2 \nORDER BY \n `id`, \n `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi'; 

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'Modulo iniziale', '`module`.`title`', '5', '1', '0', '0', '0', '', '', '1', '0', '0');

-- Aggiunta colonna tema in utenti e permessi
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES ((SELECT `id` FROM `zz_modules` WHERE name = 'Utenti e permessi'), '_bg_', 'zz_groups.theme', '4', '0', '0', '1', '0', '', '', '0', '0', '0');

INSERT INTO `zz_views` (`id`, `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES (NULL, (SELECT `id` FROM `zz_modules` WHERE `name` = 'Utenti e permessi'), 'Tema', '`zz_groups`.`theme`', '4', '1', '0', '0', '0', '', '', '1', '0', '0'); 

