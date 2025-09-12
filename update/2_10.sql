-- Aggiunta vista per le newsletter
SELECT @id_module := `id` FROM `zz_modules` WHERE `name` = 'Anagrafiche';
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `html_format`, `search_inside`, `order_by`, `visible`, `summable`, `avg`, `default`) VALUES
(@id_module, 'Opt-in newsletter', 'IF(an_anagrafiche.enable_newsletter=1,\'SI\',\'NO\')', '20', '1', '0', '0', '0', NULL, NULL, '1', '0', '0', '1');

SELECT @id_record := `id` FROM `zz_views` WHERE `id_module` = @id_module AND `name` = 'Opt-in newsletter';
INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, @id_record, 'Opt-in newsletter'),
(2, @id_record, 'Opt-in newsletter');

-- Aggiornamento flag Rientrabile
UPDATE `dt_causalet` SET `is_rientrabile` = '1' WHERE `dt_causalet`.`name` = 'Conto lavorazione';
UPDATE `dt_causalet` SET `is_rientrabile` = '1' WHERE `dt_causalet`.`name` = 'Conto visione';