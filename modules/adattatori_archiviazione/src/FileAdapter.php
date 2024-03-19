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

namespace Modules\FileAdapters;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\LocalPoolTrait;

class FileAdapter extends Model
{
    use SimpleModelTrait;
    use LocalPoolTrait;
    use SoftDeletes;

    protected $table = 'zz_storage_adapters';

    public function testConnection()
    {
        return true;
    }

    public function setIsDefaultAttribute($valore){

        self::getAll()->where('id', '!=', $this->id)->each(function($item){
            $item->attributes['is_default'] = false;
            $item->save();
        });

        $this->attributes['is_default'] = $valore;
    }

    public static function getDefaultConnector(){

        return self::where('is_default', 1)->first();
    }

    public static function getLocalConnector(){

        return self::where('is_local', 1)->first();
    }


}