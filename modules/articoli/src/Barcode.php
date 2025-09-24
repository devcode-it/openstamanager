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
use Traits\RecordTrait;

class Barcode extends Model
{
    use SimpleModelTrait;
    use RecordTrait;

    protected $table = 'mg_articoli_barcode';

    protected $fillable = [
        'idarticolo',
        'barcode',
    ];

    public static function build($idarticolo = null, $barcode = null)
    {
        $model = new static();
        $model->idarticolo = $idarticolo;
        $model->barcode = $barcode;
        $model->save();

        return $model;
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class, 'idarticolo');
    }

    public function getModuleAttribute()
    {
        return 'Articoli';
    }
}
