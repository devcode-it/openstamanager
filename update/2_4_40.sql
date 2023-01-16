-- Fix query viste Utenti e permessi
UPDATE `zz_modules` SET `options` = "SELECT
    |select|
FROM 
    `zz_groups` 
    LEFT JOIN (SELECT `zz_users`.`idgruppo`, COUNT(`id`) AS num FROM `zz_users` GROUP BY `idgruppo`) AS utenti ON `zz_groups`.`id`=`utenti`.`idgruppo`
WHERE 
    1=1
HAVING 
    2=2 
ORDER BY 
    `id`, 
    `nome` ASC" WHERE `name` = 'Utenti e permessi';