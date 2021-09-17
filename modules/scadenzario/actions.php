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

use Modules\Scadenzario\Gruppo;
use Modules\Scadenzario\Scadenza;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add':
        // Creazione nuovo Gruppo Scadenze
        $descrizione = post('descrizione');
        $gruppo = Gruppo::build($descrizione);

        // Creazione Scadenza associata al gruppo
        $data = post('data');
        $tipo = post('tipo');
        $da_pagare = post('da_pagare');
        $scadenza = Scadenza::build($gruppo, $da_pagare, $data, $tipo);

        $id_record = $scadenza->id;

        flash()->info(tr('Scadenza inserita!'));
        break;

    case 'update':
        $tipo = post('tipo');
        $descrizione = post('descrizione');
        $iddocumento = post('iddocumento') ?: 0;

        $totale_pagato = 0;
        $id_scadenza_non_completa = null;
        foreach (post('da_pagare') as $id => $da_pagare) {
            $pagato = post('pagato')[$id];
            $scadenza = post('scadenza')[$id];
            $data_concordata = post('data_concordata')[$id];

            $pagato = floatval($pagato);
            $da_pagare = floatval($da_pagare);

            if (!empty($iddocumento)) {
                $id_tipo = $dbo->selectOne('co_documenti', 'idtipodocumento', ['id' => $iddocumento])['idtipodocumento'];
                $tipo_documento = $dbo->selectOne('co_tipidocumento', '*', ['id' => $id_tipo]);

                if ($tipo_documento['dir'] == 'uscita') {
                    if ($pagato > 0) {
                        $pagato = -$pagato;
                    }
                    if ($da_pagare > 0) {
                        $da_pagare = -$da_pagare;
                    }
                } else {
                    if ($pagato < 0) {
                        $pagato = -$pagato;
                    }
                    if ($da_pagare < 0) {
                        $da_pagare = -$da_pagare;
                    }
                }

                if (!empty($tipo_documento['reversed'])) {
                    $pagato = -$pagato;
                    $da_pagare = -$da_pagare;
                }
            }

            $totale_pagato = sum($totale_pagato, $pagato);

            $id_scadenza = post('id_scadenza')[$id];
            if (!empty($id_scadenza)) {
                $database->update('co_scadenze', [
                    'descrizione' => $descrizione,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $scadenza,
                    'data_concordata' => $data_concordata,
                    'note' => post('note'),
                    'distinta' => post('distinta') ?: null,
                ], ['id' => $id_scadenza]);

                if ($da_pagare == 0) {
                    $database->delete('co_scadenze', ['id' => $id]);
                }
            } else {
                $database->insert('co_scadenze', [
                    'descrizione' => $descrizione,
                    'tipo' => $tipo,
                    'iddocumento' => $iddocumento,
                    'da_pagare' => $da_pagare,
                    'pagato' => $pagato,
                    'scadenza' => $scadenza,
                    'data_concordata' => $data_concordata,
                    'data_emissione' => date('Y-m-d'),
                    'note' => post('note'),
                ]);

                $id_scadenza = $database->lastInsertedID();
            }

            if ($pagato != $da_pagare) {
                $id_scadenza_non_completa = $id_scadenza;
            }
        }

        flash()->info(tr('Scadenze aggiornate!'));

        break;

    case 'delete':
        $gruppo->delete();

        flash()->info(tr('Scadenza eliminata!'));
        break;
}
