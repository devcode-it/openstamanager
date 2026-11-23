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

namespace Modules\Preventivi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Anagrafiche\Anagrafica;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo;

class Preventivi extends Resource implements RetrieveInterface, UpdateInterface, CreateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $table = 'co_preventivi';

        $select = [
            'co_preventivi.*',
            'co_preventivi.data_bozza AS data',
            'co_stati_preventivi_lang.title AS stato',
        ];

        $joins[] = [
            'co_stati_preventivi',
            'co_stati_preventivi.id',
            'co_preventivi.id_stato',
        ];

        $joins[] = [
            'co_stati_preventivi_lang',
            'co_stati_preventivi_lang.id_record',
            'co_stati_preventivi.id',
        ];

        $where = [];
        $where[] = ['co_stati_preventivi_lang.id_lang', '=', \Models\Locale::getDefault()->id];

        $whereraw = [];

        $group = 'co_preventivi.id';

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
        $tipo = Tipo::find($data['id_tipo_intervento']);

        $preventivo = Preventivo::build($anagrafica, $tipo, $data['nome'], $data['data'], $data['id_sede_destinazione'] ?? null, $data['id_segment'] ?? null);

        $preventivo->id_stato = $data['id_stato'];
        $preventivo->save();

        return [
            'id' => $preventivo->id,
            'numero' => $preventivo->numero,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $preventivo = Preventivo::find($data['id']);

        $preventivo->nome = $data['nome'];
        $preventivo->data_bozza = $data['data'];
        $preventivo->id_stato = $data['id_stato'];
        $preventivo->id_anagrafica = $data['id_anagrafica'];
        $preventivo->numero = $data['numero'];
        $preventivo->id_sede_destinazione = $data['id_sede_destinazione'];
        $preventivo->save();

        return [
            'id' => $preventivo->id,
            'module' => 'Preventivi',
        ];
    }

    public function delete($request)
    {
        $preventivo = Preventivo::find($request['id']);
        $preventivo->delete();

        return [
            'id' => $request['id'],
        ];
    }
}
