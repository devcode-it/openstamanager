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

use Modules\Contratti\Contratto;

if (isset($id_record)) {
    $contratto = Contratto::find($id_record);

    $record = $dbo->fetchOne('SELECT 
        *,
        `co_contratti`.`nome` AS nome,
        `an_anagrafiche`.`tipo` AS tipo_anagrafica,
        `co_staticontratti`.`is_fatturabile` AS is_fatturabile,
        `co_staticontratti`.`is_pianificabile` AS is_pianificabile,
        `co_staticontratti`.`is_completato` AS is_completato,
        `co_staticontratti_lang`.`name` AS stato,
        GROUP_CONCAT(`my_impianti_contratti`.`idimpianto`) AS idimpianti
    FROM 
        `co_contratti`
        INNER JOIN `an_anagrafiche` ON `co_contratti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
        INNER JOIN `co_staticontratti` ON `co_contratti`.`idstato` = `co_staticontratti`.`id`
        LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `my_impianti_contratti` ON `my_impianti_contratti`.`idcontratto` = `co_contratti`.`id`
    WHERE 
        `co_contratti`.`id`='.prepare($id_record));
}
