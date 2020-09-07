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

namespace Util;

/**
 * Classe dedicata alla gestione dei messaggi per l'utente.
 *
 * @since 2.4.2
 */
class Messages extends \Slim\Flash\Messages
{
    public function info($message)
    {
        return $this->addMessage('info', $message);
    }

    public function warning($message)
    {
        return $this->addMessage('warning', $message);
    }

    public function error($message)
    {
        return $this->addMessage('error', $message);
    }
}
