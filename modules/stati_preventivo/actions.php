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
        $dbo->update('co_statipreventivi', [
            'icona' => post('icona'),
            'colore' => post('colore'),
            'is_completato' => post('is_completato') ?: null,
            'is_fatturabile' => post('is_fatturabile') ?: null,
            'is_pianificabile' => post('is_pianificabile') ?: null,
            'is_revisionabile' => post('is_revisionabile') ?: null,
        ], ['id' => $id_record]);

        $dbo->update('co_statipreventivi_lang', [
            'name' => post('descrizione'),
        ], ['id_record' => $id_record]);

        flash()->info(tr('Informazioni salvate correttamente.'));

        break;

    case 'add':
        $descrizione = post('descrizione');
        $icona = post('icona');
        $colore = post('colore');
        $is_completato = post('is_completato') ?: null;
        $is_fatturabile = post('is_fatturabile') ?: null;
        $is_pianificabile = post('is_pianificabile') ?: null;

        // controlla descrizione che non sia duplicata
        if (count($dbo->fetchArray('SELECT `name` FROM `co_statipreventivi_lang` WHERE `name`='.prepare($descrizione))) > 0) {
            flash()->error(tr('Esiste già uno stato dei preventivi con questo nome.'));
        } else {
            $dbo->query('INSERT INTO `co_statipreventivi` (icona, colore, is_completato, is_fatturabile, is_pianificabile) VALUES ('.prepare($icona).', '.prepare($colore).', '.prepare($is_completato).', '.prepare($is_fatturabile).', '.prepare($is_pianificabile).' )');
            $id_record = $dbo->lastInsertedID();
            $dbo->query('INSERT INTO `co_statipreventivi_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(setting('Lingua')).' )');
            flash()->info(tr('Nuovo stato dei preventivi aggiunto.'));
        }

        break;

    case 'delete':
        // scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato nei preventivi
        if (count($dbo->fetchArray('SELECT `id` FROM `co_preventivi` WHERE `idstato`='.prepare($id_record))) > 0) {
            $query = 'UPDATE `co_statipreventivi` SET `deleted_at` = NOW() WHERE `can_delete` = 1 AND `id`='.prepare($id_record);
        } else {
            $query = 'DELETE FROM `co_statipreventivi` WHERE `can_delete` = 1 AND `id`='.prepare($id_record);
        }

        $dbo->query($query);

        flash()->info(tr('Stato preventivo eliminato.'));

        break;
}
