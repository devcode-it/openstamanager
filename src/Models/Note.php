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

class Note extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_notes';

    /**
     * Crea una nuova nota.
     *
     * @param Module|Plugin $structure
     * @param int           $id_record
     * @param string        $contenuto
     * @param string|null   $data_notifica
     *
     * @return self
     */
    public static function build(User $user, $structure, $id_record, $contenuto, $data_notifica = null)
    {
        $model = new static();

        $model->user()->associate($user);

        if ($structure instanceof Module) {
            $model->module()->associate($structure);
        } elseif ($structure instanceof Plugin) {
            $model->plugin()->associate($structure);
        }

        $model->id_record = $id_record;

        $model->content = $contenuto;
        $model->notification_date = $data_notifica;

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
}
