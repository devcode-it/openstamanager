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

namespace Modules\DDT\Components;

use Common\Components\Article;
use Modules\Articoli\Articolo as Original;
use Modules\DDT\DDT;

class Articolo extends Article
{
    use RelationTrait;

    protected $table = 'dt_righe_ddt';
    protected $serialRowID = 'ddt';

    /**
     * Crea un nuovo articolo collegato ad una ddt.
     *
     * @return self
     */
    public static function build(DDT $ddt, Original $articolo)
    {
        $model = parent::build($ddt, $articolo);

        return $model;
    }
}
