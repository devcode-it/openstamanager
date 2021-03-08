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
        if (isset($id_record)) {
            $database->update('mg_causali_movimenti', [
                'nome' => post('nome'),
                'tipo_movimento' => post('tipo_movimento'),
                'descrizione' => post('descrizione'),
            ], [
                'id' => $id_record,
            ]);
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio'));
        }

        break;

    case 'add':
        $database->insert('mg_causali_movimenti', [
            'nome' => post('nome'),
            'tipo_movimento' => post('tipo_movimento'),
            'descrizione' => post('descrizione'),
        ]);
        $id_record = $database->lastInsertedID();

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `mg_causali_movimenti` WHERE `id`='.prepare($id_record));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'movimento predefinito',
            ]));
        }

        break;
}
