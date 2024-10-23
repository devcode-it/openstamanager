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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Export\CSV;
use Modules\Anagrafiche\Tipo;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'delete-bulk':
        $id_tipo_azienda = Tipo::where('name', 'Azienda')->first()->id;

        foreach ($id_records as $id) {
            $anagrafica = $dbo->fetchArray('SELECT `an_tipianagrafiche`.`id` FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` WHERE `idanagrafica`='.prepare($id));
            $tipi = array_column($anagrafica, 'id');

            // Se l'anagrafica non è di tipo Azienda
            if (!in_array($id_tipo_azienda, $tipi)) {
                $dbo->query('UPDATE `an_anagrafiche` SET `deleted_at` = NOW() WHERE `idanagrafica` = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                ++$eliminate;
            }
        }

        if ($eliminate > 1) {
            flash()->info(tr('Sono state eliminate _NUM_ anagrafiche', ['_NUM_' => $eliminate]));
        } elseif ($eliminate == 1) {
            flash()->info(tr('E\' stata eliminata una anagrafica'));
        } else {
            flash()->warning(tr('Non è stato possibile eliminare le anagrafiche selezionate.'));
        }

        break;

    case 'ricerca-coordinate':
        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);
            $anagrafica->save();
        }

        break;

    case 'export-csv':
        $file = temp_file();
        $exporter = new CSV($file);

        // Esportazione dei record selezionati
        $anagrafiche = Anagrafica::whereIn('idanagrafica', $id_records)->get();
        $exporter->setRecords($anagrafiche);

        $count = $exporter->exportRecords();

        download($file, 'anagrafiche.csv');
        break;

    case 'cambia-relazione':
        $idrelazione = post('idrelazione');

        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);

            $anagrafica->idrelazione = $idrelazione;

            $anagrafica->save();
        }
        break;

    case 'aggiorna-listino':
        $id_listino = post('id_listino') ?: 0;
        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);
            if ($anagrafica->isTipo('Cliente')) {
                $anagrafica->id_listino = $id_listino;
                $anagrafica->save();
            }
        }

        flash()->info(tr('Listino aggiornato correttamente!'));

        break;
}

$operations = [];

$operations['delete-bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero eliminare le anagrafiche selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

$operations['export-csv'] = [
    'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta selezionati').'</span>',
    'data' => [
        'msg' => tr('Vuoi esportare un CSV con le anagrafiche selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-success',
        'blank' => true,
    ],
];

$operations['ricerca-coordinate'] = [
    'text' => '<span><i class="fa fa-map"></i> '.tr('Ricerca coordinate').'</span>',
    'data' => [
        'msg' => tr('Ricercare le coordinate per le anagrafiche selezionate senza latitudine e longitudine?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['cambia-relazione'] = [
    'text' => '<span><i class="fa fa-copy"></i> '.tr('Cambia relazione').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero cambiare la relazione delle anagrafiche selezionate?').'<br><br>{[ "type": "select", "label": "'.tr('Relazione con il cliente').'", "name": "idrelazione", "required": 1, "ajax-source": "relazioni"]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

$operations['aggiorna-listino'] = [
    'text' => '<span><i class="fa fa-refresh"></i> '.tr('Imposta listino').'</span>',
    'data' => [
        'msg' => tr('Vuoi impostare il listino cliente selezionato a queste anagrafiche?').'<br><br>{[ "type": "select", "label": "'.tr('Listino cliente').'", "name": "id_listino", "required": 0, "ajax-source": "listini", "placeholder": "'.tr('Nessun listino').'" ]}',
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-warning',
    ],
];

return $operations;
