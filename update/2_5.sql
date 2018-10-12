-- Foreign keys an_tipianagrafiche
ALTER TABLE `an_tipianagrafiche_anagrafiche` DROP FOREIGN KEY `an_tipianagrafiche_anagrafiche_ibfk_1`;

ALTER TABLE `an_tipianagrafiche` CHANGE `idtipoanagrafica` `id` int(11) NOT NULL AUTO_INCREMENT;

DELETE FROM `an_tipianagrafiche_anagrafiche` WHERE `idtipoanagrafica` NOT IN (SELECT `id` FROM `an_tipianagrafiche`);
ALTER TABLE `an_tipianagrafiche_anagrafiche` ADD FOREIGN KEY (`idtipoanagrafica`) REFERENCES `an_tipianagrafiche`(`id`) ON DELETE CASCADE;

UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `an_anagrafiche` LEFT JOIN `an_relazioni` ON `an_anagrafiche`.`idrelazione` = `an_relazioni`.`id` LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica`=`an_anagrafiche`.`idanagrafica` LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` WHERE 1=1 AND `deleted_at` IS NULL GROUP BY `an_anagrafiche`.`idanagrafica` HAVING 2=2 ORDER BY TRIM(`ragione_sociale`)' WHERE `name` = 'Anagrafiche';

UPDATE `zz_views` SET `query` = 'id' WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Tipi di anagrafiche') AND `name` = 'id';

UPDATE `zz_settings` SET `tipo` = 'query=SELECT `an_anagrafiche`.`idanagrafica` AS ''id'', `ragione_sociale` AS ''descrizione'' FROM `an_anagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica` WHERE `idtipoanagrafica` = (SELECT `id` FROM `an_tipianagrafiche` WHERE `descrizione` = ''Azienda'') AND deleted_at IS NULL' WHERE `zz_settings`.`nome` = 'Azienda predefinita';

UPDATE `zz_views` SET `search_inside` = 'idanagrafica IN(SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica IN (SELECT id FROM an_tipianagrafiche WHERE descrizione LIKE |search|))' WHERE `zz_views`.`id` = 3;

UPDATE `zz_widgets` SET `query` = REPLACE(`query`, 'an_tipianagrafiche.idtipoanagrafica', 'an_tipianagrafiche.id');
