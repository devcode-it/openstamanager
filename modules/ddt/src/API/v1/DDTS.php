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

namespace Modules\DDT\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\DDT;
use Modules\DDT\Tipo;

class DDTS extends Resource implements RetrieveInterface, UpdateInterface, CreateInterface
{
    public function retrieve($request)
    {
        $table = 'dt_ddt';

        $select = [
            'dt_ddt.*',
            'dt_ddt.data',
            'dt_statiddt_lang.title AS stato',
        ];

        $joins[] = [
            'dt_statiddt',
            'dt_statiddt.id',
            'dt_ddt.idstatoddt',
        ];

        $joins[] = [
            'dt_statiddt_lang',
            'dt_statiddt_lang.id_record',
            'dt_statiddt.id', 
        ];

        $where = [];
        $where[] = ['dt_statiddt_lang.id_lang', '=', \Models\Locale::getDefault()->id];

        $whereraw = [];

        $group = 'dt_ddt.id';

        return [
            'table' => $table,
            'select' => $select,
            'joins' => $joins,
            'where' => $where,
            'whereraw' => $whereraw,
            'group' => $group,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];

        $anagrafica = Anagrafica::find($data['id_anagrafica']);
        $tipo = Tipo::find($data['id_tipo']);

        $ddt = DDT::build($anagrafica, $tipo, $data['data'], $data['id_segment']);

        $ddt->idstatoddt = $data['id_stato'];
        $ddt->idcausalet = $data['idcausale'];
        $ddt->idsede_partenza = $data['idsede_partenza'];
        $ddt->idsede_destinazione = $data['idsede_destinazione'];
        $ddt->save();

        return [
            'id' => $ddt->id,
            'numero_esterno' => $ddt->numero_esterno,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $ddt = DDT::find($data['id']);

        $ddt->data = $data['data'];
        $ddt->idstatoddt = $data['id_stato'];
        $ddt->idcausalet = $data['idcausale'];
        $ddt->idanagrafica = $data['id_anagrafica'];
        $ddt->numero_esterno = $data['numero_esterno'];
        $ddt->idsede_partenza = $data['idsede_partenza'];
        $ddt->idsede_destinazione = $data['idsede_destinazione'];
        $ddt->save();
    }

    public function delete($request)
    {
        $ddt = DDT::find($request['id']);
        $ddt->delete();
    }
}
