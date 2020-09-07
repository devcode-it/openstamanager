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

use Carbon\Carbon;
use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Notifications\EmailNotification;
use Traits\StoreTrait;

class Account extends Model
{
    use StoreTrait;
    use SoftDeletes;

    protected $table = 'em_accounts';

    public function testConnection()
    {
        // Impostazione di connected_at a NULL
        $this->connected_at = null;
        $this->save();

        // Creazione email di test
        $mail = new EmailNotification($this->id);
        // Tentativo di connessione
        $result = $mail->testSMTP();

        // Salvataggio della data di connessione per test riuscito
        if ($result) {
            $this->connected_at = Carbon::now();
            $this->save();
        }

        return $result;
    }

    /* Relazioni Eloquent */

    public function templates()
    {
        return $this->hasMany(Template::class, 'id_account');
    }

    public function emails()
    {
        return $this->hasMany(Mail::class, 'id_account');
    }
}
