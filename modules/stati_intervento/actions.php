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
    case 'update':
        $dbo->update('in_statiintervento', [
            'codice' => post('codice'),
            'descrizione' => post('descrizione'),
            'colore' => post('colore'),
            'is_completato' => post('is_completato'),
            'is_fatturabile' => post('is_fatturabile'),
            'notifica' => post('notifica'),
            'notifica_cliente' => post('notifica_cliente'),
            'notifica_tecnici' => post('notifica_tecnici'),
            'id_email' => post('email') ?: null,
            'destinatari' => post('destinatari'),
        ], ['idstatointervento' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente.'));

        break;

    case 'add':
        $codice = post('codice');
        $descrizione = post('descrizione');
        $colore = post('colore');

        //controllo che il codice non sia duplicato
        if (count($dbo->fetchArray('SELECT idstatointervento FROM in_statiintervento WHERE codice='.prepare($codice))) > 0) {
            flash()->warning(tr('Attenzione: lo stato attività _COD_ risulta già esistente.', [
                '_COD_' => $codice,
            ]));
        } else {
            $query = 'INSERT INTO in_statiintervento(codice, descrizione, colore) VALUES ('.prepare($codice).', '.prepare($descrizione).', '.prepare($colore).')';
            $dbo->query($query);
            $id_record = $database->lastInsertedID();
            flash()->info(tr('Nuovo stato attività aggiunto.'));
        }

        break;

    case 'delete':
        //scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato negli interventi
        if (count($dbo->fetchArray('SELECT id FROM in_interventi WHERE idstatointervento='.prepare($id_record))) > 0) {
            $query = 'UPDATE in_statiintervento SET deleted_at = NOW() WHERE idstatointervento='.prepare($id_record).' AND `can_delete`=1';
        } else {
            $query = 'DELETE FROM in_statiintervento  WHERE idstatointervento='.prepare($id_record).' AND `can_delete`=1';
        }

        $dbo->query($query);

        flash()->info(tr('Stato attività eliminato.'));

        break;
}
