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

use Modules\Impianti\Export\CSV;
use Modules\Impianti\Impianto;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'export-csv':
        $file = temp_file();
        $exporter = new CSV($file);

        // Esportazione dei record selezionati
        $fatture = Impianto::whereIn('id', $id_records)->get();
        $exporter->setRecords($fatture);

        $count = $exporter->exportRecords();

        download($file, 'impianti.csv');

        break;

    // Rimuovo impianto e scollego tutti i suoi componenti
    case 'delete-bulk':
        foreach ($id_records as $id) {
            $dbo->query('DELETE FROM my_impianti WHERE id='.prepare($id));
        }

        flash()->info(tr('Impianti e relativi componenti eliminati!'));

        break;
}

$operations['export-csv'] = [
    'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi esportare un CSV con tutti gli impianti?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => true,
    ],
];

$operations['delete-bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero eliminare gli impianti selezionati?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

return $operations;
