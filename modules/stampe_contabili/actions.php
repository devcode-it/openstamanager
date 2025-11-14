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
        $id_print = post('id_print');
        $id_sezionale = post('id_sezionale');
        $dir = post('dir');
        $date_start = post('date_start');
        $date_end = post('date_end');

        $where_conditions = [
            '`id_print`='.prepare($id_print),
            'YEAR(`date_end`)='.prepare($year)
        ];

        if (!empty($id_sezionale)) {
            $where_conditions[] = '`id_sezionale`='.prepare($id_sezionale);
        } else {
            $where_conditions[] = '(`id_sezionale` IS NULL OR `id_sezionale` = "")';
        }

        if (!empty($dir)) {
            $where_conditions[] = '`dir`='.prepare($dir);
        } else {
            $where_conditions[] = '(`dir` IS NULL OR `dir` = "")';
        }

        $where_clause = implode(' AND ', $where_conditions);
        $first_page = $dbo->fetchOne('SELECT MAX(last_page) AS last_page FROM co_stampecontabili WHERE '.$where_clause)['last_page'] + 1;

        $print = Prints::render($id_print, null, null, true, true, ['reset' => $first_page - 1, 'suppress' => 0]);
        $pages = count($print['pages']);
        $last_page = $first_page + $pages - 1;

        $insert_data = [
            'id_print' => $id_print,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'first_page' => $first_page,
            'last_page' => $last_page,
        ];

        if (!empty($id_sezionale)) {
            $insert_data['id_sezionale'] = $id_sezionale;
        }

        if (!empty($dir)) {
            $insert_data['dir'] = $dir;
        }

        // Creazione movimento in prima nota per liquidazione IVA
        $idmastrino = null;
        $print_info = $dbo->fetchOne('SELECT name FROM zz_prints WHERE id = '.prepare($id_print));
        if ($print_info && $print_info['name'] === 'Liquidazione IVA') {
            $idmastrino = creaMovimentoLiquidazioneIva($date_start, $date_end);
            if ($idmastrino) {
                $insert_data['idmastrino'] = $idmastrino;
            }
        }

        $result = $dbo->table('co_stampecontabili')->insertGetId($insert_data);

        $print = Prints::render($id_print, null, null, true, true, ['reset' => $first_page - 1, 'suppress' => 0]);

        $print_name = $print['name'];
        $print_name = str_replace('.pdf', '', $print_name);
        $date_formatted = date('Y-m-d', strtotime($date_start));

        if (!empty($dir)) {
            $dir_text = ($dir == 'entrata') ? 'vendite' : 'acquisti';
            $name = $print_name.'_'.$dir_text.'_del_'.$date_formatted;
        } else {
            $name = $print_name.'_del_'.$date_formatted;
        }

        $upload = Uploads::upload($print['pdf'], [
            'name' => $name,
            'original_name' => $name.'.pdf',
            'category' => 'Generale',
            'id_module' => $id_module,
            'id_record' => $result,
        ]);

        echo json_encode(['first_page' => $first_page - 1]);

        break;
}
