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
use Illuminate\Database\Eloquent\SoftDeletes;

class Referente extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'an_referenti';

    protected $guarded = [];

    /**
     * Crea un nuovo referente.
     *
     * @param int|null $id (id anagrafica)
     * @param string|null $nome
     * @param int|null $id_mansione
     * @param int|null $id_sede
     *
     * @return self
     */
    public static function build($id = null, $nome = null, $id_mansione = null, $id_sede = null)
    {
        $model = new static();

        $model->id_anagrafica = $id;
        $model->nome = $nome;
        $model->id_mansione = $id_mansione;

        // Se non è fornita id_sede, impostiamo 0 (sede legale / default)
        $model->id_sede = $id_sede ?? 0;

        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'id_sede');
    }
}
