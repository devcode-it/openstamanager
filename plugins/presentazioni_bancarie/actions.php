<?php

use Carbon\Carbon;
use Modules\Scadenzario\Scadenza;
use Plugins\PresentazioniBancarie\Gestore;

include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'generate':
        $id_scadenze = filter('scadenze');
        $sequenze = (array) filter('sequenze');

        $codice_sequenza = [];

        foreach ($sequenze as $sequenza) {
            $id_scadenza = explode('-', $sequenza)[0];
            $codice = explode('-', $sequenza)[1];

            $codice_sequenza[$id_scadenza] = $codice;
        }

        $data = new Carbon();
        $azienda = Gestore::getAzienda();

        // Individuazione delle scadenze indicate
        $scadenze = Scadenza::with('documento')->whereIn('id', $id_scadenze)->get();
        if ($scadenze->isEmpty()) {
            echo json_encode([
                'files' => [],
                'scadenze' => [],
            ]);
        }

        // Iterazione tra le scadenze selezionate
        $scadenze_completate = [];
        $gestori_esportazione = [];

        foreach ($scadenze as $key => $scadenza) {
            $documento = $scadenza->documento;
            $descrizione = $scadenza->descrizione;
            if (!empty($documento)) {
                $descrizione = 'Fattura num. '.$documento->numero_esterno ?: $documento->numero;
                // Individuazione altre scadenze del documento
                $scadenze_documento = $documento->scadenze->sortBy('scadenza');
                $pos = $scadenze_documento->search(function ($item, $key) use ($scadenza) {
                    return $item->id == $scadenza->id;
                });

                // Generazione della descrizione del pagamento
                $descrizione .= tr(' pag _NUM_/_TOT_', [
                    '_NUM_' => $pos + 1,
                    '_TOT_' => $scadenze_documento->count(),
                ]);
            }

            // Controllo sulla banca aziendale collegata alla scadenza
            $banca_azienda = Gestore::getBancaAzienda($scadenza);
            if (!isset($gestori_esportazione[$banca_azienda->id])) {
                $gestori_esportazione[$banca_azienda->id] = new Gestore($azienda, $banca_azienda);
            }

            // Delegazione per la gestione
            $completato = $gestori_esportazione[$banca_azienda->id]->aggiungi($scadenza, $scadenza->id, $descrizione, $codice_sequenza[$scadenza->id]);

            // Salvataggio dell'esportazione
            if ($completato) {
                $scadenza->presentazioni_exported_at = $data;
                $scadenza->save();

                $scadenze_completate[] = $scadenza->id;
            }
        }

        /**
         * Salvataggio dei file nei diversi formati.
         */
        $files = [];
        foreach ($gestori_esportazione as $gestore) {
            $files = array_merge($files, $gestore->esporta($plugin->upload_directory));
        }

        echo json_encode([
            'files' => $files,
            'scadenze' => $scadenze_completate,
        ]);

        break;
}
