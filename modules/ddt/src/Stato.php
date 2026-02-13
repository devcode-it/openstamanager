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

namespace Modules\DDT;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stato extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    use SoftDeletes;
    protected $table = 'dt_statiddt';

    protected static $translated_fields = [
        'title',
    ];

    public static function build($descrizione = null, $icona = null, $colore = null, $is_bloccato = null, $is_fatturabile = null)
    {
        $model = new static();
        $model->name = $descrizione;
        $model->icona = $icona;
        $model->colore = $colore;
        $model->is_bloccato = $is_bloccato;
        $model->is_fatturabile = $is_fatturabile;
        $model->save();

        return $model;
    }

    public function ddt()
    {
        return $this->hasMany(DDT::class, 'idstatoddt');
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
