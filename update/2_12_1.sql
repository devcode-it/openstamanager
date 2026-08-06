-- Widget riepilogativi e filtri rapidi per lo Scadenzario
SET @id_module_scadenzario = (
    SELECT `id`
    FROM `zz_modules`
    WHERE `name` = 'Scadenzario'
      AND `directory` = 'scadenzario'
    LIMIT 1
);

INSERT INTO `zz_views` (
    `id_module`, `name`, `query`, `order`, `search`, `slow`, `format`,
    `html_format`, `search_inside`, `order_by`, `visible`, `summable`,
    `avg`, `default`
)
SELECT
    @id_module_scadenzario,
    'Stato scadenza',
    'IF(pagato=da_pagare,''paid'',IF(data_concordata<>''0000-00-00'',IF(data_concordata<NOW(),''agreed_overdue'',''agreed''),IF(scadenza<NOW(),''overdue'',IF(DATEDIFF(scadenza,NOW())<10,''due_soon'',''future''))))',
    120, 1, 0, 0, 0, '`Stato scadenza`', NULL, 1, 0, 0, 1
WHERE @id_module_scadenzario IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `zz_views`
      WHERE `id_module` = @id_module_scadenzario
        AND `name` = 'Stato scadenza'
  );

UPDATE `zz_views`
SET
    `query` = 'IF(pagato=da_pagare,''paid'',IF(data_concordata<>''0000-00-00'',IF(data_concordata<NOW(),''agreed_overdue'',''agreed''),IF(scadenza<NOW(),''overdue'',IF(DATEDIFF(scadenza,NOW())<10,''due_soon'',''future''))))',
    `order` = 120,
    `search` = 1,
    `slow` = 0,
    `format` = 0,
    `html_format` = 0,
    `search_inside` = '`Stato scadenza`',
    `order_by` = NULL,
    `visible` = 1,
    `summable` = 0,
    `avg` = 0,
    `default` = 1
WHERE `id_module` = @id_module_scadenzario
  AND `name` = 'Stato scadenza';

SET @id_view_stato_scadenza = (
    SELECT `id`
    FROM `zz_views`
    WHERE `id_module` = @id_module_scadenzario
      AND `name` = 'Stato scadenza'
    LIMIT 1
);

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 1, @id_view_stato_scadenza, 'Stato scadenza'
WHERE @id_view_stato_scadenza IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `zz_views_lang`
      WHERE `id_lang` = 1 AND `id_record` = @id_view_stato_scadenza
  );

INSERT INTO `zz_views_lang` (`id_lang`, `id_record`, `title`)
SELECT 2, @id_view_stato_scadenza, 'Due date status'
WHERE @id_view_stato_scadenza IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `zz_views_lang`
      WHERE `id_lang` = 2 AND `id_record` = @id_view_stato_scadenza
  );

UPDATE `zz_views_lang`
SET `title` = 'Stato scadenza'
WHERE `id_lang` = 1 AND `id_record` = @id_view_stato_scadenza;

UPDATE `zz_views_lang`
SET `title` = 'Due date status'
WHERE `id_lang` = 2 AND `id_record` = @id_view_stato_scadenza;

INSERT IGNORE INTO `zz_group_view` (`id_gruppo`, `id_vista`)
SELECT DISTINCT `zz_group_view`.`id_gruppo`, @id_view_stato_scadenza
FROM `zz_group_view`
INNER JOIN `zz_views` ON `zz_views`.`id` = `zz_group_view`.`id_vista`
WHERE `zz_views`.`id_module` = @id_module_scadenzario
  AND `zz_views`.`id` <> @id_view_stato_scadenza
  AND @id_view_stato_scadenza IS NOT NULL;
