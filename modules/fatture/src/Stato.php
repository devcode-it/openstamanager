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

class Stato extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'co_statidocumento';

    protected static $translated_fields = [
        'title',
    ];

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idstatodocumento');
    }

    public function getModuleAttribute()
    {
        return 'Stati dei documenti';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
