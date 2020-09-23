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
use Modules\Emails\Mail;

class OperationLog extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_operations';

    protected static $info = [];

    public static function setInfo($name, $value)
    {
        self::$info[$name] = $value;
    }

    public static function getInfo($name)
    {
        return self::$info[$name];
    }

    public static function build($operation)
    {
        if (!\Auth::check()) {
            return null;
        }

        $model = new static();

        foreach (self::$info as $key => $value) {
            $model->{$key} = $value;
        }

        $model->op = $operation;
        $model->id_utente = \Auth::user()->id;

        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function email()
    {
        return $this->belongsTo(Mail::class, 'id_email');
    }
}
