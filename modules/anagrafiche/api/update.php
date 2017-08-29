<?php

switch ($resource) {
    case 'update_anagrafica':
        // Inserisco l'anagrafica
        $dbo->update('an_anagrafiche', [
            'ragione_sociale' => $request['data']['ragione_sociale'],
        ], ['idanagrafica' => $request['id']]);

        // Inserisco il rapporto dell'anagrafica (cliente, tecnico, ecc)
        $dbo->sync('an_tipianagrafiche_anagrafiche', ['idanagrafica' => $request['id']], ['idtipoanagrafica' => (array) $request['data']['tipi']]);

        break;
}

return [
    'update_anagrafica',
];
