SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Categoria', '`co_categorie_contratti_lang`.`title`', 16, 1, 0, 0, 0, '', '', 0, 0, 0, 0),
(@id_module, 'Sottocategoria', '`co_categorie_contratti_lang`.`title`', 17, 1, 0, 0, 0, '', '', 0, 0, 0, 0);

SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Contratti';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Categoria' AND `zz_views`.`id_module` = @id_module), 'Categoria'),
(1, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Sottocategoria' AND `zz_views`.`id_module` = @id_module), 'Sottocategoria'),
(2, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Categoria' AND `zz_views`.`id_module` = @id_module), 'Category'),
(2, (SELECT `zz_views`.`id` FROM `zz_views` WHERE `zz_views`.`name` = 'Sottocategoria' AND `zz_views`.`id_module` = @id_module), 'Subcategory');

-- Miglioria impostazione Condizioni generali di fornitura
UPDATE `zz_settings_lang` SET `help` = 'Quanto qui definito verrà proposto come condizione di fornitura per tutti i contratti' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura contratti') AND `zz_settings_lang`.`id_lang` = 1;
UPDATE `zz_settings_lang` SET `help` = 'This text will be proposed as a supply condition for all contracts.' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura contratti') AND `zz_settings_lang`.`id_lang` = 2;
UPDATE `zz_settings_lang` SET `help` = 'Quanto qui definito verrà proposto come condizione di fornitura per tutti i preventivi' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura preventivi') AND `zz_settings_lang`.`id_lang` = 1;
UPDATE `zz_settings_lang` SET `help` = 'This text will be proposed as a supply condition for all quotations.' WHERE `zz_settings_lang`.`id_record` = (SELECT `id` FROM `zz_settings` WHERE `nome` = 'Condizioni generali di fornitura preventivi') AND `zz_settings_lang`.`id_lang` = 2;
  
-- Allineamento modulo Marchi
ALTER TABLE `mg_marchi` ADD `immagine` VARCHAR(255) NOT NULL;

-- Spostamento Adattatori di archiviazione in Strumenti
SET @id_strumenti = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Strumenti');
UPDATE `zz_modules` SET `parent` = @id_strumenti WHERE `name` = 'Adattatori di archiviazione';