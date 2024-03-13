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

class Tipo extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_tipidocumento';


    public static function build($dir, $codice_tipo_documento_fe)
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

    /**
     * Ritorna l'attributo name del tipo documento.
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
     * Imposta l'attributo name del tipo di documento.
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

    /**
     * Ritorna l'id del tipo documento a partire dal nome.
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
