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
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Interventi extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface
{
    public function retrieve($request)
    {
        // Periodo per selezionare interventi
        $user = \Auth::user();

        $table = 'in_interventi';

        $select = [
            'in_interventi.*',
            'MAX(in_interventi_tecnici.orario_fine) as data',
            'GROUP_CONCAT(DISTINCT b.ragione_sociale SEPARATOR \', \') AS tecnici',
            'in_statiintervento.descrizione AS stato',
        ];

        $joins[] = [
            'in_statiintervento',
            'in_interventi.idstatointervento',
            'in_statiintervento.idstatointervento',
        ];

        $joins[] = [
            'an_anagrafiche',
            'in_interventi.idanagrafica',
            'an_anagrafiche.idanagrafica',
        ];

        $joins[] = [
            'in_interventi_tecnici',
            'in_interventi_tecnici.idintervento',
            'in_interventi.id',
        ];

        $joins[] = [
            'an_anagrafiche as b',
            'in_interventi_tecnici.idtecnico',
            'b.ragione_sociale',
        ];

        $where = [];

        if (!$user->is_admin) {
            $where[] = ['in_interventi_tecnici.idtecnico', '=', $user->idanagrafica];
        }

        $whereraw = [];

        $group = 'in_interventi.id';

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
        $tipo = TipoSessione::find($data['id_tipo_intervento']);
        $stato = Stato::find($data['id_stato_intervento']);

        $intervento = Intervento::build($anagrafica, $tipo, $stato, $data['data_richiesta']);

        $intervento->richiesta = $data['richiesta'];
        $intervento->descrizione = $data['descrizione'];
        $intervento->informazioniaggiuntive = $data['informazioni_aggiuntive'];
        $intervento->save();

        return [
            'id' => $intervento->id,
            'codice' => $intervento->codice,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $intervento = Intervento::find($data['id']);

        $intervento->idstatointervento = $data['id_stato_intervento'];
        $intervento->descrizione = $data['descrizione'];
        $intervento->informazioniaggiuntive = $data['informazioni_aggiuntive'];
        $intervento->save();
    }
}
