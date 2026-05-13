-- Miglioramento query per modulo Utenti e permessi
UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `zz_groups`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_users`.`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id` = `utenti`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_users`.`id`) AS num FROM `zz_users` WHERE `zz_users`.`enabled` = 1 GROUP BY `idgruppo`) AS utenti_abilitati ON `zz_groups`.`id` = `utenti_abilitati`.`idgruppo`
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`zz_tokens`.`id`) AS num FROM `zz_users` INNER JOIN `zz_tokens` ON `zz_users`.`id` = `zz_tokens`.`id_utente` WHERE `zz_tokens`.`enabled` = 1 GROUP BY `idgruppo`) AS api_abilitate ON `zz_groups`.`id` = `api_abilitate`.`idgruppo`
    LEFT JOIN (SELECT `zz_modules_lang`.`title`, `zz_modules`.`id` FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules_lang`.`id_record` = `zz_modules`.`id` AND `zz_modules_lang`.|lang|)) AS `module` ON `module`.`id` = `zz_groups`.`id_module_start`
WHERE
    1=1
HAVING
    2=2
ORDER BY
    `id`, `nome` ASC' WHERE `zz_modules`.`name` = 'Utenti e permessi';

-- fix: invio via mail token otp
ALTER TABLE `em_emails` CHANGE `created_by` `created_by` INT(11) NULL;

-- fix: dimensioni QR Code
UPDATE `zz_prints` SET `options` = '{\"width\": 40, \"height\": 30, \"format\": [40, 30], \"margins\": {\"top\": 1,\"bottom\": 0,\"left\": 0,\"right\": 0}}' WHERE `zz_prints`.`id` = 56;

-- Anagrafiche: colonna Zone con nome - descrizione
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id`
SET `zz_views`.`query` = 'CONCAT_WS('' - '', an_zone.nome, an_zone.descrizione)'
WHERE `zz_modules`.`name` = 'Anagrafiche' AND `zz_views`.`name` = 'Zone';

-- Marche: parent = 0 diventa NULL
ALTER TABLE `zz_marche` CHANGE `parent` `parent` INT NULL DEFAULT NULL;
UPDATE `zz_marche` SET `parent` = NULL WHERE `parent` = 0;
UPDATE `mg_articoli` SET `id_marca` = NULL WHERE `id_marca` = 0;

UPDATE `zz_modules` SET `options` = 'SELECT
    |select|
FROM
    `zz_marche`
WHERE
    1=1
    AND
    `parent` IS NULL
HAVING
    2=2
ORDER BY
    `name`' WHERE `zz_modules`.`name` = 'Marche';

ALTER TABLE `in_fasceorarie` ADD `name` VARCHAR(255) NOT NULL AFTER `id`;
ALTER TABLE `mg_causali_movimenti` ADD `name` VARCHAR(255) NOT NULL AFTER `id`; 
ALTER TABLE `co_categorie_contratti` ADD `name` VARCHAR(255) NOT NULL AFTER `id`; 
ALTER TABLE `mg_attributi` ADD `name` VARCHAR(255) NOT NULL AFTER `id`; 


-- Indici per categoria e sottocategoria
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_categoria` (`id_categoria`);
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_sottocategoria` (`id_sottocategoria`);

-- Indici per marca e modello
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_marca` (`id_marca`);
ALTER TABLE `mg_articoli` ADD INDEX `idx_id_modello` (`id_modello`);

-- Indice per iva di vendita
ALTER TABLE `mg_articoli` ADD INDEX(`idiva_vendita`);

-- Correzione in fase di creazione nuovo token
ALTER TABLE `zz_otp_tokens` CHANGE `id_module_target` `id_module_target` INT(11) NULL;
ALTER TABLE `zz_otp_tokens` CHANGE `id_record_target` `id_record_target` INT(11) NULL;
