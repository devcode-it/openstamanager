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

use Carbon\Carbon;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Components\Sconto;
use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;

switch (filter('op')) {
    case 'update':
        $riga = Articolo::find($id_record) ?: Riga::find($id_record);
        $riga = $riga ?: Sconto::find($id_record);
        $riga->codice_interno_cespite = post('codice_interno_cespite');
        $riga->is_smaltito = post('is_smaltito');
        $riga->save();

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'add_ammortamento':
        $id_conto = post('id_conto');
        $anni = (array) post('anno');
        $percentuale = (array) post('percentuale');

        // Recupero informazioni sulla riga del documento
        $riga = Articolo::find($id_record) ?: Riga::find($id_record);
        $id_conto_dare = $riga->idconto;
        $id_conto_avere = $id_conto;

        // Elimino i movimenti collegati agli ammortamenti precedenti
        $ammortamenti_precedenti = $dbo->fetchArray('SELECT * FROM `co_righe_ammortamenti` WHERE `id_riga` = '.prepare($id_record));
        foreach ($ammortamenti_precedenti as $ammortamento) {
            if (!empty($ammortamento['id_mastrino'])) {
                $mastrino_precedente = Mastrino::find($ammortamento['id_mastrino']);
                if (!empty($mastrino_precedente)) {
                    $mastrino_precedente->delete();
                }
            }
        }

        // Elimino le righe di ammortamento precedenti
        $dbo->query('DELETE FROM `co_righe_ammortamenti` WHERE `id_riga` = '.prepare($id_record));
        foreach ($anni as $i => $anno) {
            $perc = $percentuale[$i];

            if ($perc) {
                // Calcolo importo in base alla percentuale
                $importo = $riga->subtotale * $perc / 100;

                // Creazione mastrino
                $data = Carbon::createFromDate($anno, 12, 31);
                $descrizione = 'Ammortamento del '.$data->format('d/m/Y').' - '.$perc.'%';
                $mastrino = Mastrino::build($descrizione, $data, false, true);

                // Movimento in dare (conto della riga)
                $movimento_dare = Movimento::build($mastrino, $id_conto_dare);
                $movimento_dare->setTotale($importo, 0);
                $movimento_dare->save();

                // Movimento in avere (conto selezionato)
                $movimento_avere = Movimento::build($mastrino, $id_conto_avere);
                $movimento_avere->setTotale(0, $importo);
                $movimento_avere->save();

                // Salvataggio record ammortamento
                $dbo->query('INSERT INTO `co_righe_ammortamenti` (`id_riga`, `anno`, `id_conto`, `percentuale`, `id_mastrino`) VALUES ('.prepare($id_record).', '.prepare($anno).', '.prepare($id_conto).', '.prepare($perc).', '.prepare($movimento_dare->idmastrino).')');
            }
        }

        flash()->info(tr('Ammortamenti registrati con successo!'));

        break;
}
