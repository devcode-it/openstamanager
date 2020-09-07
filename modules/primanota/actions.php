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

use Modules\Fatture\Fattura;
use Modules\PrimaNota\Mastrino;
use Modules\PrimaNota\Movimento;
use Modules\Scadenzario\Scadenza;

switch (post('op')) {
    case 'add':
        $data = post('data');
        $descrizione = post('descrizione');
        $is_insoluto = post('is_insoluto');

        $mastrino = Mastrino::build($descrizione, $data, $is_insoluto, true);

        $conti = post('idconto');
        foreach ($conti as $i => $id_conto) {
            $id_scadenza = post('id_scadenza')[$i];
            $id_documento = post('id_documento')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            $scadenza = Scadenza::find($id_scadenza);
            $fattura = Fattura::find($id_documento);

            $movimento = Movimento::build($mastrino, $id_conto, $fattura, $scadenza);
            $movimento->setTotale($avere, $dare);
            $movimento->save();
        }

        $mastrino->aggiornaScadenzario();

        $id_record = $mastrino->id;

        flash()->info(tr('Movimento aggiunto in prima nota!'));

        // Creo il modello di prima nota
        if (!empty(post('crea_modello'))) {
            if (empty(post('idmastrino'))) {
                $idmastrino = get_new_idmastrino('co_movimenti_modelli');
            } else {
                $dbo->query('DELETE FROM co_movimenti_modelli WHERE idmastrino='.prepare(post('idmastrino')));
                $idmastrino = post('idmastrino');
            }

            foreach ($conti as $i => $id_conto) {
                $idconto = post('idconto')[$i];
                $query = 'INSERT INTO co_movimenti_modelli(idmastrino, nome, descrizione, idconto) VALUES('.prepare($idmastrino).', '.prepare($descrizione).', '.prepare($descrizione).', '.prepare($id_conto).')';
                $dbo->query($query);
            }
        }

        break;

    case 'update':
        $data = post('data');
        $descrizione = post('descrizione');

        $mastrino->descrizione = $descrizione;
        $mastrino->data = $data;

        $mastrino->cleanup();

        $conti = post('idconto');
        foreach ($conti as $i => $id_conto) {
            $id_scadenza = post('id_scadenza')[$i];
            $id_documento = post('id_documento')[$i];
            $dare = post('dare')[$i];
            $avere = post('avere')[$i];

            $scadenza = Scadenza::find($id_scadenza);
            $fattura = Fattura::find($id_documento);

            $movimento = Movimento::build($mastrino, $id_conto, $fattura, $scadenza);
            $movimento->setTotale($avere, $dare);
            $movimento->save();
        }

        $mastrino->aggiornaScadenzario();

        flash()->info(tr('Movimento modificato in prima nota!'));
        break;

    // eliminazione movimento prima nota
    case 'delete':
        $mastrino->delete();
        break;
}
