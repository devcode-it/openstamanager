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

class StatoFE extends Model
{
    use SimpleModelTrait;

    public $incrementing = false;
    protected $table = 'fe_stati_documento';
    protected $primaryKey = 'codice';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'codice_stato_fe');
    }

    /**
     * Ritorna l'attributo name dello stato fe.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('name')
            ->where('id_record', '=', $this->codice)
            ->where('id_lang', '=', \App::getLang())
            ->first()->name;
    }

    /**
     * Ritorna l'id dello stato fe a partire dal nome.
     *
     * @param string $name il nome da ricercare
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByName($name)
    {
        return database()->table($this->table.'_lang')
            ->select('id_record')
            ->where('name', '=', $name)
            ->where('id_lang', '=', \App::getLang())
            ->first();
    }
}
