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
    // Aggiunta manutenzione
    case 'addmanutenzione':
        $descrizione = post('descrizione');
        $data_inizio = post('data_inizio');
        $km = post('km');
        $codice = post('codice');

        $dbo->insert('an_automezzi_scadenze', [
            'idsede' => $id_record,
            'descrizione' => $descrizione,
            'data_inizio' => $data_inizio,
            'km' => $km,
            'codice' => $codice,
            'is_manutenzione' => 1,
        ]);

        flash()->info(tr('Manutenzione aggiunta correttamente!'));

        break;

        // Modifica manutenzione
    case 'editmanutenzione':
        $idmanutenzione = post('idmanutenzione');
        $descrizione = post('descrizione');
        $data_inizio = post('data_inizio');
        $km = post('km');
        $codice = post('codice');
        $is_completato = post('is_completato');

        $dbo->update('an_automezzi_scadenze', [
            'descrizione' => $descrizione,
            'data_inizio' => $data_inizio,
            'km' => $km,
            'codice' => $codice,
            'is_completato' => $is_completato,
        ], [
            'id' => $idmanutenzione,
        ]);

        flash()->info(tr('Manutenzione modificata correttamente!'));

        break;

        // Eliminazione scadenza
    case 'delscadenza':
        $idscadenza = post('id');

        $dbo->delete('an_automezzi_scadenze', ['id' => $idscadenza]);

        flash()->info(tr('Scadenza eliminata correttamente!'));

        break;
}
