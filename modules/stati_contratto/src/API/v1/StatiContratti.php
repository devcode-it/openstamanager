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

namespace Modules\StatiContratto\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

class StatiContratti extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $table = 'co_staticontratti';

        $select = [
            '*',
        ];

        $where = $request['where'];
        if (empty($where['deleted_at'])) {
            $where['deleted_at'] = null;
        }

        return [
            'select' => $select,
            'table' => $table,
        ];
    }

    /**
     * Ritorna l'attributo name dello stato contratto.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('name')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->name;
    }

    /**
     * Ritorna l'id dello stato contratto a partire dal nome.
     *
     * @param string $name il nome da ricercare
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByName($name)
    {
        return database()->table($this->table.'_lang')
            ->select('id_record')
            ->where('name', '=', $name)
            ->where('id_lang', '=', setting('Lingua'))
            ->first();
    }
}
