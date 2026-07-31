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
        $nome = post('nome');
        $data = post('data');
        $id_nazione = post('id_nazione');
        $id_regione = post('id_regione');
        $is_recurring = post('is_recurring');
        $is_bank_holiday = post('is_bank_holiday');

        $exists = Models\Event::where('nome', $nome)->where('id', '!=', $id_record)->exists();
        if (!$exists) {
            Models\Event::find($id_record)->update([
                'nome' => $nome,
                'data' => $data,
                'id_nazione' => $id_nazione,
                'id_regione' => $id_regione,
                'is_recurring' => $is_recurring,
                'is_bank_holiday' => $is_bank_holiday,
            ]);

            flash()->info(tr('Salvataggio completato.'));
        } else {
            flash()->error(tr("E' già presente un _TYPE_ con lo stesso nome", [
                '_TYPE_' => 'evento',
            ]));
        }

        break;

    case 'add':
        $nome = post('nome');
        $data = post('data');
        $id_nazione = post('id_nazione');
        $exists = Models\Event::where('id_nazione', $id_nazione)
            ->where('nome', $nome)
            ->where('data', $data)
            ->exists();
        if (!$exists) {
            $event = Models\Event::create([
                'nome' => $nome,
                'data' => $data,
                'id_nazione' => $id_nazione,
            ]);
            $id_record = $event->id;

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome]);
            }

            flash()->info(tr('Aggiunto nuovo _TYPE_', [
                '_TYPE_' => 'evento',
            ]));
        } else {
            flash()->error(tr("E' già presente un _TYPE_ con lo stesso nome e nazione", [
                '_TYPE_' => 'evento',
            ]));
        }

        break;

    case 'delete':
        Models\Event::find($id_record)->delete();

        flash()->info(tr('_TYPE_ eliminato con successo.', [
            '_TYPE_' => 'Evento',
        ]));

        break;
}
