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

namespace API\App\v1;

use API\Interfaces\CreateInterface;
use API\Resource;
use API\Response;

class Login extends Resource implements CreateInterface
{
    public function create($request)
    {
        $database = database();

        // Controllo sulle credenziali
        if (auth()->attempt($request['username'], $request['password'])) {
            $user = $this->getUser();
            $token = auth()->getToken();

            if (setting("Permetti l'accesso agli amministratori")) {
                $utente = $database->fetchOne('SELECT
                    `an_anagrafiche`.`idanagrafica` AS id_anagrafica,
                    `an_anagrafiche`.`ragione_sociale`,
                    `zz_groups_lang`.`name` AS gruppo
                FROM `zz_users`
                    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica`
                    INNER JOIN `zz_groups` ON `zz_users`.`idgruppo`=`zz_groups`.`id`
                    LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
                WHERE `an_anagrafiche`.`deleted_at` IS NULL AND `zz_users`.`id` = :id', [
                    ':id' => $user['id'],
                ]);
            } else {
                $utente = $database->fetchOne('SELECT
                    `an_anagrafiche`.`idanagrafica` AS id_anagrafica,
                    `an_anagrafiche`.`ragione_sociale`,
                    `zz_groups_lang`.`name` AS gruppo
                FROM `zz_users`
                    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica`
                    INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
                    INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche`.`id`
                    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).')
                    INNER JOIN `zz_groups` ON `zz_users`.`idgruppo`=`zz_groups`.`id`
                    LEFT JOIN `zz_groups_lang` ON (`zz_groups_lang`.`id_record` = `zz_groups`.`id` AND `zz_groups_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).")
                WHERE `an_tipianagrafiche_lang`.`name` = 'Tecnico' AND `an_anagrafiche`.`deleted_at` IS NULL AND `zz_users`.`id` = :id", [
                    ':id' => $user['id'],
                ]);
            }

            if (!empty($utente)) {
                // Informazioni da restituire tramite l'API
                $response = [
                    'id_anagrafica' => (string) $utente['id_anagrafica'],
                    'ragione_sociale' => $utente['ragione_sociale'],
                    'token' => $token,
                    'gruppo' => $utente['gruppo'],
                    'version' => \Update::getVersion(),
                ];
            } else {
                $response = [
                    'status' => Response::getStatus()['unauthorized']['code'],
                ];
            }
        } else {
            $response = [
                'status' => Response::getStatus()['unauthorized']['code'],
            ];

            // Se è in corso un brute-force, aggiunge il timeout
            if (\Auth::isBrute()) {
                $response['timeout'] = \Auth::getBruteTimeout();
            }
        }

        return $response;
    }
}
