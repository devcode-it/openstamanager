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
    case 'controlla_stampa':
        $date_start = get('date_start');
        $date_end = get('date_end');
        $id_print = get('id_print');
        $id_sezionale = get('id_sezionale');
        $dir = get('dir');

        $where_conditions = [
            'id_print='.prepare($id_print),
            'date_start='.prepare($date_start),
            'date_end='.prepare($date_end)
        ];

        if (!empty($id_sezionale)) {
            $where_conditions[] = 'id_sezionale='.prepare($id_sezionale);
        } else {
            $where_conditions[] = '(id_sezionale IS NULL OR id_sezionale = "")';
        }

        if (!empty($dir)) {
            $where_conditions[] = 'dir='.prepare($dir);
        } else {
            $where_conditions[] = '(dir IS NULL OR dir = "")';
        }

        $where_clause = implode(' AND ', $where_conditions);
        $stampa_definitiva = $dbo->fetchOne('SELECT id FROM co_stampecontabili WHERE '.$where_clause)['id'];

        echo json_encode($stampa_definitiva ?: 0);

        break;

    case 'controlla_sbilanci_libro_giornale':
        $date_start = get('date_start');
        $date_end = get('date_end');

        // Includo il file modutil per utilizzare la funzione verificaSbilanciLibroGiornale
        include_once __DIR__.'/../modutil.php';

        $risultato = verificaSbilanciLibroGiornale($date_start, $date_end);

        echo json_encode($risultato);

        break;
}
