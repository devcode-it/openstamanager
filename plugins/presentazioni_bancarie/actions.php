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
        $scadenze_originali = Scadenza::with('documento')->whereIn('id', $id_scadenze)->get();
        if ($scadenze_originali->isEmpty()) {
            echo json_encode([
                'files' => [],
                'scadenze' => [],
            ]);
        }

        // Filtro per note di credito e gestione storno
        $scadenze = collect();
        $scadenze_da_escludere = collect();
        $scadenze_modificate = collect(); // Mappa id_scadenza => scadenza_modificata
        $note_credito_escluse = collect();
        $fatture_stornate = collect();
        $storni_parziali = collect(); // Per tracciare gli storni parziali

        foreach ($scadenze_originali as $scadenza) {
            $documento = $scadenza->documento;

            // Se la scadenza non ha un documento associato, la includiamo
            if (empty($documento)) {
                $scadenze->push($scadenza);
                continue;
            }

            // Se è una nota di credito, non la consideriamo per l'esportazione
            if ($documento->isNota()) {
                $note_credito_escluse->push($documento);

                // Verifica se corrisponde all'importo della fattura originale
                $fattura_originale = $documento->getFatturaOriginale();
                if (!empty($fattura_originale)) {
                    $importo_nota = abs($documento->netto);
                    $importo_fattura_originale = abs($fattura_originale->netto);

                    // Se gli importi corrispondono (tolleranza di 1 centesimo), escludiamo anche la fattura originale
                    if (abs($importo_nota - $importo_fattura_originale) < 0.01) {
                        // Trova tutte le scadenze della fattura originale e le esclude
                        $scadenze_fattura_originale = $scadenze_originali->where('iddocumento', $fattura_originale->id);
                        foreach ($scadenze_fattura_originale as $scad_orig) {
                            $scadenze_da_escludere->push($scad_orig->id);
                        }
                        $fatture_stornate->push($fattura_originale);
                    } else {
                        // Importi diversi: storniamo il valore della nota di credito dalla fattura originale
                        $scadenze_fattura_originale = $scadenze_originali->where('iddocumento', $fattura_originale->id);
                        $importo_da_stornare = $importo_nota;

                        // Distribuiamo lo storno proporzionalmente tra le scadenze della fattura originale
                        $totale_scadenze_originale = $scadenze_fattura_originale->sum(function($s) {
                            return abs($s->da_pagare - $s->pagato);
                        });

                        if ($totale_scadenze_originale > 0) {
                            foreach ($scadenze_fattura_originale as $scad_orig) {
                                $importo_scadenza = abs($scad_orig->da_pagare - $scad_orig->pagato);
                                $percentuale = $importo_scadenza / $totale_scadenze_originale;
                                $storno_scadenza = $importo_da_stornare * $percentuale;

                                // Creiamo una copia della scadenza con l'importo modificato
                                $scadenza_modificata = clone $scad_orig;
                                $nuovo_da_pagare = $scad_orig->da_pagare - ($scad_orig->da_pagare > 0 ? $storno_scadenza : -$storno_scadenza);
                                $scadenza_modificata->da_pagare = $nuovo_da_pagare;

                                // Se dopo lo storno l'importo diventa zero o negativo, escludiamo la scadenza
                                if (abs($nuovo_da_pagare - $scad_orig->pagato) < 0.01) {
                                    $scadenze_da_escludere->push($scad_orig->id);
                                } else {
                                    // Salviamo la scadenza modificata
                                    $scadenze_modificate->put($scad_orig->id, $scadenza_modificata);
                                }
                            }

                            // Tracciamo lo storno parziale
                            $storni_parziali->push([
                                'nota_credito' => $documento,
                                'fattura_originale' => $fattura_originale,
                                'importo_stornato' => $importo_da_stornare
                            ]);
                        }
                    }
                }
                continue; // Non includiamo mai le note di credito nell'esportazione
            }

            // Se non è una nota di credito e non è da escludere, la includiamo (eventualmente modificata)
            if (!$scadenze_da_escludere->contains($scadenza->id)) {
                if ($scadenze_modificate->has($scadenza->id)) {
                    $scadenze->push($scadenze_modificate->get($scadenza->id));
                } else {
                    $scadenze->push($scadenza);
                }
            }
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
                $pos = $scadenze_documento->search(fn ($item, $key) => $item->id == $scadenza->id);

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
            $completato = $gestori_esportazione[$banca_azienda->id]->aggiungi($scadenza, $scadenza->id, strip_tags((string) $descrizione), $codice_sequenza[$scadenza->id]);

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
