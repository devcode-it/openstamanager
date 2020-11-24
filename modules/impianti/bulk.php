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

include_once __DIR__.'/../../core.php';

use Modules\Impianti\Impianto;

switch (post('op')) {
   
    case 'download-csv':
        
        $dir = base_dir().'/files/export_impianti/';
        directory($dir.'tmp/');
        $file = secure_random_string().'.csv';
        $dir_csv = slashes($dir.'tmp/'.$file);

        $filename = 'impianti.csv';

        $t = new Modules\Impianti\Export\CSV($dir_csv);

        if($t->exportRecords()){
            
            download($dir_csv, $filename);
            delete($dir.'tmp/');
        }

        break;
}

if (App::debug()) {
    $operations['download-csv'] = [
        'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta tutto').'</span> <span class="label label-danger" >beta</span>',
        'data' => [
            'msg' => tr('Vuoi davvero esportare un CSV con tutti gli impianti?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
            'blank' => true,
        ],
    ];
}

return $operations;