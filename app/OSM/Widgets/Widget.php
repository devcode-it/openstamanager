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

namespace App\OSM\Widgets;

use App\OSM\ComponentManagerTrait;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Models\Module;

/**
 * Modello Eloquent per i widget del gestionale.
 *
 * @since 2.5
 */
class Widget extends Model
{
    use SimpleModelTrait;
    use ComponentManagerTrait;

    protected $table = 'zz_widgets';

    protected $appends = [
        'permission',
    ];

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    /*
    public function groups()
    {
        return $this->morphToMany(Group::class, 'permission', 'zz_permissions', 'external_id', 'group_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }
    */

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });

        static::addGlobalScope('permission', function (Builder $builder) {
            //$builder->with('groups');
        });
    }
}
