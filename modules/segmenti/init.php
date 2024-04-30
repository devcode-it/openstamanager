<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

use PHPSQLParser\PHPSQLParser;

if (!empty($id_record)) {
    $record = $dbo->fetchOne('SELECT 
            `zz_segments`.*, 
            `zz_segments_lang`.`title`,
            `zz_modules`.`options`, 
            `zz_modules_lang`.`title` AS modulo, 
            (SELECT COUNT(`t`.`id`) FROM `zz_segments` t WHERE `t`.`id_module` = `zz_segments`.`id_module`) AS n_sezionali 
        FROM 
            `zz_segments` 
            LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
            INNER JOIN `zz_modules` ON `zz_modules`.`id` = `zz_segments`.`id_module` 
            LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
        WHERE 
            `zz_segments`.`id`='.prepare($id_record));

    $parser = new PHPSQLParser();
    $parsed = $parser->parse($record['options']);
    $table = $parsed['FROM'][0]['table'];

    if ($record['is_sezionale'] == 1) {
        $righe = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM '.$table.' WHERE `id_segment` = '.prepare($id_record));
        $tot = $righe[0]['tot'];
    } else {
        $tot = 0;
    }
}
