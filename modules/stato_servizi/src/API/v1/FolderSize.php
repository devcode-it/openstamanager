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

namespace Modules\StatoServizi\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;
use App;
use Util\FileSystem;

class FolderSize extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $dirs = $request['dirs'];

        if (empty($dirs)) {
            $backup_dir = App::getConfig()['backup_dir'];

            $dirs = [
                $backup_dir => tr('Backup'),
                base_dir().'/files' => tr('Allegati'),
                base_dir().'/logs' => tr('Logs'),
            ];
        } else {
            $array = explode(',', $dirs);
            foreach ($array as $key => $value) {
                $dirs = [
                    base_dir().'/'.$value => $key,
                ];
            }
        }

        $results = [];
        $total = 0;
        foreach ($dirs as $dir => $description) {
            $size = FileSystem::folderSize($dir);

            $results[] = [
                'description' => $description,
                'size' => $size,
                'formattedSize' => FileSystem::formatBytes($size),
            ];

            $total += $size;
        }

        $response = [
            'dirs' => $results,
            'size' => $total,
            'formattedSize' => FileSystem::formatBytes($total),
        ];

        return $response;
    }
}
