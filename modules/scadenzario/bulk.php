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

use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Fattura;
use Modules\Scadenzario\Scadenza;

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));

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

    case 'change-bank':
        $list = [];
        foreach ($id_records as $id) {
            $scadenza = Scadenza::find($id);
            if ($scadenza->iddocumento){
                $documento = Fattura::find($scadenza->iddocumento);
                $documento->id_banca_azienda = post('id_banca');
                $documento->save();
                array_push($list, $documento->numero_esterno);
            }
        }

        if ($list){
            flash()->info(tr('Banca aggiornata per le Fatture _LIST_ !', [
                '_LIST_' => implode(',', $list),
            ]));
        }

        break;
}

$operations['registrazione-contabile'] = [
    'text' => '<span><i class="fa fa-calculator"></i> '.tr('Registrazione contabile').'</span>',
    'data' => [
        'title' => tr('Registrazione contabile'),
        'type' => 'modal',
        'origine' => 'scadenzario',
        'url' => base_url().'/add.php?id_module='.Modules::get('Prima nota')['id'],
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

$operations['change-bank'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Aggiorna banca').'</span>',
    'data' => [
        'title' => tr('Aggiornare la banca?'),
        'msg' => tr('Per ciascuna scadenza selezionata, verrà aggiornata la banca della fattura di riferimento e quindi di conseguenza di tutte le scadenze collegate').'
        <br><br>{[ "type": "select", "label": "'.tr('Banca').'", "name": "id_banca", "required": 1, "values": "query=SELECT id, CONCAT (nome, \' - \' , iban) AS descrizione FROM co_banche WHERE id_anagrafica='.prepare($anagrafica_azienda->idanagrafica).'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

return $operations;
