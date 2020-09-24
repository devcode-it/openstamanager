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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Util\Query;

class View extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_views';

    public function getQueryAttribute($value)
    {
        return Query::replacePlaceholder($value);
    }

    /* Relazioni Eloquent */

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'zz_group_view', 'id_vista', 'id_gruppo');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }
}
