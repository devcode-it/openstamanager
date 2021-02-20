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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use SimpleModelTrait;

    /** @var array Stati previsti dal sistema di autenticazione */
    protected static $status = [
        'success' => [
            'code' => 1,
            'message' => 'Login riuscito!',
            'color' => 'success',
        ],
        'failed' => [
            'code' => 0,
            'message' => 'Autenticazione fallita!',
            'color' => 'danger',
        ],
        'disabled' => [
            'code' => 2,
            'message' => 'Utente non abilitato!',
            'color' => 'info',
        ],
        'unauthorized' => [
            'code' => 5,
            'message' => "L'utente non ha nessun permesso impostato!",
            'color' => 'warning',
        ],
    ];

    protected $table = 'zz_logs';

    public function getCodeAttribute()
    {
        return $this->getStatus()['code'];
    }

    public function getMessageAttribute()
    {
        return $this->getStatus()['message'];
    }

    public function getColorAttribute()
    {
        return $this->getStatus()['color'];
    }

    public function getStatus()
    {
        return self::$status[$this->stato];
    }

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }
}
