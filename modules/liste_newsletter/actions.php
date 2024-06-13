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
use Modules\ListeNewsletter\Lista;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $name = post('name');
        $lista_new = Lista::where('id', '=', (new Lista())->getByField('title', $name))->orWhere('name', $name)->where('id', '!=', $id_record)->first();

        if (!empty($lista_new)) {
            flash()->error(tr('Esiste già una lista con questo nome.'));
        } else {
            $lista = Lista::build($name);
            if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                $lista->name = $name;
            }
            $id_record = $lista->id;
            $lista->setTranslation('title', $name);
            $lista->save();

            flash()->info(tr('Nuova lista aggiunta.'));
        }

        break;

    case 'update':
        $name = post('name');
        $description = post('description');
        $query = post('query');

        if (check_query($query)) {
            $query = html_entity_decode($query);
        }

        $lista_new = Lista::where('id', '=', (new Lista())->getByField('title', $name))->orWhere('name', $name)->where('id', '!=', $id_record)->first();

        if (!empty($lista_new)) {
            flash()->error(tr('Esiste già una lista con questo nome.'));
        } else {
            if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                $lista->name = $name;
            }
            $lista->setTranslation('title', $name);
            $lista->setTranslation('description', $description);
            $lista->query = $query;
            $lista->save();

            flash()->info(tr('Informazioni salvate correttamente.'));
        }

        break;

    case 'delete':
        $lista = Lista::find($id_record);
        $lista->delete();

        flash()->info(tr('Lista newsletter rimossa!'));

        break;

    case 'add_receivers':
        $destinatari = [];

        // Selezione manuale
        $id_receivers = post('receivers');
        foreach ($id_receivers as $id_receiver) {
            [$tipo, $id] = explode('_', (string) $id_receiver);
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

            $registrato = $database->select('em_list_receiver', '*', [], $data);
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

    case 'remove_all_receivers':
        $database->delete('em_list_receiver', [
            'id_list' => $lista->id,
        ]);

        flash()->info(tr('Tutti i destinatari sono stati rimossi dalla lista newsletter!'));

        break;
}
