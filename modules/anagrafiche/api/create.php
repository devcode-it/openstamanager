<?php

switch ($resource) {
    case 'add_anagrafica':
        $rs = $dbo->fetchArray('SELECT codice FROM an_anagrafiche ORDER BY CAST(codice AS SIGNED) DESC LIMIT 0, 1');
        $codice = Util\Generator::generate(setting('Formato codice anagrafica'), $rs[0]['codice']);

        // Inserisco l'anagrafica
        $dbo->insert('an_anagrafiche', [
            'ragione_sociale' => $request['data']['ragione_sociale'],
            'codice' => $codice,
        ]);

        // Inserisco il rapporto dell'anagrafica (cliente, tecnico, ecc)
        $dbo->sync('an_tipianagrafiche_anagrafiche', ['idanagrafica' => $dbo->lastInsertedID()], ['idtipoanagrafica' => (array) $request['data']['tipi']]);

        break;
}

return [
    'add_anagrafica',
];
