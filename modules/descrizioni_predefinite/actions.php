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

switch (filter('op')) {
    case 'update':
        Models\DefaultDescription::find($id_record)->update([
            'name' => filter('name'),
            'descrizione' => filter('descrizione'),
            'note' => filter('note'),
        ]);

        Models\DefaultDescriptionModule::where('id_description', $id_record)->delete();
        $id_moduli = (array) post('id_moduli');
        foreach ($id_moduli as $id_modulo) {
            Models\DefaultDescriptionModule::create([
                'id_description' => $id_record,
                'id_module' => $id_modulo,
            ]);
        }

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'add':
        $description = Models\DefaultDescription::create([
            'name' => filter('name'),
            'descrizione' => filter('descrizione'),
            'note' => filter('note'),
        ]);

        $id_record = $description->id;
        $id_moduli = (array) post('id_moduli');
        foreach ($id_moduli as $id_modulo) {
            Models\DefaultDescriptionModule::create([
                'id_description' => $id_record,
                'id_module' => $id_modulo,
            ]);
        }

        flash()->info(tr('Aggiunta nuova risposta predefinita!'));

        break;

    case 'delete':
        Models\DefaultDescription::find($id_record)->delete();

        flash()->info(tr('Risposta predefinita eliminata!'));

        break;
}
