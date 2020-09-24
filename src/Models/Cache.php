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

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\LocalPoolTrait;

/**
 * Risorsa di cache per la gestione delle informazioni temporanee del gestionale.
 * Da utilizzare in sola lettura oppure in creazione e diretta scritture.
 */
class Cache extends Model
{
    use SimpleModelTrait;
    use LocalPoolTrait;

    protected $table = 'zz_cache';

    protected $casts = [
        'content' => 'array',
    ];

    protected $dates = [
        'expire_at',
    ];

    public static function build($name, $valid_time, $expire_at = null)
    {
        $model = new self();

        $model->name = $name;
        $model->valid_time = $valid_time;
        $model->expire_at = $expire_at;

        $model->save();

        return $model;
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function getValidAttribute()
    {
        return !empty($this->expire_at) && $this->expire_at->greaterThanOrEqualTo(Carbon::now());
    }

    public function set($content)
    {
        $this->content = $content;

        return $this->save();
    }

    public function save(array $options = [])
    {
        if (!empty($this->valid_time)) {
            $interval = CarbonInterval::make($this->valid_time);
            $this->expire_at = (new Carbon())->add($interval);
        } elseif (empty($this->expire_at)) {
            $interval = CarbonInterval::make('6 hours');
            $this->expire_at = (new Carbon())->add($interval);
        }

        return parent::save($options);
    }

    public function delete()
    {
        if (empty($this->valid_time)) {
            return parent::delete();
        }

        return false;
    }

    public function scopeValid($query)
    {
        return $query->where('expire_at', '>', Carbon::now());
    }

    public function scopeInvalid($query)
    {
        return $query->where('expire_at', '<=', Carbon::now());
    }
}
