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

use Models\Group;
use Models\User;

$name = filter('name');
$value = filter('value');

switch ($name) {
    case 'username':
        $disponibile = User::where([
            ['username', $value],
            ['id', '<>', $id_record],
        ])->count() == 0;

        $message = ($disponibile ? tr("L'username è disponibile") : tr("L'username _COD_ è già stato utilizzato", ['_COD_' => $value])).'.';
        $result = $disponibile;

        // Lunghezza minima del nome utente (username)
        $min_length_username = 4;
        if (strlen($value) < $min_length_username) {
            $message .= '<br>'.tr("Lunghezza dell'username non sufficiente: inserisci _MIN_ caratteri o più", ['_MIN_' => $min_length_username]).'.';
            $result = false;
        }

        $response = [
            'result' => $result,
            'message' => $message,
        ];

        break;

    case 'gruppo':
        $disponibile = Group::where([
            ['nome', $value],
            // ['id', '<>', $id_record],
        ])->count() == 0;

        $message = ($disponibile ? tr('Il nome del gruppo è disponibile') : tr('Il nome del gruppo _COD_ è già stato utilizzato', ['_COD_' => $value])).'.';
        $result = $disponibile;

        $response = [
            'result' => $result,
            'message' => $message,
        ];

        break;

    case 'email':
        $disponibile = User::where([
            ['email', $value],
            ['email', '<>', ''],
            // ['idanagrafica', '<>', $id_record],
        ])->count() == 0;
        $result = $disponibile;

        $message = $disponibile ? '<i class="fa fa-check text-green"></i> '.tr('Questa email non è ancora stata utilizzata') : '<i class="fa fa-warning text-yellow"></i> '.tr("L'email è già utilizzata in un'altra anagrafica");

        $errors = [];
        $check = Validate::isValidEmail($value);
        if (empty($check['valid-format'])) {
            $result = false;

            $errors[] = tr("L'email _COD_ non possiede un formato valido.", [
                '_COD_' => $value,
            ]);
        }

        if (isset($check['smtp-check']) && empty($check['smtp-check'])) {
            $result = false;
            $errors[] = tr("Impossibile verificare l'origine dell'email.");
        }

        $message .= '. ';
        if (!empty($errors)) {
            $message .= '<br><i class="fa fa-times text-red"></i> '.tr('_NUM_ errori', ['_NUM_' => count($errors)]).':<ul>';
            foreach ($errors as $error) {
                $message .= '<li>'.$error.'</li>';
            }
            $message .= '</ul>';
        }

        $response = [
            'result' => $result,
            'message' => $message,
        ];

        break;
}
