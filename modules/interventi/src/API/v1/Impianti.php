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
use API\Interfaces\RetrieveInterface;
use API\Resource;

class Impianti extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $query = 'SELECT idimpianto AS id_impianto, idintervento AS id_intervento FROM my_impianti_interventi WHERE `idintervento` = :id_intervento';

        $parameters = [
            ':id_intervento' => $request['id_intervento'],
        ];

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];
        $id_record = $data['id_intervento'];

        $database = database();
        $database->query('DELETE FROM my_impianti_interventi WHERE `idintervento` = :id_intervento', [
            ':id_intervento' => $id_record,
        ]);

        $impianti = $data['impianti'];
        foreach ($impianti as $impianto) {
            $database->insert('my_impianti_interventi', [
                'idintervento' => $id_record,
                'idimpianto' => $impianto,
            ]);
        }
    }
}
