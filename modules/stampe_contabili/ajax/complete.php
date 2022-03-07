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
        $dir = get('dir');

        $stampa_definitiva = $database->fetchOne('SELECT id FROM co_stampecontabili WHERE id_print='.prepare($id_print).' AND dir='.prepare($dir).' AND date_start='.prepare($date_start).' AND date_end='.prepare($date_end))['id'];

        echo json_encode($stampa_definitiva ?: 0);

        break;
}
