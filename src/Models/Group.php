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
use Illuminate\Database\Eloquent\Model;
;

class Group extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_groups';

    public static function build($nome = null, $theme = null, $id_module_start = null)
    {
        $model = new static();
        $model->nome = $nome;
        $model->theme = $theme;
        $model->id_module_start = $id_module_start;
        $model->save();

        return $model;
    }


    /* Relazioni Eloquent */

    public function users()
    {
        return $this->hasMany(User::class, 'idgruppo');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'zz_permissions', 'idgruppo', 'idmodule')->withPivot('permessi');
    }

    public function views()
    {
        return $this->belongsToMany(View::class, 'zz_group_view', 'id_gruppo', 'id_vista');
    }

    /**
     * Ritorna l'attributo name del gruppo.
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
     * Imposta l'attributo name del gruppo.
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
     * Ritorna l'id del gruppo a partire dal nome.
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
