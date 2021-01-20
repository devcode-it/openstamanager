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

namespace Modules\CategorieDocumentali;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'do_categorie';

    public static function build($descrizione)
    {
        $model = new static();
        $model->descrizione = $descrizione;

        $model->save();

        $gruppi = database()->fetchArray('SELECT `id` FROM `zz_groups`');
        $model->syncPermessi(array_column($gruppi, 'id'));

        return $model;
    }

    public function syncPermessi(array $groups)
    {
        $groups[] = 1;

        $database = database();
        $database->sync('do_permessi', ['id_categoria' => $this->id], [
           'id_gruppo' => $groups,
       ]);
    }
}
