<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
}

if (App::debug()) {
    $operations['export-csv'] = [
        'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta selezionati').'</span> <span class="label label-danger" >beta</span>',
        'data' => [
            'msg' => tr('Vuoi davvero esportare un CSV con tutti gli impianti?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
            'blank' => true,
        ],
    ];
}

return $operations;
