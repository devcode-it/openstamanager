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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Traits\LocalPoolTrait;
use Traits\PathTrait;

class PrintTemplate extends Model
{
    use SimpleModelTrait;
    use PathTrait;
    use LocalPoolTrait;

    protected $table = 'zz_prints';
    protected $main_folder = 'templates';

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });
    }

    /**
     * Ritorna l'attributo name del template.
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
     * Ritorna l'attributo title del template.
     *
     * @return string
     */
    public function getTitleAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('title')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->title;
    }

    /**
     * Imposta l'attributo title della categoria.
     */
    public function setTitleAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'title' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'title' => $value
            ]);
        }
    }

    /**
     * Ritorna l'attributo filename del template.
     *
     * @return string
     */
    public function getFilenameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('filename')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->filename;
    }

    /**
     * Imposta l'attributo filename della categoria.
     */
    public function setFilenameAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'filename' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'filename' => $value
            ]);
        }
    }

    /**
     * Ritorna l'id del template a partire dal nome.
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
