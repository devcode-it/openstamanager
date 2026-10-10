-- Stampa Etichetta spedizione per il modulo DDT.

SET @id_module_ddt := (
    SELECT `id`
    FROM `zz_modules`
    WHERE `directory` = 'ddt'
    LIMIT 1
);

INSERT INTO `zz_prints`
    (`id_module`, `is_record`, `name`, `directory`, `previous`, `options`, `icon`, `version`, `compatibility`, `order`, `predefined`, `enabled`, `available_options`)
SELECT
    @id_module_ddt,
    1,
    'Etichetta spedizione',
    'etichetta_spedizione_ddt',
    'idddt',
    '{"format":"A6","orientation":"P","margin-top":5,"margin-bottom":5,"margin-left":5,"margin-right":5,"hide-header":true,"last-page-footer":false}',
    'fa fa-print',
    '1.0',
    '2.10.0',
    20,
    0,
    1,
    '[]'
WHERE @id_module_ddt IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `zz_prints`
      WHERE `name` = 'Etichetta spedizione'
        AND `id_module` = @id_module_ddt
  );

SET @id_print_etichetta := (
    SELECT `id`
    FROM `zz_prints`
    WHERE `name` = 'Etichetta spedizione'
      AND `id_module` = @id_module_ddt
    LIMIT 1
);

INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`)
SELECT 1, @id_print_etichetta, 'Etichetta spedizione', 'Etichetta spedizione DDT'
WHERE @id_print_etichetta IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `zz_prints_lang`
      WHERE `id_record` = @id_print_etichetta
        AND `id_lang` = 1
  );

INSERT INTO `zz_prints_lang` (`id_lang`, `id_record`, `title`, `filename`)
SELECT 2, @id_print_etichetta, 'Shipping label', 'DDT shipping label'
WHERE @id_print_etichetta IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `zz_prints_lang`
      WHERE `id_record` = @id_print_etichetta
        AND `id_lang` = 2
  );

UPDATE `zz_prints`
SET `icon` = 'fa fa-print'
WHERE `name` = 'Etichetta spedizione'
  AND `id_module` = @id_module_ddt;
