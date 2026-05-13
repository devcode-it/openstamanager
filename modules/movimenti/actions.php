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
        $articolo = Articolo::find(post('id_articolo'));
        $tipo_movimento = post('tipo_movimento');
        $descrizione = post('movimento');
        $data = post('data');
        $qta = post('qta');

        $id_sede_partenza = post('id_sede_partenza');
        $id_sede_destinazione = post('id_sede_destinazione');

        if ($tipo_movimento == 'carico' || $tipo_movimento == 'scarico') {
            if ($tipo_movimento == 'carico') {
                $id_sede_azienda = $id_sede_destinazione;
                $id_sede_controparte = 0;
            } elseif ($tipo_movimento == 'scarico') {
                $id_sede_azienda = $id_sede_partenza;
                $id_sede_controparte = 0;

                $qta = -$qta;
            }

            // Registrazione del movimento con variazione della quantità
            $articolo->movimenta($qta, $descrizione, $data, 1, [
                'id_sede' => $id_sede_azienda,
            ]);
        } elseif ($tipo_movimento == 'spostamento') {
            // Registrazione del movimento verso la sede di destinazione
            $articolo->registra($qta, $descrizione, $data, 1, [
                'id_sede' => $id_sede_destinazione,
            ]);

            // Registrazione del movimento dalla sede di origine
            $articolo->registra(-$qta, $descrizione, $data, 1, [
                'id_sede' => $id_sede_partenza,
            ]);
        }

        break;

    case 'salva_inventario':
        $id_sede = post('id_sede');
        $data = post('data');
        $righe = post('righe');

        if (empty($righe) || !is_array($righe)) {
            echo json_encode(['success' => false, 'message' => tr('Nessuna riga da salvare')]);
            break;
        }

        try {
            foreach ($righe as $riga) {
                $id_articolo = $riga['id_articolo'];
                $giacenza_attuale = floatval($riga['giacenza_attuale']);
                $nuova_giacenza = floatval($riga['nuova_giacenza']);
                $ubicazione = $riga['ubicazione'] ?: '';

                // Calcola la differenza
                $differenza = $nuova_giacenza - $giacenza_attuale;

                if ($differenza != 0) {
                    $articolo = Articolo::find($id_articolo);

                    if ($articolo) {
                        $descrizione = tr('Inventario - Rettifica giacenza (Q.r. _QTA_)', [
                            '_QTA_' => $nuova_giacenza,
                        ]);

                        // Registra il movimento
                        $articolo->movimenta($differenza, $descrizione, $data, 1, [
                            'id_sede' => $id_sede,
                        ]);

                        $articolo->ubicazione = $ubicazione;
                        $articolo->save();
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => tr('Inventario salvato correttamente')]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => tr('Errore durante il salvataggio: ').$e->getMessage()]);
        }

        break;
}
