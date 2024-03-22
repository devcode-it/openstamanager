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

namespace Modules\Fatture;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;

class Tipo extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'co_tipidocumento';

    protected static $translated_fields = [
        'name',
    ];

    public static function build($dir = null, $codice_tipo_documento_fe = null)
    {
        $model = new static();
        $model->dir = $dir;
        $model->codice_tipo_documento_fe = $codice_tipo_documento_fe;
        $model->save();

        return $model;
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idtipodocumento');
    }

    public function getModuleAttribute()
    {
        return '';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
