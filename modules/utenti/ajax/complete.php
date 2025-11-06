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
        $idanagrafica = get('idanagrafica');
        $result = [
            'is_tecnico' => false,
            'tipi' => [],
        ];

        if (!empty($idanagrafica)) {
            // Query per ottenere i tipi di anagrafica
            $query = 'SELECT `an_tipianagrafiche_lang`.`title` 
                     FROM `an_tipianagrafiche_anagrafiche` 
                     INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche`.`id`
                     LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                     WHERE `an_tipianagrafiche_anagrafiche`.`idanagrafica` = '.prepare($idanagrafica);

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
