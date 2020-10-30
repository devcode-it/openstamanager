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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Plugins\DettagliArticolo\DettaglioFornitore;
use Plugins\DettagliArticolo\DettaglioPrezzo;

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
            $fornitore = $precedente->replicate();
            $precedente->delete();
        }

        $fornitore->codice_fornitore = post('codice_fornitore');
        $fornitore->descrizione = post('descrizione');
        $fornitore->qta_minima = post('qta_minima');
        $fornitore->giorni_consegna = post('giorni_consegna');

        $fornitore->save();

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;

    case 'update_prezzi':
        // Informazioni di base
        $id_articolo = filter('id_articolo');
        $id_anagrafica = filter('id_anagrafica');
        $direzione = filter('dir') == 'uscita' ? 'uscita' : 'entrata';

        $articolo = Articolo::find($id_articolo);
        $anagrafica = Anagrafica::find($id_anagrafica);

        $modifica_prezzi = filter('modifica_prezzi');
        if (empty($modifica_prezzi)) {
            return;
        }

        // Salvataggio del prezzo predefinito
        $prezzo_unitario = filter('prezzo_unitario_fisso');
        $sconto = filter('sconto_fisso');
        $dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
            ->first();
        if (empty($dettaglio_predefinito)) {
            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
        }
        $dettaglio_predefinito->sconto_percentuale = $sconto;
        $dettaglio_predefinito->setPrezzoUnitario($prezzo_unitario);
        $dettaglio_predefinito->save();

        // Salvataggio dei prezzi variabili
        $prezzo_fisso = filter('prezzo_fisso');
        $dettagli = DettaglioPrezzo::dettagli($id_articolo, $id_anagrafica, $direzione);
        if (empty($prezzo_fisso)) {
            $prezzi_unitari = (array) filter('prezzo_unitario');
            $minimi = filter('minimo');
            $massimi = filter('massimo');
            $sconti = (array) filter('sconto');

            // Rimozione dei prezzi cancellati
            $registrati = filter('dettaglio');
            $dettagli = $dettagli->whereNotIn('id', $registrati)->delete();

            // Aggiornamento e creazione dei prezzi registrati
            foreach ($prezzi_unitari as $key => $prezzo_unitario) {
                if (isset($registrati[$key])) {
                    $dettaglio = DettaglioPrezzo::find($registrati[$key]);
                } else {
                    $dettaglio = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
                }

                $dettaglio->minimo = $minimi[$key];
                $dettaglio->massimo = $massimi[$key];
                $dettaglio->sconto_percentuale = $sconti[$key];
                $dettaglio->setPrezzoUnitario($prezzo_unitario);
                $dettaglio->save();
            }
        } else {
            $dettagli->delete();
        }

        break;

    case 'delete_fornitore':
        $id_riga = post('id_riga');

        $fornitore = DettaglioFornitore::find($id_riga);
        $fornitore->delete();

        flash()->info(tr('Relazione articolo-fornitore rimossa correttamente!'));
        break;
}
