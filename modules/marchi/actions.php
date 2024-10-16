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

switch (post('op')) {
    // Aggiorno informazioni di base impianto
    case 'update':
        $dbo->update('mg_marchi', [
            'name' => post('name'),
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

        // Aggiungo impianto
    case 'add':
        $dbo->insert('mg_marchi', [
            'name' => post('name'),
        ]);
        $id_record = $dbo->lastInsertedID();

        flash()->info(tr('Aggiunto nuovo marchio!'));

        break;

        // Rimuovo impianto e scollego tutti i suoi componenti
    case 'delete':
        $dbo->query('DELETE FROM mg_marchi WHERE id='.prepare($id_record));

        flash()->info(tr('Marchio eliminato!'));
        break;
}
