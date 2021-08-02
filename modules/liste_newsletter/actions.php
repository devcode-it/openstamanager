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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Modules\Newsletter\Lista;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $lista = Lista::build(filter('name'));
        $id_record = $lista->id;

        flash()->info(tr('Nuova lista newsletter creata!'));

        break;

    case 'update':
        $lista->name = filter('name');
        $lista->description = filter('description');

        $query = filter('query');
        if (check_query($query)) {
            $lista->query = html_entity_decode($query);
        }

        $lista->save();

        flash()->info(tr('Lista newsletter salvata!'));

        break;

    case 'delete':
        $lista->delete();

        flash()->info(tr('Lista newsletter rimossa!'));

        break;

    case 'add_receivers':
        $destinatari = [];

        // Selezione manuale
        $id_receivers = post('receivers');
        foreach ($id_receivers as $id_receiver) {
            list($tipo, $id) = explode('_', $id_receiver);
            if ($tipo == 'anagrafica') {
                $type = Anagrafica::class;
            } elseif ($tipo == 'sede') {
                $type = Sede::class;
            } else {
                $type = Referente::class;
            }

            $destinatari[] = [
                'record_type' => $type,
                'record_id' => $id,
            ];
        }

        // Aggiornamento destinatari
        foreach ($destinatari as $destinatario) {
            $data = array_merge($destinatario, [
                'id_list' => $lista->id,
            ]);

            $registrato = $database->select('em_list_receiver', '*', $data);
            if (empty($registrato)) {
                $database->insert('em_list_receiver', $data);
            }
        }

        flash()->info(tr('Aggiunti nuovi destinatari alla lista!'));

        break;

    case 'remove_receiver':
        $receiver_id = post('id');
        $receiver_type = post('type');

        $database->delete('em_list_receiver', [
            'record_type' => $receiver_type,
            'record_id' => $receiver_id,
            'id_list' => $lista->id,
        ]);

        flash()->info(tr('Destinatario rimosso dalla lista!'));

        break;
}
