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

use Models\Setting;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'salva':
        $id = filter('id');
        $valore = filter('valore');

        $impostazione = Setting::find($id);
        if (!$impostazione->editable) {
            echo json_encode([
                'result' => true,
            ]);

            return;
        }

        $result = Settings::setValue($impostazione->id, $valore);
        echo json_encode([
            'result' => $result,
        ]);

        if ($result) {
            flash()->info('Impostazione modificata con successo!');
        } else {
            flash()->error('Errore durante il salvataggio!');
        }

        break;

    case 'ricerca':
        $search = filter('search');
        $sezioni = Setting::select('sezione')
            ->where('sezione', 'like', '%'.$search.'%')
            ->orWhere('nome', 'like', '%'.$search.'%')
            ->groupBy(['sezione'])
            ->get()->pluck('sezione');

        echo json_encode($sezioni);

        break;

    case 'update':
        $is_all_valid = true;

        foreach (post('setting') as $id => $value) {
            $result = Settings::get($id);

            if (preg_match("/multiple\[(.+?)\]/", $result['tipo'], $m)) {
                $value = implode(',', $value);
            }

            //Se è un'impostazione editabile
            if ($result->editable) {
                $is_valid = Settings::setValue($id, $value);

                if (!$is_valid) {
                    // integer
                    if ($result['tipo'] == 'integer') {
                        flash()->error(tr('Il valore inserito del parametro _NAME_ deve essere un numero intero!', [
                            '_NAME_' => '"'.$result['nome'].'"',
                        ]));
                    }

                    // list
                    // verifico che il valore scelto sia nella lista enumerata nel db
                    elseif (preg_match("/list\[(.+?)\]/", $result['tipo'], $m)) {
                        flash()->error(tr('Il valore inserito del parametro _NAME_ deve essere un compreso tra i valori previsti!', [
                            '_NAME_' => '"'.$result['nome'].'"',
                        ]));
                    }
                }
            }

            $is_all_valid &= $is_valid;
        }

        if ($is_all_valid) {
            flash()->info(tr('Impostazioni aggiornate correttamente!'));
        }

        break;
}
