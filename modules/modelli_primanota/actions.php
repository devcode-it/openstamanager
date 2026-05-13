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
        $id_mastrino = get_new_id_mastrino('co_movimenti_modelli');
        $descrizione = post('descrizione');
        $nome = post('nome');

        for ($i = 0; $i < sizeof(post('id_conto')); ++$i) {
            $id_conto = post('id_conto')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            if (!empty($id_conto)) {
                if (!empty($dare)) {
                    $totale = $dare;
                } else {
                    $totale = -$avere;
                }
                $query = 'INSERT INTO co_movimenti_modelli(id_mastrino, nome, descrizione, id_conto, totale) VALUES (:id_mastrino, :nome, :descrizione, :id_conto, :totale)';
                $params = [
                    ':id_mastrino' => $id_mastrino,
                    ':nome' => $nome,
                    ':descrizione' => $descrizione,
                    ':id_conto' => $id_conto,
                    ':totale' => $totale,
                ];
                if ($dbo->query($query, $params)) {
                    $id_record = $id_mastrino;
                }
            }
        }

        break;

    case 'editriga':
        $id_mastrino = post('id_mastrino');
        $descrizione = post('descrizione');
        $nome = post('nome');

        // Eliminazione prima nota
        $dbo->delete('co_movimenti_modelli', ['id_mastrino' => $id_mastrino]);

        for ($i = 0; $i < sizeof(post('id_conto')); ++$i) {
            $id_conto = post('id_conto')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            if (!empty($id_conto)) {
                if (!empty($dare)) {
                    $totale = $dare;
                } else {
                    $totale = -$avere;
                }
                $query = 'INSERT INTO co_movimenti_modelli(id_mastrino, nome, descrizione, id_conto, totale) VALUES (:id_mastrino, :nome, :descrizione, :id_conto, :totale)';
                $params = [
                    ':id_mastrino' => $id_mastrino,
                    ':nome' => $nome,
                    ':descrizione' => $descrizione,
                    ':id_conto' => $id_conto,
                    ':totale' => $totale,
                ];
                if ($dbo->query($query, $params)) {
                    $id_record = $id_mastrino;
                }
            }
        }

        break;

    case 'delete':
        $id_mastrino = post('id_mastrino');

        if (!empty($id_mastrino)) {
            // Eliminazione prima nota
            $dbo->delete('co_movimenti_modelli', ['id_mastrino' => $id_mastrino]);

            flash()->info(tr('Movimento eliminato!'));
        }

        break;
}
