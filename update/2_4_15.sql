-- Aggiunta del campo per permettere la modifica delle Viste di default
INSERT INTO `zz_settings` (`nome`, `valore`, `tipo`, `editable`, `sezione`) VALUES ('Modifica Viste di default', '0', 'boolean', 0, 'Generali');

-- Retrofix
UPDATE `mg_articoli` SET `id_categoria` = NULL WHERE `codice` = 'DELETED';

-- Aggiunta colonna Rif. fattura per attività
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `format`, `default`, `visible`) VALUES
((SELECT `id` FROM `zz_modules` WHERE `name` = 'Interventi'), 'Rif. fattura', 'fattura.info', 17, 1, 0, 0, 1);

-- Aggiunta indice per idintervento in co_righe_documenti
ALTER TABLE `co_righe_documenti` ADD INDEX(`idintervento`);

-- Ore preavviso rinnovo con decimali per frazioni ore
ALTER TABLE `co_contratti` CHANGE `ore_preavviso_rinnovo` `ore_preavviso_rinnovo` DECIMAL(15,6) NULL DEFAULT NULL;

-- Canale aggiornamenti stable/pre-release
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Abilita canale pre-release per aggiornamenti', '0', 'boolean', '1', 'Generali', NULL, "Consente di recuperare dal canale di pre-release gli aggiornamenti NON stabili del gestionale.");

-- Per gli articoli con id_categoria e id_sottocategoria a 0 imposto il valore NULL (permesso dalla 2.4.14)
UPDATE `mg_articoli` SET `id_categoria` = NULL WHERE `mg_articoli`.`id_categoria` = 0;
UPDATE `mg_articoli` SET `id_sottocategoria` = NULL WHERE `mg_articoli`.`id_sottocategoria` = 0;

-- Allineamento title stampe
UPDATE `zz_prints` SET `title` = 'Ddt in uscita (senza prezzi)' WHERE `zz_prints`.`name` = 'Ddt di vendita (senza costi)';
UPDATE `zz_prints` SET `title` = 'Ddt in uscita' WHERE `zz_prints`.`name` = 'Ddt di vendita';

-- Rimozione stampe ordini di servizio a database
DELETE FROM `zz_prints` WHERE `zz_prints`.`name` = 'Ordine di servizio (senza costi)';
DELETE FROM `zz_prints` WHERE `zz_prints`.`name` = 'Ordine di servizio';
