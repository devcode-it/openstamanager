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

namespace Modules\Anagrafiche\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Anagrafiche\Anagrafica;

class Anagrafiche extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $table = '`an_anagrafiche`';

        $select = [
            '`an_anagrafiche`.*',
            '`an_nazioni_lang`.`name` AS nazione',
        ];

        $joins[] = [
            'an_nazioni_lang' => '`an_nazioni_lang`.`id_record` = `an_nazioni`.`id` AND `an_nazioni_lang`.`id_lang` = '.setting('Lingua'),
        ]; 

        $where[] = ['`an_anagrafiche`.`deleted_at`', '=', null];

        $order['`an_anagrafiche`.`ragione_sociale`'] = 'ASC';

        if ($request['resource'] != 'anagrafiche') {
            $type = 'Cliente';

            $joins[] = [
                '`an_tipianagrafiche_anagrafiche`',
                '`an_anagrafiche`.`idanagrafica`',
                '`an_tipianagrafiche_anagrafiche`.`idanagrafica`',
            ];

            $joins[] = [
                '`an_tipianagrafiche`',
                '`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`',
                '`an_tipianagrafiche`.`id`',
            ];

            $joins[] = [
                'an_tipianagrafiche_lang' => '`an_tipianagrafiche_lang`.`idrecord` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.`idlang` = '.setting('Lingua'),
            ]; 

            $where[] = ['`an_tipianagrafiche_lang`.`name`', '=', $type];
        }

        return [
            'table' => $table,
            'select' => $select,
            'joins' => $joins,
            'where' => $where,
            'order' => $order,
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

        $anagrafica->delete();
    }

    public function update($request)
    {
        $data = $request['data'];

        $anagrafica = Anagrafica::find($data['id']);

        if (isset($data['ragione_sociale'])) {
            $anagrafica->ragione_sociale = $data['ragione_sociale'];
        }
        if (isset($data['piva'])) {
            $anagrafica->piva = $data['piva'];
        }
        if (isset($data['codice_fiscale'])) {
            $anagrafica->codice_fiscale = $data['codice_fiscale'];
        }
        if (isset($data['indirizzo'])) {
            $anagrafica->indirizzo = $data['indirizzo'];
        }
        if (isset($data['citta'])) {
            $anagrafica->citta = $data['citta'];
        }
        if (isset($data['provincia'])) {
            $anagrafica->provincia = $data['provincia'];
        }
        if (isset($data['id_nazione'])) {
            $anagrafica->id_nazione = $data['id_nazione'];
        }
        if (isset($data['telefono'])) {
            $anagrafica->telefono = $data['telefono'];
        }
        if (isset($data['fax'])) {
            $anagrafica->fax = $data['fax'];
        }
        if (isset($data['cellulare'])) {
            $anagrafica->cellulare = $data['cellulare'];
        }
        if (isset($data['email'])) {
            $anagrafica->email = $data['email'];
        }
        if (isset($data['tipi'])) {
            $anagrafica->tipologie = (array) $data['tipi'];
        }

        $anagrafica->save();
    }
}
