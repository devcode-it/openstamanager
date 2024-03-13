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

if (isset($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT 
            `co_preventivi`.*,
            `an_anagrafiche`.`tipo` AS tipo_anagrafica,
            `co_statipreventivi`.`is_fatturabile`,
            `co_statipreventivi`.`is_pianificabile`,
            `co_statipreventivi`.`is_completato`,
            `co_statipreventivi`.`is_revisionabile`,
            `co_statipreventivi_lang`.`name` AS stato
        FROM 
            `co_preventivi` 
            INNER JOIN `an_anagrafiche` ON `co_preventivi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
            LEFT JOIN `co_statipreventivi` ON `co_preventivi`.`idstato`=`co_statipreventivi`.`id`
            LEFT JOIN `co_statipreventivi_lang` ON (`co_preventivi`.`idstato`=`co_statipreventivi_lang`.`id_record` AND `co_statipreventivi_lang`.`id_lang`='.prepare(\App::getLang()).')
        WHERE 
            `co_preventivi`.`id`='.prepare($id_record));
}
