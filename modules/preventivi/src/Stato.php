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

namespace Modules\Preventivi;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;

class Stato extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'co_statipreventivi';

    protected static $translated_fields = [
        'title',
    ];

    public static function build($icona = null, $colore = null, $is_completato = null, $is_fatturabile = null, $is_pianificabile = null)
    {
        $model = new static();
        $model->icona = $icona;
        $model->colore = $colore;
        $model->is_completato = $is_completato;
        $model->is_fatturabile = $is_fatturabile;
        $model->is_pianificabile = $is_pianificabile;
        $model->save();

        return $model;
    }

    public function preventivi()
    {
        return $this->hasMany(Preventivo::class, 'idstatopreventivo');
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
