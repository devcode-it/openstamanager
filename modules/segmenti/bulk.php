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

switch (post('op')) {
    case 'set_groups':
        $id_gruppi = explode(',', post('gruppi', true)[0]);

        foreach ($id_records as $id) {
            // Aggiornamento dei permessi relativi
            $dbo->sync('zz_group_segment', ['id_segment' => $id], ['id_gruppo' => (array) $id_gruppi]);
        }

        flash()->info(tr('Gruppi con accesso ai segmenti aggiornati!'));

        break;
}

// Convert to array and filter out empty values
$records = array_filter((array) $id_records);

// Default query for when there are no valid records
$query = 'SELECT DISTINCT `zz_groups`.`id`, `title` AS descrizione FROM `zz_groups`
         LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record`
         AND `zz_groups_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
         WHERE `zz_groups`.`id` = 1
         ORDER BY `zz_groups`.`id` ASC';

// If we have valid records, use the full query with IN clause
if (!empty($records)) {
    $records_list = implode(',', $records);
    $query = 'SELECT DISTINCT `zz_groups`.`id`, `title` AS descrizione FROM `zz_groups`
             LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record`
             AND `zz_groups_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).")
             WHERE `zz_groups`.`id` = 1
             OR `zz_groups`.`id` IN (
                 SELECT DISTINCT `idgruppo` FROM `zz_permissions`
                 WHERE `permessi` IN ('r', 'rw')
                 AND `idmodule` IN (
                     SELECT DISTINCT `id_module` FROM `zz_segments`
                     WHERE `id` IN (".$records_list.')
                 )
             )
             ORDER BY `zz_groups`.`id` ASC';
}

$msg = '{[ "type": "select", "multiple":"1", "label": "<small>'.tr('Seleziona i gruppi che avranno accesso ai segmenti selezionati:').'</small>", "values": "query='.$query.'", "name": "gruppi[]" ]}';

$operations['set_groups'] = [
    'text' => '<span><i class="fa fa-users"></i> '.tr('Imposta l\'accesso ai segmenti').'</span>',
    'data' => [
        'title' => tr('Imposta l\'accesso ai segmenti.'),
        'msg' => $msg,
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
