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

namespace Modules\Anagrafiche;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use SimpleModelTrait;

    protected $table = 'an_sedi';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Crea una nuova sede.
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $is_sede_legale = false)
    {
        $model = parent::make();

        if (!empty($is_sede_legale)) {
            $model->nomesede = 'Sede legale';
        }
        $model->anagrafica()->associate($anagrafica);
        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }
}
