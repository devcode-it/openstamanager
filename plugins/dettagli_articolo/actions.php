<?php

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
        $fornitore->prezzo_acquisto = post('prezzo_acquisto');
        $fornitore->qta_minima = post('qta_minima');
        $fornitore->giorni_consegna = post('giorni_consegna');

        $fornitore->save();

        flash()->info(tr('Informazioni salvate correttamente!'));
        break;

    case 'update_prezzi':
        // Informazioni di base
        $id_articolo = filter('id_articolo');
        $id_anagrafica = filter('id_anagrafica');
        $direzione = filter('direzione') == 'uscita' ? 'uscita' : 'entrata';

        $articolo = Articolo::find($id_articolo);
        $anagrafica = Anagrafica::find($id_anagrafica);

        $modifica_prezzi = filter('modifica_prezzi');
        if (empty($modifica_prezzi)) {
            return;
        }

        // Salvataggio del prezzo predefinito
        $prezzo_unitario = filter('prezzo_unitario_fisso');
        $dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
            ->first();
        if (empty($dettaglio_predefinito)) {
            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $direzione);
        }
        $dettaglio_predefinito->setPrezzoUnitario($prezzo_unitario);
        $dettaglio_predefinito->save();

        // Salvataggio dei prezzi variabili
        $prezzo_fisso = filter('prezzo_fisso');
        $dettagli = DettaglioPrezzo::dettagli($id_articolo, $id_anagrafica, $direzione);
        if (empty($prezzo_fisso)) {
            $prezzi_unitari = (array) filter('prezzo_unitario');
            $minimi = filter('minimo');
            $massimi = filter('massimo');

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
