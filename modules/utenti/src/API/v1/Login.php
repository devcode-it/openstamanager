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
            $response['user'] = $database->fetchOne('SELECT `an_anagrafiche`.`idanagrafica` AS idanagrafica, `ragione_sociale`, `codice`, `piva`, `codice_fiscale`, `indirizzo`, `citta`, `provincia`, (SELECT `nome` FROM `an_nazioni` WHERE `an_nazioni`.`id` = `an_anagrafiche`.`id_nazione`) AS nazione, `telefono`, `fax`, `cellulare`, `an_anagrafiche`.`email` FROM `zz_users` LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica` WHERE `id` = :id', [
                ':id' => $user['id'],
            ]);

            $response['token'] = $token;
            $response['group'] = $user['gruppo'];
            $response['google_maps_token'] = setting('Google Maps API key');
            $response['prezzi_al_tecnico'] = setting('Mostra i prezzi al tecnico');

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
