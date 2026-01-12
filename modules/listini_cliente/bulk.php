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

use Modules\ListiniCliente\Articolo;
use Modules\ListiniCliente\Listino;

switch (post('op')) {
    case 'change_prezzo':
        foreach ($id_records as $id) {
            $listino = Listino::find($id);

            $articoli = Articolo::where('id_listino', $id)->get();
            foreach ($articoli as $articolo) {
                $articolo->sconto_percentuale = post('percentuale');
                $articolo->save();
            }
        }

        flash()->info(tr('Listini aggiornati!'));

        break;
}

$operations['change_prezzo'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna sconto percentuale').'</span>',
    'data' => [
        'title' => tr('Aggiornare lo sconto percentuale per i listini selezionati?'),
        'msg' => tr('Per indicare uno sconto inserire la percentuale con il segno meno, al contrario per un rincaro inserire la percentuale senza segno.').'<br><br>{[ "type": "number", "label": "'.tr('Percentuale sconto/magg.').'", "name": "percentuale", "required": 1, "icon-after": "%" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
