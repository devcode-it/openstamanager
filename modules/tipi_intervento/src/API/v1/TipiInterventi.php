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

namespace Modules\TipiIntervento\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

class TipiInterventi extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'in_tipiintervento';

        $select = $request['select'];
        if (empty($select)) {
            $select = [
                '*',
            ];
        }

        $joins = [
            'in_tipiintervento_lang' => 'in_tipiintervento_lang.id_record = in_tipiintervento.id AND in_tipiintervento_lang.id_lang = '.\Models\Locale::getDefault()->id,
        ];

        return [
            'select' => $select,
            'table' => $table,
            'joins' => $joins,
        ];
    }
}
