-- Aggiunto sconto combinato
ALTER TABLE `co_righe_promemoria` CHANGE `tipo_sconto` `tipo_sconto` ENUM('UNT','PRC','PRC+') NOT NULL DEFAULT 'UNT';
ALTER TABLE `co_righe_promemoria` ADD `sconto_percentuale_combinato` VARCHAR(255) NULL AFTER `sconto_percentuale`;

-- Fix ordinamento record modulo fatture di vendita
UPDATE `zz_views` INNER JOIN `zz_modules` ON `zz_views`.`id_module`=`zz_modules`.`id` SET `zz_views`.`order_by` = 'CAST(REGEXP_REPLACE(co_documenti.numero_esterno, ''[^0-9]'', '''') AS UNSIGNED)' WHERE `zz_modules`.`name` = 'Fatture di vendita' AND `zz_views`.`name` = 'Numero';
UPDATE `zz_modules` SET `options` = REPLACE(`options`, 'CAST(`co_documenti`.`numero_esterno` AS UNSIGNED)', 'CAST(REGEXP_REPLACE(`co_documenti`.`numero_esterno`, ''[^0-9]'', '''') AS UNSIGNED)') WHERE `name` = 'Fatture di vendita' AND `options` LIKE '%CAST(`co_documenti`.`numero_esterno` AS UNSIGNED)%';

-- Cambio filtro Scadenzario completo
UPDATE `zz_segments` SET `clause` = '(`co_scadenziario`.`scadenza` BETWEEN \'|period_start|\' AND \'|period_end|\' AND (codice_tipo_documento_fe NOT IN (\'TD16\', \'TD17\', \'TD18\', \'TD19\', \'TD20\', \'TD21\', \'TD22\', \'TD23\', \'TD26\', \'TD27\', \'TD28\') OR codice_tipo_documento_fe IS NULL))' WHERE `zz_segments`.`name` = 'Scadenzario completo';
