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
        $dbo->update('or_statiordine', [
            'descrizione' => (count($dbo->fetchArray('SELECT descrizione FROM or_statiordine WHERE descrizione = '.prepare(post('descrizione')))) > 0) ? $dbo->fetchOne('SELECT descrizione FROM or_statiordine WHERE id ='.$id_record)['descrizione'] : post('descrizione'),
            'icona' => post('icona'),
            'colore' => post('colore'),
            'completato' => post('completato') ?: null,
            'is_fatturabile' => post('is_fatturabile') ?: null,
            'impegnato' => post('impegnato') ?: null,
        ], ['id' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente.'));

        break;

    case 'add':
        $descrizione = post('descrizione');
        $icona = post('icona');
        $colore = post('colore');
        $completato = post('completato') ?: null;
        $is_fatturabile = post('is_fatturabile') ?: null;
        $impegnato = post('impegnato') ?: null;

        // controlla descrizione che non sia duplicata
        if (count($dbo->fetchArray('SELECT descrizione FROM or_statiordine WHERE descrizione='.prepare($descrizione))) > 0) {
            flash()->error(tr('Stato ordine già esistente.'));
        } else {
            $query = 'INSERT INTO or_statiordine(descrizione, icona, colore, completato, is_fatturabile, impegnato) VALUES ('.prepare($descrizione).', '.prepare($icona).', '.prepare($colore).','.prepare($completato).', '.prepare($is_fatturabile).', '.prepare($impegnato).' )';
            $dbo->query($query);
            $id_record = $dbo->lastInsertedID();
            flash()->info(tr('Nuovo stato ordine aggiunto.'));
        }

        break;

    case 'delete':
        // scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato negli ordini
        if (count($dbo->fetchArray('SELECT id FROM or_statiordine WHERE id='.prepare($id_record))) > 0) {
            $query = 'UPDATE or_statiordine SET deleted_at = NOW() WHERE can_delete = 1 AND id='.prepare($id_record);
        } else {
            $query = 'DELETE FROM or_statiordine WHERE can_delete = 1 AND id='.prepare($id_record);
        }

        $dbo->query($query);

        flash()->info(tr('Stato ordine eliminato.'));

        break;
}
