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

use Modules\Articoli\Articolo;

$name = filter('name');
$value = filter('value');

switch ($name) {
    case 'codice':
        $disponibile = Articolo::where([
            ['codice', $value],
            ['id', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr('Il codice è disponbile') : tr('Il codice è già utilizzato in un altro articolo');

        $response = [
            'result' => $disponibile,
            'message' => $message,
        ];

        break;

    case 'barcode':
        $disponibile = Articolo::where([
            ['barcode', $value],
            ['id', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr('Il barcode è disponbile') : tr('Il barcode è già utilizzato in un altro articolo');

        $response = [
            'result' => $disponibile,
            'message' => $message,
        ];

        break;
}
