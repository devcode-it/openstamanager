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

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'check_anagrafica_tipo':
        $id_anagrafica = get('id_anagrafica');
        $result = [
            'is_tecnico' => false,
            'tipi' => [],
        ];

        if (!empty($id_anagrafica)) {
            // Query per ottenere i tipi di anagrafica
            $query = 'SELECT `an_tipi_anagrafiche_lang`.`title` 
                     FROM `an_tipi_anagrafiche_anagrafiche` 
                     INNER JOIN `an_tipi_anagrafiche` ON `an_tipi_anagrafiche_anagrafiche`.`id_tipo_anagrafica` = `an_tipi_anagrafiche`.`id`
                     LEFT JOIN `an_tipi_anagrafiche_lang` ON (`an_tipi_anagrafiche`.`id` = `an_tipi_anagrafiche_lang`.`id_record` AND `an_tipi_anagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                     WHERE `an_tipi_anagrafiche_anagrafiche`.`id_anagrafica` = '.prepare($id_anagrafica);

            $rs = $dbo->fetchArray($query);

            foreach ($rs as $r) {
                $result['tipi'][] = $r['title'];
                if ($r['title'] == 'Tecnico') {
                    $result['is_tecnico'] = true;
                }
            }
        }

        echo json_encode($result);
        break;
}
