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

namespace Modules\StatoServizi;

use Tasks\Manager;
use Util\FileSystem;
use Models\Cache;

class SpaceHookTask extends Manager
{
    public $cache_name = 'Spazio utilizzato';

    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('Calcolo spazio disponibile completato!'),
        ];      
        
        try {
            if (!empty(setting('Soft quota'))) {
                $osm_size = FileSystem::folderSize(base_dir(), ['htaccess']);
            }

            $soft_quota = (float) setting('Soft quota'); // Impostazione in GB
            $soft_quota = $soft_quota * (1024 ** 3); // Trasformazione in GB

            $cache = Cache::where('name', $this->cache_name)->first();
            $cache->set($osm_size);        
        } catch (\Exception $e) {
            $result = [
                'response' => 0,
                'message' => tr('Errore nel calcolo dello spazio disponibile! _error_',[
                    '_error_' => $e->getMessage(),
                ]),
            ];
        }     

        return $result;
    }
}
