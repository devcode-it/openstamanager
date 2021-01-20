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

include_once __DIR__.'/../../core.php';

use Models\User;

$name = filter('name');
$value = filter('value');

switch ($name) {
    case 'username':
        $disponibile = User::where([
            ['username', $value],
            ['id', '<>', $id_record],
        ])->count() == 0;

        $message = $disponibile ? tr("L'username è disponbile") : tr("L'username è già in uso");
        $result = $disponibile;

        // Lunghezza minima del nome utente (username)
        $min_length_username = 4;
        if (strlen($value) < $min_length_username) {
            $message .= '. '.tr("Lunghezza dell'username non sufficiente").'.';
            $result = false;
        }

        $response = [
            'result' => $result,
            'message' => $message,
        ];

        break;
}
