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
use Modules\Anagrafiche\Anagrafica;
use Illuminate\Contracts\Auth\Authenticatable;

class UserTokens extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_tokens';

    public static function build(?Group $gruppo = null, $username = null, $email = null, $password = null)
    {
        $model = new static();
        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }
}
