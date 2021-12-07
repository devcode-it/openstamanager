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

switch (post('op')) {
    case 'change_distinta':
        $distinta = post('distinta');

        $n_scadenze = 0;
        foreach ($id_records as $id) {
            $database->update('co_scadenziario', [
                'distinta' => $distinta,
            ], ['id' => $id]);

            ++$n_scadenze;
        }

        if ($n_scadenze > 0) {
            flash()->info(tr('Distinta aggiornata a _NUM_ scadenze!', [
                '_NUM_' => $n_scadenze,
            ]));
        } else {
            flash()->warning(tr('Nessuna scadenza modificata!'));
        }

    break;
}

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'scadenzario',
        'url' => base_path().'/add.php?id_module='.Modules::get('Prima nota')['id'],
    ],
];

$operations['change_distinta'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna distinta'),
    'data' => [
        'title' => tr('Aggiornare la distinta per le scadenze selezionate?'),
        'msg' => tr('Per ciascuna scadenza selezionata verrà aggiornata la distinta').'.<br>
        <br>{[ "type": "text", "label": "'.tr('Distinta').'", "name": "distinta", "required": 1 ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
        'blank' => false,
    ],
];

return $operations;
