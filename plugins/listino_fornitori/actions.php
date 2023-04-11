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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Plugins\ListinoFornitori\DettaglioFornitore;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update_fornitore':
        $id_articolo = filter('id_articolo');
        $articolo = Articolo::find($id_articolo);

        $id_anagrafica = filter('id_anagrafica');
        $precedente = DettaglioFornitore::where('id_articolo', $id_record)
            ->where('id_fornitore', $id_anagrafica)
            ->first();

        if (empty($precedente)) {
            $anagrafica = Anagrafica::find($id_anagrafica);

            $fornitore = DettaglioFornitore::build($anagrafica, $articolo);
        } else {
            $fornitore = $precedente;
        }

        $fornitore->codice_fornitore = post('codice_fornitore');
        $fornitore->barcode_fornitore = post('barcode_fornitore');
        $fornitore->descrizione = post('descrizione');
        $fornitore->qta_minima = post('qta_minima');
        $fornitore->giorni_consegna = post('giorni_consegna');

        $fornitore->save();

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;

    case 'delete_fornitore':
        $id_riga = post('id_riga');

        $fornitore = DettaglioFornitore::find($id_riga);
        $fornitore->delete();

        flash()->info(tr('Relazione articolo-fornitore rimossa correttamente!'));
        break;

    case 'update_prezzi':
        require base_dir().'/plugins/listino_clienti/actions.php';
        break;

    case 'update_prezzi':
        require base_dir().'/plugins/listino_clienti/actions.php';

        $id_articolo = filter('id_articolo');
        $articolo = Articolo::find($id_articolo);

        $id_anagrafica = filter('id_anagrafica');
        $precedente = DettaglioFornitore::where('id_articolo', $id_record)
            ->where('id_fornitore', $id_anagrafica)
            ->first();

        if (empty($precedente)) {
            $anagrafica = Anagrafica::find($id_anagrafica);

            $fornitore = DettaglioFornitore::build($anagrafica, $articolo);
        } else {
            $fornitore = $precedente;
        }

        $fornitore->codice_fornitore = post('codice_fornitore');
        $fornitore->barcode_fornitore = post('barcode_fornitore');
        $fornitore->descrizione = post('descrizione');
        $fornitore->qta_minima = post('qta_minima');
        $fornitore->giorni_consegna = post('giorni_consegna');

        $fornitore->save();

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;
}
