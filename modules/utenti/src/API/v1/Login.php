<?php

namespace Modules\Utenti\API\v1;

use API\Interfaces\CreateInterface;
use API\Resource;
use API\Response;
use Auth;
use Update;

class Login extends Resource implements CreateInterface
{
    public function create($request)
    {
        $database = database();

        // Controllo sulle credenziali
        if (auth()->attempt($request['username'], $request['password'])) {
            $user = $this->getUser();
            $token = auth()->getToken();

            // Informazioni da restituire tramite l'API
            $response['user'] = $database->fetchOne('SELECT `ragione_sociale`, `codice`, `piva`, `codice_fiscale`, `indirizzo`, `citta`, `provincia`, (SELECT `nome` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione, `telefono`, `fax`, `cellulare`, `an_anagrafiche`.`email` FROM `zz_users` LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica` WHERE `id` = :id', [
                ':id' => $user['id'],
            ]);

            $response['token'] = $token;
            $response['group'] = $user['gruppo'];
            $response['google_maps_token'] = setting('Google Maps API key');

            $response['version'] = Update::getVersion();
        } else {
            $response = [
                'status' => Response::getStatus()['unauthorized']['code'],
            ];

            // Se è in corso un brute-force, aggiunge il timeout
            if (Auth::isBrute()) {
                $response['timeout'] = Auth::getBruteTimeout();
            }
        }

        return $response;
    }
}
