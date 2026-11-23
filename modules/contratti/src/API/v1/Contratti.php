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

namespace Modules\Contratti\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;

class Contratti extends Resource implements RetrieveInterface, UpdateInterface, CreateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $table = 'co_contratti';

        $select = [
            'co_contratti.*',
            'co_contratti.data_bozza AS data',
            'co_stati_contratti_lang.title AS stato',
        ];

        $joins[] = [
            'co_stati_contratti',
            'co_stati_contratti.id',
            'co_contratti.id_stato',
        ];

        $joins[] = [
            'co_stati_contratti_lang',
            'co_stati_contratti_lang.id_record',
            'co_stati_contratti.id',
        ];

        $where = [];
        $where[] = ['co_stati_contratti_lang.id_lang', '=', \Models\Locale::getDefault()->id];

        $whereraw = [];

        $group = 'co_contratti.id';

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

        $contratto = Contratto::build($anagrafica, $data['nome'], $data['id_segment'] ?? null);

        $contratto->id_stato = $data['id_stato'];
        $contratto->save();

        return [
            'id' => $contratto->id,
            'numero' => $contratto->numero,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $contratto = Contratto::find($data['id']);

        $contratto->nome = $data['nome'];
        $contratto->data_bozza = $data['data'];
        $contratto->id_stato = $data['id_stato'];
        $contratto->id_anagrafica = $data['id_anagrafica'];
        $contratto->numero = $data['numero'];
        $contratto->id_sede_destinazione = $data['id_sede_destinazione'];
        $contratto->save();

        return [
            'id' => $contratto->id,
            'module' => 'Contratti',
        ];
    }

    public function delete($request)
    {
        $contratto = Contratto::find($request['id']);
        $contratto->delete();

        return [
            'id' => $request['id'],
        ];
    }
}
