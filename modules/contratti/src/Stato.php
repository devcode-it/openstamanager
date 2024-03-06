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

namespace Modules\Contratti;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Stato extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_staticontratti';

    public static function build($icona, $colore, $is_completato, $is_fatturabile, $is_pianificabile)
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
        return $this->hasMany(Contratto::class, 'idstato');
    }

    /**
     * Ritorna l'attributo name dello stato contratto.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('name')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->name;
    }


    /**
     * Imposta l'attributo name dell'articolo.
     */
    public function setNameAttribute($value)
    {
        $translated = database()->table($this->table.'_lang')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'name' => $value
            ]);
        } else {
            $translated->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'name' => $value
            ]);
        }
    }

    /**
     * Ritorna l'id dello stato contratto a partire dal nome.
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
            ->where('id_lang', '=', setting('Lingua'))
            ->first();
    }
}
