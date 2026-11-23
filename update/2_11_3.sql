-- Scadenzario: priorità alla data concordata sulle scadenze
UPDATE `zz_views` SET `query` = 'IF(data_concordata IS NOT NULL AND data_concordata != ''0000-00-00'', data_concordata, scadenza)'
WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = 'Data scadenza';

-- Colonna "Scadenza giorni": giorni calcolati sulla data effettiva
UPDATE `zz_views` SET `query` = 'DATEDIFF(IF(data_concordata IS NOT NULL AND data_concordata != ''0000-00-00'', data_concordata, co_scadenzario.scadenza), NOW())'
WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = 'Scadenza giorni';

-- Ordinamento di lista sulla data effettiva
UPDATE `zz_modules` SET `options` = REPLACE(`options`, '`scadenza` ASC', 'IF(`co_scadenzario`.`data_concordata` IS NOT NULL AND `co_scadenzario`.`data_concordata` != ''0000-00-00'', `co_scadenzario`.`data_concordata`, `co_scadenzario`.`scadenza`) ASC') WHERE `name` = 'Scadenzario';

UPDATE `zz_views` SET `order_by` = 'IF(data_concordata IS NOT NULL AND data_concordata != ''0000-00-00'', data_concordata, scadenza)'
WHERE `id_module` = (SELECT `id` FROM `zz_modules` WHERE `name` = 'Scadenzario') AND `name` = 'Data scadenza';

-- Segmento "Scadenzario completo": filtro periodo sulla data concordata altrimenti scadenza
UPDATE `zz_segments` SET `clause` = '(COALESCE(NULLIF(`co_scadenzario`.`data_concordata`, ''0000-00-00''), NULLIF(`co_scadenzario`.`scadenza`, ''0000-00-00'')) BETWEEN ''|period_start|'' AND ''|period_end|'' AND (codice_tipo_documento_fe NOT IN (''TD16'', ''TD17'', ''TD18'', ''TD19'', ''TD20'', ''TD21'', ''TD22'', ''TD23'', ''TD26'', ''TD27'', ''TD28'') OR codice_tipo_documento_fe IS NULL))'
WHERE `name` = 'Scadenzario completo';