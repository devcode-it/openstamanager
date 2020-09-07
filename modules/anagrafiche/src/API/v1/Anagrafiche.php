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
        $query = 'SELECT an_anagrafiche.idanagrafica AS id,
            an_anagrafiche.ragione_sociale,
            an_anagrafiche.piva,
            an_anagrafiche.codice_fiscale,
            an_anagrafiche.indirizzo,
            an_anagrafiche.indirizzo2,
            an_anagrafiche.citta,
            an_anagrafiche.cap,
            an_anagrafiche.provincia,
            an_anagrafiche.km,
            IFNULL(an_anagrafiche.lat, 0.00) AS latitudine,
            IFNULL(an_anagrafiche.lng, 0.00) AS longitudine,
            an_nazioni.nome AS nazione,
            an_anagrafiche.telefono,
            an_anagrafiche.fax,
            an_anagrafiche.cellulare,
            an_anagrafiche.email,
            an_anagrafiche.sitoweb,
            an_anagrafiche.note,
            an_anagrafiche.idzona,
            an_anagrafiche.deleted_at
        FROM an_anagrafiche
            LEFT OUTER JOIN an_nazioni ON an_anagrafiche.id_nazione=an_nazioni.id
        WHERE
            1=1 AND an_anagrafiche.deleted_at IS NULL';

        $filters = [];
        if ($request['resource'] != 'anagrafiche') {
            $type = 'Cliente';

            $filters[] = 'an_anagrafiche.idanagrafica IN (SELECT idanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idtipoanagrafica = (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = '.prepare($type).'))';
        }
        $query .= !empty($filters) ? ' AND ('.implode('OR ', $filters).')' : '';

        $query .= '
        HAVING 2=2
        ORDER BY an_anagrafiche.ragione_sociale';

        $module = Modules::get('Anagrafiche');
        $query = Modules::replaceAdditionals($module->id, $query);

        return [
            'query' => $query,
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
