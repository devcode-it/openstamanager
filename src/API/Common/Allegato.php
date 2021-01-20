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

namespace API\Common;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Models\Upload;
use Modules;

class Allegato extends Resource implements RetrieveInterface, CreateInterface
{
    public function create($request)
    {
        $module = Modules::get($request['module']);

        $name = !empty($request['name']) ? $request['name'] : null;
        $category = !empty($request['category']) ? $request['category'] : null;

        $upload = Upload::build($_FILES['upload'], [
            'id_module' => $module['id'],
            'id_record' => $request['id'],
        ], $name, $category);

        return [
            'id' => $upload->id,
            'filename' => $upload->filename,
        ];
    }

    public function retrieve($request)
    {
        $upload = Upload::where('name', $request['name'])
            ->where('id', $request['id'])
            ->where('id_record', $request['id_record'])
            ->first();
        if (!empty($upload)) {
            download(base_dir().'/'.$upload->filepath, $upload->original_name);
        }

        return [
            'custom' => '',
        ];
    }
}
