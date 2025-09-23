-- fix permessi otp token
ALTER TABLE `zz_otp_tokens` CHANGE `permessi` `permessi` ENUM('r','rw','ra','rwa') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;

-- Nuovo sottomodulo "Statistiche vendita" sotto Articoli
SET @parent_id = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Articoli');
INSERT INTO `zz_modules` (`name`, `directory`, `options`, `options2`, `icon`, `version`, `compatibility`, `order`, `parent`, `default`, `enabled`, `use_notes`, `use_checklists`) VALUES
('Statistiche vendita', 'statistiche_articoli',
'SELECT
    |select|
FROM
    `co_documenti`
    INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
    LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`. |lang|)
    INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
    INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `co_righe_documenti`.`idarticolo`
    LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id` = `mg_articoli_lang`.`id_record` AND `mg_articoli_lang`. |lang|)
    LEFT JOIN (
        SELECT
            SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) AS totale_qta_generale
        FROM
            `co_documenti`
            INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
            INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
            INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `co_righe_documenti`.`idarticolo`
            INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
            LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = |lang|)
        WHERE
            `co_tipidocumento`.`dir` = \'entrata\'
            AND (`co_statidocumento_lang`.`title` = \'Pagato\' OR `co_statidocumento_lang`.`title` = \'Parzialmente pagato\' OR `co_statidocumento_lang`.`title` = \'Emessa\')
    ) AS `totali_generali` ON 1=1
WHERE
    1=1
    AND `co_tipidocumento`.`dir` = \'entrata\'
    AND (`co_statidocumento_lang`.`title` = \'Pagato\' OR `co_statidocumento_lang`.`title` = \'Parzialmente pagato\' OR `co_statidocumento_lang`.`title` = \'Emessa\')
GROUP BY
    `co_righe_documenti`.`idarticolo`, `mg_articoli_lang`.`title`
HAVING
    2=2
ORDER BY
    SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) DESC',
'', 'fa fa-circle-o', '2.9.0', '2.*', '1', @parent_id, '1', '1', '0', '0');

INSERT INTO `zz_modules_lang` (`id_lang`, `id_record`, `title`, `meta_title`) VALUES
('1', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Statistiche vendita'), 'Statistiche vendita', 'Statistiche vendita'),
('2', (SELECT `id` FROM `zz_modules` WHERE `name` = 'Statistiche vendita'), 'Sales statistics', 'Sales statistics');

-- Viste per il modulo Statistiche vendita
SET @id_module_stat = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Statistiche vendita');
INSERT INTO `zz_views` (`id_module`, `name`, `query`, `order`, `search`, `slow`, `format`, `search_inside`, `order_by`, `visible`, `summable`, `default`) VALUES
(@id_module_stat, 'id', '`mg_articoli`.`id`', 1, 1, 0, 0, '', '', 0, 0, 1),
(@id_module_stat, '_link_module_', '(SELECT `id` FROM `zz_modules` WHERE `name` = \'Articoli\')', 2, 0, 0, 0, '', '', 0, 0, 1),
(@id_module_stat, '_link_record_', '`mg_articoli`.`id`', 3, 0, 0, 0, '', '', 0, 0, 1),
(@id_module_stat, 'Articolo', 'CONCAT(`mg_articoli`.`codice`, \' - \', `mg_articoli_lang`.`title`)', 4, 1, 0, 0, '', '', 1, 0, 1),
(@id_module_stat, 'Q.ta', 'SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`))', 5, 1, 0, 1, '', '', 1, 1, 1),
(@id_module_stat, 'Percentuale Iva', 'ROUND((SUM(IF(`co_tipidocumento`.`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) * 100 / `totali_generali`.`totale_qta_generale`), 2)', 6, 1, 0, 1, '', '', 1, 0, 1),
(@id_module_stat, 'Totale', 'SUM(IF(`co_tipidocumento`.`reversed`=1, -(`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`)))', 7, 1, 0, 1, '', '', 1, 1, 1);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`) VALUES
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'id'), 'ID'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'id'), 'ID'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_module_'), '_link_module_'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_module_'), '_link_module_'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_record_'), '_link_record_'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = '_link_record_'), '_link_record_'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Articolo'), 'Articolo'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Articolo'), 'Article'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Q.ta'), 'Q.tà'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Q.ta'), 'Qty'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Percentuale Iva'), 'Percentuale Iva'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Percentuale Iva'), 'VAT Percentage'),
(1, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Totale'), 'Totale'),
(2, (SELECT `id` FROM `zz_views` WHERE `id_module` = @id_module_stat AND `name` = 'Totale'), 'Total');

DELETE FROM zz_plugins WHERE name = 'Statistiche vendita';