<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Modules\Newsletter;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Traits\RecordTrait;

class Lista extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_lists';

    public static function build($name)
    {
        $model = new static();
        $model->name = $name;

        $model->save();

        return $model;
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        $query = $this->query;
        if (!empty($query)) {
            $results = database()->fetchArray($query);

            $anagrafiche = array_column($results, 'id');
            $this->anagrafiche()->sync($anagrafiche);
        }

        return $result;
    }

    // Relazione Eloquent

    public function anagrafiche()
    {
        return $this->belongsToMany(Anagrafica::class, 'em_list_anagrafica', 'id_list', 'id_anagrafica')->withTrashed();
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Liste';
    }
}
