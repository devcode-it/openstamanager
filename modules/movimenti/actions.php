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

use Modules\Articoli\Articolo;

switch (post('op')) {
    case 'add':
        $articolo = Articolo::find(post('idarticolo'));
        $tipo_movimento = post('tipo_movimento');
        $descrizione = post('movimento');
        $data = post('data');
        $qta = post('qta');

        $idsede_partenza = post('idsede_partenza');
        $idsede_destinazione = post('idsede_destinazione');

        if ($tipo_movimento == 'carico' || $tipo_movimento == 'scarico') {
            if ($tipo_movimento == 'carico') {
                $id_sede_azienda = $idsede_destinazione;
                $id_sede_controparte = 0;
            } elseif ($tipo_movimento == 'scarico') {
                $id_sede_azienda = $idsede_partenza;
                $id_sede_controparte = 0;

                $qta = -$qta;
            }

            // Registrazione del movimento con variazione della quantità
            $articolo->movimenta($qta, $descrizione, $data, 1, [
                'idsede' => $id_sede_azienda,
            ]);
        } elseif ($tipo_movimento == 'spostamento') {
            // Registrazione del movimento verso la sede di destinazione
            $articolo->registra($qta, $descrizione, $data, 1, [
                'idsede' => $idsede_destinazione,
            ]);

            // Registrazione del movimento dalla sede di origine
            $articolo->registra(-$qta, $descrizione, $data, 1, [
                'idsede' => $idsede_partenza,
            ]);
        }

        break;
}
