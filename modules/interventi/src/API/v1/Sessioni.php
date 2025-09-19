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

namespace Modules\Interventi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;

class Sessioni extends Resource implements RetrieveInterface, CreateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $user = $this->getUser();

        $query = 'SELECT id, idtecnico AS id_tecnico, idintervento AS id_intervento, orario_inizio, orario_fine, ragione_sociale AS tecnico FROM in_interventi_tecnici INNER JOIN an_anagrafiche ON idanagrafica = idtecnico  WHERE `idintervento` = :id_intervento';

        $parameters = [
            ':id_intervento' => $request['id_intervento'],
        ];

        if ($user['gruppo'] == 'Tecnici') {
            $query .= ' AND `idtecnico` = :id_tecnico';
            $parameters[':id_tecnico'] = $user['idanagrafica'];
        }

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }

    public function create($request)
    {
        $user = $this->getUser();
        $data = $request['data'];

        try {
            add_tecnico($data['id_intervento'], $user['idanagrafica'], $data['orario_inizio'], $data['orario_fine']);
        } catch (\InvalidArgumentException) {
        }

        return [
            'id' => $data['id_intervento'],
            'op' => 'add_sessione',
        ];
    }

    public function delete($request)
    {
        $database = database();
        $user = $this->getUser();
        $data = $request['data'];

        $database->query('DELETE FROM `in_interventi_tecnici` WHERE `idintervento` = :id_intervento AND `idtecnico` = :id_tecnico', [
            ':id_intervento' => $data['id_intervento'],
            ':id_tecnico' => $user['idanagrafica'],
        ]);

        return [
            'id' => $data['id_intervento'],
            'op' => 'delete_sessione',
        ];
    }
}
