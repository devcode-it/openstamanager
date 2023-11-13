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