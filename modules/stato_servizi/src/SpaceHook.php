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

namespace Modules\StatoServizi;

use Hooks\CachedManager;
use Util\FileSystem;

class SpaceHook extends CachedManager
{
    public function getCacheName()
    {
        return 'Spazio utilizzato';
    }

    public function cacheData()
    {
        if (!empty(setting('Soft quota'))) {
            return FileSystem::folderSize(base_dir(), ['htaccess']);
        }

        return false;
    }

    public function response()
    {
        $osm_size = $this->getCache()->content;

        $soft_quota = (float) setting('Soft quota'); // Impostazione in GB
        $space_limit = ($soft_quota / 100) * 95; // 95% dello spazio indicato
        $space_limit = $space_limit * 1024 ^ 3; // Trasformazione in GB

        $message = tr('Attenzione: occupati _TOT_ dei _QUOTA_ previsti', [
            '_TOT_' => FileSystem::formatBytes($osm_size),
            '_QUOTA_' => FileSystem::formatBytes($space_limit),
        ]);

        return [
            'icon' => 'fa fa-database text-warning',
            'message' => $message,
            'show' => ($osm_size > $space_limit),
        ];
    }
}
