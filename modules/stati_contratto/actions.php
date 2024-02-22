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

use Modules\Contratti\Stato;

switch (post('op')) {
    case 'update':
        $id_stato_old = (new Stato())->getByName($record['name'])->id_record;
        $id_stato = (new Stato())->getByName(post('descrizione'))->id_record;

        if ($id_stato) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato dei contratti.'));
        } else {
            $dbo->update('co_staticontratti', [
                'icona' => post('icona'),
                'colore' => post('colore'),
                'is_completato' => post('is_completato') ?: null,
                'is_fatturabile' => post('is_fatturabile') ?: null,
                'is_pianificabile' => post('is_pianificabile') ?: null,
            ], ['id' => $id_stato_old]);

            $dbo->update('co_staticontratti_lang', [
                'name' => post('descrizione'),
            ], ['id_record' => $id_stato_old]);

            flash()->info(tr('Informazioni salvate correttamente.'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');
        $icona = post('icona');
        $colore = post('colore');
        $is_completato = post('is_completato') ?: null;
        $is_fatturabile = post('is_fatturabile') ?: null;
        $is_pianificabile = post('is_pianificabile') ?: null;

        // controlla descrizione che non sia duplicata
        if ((new Stato())->getByName($descrizione)->id_record) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato dei contratti.'));
        } else {
            $dbo->query('INSERT INTO `co_staticontratti` (`icona`, `colore`, `is_completato`, `is_fatturabile`, `is_pianificabile`) VALUES ('.prepare($icona).', '.prepare($colore).', '.prepare($is_completato).', '.prepare($is_fatturabile).', '.prepare($is_pianificabile).' )');
            $id_record = $dbo->lastInsertedID();
            $dbo->query('INSERT INTO `co_staticontratti_lang` (`name`, `id_record`, `id_lang`) VALUES ('.prepare($descrizione).', '.prepare($id_record).', '.prepare(setting('Lingua')).' )');

            flash()->info(tr('Nuovo stato contratto aggiunto.'));
        }

        break;

    case 'delete':
        // scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato nei contratti
        if (count($dbo->fetchArray('SELECT `id` FROM `co_contratti` WHERE `idstato`='.prepare($id_record))) > 0) {
            $query = 'UPDATE `co_staticontratti` SET `deleted_at` = NOW() WHERE `can_delete` = 1 AND `id`='.prepare($id_record);
        } else {
            $query = 'DELETE FROM `co_staticontratti` WHERE `can_delete` = 1 AND `id`='.prepare($id_record);
        }

        $dbo->query($query);

        flash()->info(tr('Questo stato dei contratti è stato correttamente eliminato.'));

        break;
}
