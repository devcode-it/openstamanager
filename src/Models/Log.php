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
    protected static $status;

    protected $table = 'zz_logs';

    public function getCodeAttribute()
    {
        return $this->stato;
    }

    public function getStateAttribute()
    {
        return $this->getStatus()['state'];
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
        return self::getAvailableStatus()[$this->stato];
    }

    public function setStatus($name)
    {
        $code = self::getStatusCode($name);

        $this->stato = $code;
    }

    /**
     * @param string $name
     *
     * @return int
     */
    public static function getStatusCode($name)
    {
        $status = self::getAvailableStatus();
        $code = 0;

        foreach ($status as $c => $s) {
            if ($s['state'] == $name) {
                $code = $c;
            }
        }

        return $code;
    }

    /**
     * Restituisce un elenco di stati previsti dal sistema di autenticazione.
     *
     * @return string[][]
     */
    public static function getAvailableStatus()
    {
        if (!isset(self::$status)) {
            self::$status = [
                1 => [
                    'state' => 'success',
                    'message' => tr('Login riuscito!'),
                    'color' => 'success',
                ],
                0 => [
                    'state' => 'failed',
                    'message' => tr('Autenticazione fallita!'),
                    'color' => 'danger',
                ],
                2 => [
                    'state' => 'disabled',
                    'message' => tr('Utente non abilitato!'),
                    'color' => 'info',
                ],
                5 => [
                    'state' => 'unauthorized',
                    'message' => tr("L'utente non ha nessun permesso impostato!"),
                    'color' => 'warning',
                ],
            ];
        }

        return self::$status;
    }

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }
}
