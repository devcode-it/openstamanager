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

class Logout extends Resource implements CreateInterface
{
    public function create($request)
    {
        $database = database();
        $user = $this->getUser();

        if (!empty($request['token']) && !empty($user)) {
            // Cancellazione della chiave
            $database->query('DELETE FROM `zz_tokens` WHERE `token` = '.prepare($request['token']).' AND `id_utente` = '.prepare($user['id']));
        } else {
            $response = [
                'status' => API\Response::getStatus()['unauthorized']['code'],
            ];
        }

        return $response;
    }
}
