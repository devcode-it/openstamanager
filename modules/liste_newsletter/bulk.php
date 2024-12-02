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

use Modules\ListeNewsletter\Lista;

switch (post('op')) {
    case 'aggiorna-liste':
        foreach ($id_records as $id) {
            $lista = Lista::find($id);

            $query = $lista->query;
            if (check_query($query)) {
                $lista->query = html_entity_decode($query);
            }

            $lista->save();
        }

        flash()->info(tr('Liste aggiornate!'));

        break;
}

$operations['aggiorna-liste'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna liste').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero aggiornare le liste dei destinatari?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

return $operations;
