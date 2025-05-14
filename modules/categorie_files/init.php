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

use Modules\CategorieFiles\Categoria;

if (!empty($id_record)) {
    $categoria = Categoria::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
        (SELECT COUNT(`id`) FROM `zz_files` WHERE `id_category` = '.prepare($id_record).') AS doc_associati
    FROM 
        `zz_files_categories`
    WHERE 
        `zz_files_categories`.`id`='.prepare($id_record).'
    GROUP BY 
        `zz_files_categories`.`id`');
}
