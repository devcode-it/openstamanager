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
   

    case 'export-bulk':

        $t = new Modules\Impianti\Export\CSV(__DIR__.'\..\..\files\impianti\Impianti.csv');
      
        if($t->exportRecords())
            flash()->info(tr('Esportazione riuscita!'));

        break;
}

if (App::debug()) {
    $operations['export-bulk'] = [
        'text' => '<span><i class="fa fa-upload"></i> '.tr('Esporta tutto').'</span>',
        'data' => [
            'msg' => tr('Vuoi davvero esportare un CSV degli impianti?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
        ],
    ];
}



return $operations;
