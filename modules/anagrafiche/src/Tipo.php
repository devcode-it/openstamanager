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

class Tipo extends Model
{
    use SimpleModelTrait;

    protected $table = 'an_tipianagrafiche';
    protected $primaryKey = 'idtipoanagrafica';

    protected $appends = [
        'id',
    ];

    protected $hidden = [
        'idtipoanagrafica',
    ];

    /**
     * Restituisce l'identificativo.
     *
     * @return int
     */
    public function getIdAttribute()
    {
        return $this->idtipoanagrafica;
    }

    public function anagrafiche()
    {
        return $this->hasMany(Anagrafica::class, 'an_tipianagrafiche_anagrafiche', 'idtipoanagrafica', 'idanagrafica');
    }
}
