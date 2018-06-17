<?php

switch ($resource) {
    case 'login':
        // Controllo sulle credenziali
        if (Auth::getInstance()->attempt($request['username'], $request['password'])) {
            $token = Auth::getInstance()->getToken();

            // Informazioni da restituire tramite l'API
            $results = $dbo->fetchArray('SELECT `ragione_sociale`, `codice`, `piva`, `codice_fiscale`, `indirizzo`, `citta`, `provincia`, (SELECT `nome` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione, `telefono`, `fax`, `cellulare`, `an_anagrafiche`.`email` FROM `zz_users` LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica` WHERE `id` = '.prepare($user['id_utente']))[0];

            $results['token'] = $token;
        } else {
            $results = [
                'status' => API::getStatus()['unauthorized']['code'],
            ];

            // Se è in corso un brute-force, aggiunge il timeout
            if (Auth::isBrute()) {
                $results['timeout'] = Auth::getBruteTimeout();
            }
        }

        break;

    // Operazione di logout
    case 'logout':
        if (!empty($request['token']) && !empty($user)) {
            // Cancellazione della chiave
            $database->query('DELETE FROM `zz_tokens` WHERE `token` = '.prepare($request['token']).' AND `id_utente` = '.prepare($user['id_utente']));
        } else {
            $results = [
                'status' => API::getStatus()['unauthorized']['code'],
            ];
        }

        break;
}

return [
    'login',
    'logout',
];
