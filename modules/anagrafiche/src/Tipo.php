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

namespace Modules\Anagrafiche;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Tipo extends Model
{
    use SimpleModelTrait;

    protected $table = 'an_tipianagrafiche';

    public static function build($descrizione)
    {
        $model = new static();
        $model->save();

        return $model;
    }

    public function anagrafiche()
    {
        return $this->hasMany(Anagrafica::class, 'idtipoanagrafica');
    }

    /**
     * Ritorna l'attributo name del tipo anagrafica.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('name')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', \App::getLang())
            ->first()->name;
    }

    /**
     * Ritorna l'id del tipo di anagrafica a partire dal nome.
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

    /**
     * Imposta l'attributo name del tipo di anagrafica.
     */
    public function setNameAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', \App::getLang());

        if ($translated->count() > 0) {
            $translated->update([
                'name' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => \App::getLang(),
                'name' => $value
            ]);
        }
    }
}
