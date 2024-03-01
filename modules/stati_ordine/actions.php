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

use Modules\Ordini\Stato;

switch (post('op')) {
    case 'update':
        $id_stato= (new Stato())->getByName(post('descrizione'))->id_record;
        $dbo->update('or_statiordine', [
            'icona' => post('icona'),
            'colore' => post('colore'),
            'completato' => post('completato') ?: null,
            'is_fatturabile' => post('is_fatturabile') ?: null,
            'impegnato' => post('impegnato') ?: null,
        ], ['id' => $id_record]);

        $dbo->update('or_statiordine_lang', [
            'name' => $id_stato ? $id_stato->name : post('descrizione'),
        ], ['id_record' => $id_record, 'id_lang' => setting('Lingua')]);

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
        $id_stato= (new Stato())->getByName(post('descrizione'))->id_record;
        if ($id_stato) {
            flash()->error(tr('Stato ordine già esistente.'));
        } else {
            $dbo->query('INSERT INTO `or_statiordine` (icona, colore, completato, is_fatturabile, impegnato) VALUES ('.prepare($icona).', '.prepare($colore).','.prepare($completato).', '.prepare($is_fatturabile).', '.prepare($impegnato).' )');
            $id_record = $dbo->lastInsertedID();
            $dbo->query('INSERT INTO `or_statiordine_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(setting('Lingua')).')');
            flash()->info(tr('Nuovo stato ordine aggiunto.'));
        }

        break;

    case 'delete':
        // scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato negli ordini
        if (count($dbo->fetchArray('SELECT `id` FROM `or_statiordine` WHERE `id`='.prepare($id_record))) > 0) {
            $query = 'UPDATE `or_statiordine` SET `deleted_at` = NOW() WHERE `can_delete` = 1 AND `id`='.prepare($id_record);
        } else {
            $query = 'DELETE FROM `or_statiordine` WHERE `can_delete` = 1 AND `id`='.prepare($id_record);
        }

        $dbo->query($query);

        flash()->info(tr('Stato ordine eliminato.'));

        break;
}
