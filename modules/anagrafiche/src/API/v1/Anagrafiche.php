<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Modules\Anagrafiche\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules;
use Modules\Anagrafiche\Anagrafica;

class Anagrafiche extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $database = database();

        $query = $database->table('an_anagrafiche')
        ->leftJoin('an_nazioni', 'an_anagrafiche.id_nazione', '=', 'an_nazioni.id')
        ->select(
    'an_anagrafiche.ragione_sociale',
            'an_anagrafiche.piva',
            'an_anagrafiche.codice_fiscale',
            'an_anagrafiche.indirizzo',
            'an_anagrafiche.indirizzo2',
            'an_anagrafiche.citta',
            'an_anagrafiche.cap',
            'an_anagrafiche.provincia',
            'an_anagrafiche.km',
            $database->raw('IFNULL(an_anagrafiche.lat, 0.00) AS latitudine'),
            $database->raw('IFNULL(an_anagrafiche.lng, 0.00) AS longitudine'),
            $database->raw('an_nazioni.nome AS nazione'),
            'an_anagrafiche.telefono',
            'an_anagrafiche.fax',
            'an_anagrafiche.cellulare',
            'an_anagrafiche.email',
            'an_anagrafiche.sitoweb',
            'an_anagrafiche.note',
            'an_anagrafiche.idzona',
            'an_anagrafiche.deleted_at'
        )->orderBy('an_anagrafiche.ragione_sociale');

        if ($request['resource'] != 'anagrafiche') {
            $type = 'Cliente';

            $query = $query->whereRaw('an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica = (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = ?))', [$type]);
        }

        // Filtri da richiesta API
        $allow_list = [
            'idanagrafica',
            'ragione_sociale',
        ];
        $conditions = array_intersect_key((array) $request['where'], array_flip($allow_list));

        // Filtro per ID
        if (!empty($conditions['idanagrafica'])) {
            $query = $query->whereIn('an_anagrafiche.idanagrafica', (array) $conditions['idanagrafica']);
        }

        // Filtro per Ragione sociale
        if (!empty($conditions['ragione_sociale'])) {
            $query = $query->where('an_anagrafiche.ragione_sociale', 'like', '%'.$conditions['ragione_sociale'].'%');
        }

        // Filtri aggiuntivi predefiniti
        $module = Modules::get('Anagrafiche');
        $additionals = Modules::getAdditionals($module->id, false);
        foreach ($additionals['WHR'] as $where) {
            $query = $query->whereRaw($where);
        }

        foreach ($additionals['HVN'] as $having) {
            $query = $query->havingRaw($having);
        }

        $total_count = $query->count();

        return [
            'results' => $query->skip($request['page'] * $request['length'])
                ->limit($request['length'])
                ->get()->toArray(),
            'total-count' => $total_count,
        ];
    }

    public function create($request)
    {
        $ragione_sociale = $request['data']['ragione_sociale'];
        $id_tipo = (array) $request['data']['tipi'];

        $anagrafica = Anagrafica::build($ragione_sociale, null, null, $id_tipo);
        $id_record = $anagrafica->id;

        return [
            'id' => $id_record,
        ];
    }

    public function delete($request)
    {
        $anagrafica = Anagrafica::find($request['id']);

        $result = $anagrafica->delete();
    }

    public function update($request)
    {
        $data = $request['data'];

        $anagrafica = Anagrafica::find($request['id']);

        $anagrafica->ragione_sociale = $data['ragione_sociale'];
        $anagrafica->piva = $data['piva'];
        $anagrafica->codice_fiscale = $data['codice_fiscale'];
        $anagrafica->indirizzo = $data['indirizzo'];
        $anagrafica->citta = $data['citta'];
        $anagrafica->provincia = $data['provincia'];
        $anagrafica->id_nazione = $data['id_nazione'];
        $anagrafica->telefono = $data['telefono'];
        $anagrafica->fax = $data['fax'];
        $anagrafica->cellulare = $data['cellulare'];
        $anagrafica->email = $data['email'];

        $anagrafica->tipologie = (array) $data['tipi'];

        $anagrafica->save();
    }
}
