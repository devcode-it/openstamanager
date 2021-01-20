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

namespace Traits\Components;

use Models\Upload;

trait UploadTrait
{
    protected $uploads_directory = 'files';

    /**
     * Restituisce il percorso per il salvataggio degli upload.
     *
     * @return string
     */
    public function getUploadDirectoryAttribute()
    {
        $directory = $this->directory ?: 'common';

        $result = $this->uploads_directory.'/'.$directory;
        directory($result);

        return $result;
    }

    public function uploads($id_record)
    {
        return $this->hasMany(Upload::class, $this->component_identifier)->where('id_record', $id_record)->get();
    }
}
