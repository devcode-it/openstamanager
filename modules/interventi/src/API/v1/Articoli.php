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
        $query = 'SELECT id, idarticolo AS id_articolo, idintervento AS id_intervento, qta, created_at as data FROM in_righe_interventi WHERE `idarticolo` IS NOT NULL AND `idintervento` = :id_intervento';

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

        $originale = ArticoloOriginale::find($data['id_articolo']);
        $intervento = Intervento::find($data['id_intervento']);
        $articolo = Articolo::build($intervento, $originale);

        $articolo->qta = $data['qta'];
        $articolo->um = $data['um'];

        $articolo->prezzo_unitario = $originale->prezzo_vendita;
        $articolo->costo_unitario = $originale->prezzo_acquisto;

        $articolo->save();
    }

    public function delete($request)
    {
        $database = database();

        $database->query('DELETE FROM `in_righe_interventi` WHERE `idarticolo` IS NOT NULL AND `idintervento` = :id_intervento', [
            ':id_intervento' => $request['id_intervento'],
        ]);
    }
}
