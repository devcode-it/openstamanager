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

use Geocoder\Provider\GoogleMaps;
use Ivory\HttpAdapter\CurlHttpAdapter;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Export\CSV;

include_once __DIR__.'/../../core.php';
$google = setting('Google Maps API key');

switch (post('op')) {
    case 'delete-bulk':
        $id_tipo_azienda = $dbo->fetchArray("SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Azienda'")[0]['idtipoanagrafica'];

        foreach ($id_records as $id) {
            $anagrafica = $dbo->fetchArray('SELECT an_tipianagrafiche.idtipoanagrafica FROM an_tipianagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche.idtipoanagrafica=an_tipianagrafiche_anagrafiche.idtipoanagrafica WHERE idanagrafica='.prepare($id));
            $tipi = array_column($anagrafica, 'idtipoanagrafica');

            // Se l'anagrafica non è di tipo Azienda
            if (!in_array($id_tipo_azienda, $tipi)) {
                $dbo->query('UPDATE an_anagrafiche SET deleted_at = NOW() WHERE idanagrafica = '.prepare($id).Modules::getAdditionalsQuery($id_module));
            }
        }

        flash()->info(tr('Anagrafiche eliminate!'));

        break;

    case 'ricerca-coordinate':
        $curl = new CurlHttpAdapter();
        $geocoder = new GoogleMaps($curl, null, null, true, $google);

        foreach ($id_records as $id) {
            $anagrafica = Anagrafica::find($id);
            if (empty($anagrafica->lat) && empty($anagrafica->lng) && !empty($anagrafica->sedeLegale->citta) && !empty($anagrafica->sedeLegale->cap)) {
                $indirizzo = $anagrafica->sedeLegale->citta.' '.$anagrafica->sedeLegale->cap;

                // Ricerca indirizzo
                $address = $geocoder->geocode($indirizzo)->first();
                $coordinates = $address->getCoordinates();

                // Salvataggio informazioni
                $anagrafica->lat = $coordinates->getLatitude();
                $anagrafica->lng = $coordinates->getLongitude();
                $anagrafica->save();
            }
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
}

$operations = [];

if (App::debug()) {
    $operations['delete-bulk'] = [
        'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span> <span class="label label-danger" >beta</span>',
        'data' => [
            'msg' => tr('Vuoi davvero eliminare le anagrafiche selezionate?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
        ],
    ];

    $operations['export-csv'] = [
        'text' => '<span><i class="fa fa-download"></i> '.tr('Esporta selezionati').'</span> <span class="label label-danger" >beta</span>',
        'data' => [
            'msg' => tr('Vuoi davvero esportare un CSV con tutte le anagrafiche?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
            'blank' => true,
        ],
    ];
}

if (App::debug() && $google) {
    $operations['ricerca-coordinate'] = [
        'text' => '<span><i class="fa fa-map"></i> '.tr('Ricerca coordinate').'</span>',
        'data' => [
            'msg' => tr('Ricercare le coordinate per le anagrafiche selezionate senza latitudine e longitudine?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
        ],
    ];
}

return $operations;
