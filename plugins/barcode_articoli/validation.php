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
    case 'barcode':
        // Controllo duplicati nella tabella principale mg_articoli (campo barcode)
        // Verifica che il barcode non sia già utilizzato come barcode principale di un altro articolo
        $disponibile = Articolo::where([
            ['barcode', $value],
        ])->count() == 0;

        // Controllo duplicati nella tabella mg_articoli_barcode (barcode multipli)
        // Verifica che il barcode non sia già presente tra i barcode aggiuntivi di altri articoli
        // Esclude il record corrente se stiamo modificando un barcode esistente
        if ($disponibile) {
            $query = $dbo->table('mg_articoli_barcode')
                ->where('barcode', $value);

            // Se stiamo modificando un barcode esistente, escludiamo il record corrente dalla verifica
            if (!empty($id_record) && $id_record != 0) {
                $query->where('id', '<>', $id_record);
            }

            $disponibile = $query->count() == 0;
        }

        // Controllo se il barcode coincide con un codice articolo esistente
        // Evita conflitti tra barcode e codici articolo per articoli senza barcode principale
        if ($disponibile) {
            $disponibile = Articolo::where([
                ['codice', $value],
                ['barcode', '=', ''],
            ])->count() == 0;
        }

        $message = $disponibile ? tr('Il barcode è disponibile') : tr('Il barcode è già utilizzato in un altro articolo o nei suoi barcode aggiuntivi');

        $response = [
            'result' => $disponibile,
            'message' => $message,
        ];

        break;
}
