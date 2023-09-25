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
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Intervento;

class Articoli extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $table = 'in_righe_interventi';

        $select = [
            'in_righe_interventi.id',
            'in_righe_interventi.idarticolo AS id_articolo',
            'in_righe_interventi.idintervento AS id_intervento',
            'in_righe_interventi.qta',
            'in_righe_interventi.created_at as data',
        ];

        $where = [['in_righe_interventi.idarticolo', '!=', null], ['in_righe_interventi.idintervento', '=', $request['id_intervento']]];

        return [
            'table' => $table,
            'select' => $select,
            'where' => $where,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];

        $originale = ArticoloOriginale::find($data['id_articolo']);
        $intervento = Intervento::find($data['id_intervento']);
        $articolo = Articolo::build($intervento, $originale);

        $articolo->qta = $data['qta'];
        $articolo->um = $data['um'];

        $articolo->prezzo_unitario = $originale->prezzo_vendita;
        $articolo->costo_unitario = $originale->prezzo_acquisto;

        $articolo->save();
    }
}
