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

switch (filter('op')) {
    case 'crea_definitiva':
        $year = date('Y', strtotime(post('date_start')));

        $first_page = $dbo->fetchOne('SELECT MAX(last_page) AS last_page FROM co_stampecontabili WHERE `id_print`='.prepare(post('id_print')).' AND YEAR(`date_end`)='.prepare($year).' AND `dir`='.prepare(post('dir')))['last_page'] + 1;

        $print = Prints::render(post('id_print'), null, null, true, true, ['reset' => $first_page - 1, 'suppress' => 0]);
        $pages = count($print['pages']);
        $last_page = $first_page + $pages - 1;

        $result = $dbo->table('co_stampecontabili')->insertGetId([
            'id_print' => post('id_print'),
            'date_start' => post('date_start'),
            'date_end' => post('date_end'),
            'first_page' => $first_page,
            'last_page' => $last_page,
            'dir' => post('dir'),
        ]);

        $print = Prints::render(post('id_print'), null, null, true, true, ['reset' => $first_page - 1, 'suppress' => 0]);
        $name = 'Registro_iva_'.(post('dir') == 'entrata' ? 'vendite' : 'acquisti').'_del_'.post('date_start');
        $upload = Uploads::upload($print['pdf'], [
            'name' => $name,
            'original_name' => $name.'.pdf',
            'category' => 'Generale',
            'id_module' => $id_module,
            'id_record' => $result,
        ]);

        echo json_encode($result);

        break;
}
