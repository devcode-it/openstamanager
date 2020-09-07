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

namespace Modules\Emails;

use Common\Model;

class Receiver extends Model
{
    protected $table = 'em_email_receiver';

    /* Relazioni Eloquent */

    public static function build(Mail $mail, $address, $type = null)
    {
        $model = parent::build();

        $model->email()->associate($mail);

        $model->address = $address;
        $model->type = $type ?: 'a';

        $model->save();
    }

    public function email()
    {
        return $this->belongsTo(Mail::class, 'id_email');
    }
}
