<?php

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;
use Plugins\DettagliArticolo\DettaglioFornitore;

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

    case 'delete_fornitore':
        $id_riga = post('id_riga');

        $fornitore = DettaglioFornitore::find($id_riga);
        $fornitore->delete();

        flash()->info(tr('Relazione articolo-fornitore rimossa correttamente!'));
        break;
}
