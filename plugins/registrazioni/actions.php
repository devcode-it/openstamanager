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

use Modules\Fatture\Components\Articolo as RigaArticolo;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Components\Sconto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Movimenti as GestoreMovimenti;

$operazione = filter('op');

switch ($operazione) {
    case 'change-conto':
        $conti = (array) post('idconto');

        foreach ($conti as $id_riga => $conto) {
            $riga = RigaArticolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            // Modifica descrizione fornitore
            $riga->id_conto = $conto;
            $riga->save();
        }

        $fattura = Fattura::find($id_record);

        if ($fattura->stato->getTranslation('title') != 'Bozza') {
            $fattura->gestoreMovimenti = new GestoreMovimenti($fattura);
            $fattura->gestoreMovimenti->registra();
        }

        flash()->info(tr('Salvataggio completato!'));

        break;
}
