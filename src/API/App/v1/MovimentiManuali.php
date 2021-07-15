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

namespace API\App\v1;

use API\App\AppResource;
use Carbon\Carbon;
use Modules\Articoli\Articolo;

class MovimentiManuali extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return [];
    }

    public function getModifiedRecords($last_sync_at)
    {
        return [];
    }

    public function retrieveRecord($id)
    {
        return [];
    }

    public function createRecord($data)
    {
        $articolo = Articolo::find($data['id_articolo']);
        $data_movimento = new Carbon($data['created_at']);

        $id_movimento = $articolo->movimenta($data['qta'], $data['descrizione'], $data_movimento, true, [
            'idsede' => $data['id_sede_azienda'],
        ]);

        return [
            'id' => $id_movimento,
        ];
    }
}
