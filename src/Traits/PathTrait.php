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

namespace Traits;

use App;

trait PathTrait
{
    /**
     * Restituisce il percorso per i contenuti della struttura.
     *
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->main_folder.'/'.$this->directory;
    }

    /**
     * Restituisce il percorso completo per il file indicato della struttura.
     *
     * @param $file
     *
     * @return string|null
     */
    public function filepath($file)
    {
        return App::filepath($this->path.'|custom|', $file);
    }

    /**
     * Restituisce l'URL completa per il file indicato della struttura.
     *
     * @param $file
     *
     * @return string|null
     */
    public function fileurl($file)
    {
        $filepath = $this->filepath($file);

        $result = str_replace(base_dir(), base_path(), $filepath);
        $result = str_replace('\\', '/', $result);

        return $result;
    }
}
