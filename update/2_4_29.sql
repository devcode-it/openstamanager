-- Aggiunto condizioni fornitura in contratti
ALTER TABLE `co_contratti` ADD `condizioni_fornitura` TEXT NOT NULL AFTER `informazioniaggiuntive`; 

UPDATE `zz_settings` SET `nome` = 'Condizioni generali di fornitura preventivi' WHERE `zz_settings`.`nome` = 'Condizioni generali di fornitura';
INSERT INTO `zz_settings` (`id`, `nome`, `valore`, `tipo`, `editable`, `sezione`, `order`, `help`) VALUES (NULL, 'Condizioni generali di fornitura contratti', '', 'ckeditor', '1', 'Contratti', NULL, NULL);

-- Rimossa visualizzazione stampe disabilitate
UPDATE `zz_modules` SET `options` = 'SELECT |select| FROM `zz_prints` WHERE 1=1 AND enabled=1 HAVING 2=2' WHERE `zz_modules`.`name` = 'Stampe'; 

-- Filtro che esclude gli articoli eliminati dal widget degli articoli in esaurimento
UPDATE `zz_widgets` SET `query` = 'SELECT COUNT(id) AS dato FROM mg_articoli WHERE qta < threshold_qta AND attivo=1 AND deleted_at IS NULL' WHERE `zz_widgets`.`name` = 'Articoli in esaurimento'; 