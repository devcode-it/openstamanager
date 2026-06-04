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
    case 'add':
        $dbo->query('INSERT INTO `ns_note_spese` (`numero`, `data`, `id_anagrafica`, `oggetto`, `stato`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, NOW(), NOW())', [
            post('numero'),
            post('data'),
            post('id_anagrafica') ?: null,
            post('oggetto'),
            'bozza',
        ]);

        $id_record = $dbo->lastInsertedID();
        flash()->info(tr('Nuova nota spese aggiunta.'));

        break;

    case 'update':
        $dbo->query('UPDATE `ns_note_spese` SET `numero` = ?, `data` = ?, `id_anagrafica` = ?, `oggetto` = ?, `stato` = ?, `note` = ?, `updated_at` = NOW() WHERE `id` = ?', [
            post('numero'),
            post('data'),
            post('id_anagrafica') ?: null,
            post('oggetto'),
            post('stato'),
            post('note', true),
            $id_record,
        ]);

        flash()->info(tr('Informazioni salvate correttamente.'));

        break;

    case 'add_riga':
        $dbo->query('INSERT INTO `ns_righe_note_spese` (`id_nota_spesa`, `data`, `categoria`, `descrizione`, `importo`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, NOW(), NOW())', [
            $id_record,
            post('data_riga'),
            post('categoria'),
            post('descrizione_riga'),
            post('importo'),
        ]);

        flash()->info(tr('Riga nota spese aggiunta.'));

        break;

    case 'update_riga':
        $dbo->query('UPDATE `ns_righe_note_spese` SET `data` = ?, `categoria` = ?, `descrizione` = ?, `importo` = ?, `updated_at` = NOW() WHERE `id` = ? AND `id_nota_spesa` = ?', [
            post('data_riga'),
            post('categoria'),
            post('descrizione_riga'),
            post('importo'),
            post('id_riga'),
            $id_record,
        ]);

        flash()->info(tr('Riga nota spese aggiornata.'));

        break;

    case 'delete_riga':
        $dbo->query('DELETE FROM `ns_righe_note_spese` WHERE `id` = ? AND `id_nota_spesa` = ?', [
            post('id_riga'),
            $id_record,
        ]);

        flash()->info(tr('Riga nota spese eliminata.'));

        break;

    case 'delete':
        $dbo->query('DELETE FROM `ns_note_spese` WHERE `id` = ?', [$id_record]);
        flash()->info(tr('Nota spese eliminata.'));

        break;
}
