<?php

switch ($resource) {
    case 'update_anagrafica':
        // Inserisco l'anagrafica
        $dbo->update('an_anagrafiche', [
            'ragione_sociale' => $request['data']['ragione_sociale'],
            'piva' => $request['data']['piva'],
            'codice_fiscale' => $request['data']['codice_fiscale'],
            'indirizzo' => $request['data']['indirizzo'],
            'citta' => $request['data']['citta'],
            'provincia' => $request['data']['provincia'],
            'id_nazione' => $request['data']['id_nazione'],
            'telefono' => $request['data']['telefono'],
            'fax' => $request['data']['fax'],
            'cellulare' => $request['data']['cellulare'],
            'email' => $request['data']['email'],
        ], ['idanagrafica' => $request['id']]);

        // Inserisco il rapporto dell'anagrafica (cliente, tecnico, ecc)
        $dbo->sync('an_tipianagrafiche_anagrafiche', ['idanagrafica' => $request['id']], ['idtipoanagrafica' => (array) $request['data']['tipi']]);

        break;
}

return [
    'update_anagrafica',
];
