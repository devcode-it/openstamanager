<?php

use Geocoder\Provider\GoogleMaps;
use Ivory\HttpAdapter\CurlHttpAdapter;
use Modules\Anagrafiche\Anagrafica;

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
                $address = $geocoder->geocode($anagrafica->sedeLegale->citta.' '.$anagrafica->sedeLegale->cap)->first();
                $coordinates = $address->getCoordinates();
                dd($coordinates);
            }
        }

        break;
}

if (App::debug()) {
    $operations['delete-bulk'] = [
        'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina selezionati').'</span>',
        'data' => [
            'msg' => tr('Vuoi davvero eliminare le anagrafiche selezionate?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-danger',
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
