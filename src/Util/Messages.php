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

namespace Util;

use Illuminate\Support\Facades\Session;

/**
 * Classe dedicata alla gestione dei messaggi per l'utente.
 *
 * @since 2.4.2
 */
class Messages
{
    public function __contruct($name)
    {
    }

    public function info($message)
    {
        Session::push('messages.info', $message);
    }

    public function warning($message)
    {
        Session::push('messages.info', $message);
    }

    public function error($message)
    {
        Session::push('messages.error', $message);
    }

    public function getMessage($type)
    {
        $messages = Session::get('messages.'.$type);
        Session::remove('messages.'.$type);

        return $messages;
    }

    public function getMessages()
    {
        $messages = Session::get('messages');
        Session::remove('messages');

        return $messages;
    }
}
