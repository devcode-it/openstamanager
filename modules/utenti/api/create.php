<?php

switch ($resource) {
    case 'login':
        // Controllo sulle credenziali
        if (Auth::getInstance()->attempt($request['username'], $request['password'])) {
            $user = Auth::user();

            // Generazione del token per l'utente
            $tokens = $database->fetchArray('SELECT `token` FROM `zz_tokens` WHERE `enabled` = 1 AND `id_utente` = '.prepare($user['id_utente']));
            if (empty($tokens)) {
                $token = secure_random_string();
                $database->insert('zz_tokens', [
                    'id_utente' => $user['id_utente'],
                    'token' => $token,
                ]);
            } else {
                $token = $tokens[0]['token'];
            }

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
