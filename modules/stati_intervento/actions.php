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
use Modules\Interventi\Stato;

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $stato_new = (new Stato())->getByField('name', $descrizione);

        if (!empty($stato_new) && $stato_new != $id_record){
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato attività.'));
        } else {
            $stato->codice = post('codice');
            $stato->colore = post('colore');
            $stato->is_completato = post('is_completato');
            $stato->is_fatturabile = post('is_fatturabile');
            $stato->notifica = post('notifica');
            $stato->notifica_cliente = post('notifica_cliente');
            $stato->notifica_tecnico_sessione = post('notifica_tecnico_sessione');
            $stato->notifica_tecnico_assegnato = post('notifica_tecnico_assegnato');
            $stato->id_email = post('email') ?: null;
            $stato->destinatari = post('destinatari');

            $stato->setTranslation('name', $descrizione);
            $stato->save();

            flash()->info(tr('Informazioni salvate correttamente.'));
        }

        break;

    case 'add':
        $codice = post('codice');
        $descrizione = post('descrizione');
        $colore = post('colore');

        $stato_new = Stato::find((new Stato())->getByField('name', $descrizione));

        if ($stato_new) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro stato attività.'));
        } else {
            $stato = Stato::build($codice, $colore);
            $id_record= $dbo->lastInsertedID();
            $stato->setTranslation('name', $descrizione);
            $stato->save();

            flash()->info(tr('Nuovo stato attività aggiunto.'));
        }
        break;

    case 'delete':
        // scelgo se settare come eliminato o cancellare direttamente la riga se non è stato utilizzato negli interventi
        if (count($dbo->fetchArray('SELECT `id` FROM `in_interventi` WHERE `idstatointervento`='.prepare($id_record))) > 0) {
            $query = 'UPDATE `in_statiintervento` SET `deleted_at` = NOW() WHERE `id`='.prepare($id_record).' AND `can_delete`=1';
        } else {
            $query = 'DELETE FROM `in_statiintervento`  WHERE `id`='.prepare($id_record).' AND `can_delete`=1';
        }

        $dbo->query($query);

        flash()->info(tr('Stato attività eliminato.'));

        break;
}
