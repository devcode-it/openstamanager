<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
        $idmastrino = get_new_idmastrino('co_movimenti_modelli');
        $descrizione = post('descrizione');
        $nome = post('nome');

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            if (!empty($idconto)) {
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($nome).', '.prepare($descrizione).', '.prepare($idconto).')';
                if ($dbo->query($query)) {
                    $id_record = $idmastrino;
                }
            }
        }

        break;

    case 'editriga':
        $idmastrino = post('idmastrino');
        $descrizione = post('descrizione');
        $nome = post('nome');

        // Eliminazione prima nota
        $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino));

        for ($i = 0; $i < sizeof(post('idconto')); ++$i) {
            $idconto = post('idconto')[$i];
            if (!empty($idconto)) {
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($nome).', '.prepare($descrizione).', '.prepare($idconto).')';
                if ($dbo->query($query)) {
                    $id_record = $idmastrino;
                }
            }
        }

        break;

    case 'delete':
        $idmastrino = post('idmastrino');

        if (!empty($idmastrino)) {
            // Eliminazione prima nota
            $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino));

            flash()->info(tr('Movimento eliminato!'));
        }

        break;
}
