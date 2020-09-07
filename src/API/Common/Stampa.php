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

namespace API\Common;

use API\Interfaces\RetrieveInterface;
use API\Resource;
use Models\PrintTemplate;
use Prints;

class Stampa extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $print = PrintTemplate::where('name', $request['name'])->first();
        if (!empty($print)) {
            $directory = DOCROOT.'/files/api';
            $data = Prints::render($print->id, $request['id_record'], $directory);

            download($data['path']);
            delete($data['path']);
        }

        return [
            'custom' => '',
        ];
    }
}
