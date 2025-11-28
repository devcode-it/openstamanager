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

namespace Modules\Articoli;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Models\Module;
use Traits\HierarchyTrait;
use Traits\RecordTrait;

class Marca extends Model
{
    use SimpleModelTrait;
    use HierarchyTrait;
    use RecordTrait;

    protected $table = 'zz_marche';

    public static function build($nome = null)
    {
        $model = new static();
        $model->name = $nome;
        $model->save();

        return $model;
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'id_marca');
    }

    public function getImmagineUploadAttribute()
    {
        $module = Module::where('name', 'Marche')->first();

        return $module->files($this->id, true)->where('key', 'cover')->first();
    }

    public function getImageAttribute()
    {
        $module = Module::where('name', 'Marche')->first();
        $upload = $module->files($this->id, true)
            ->where('key', 'cover')
            ->first();

        if (empty($upload)) {
            return null;
        }

        $fileinfo = \Uploads::fileInfo($upload->filename);

        $directory = '/files/'.$module->attachments_directory.'/';
        $image = $directory.$upload->filename;
        $image_thumbnail = $directory.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

        $url = file_exists(base_dir().$image_thumbnail) ? base_path_osm().$image_thumbnail : base_path_osm().$image;

        return $url;
    }

    public function getModuleAttribute()
    {
        return 'marche';
    }
}
