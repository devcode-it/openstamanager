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
        $descrizione = post('descrizione');
        $stato_new = Stato::find((new Stato())->getByField('title', $descrizione));

        if ($stato_new) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato dei contratti.'));
        } else {
            $stato->icona = post('icona');
            $stato->colore = post('colore');
            $stato->is_completato = post('is_completato');
            $stato->is_fatturabile = post('is_fatturabile');
            $stato->is_pianificabile = post('is_pianificabile');
            $stato->setTranslation('title', $descrizione);
            $stato->save();

            flash()->info(tr('Informazioni salvate correttamente.'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');
        $icona = post('icona');
        $colore = post('colore');
        $is_completato = post('is_completato');
        $is_fatturabile = post('is_fatturabile');
        $is_pianificabile = post('is_pianificabile');

        $stato_new = Stato::find((new Stato())->getByField('title', $descrizione));

        if ($stato_new) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato dei contratti.'));
        } else {
            $stato = Stato::build($icona, $colore, $is_completato, $is_fatturabile, $is_pianificabile);
            $id_record = $dbo->lastInsertedID();
            $stato->setTranslation('title', $descrizione);
            $stato->save();

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
