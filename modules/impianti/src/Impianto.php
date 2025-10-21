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

namespace Modules\Impianti;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Categoria;

class Impianto extends Model
{
    use SimpleModelTrait;

    protected $table = 'my_impianti';

    // Relazioni Eloquent
    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public static function build($matricola = null, $nome = null, ?Categoria $categoria = null, $anagrafica = null)
    {
        $model = new static();
        $model->matricola = $matricola;
        $model->nome = $nome;
        $model->idanagrafica = $anagrafica;

        $model->categoria()->associate($categoria);

        $model->save();

        return $model;
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }
}
