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
        $descrizione = post('descrizione');
        $stato_new = (new Stato())->getByField('name', $descrizione);

        if (!empty($stato_new) && $stato_new != $id_record) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato attività.'));
        } else {
            $stato->icona = post('icona');
            $stato->colore = post('colore');
            $stato->completato = post('completato');
            $stato->is_fatturabile = post('is_fatturabile');
            $stato->impegnato = post('impegnato');
            $stato->setTranslation('name', $descrizione);
            $stato->save();

            flash()->info(tr('Informazioni salvate correttamente.'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');
        $icona = post('icona');
        $colore = post('colore');
        $completato = post('completato');
        $is_fatturabile = post('is_fatturabile');
        $impegnato = post('impegnato');

        $stato_new = Stato::find((new Stato())->getByField('name', $descrizione));

        if ($stato_new) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato ordine.'));
        } else {
            $stato = Stato::build($icona, $colore, $completato, $is_fatturabile, $impegnato);
            $id_record = $dbo->lastInsertedID();
            $stato->setTranslation('name', $descrizione);
            $stato->save();
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
