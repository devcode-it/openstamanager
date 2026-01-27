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
    // Aggiunta danno
    case 'adddanno':
        $descrizione = post('descrizione');
        $data = post('data');
        $luogo = post('luogo');

        $dbo->insert('an_automezzi_danni', [
            'idsede' => $id_record,
            'descrizione' => $descrizione,
            'data' => $data,
            'luogo' => $luogo
        ]);

        flash()->info(tr('Danno aggiunto correttamente!'));

        break;

        // Modifica danno
    case 'editdanno':
        $iddanno = post('iddanno');
        $descrizione = post('descrizione');
        $data = post('data');
        $luogo = post('luogo');

        $dbo->update('an_automezzi_danni', [
            'descrizione' => $descrizione,
            'data' => $data,
            'luogo' => $luogo
        ], [
            'id' => $iddanno,
        ]);

        flash()->info(tr('Danno modificato correttamente!'));

        break;

        // Eliminazione scadenza
    case 'deldanno':
        $idscadenza = post('id');

        $dbo->query('DELETE FROM an_automezzi_danni WHERE id = '.prepare($idscadenza));

        flash()->info(tr('Scadenza eliminata correttamente!'));

        break;
}
