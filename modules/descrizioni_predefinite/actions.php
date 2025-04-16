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
use Modules\DDT\Causale;

switch (filter('op')) {
    case 'update':
        $dbo->update('zz_default_description', [
            'name' => filter('name'),
            'descrizione' => filter('descrizione'),
            'note' => filter('note')
        ], [
            'id' => $id_record
        ]);

        $dbo->delete('zz_default_description_module', [
            'id_description' => $id_record
        ]);
        $id_moduli = (array) post('id_moduli');
        foreach ($id_moduli as $id_modulo) {
            $dbo->insert('zz_default_description_module', [
                'id_description' => $id_record,
                'id_module' => $id_modulo
            ]);
        }

        flash()->info(tr('Salvataggio completato!'));

        break;

    case 'add':
        $dbo->insert('zz_default_description', [
            'name' => filter('name'),
            'descrizione' => filter('descrizione'),
            'note' => filter('note')
        ]);

        $id_record = $dbo->lastInsertedId();
        $id_moduli = (array) post('id_moduli');
        foreach ($id_moduli as $id_modulo) {
            $dbo->insert('zz_default_description_module', [
                'id_description' => $id_record,
                'id_module' => $id_modulo
            ]);
        }

        flash()->info(tr('Aggiunta nuova risposta predefinita!'));

        break;

    case 'delete':
        $dbo->delete('zz_default_description', [
            'id' => $id_record
        ]);

        flash()->info(tr('Risposta predefinita eliminata!'));

        break;
}
