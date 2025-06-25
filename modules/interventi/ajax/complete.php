<?php

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Interventi\Intervento;
use Modules\Preventivi\Preventivo;

include_once __DIR__.'/../../core.php';

$id_anagrafica = get('id_anagrafica');
$op = get('op');
$numero_documenti = 5;

switch ($op) {
    case 'dettagli':
        // Recupero informazioni cliente
        $anagrafica = Anagrafica::find($id_anagrafica);

        echo '
        <div class="alert alert-info mb-3">
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-2"><i class="fa fa-user"></i> '.tr('Informazioni cliente').'</h5>
                    <p class="mb-0"><strong>'.tr('Ragione sociale').':</strong> '.$anagrafica->ragione_sociale.'</p>
                    <p class="mb-0"><strong>'.tr('Partita IVA').':</strong> '.(!empty($anagrafica->piva) ? $anagrafica->piva : tr('Non specificata')).'</p>
                    <p class="mb-0"><strong>'.tr('Codice fiscale').':</strong> '.(!empty($anagrafica->codice_fiscale) ? $anagrafica->codice_fiscale : tr('Non specificato')).'</p>
                    <p class="mb-0"><strong>'.tr('Telefono').':</strong> '.(!empty($anagrafica->telefono) ? $anagrafica->telefono : tr('Non specificato')).'</p>
                </div>
            </div>
        </div>

        <div class="row">';

        // Informazioni sui contratti
        $modulo_contratti = Module::where('name', 'Contratti')->first();
        if ($modulo_contratti->permission != '-') {
            // Contratti attivi per l'anagrafica
            $contratti = Contratto::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();

            echo '
            <div class="col-md-6 mb-3">
                <div class="alert alert-light">
                    <h6><i class="fa fa-file-text-o"></i> '.tr('Ultimi _NUM_ Contratti', ['_NUM_' => $numero_documenti]).'</h6>
                    <ul class="list-unstyled">';
            if (!$contratti->isEmpty()) {
                foreach ($contratti as $contratto) {
                    echo '
                        <li class="mb-1"><i class="fa fa-angle-right"></i> '.Modules::link('Contratti', $contratto->id, $contratto->getReference().' ['.$contratto->stato->getTranslation('title').']: '.dateFormat($contratto->data_accettazione).' - '.dateFormat($contratto->data_conclusione)).'</li>';
                }
            } else {
                echo '
                        <li>'.tr('Nessun contratto attivo per questo cliente').'</li>';
            }
            echo '
                    </ul>
                </div>
            </div>';
        }

        // Informazioni sui preventivi
        $modulo_preventivi = Module::where('name', 'Preventivi')->first();
        if ($modulo_preventivi->permission != '-') {
            // Preventivi attivi
            $preventivi = Preventivo::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();
            echo '
            <div class="col-md-6 mb-3">
                <div class="alert alert-light">
                    <h6><i class="fa fa-file-o"></i> '.tr('Ultimi _NUM_ Preventivi', ['_NUM_' => $numero_documenti]).'</h6>
                    <ul class="list-unstyled">';
            if (!$preventivi->isEmpty()) {
                foreach ($preventivi as $preventivo) {
                    echo '
                        <li class="mb-1"><i class="fa fa-angle-right"></i> '.Modules::link('Preventivi', $preventivo->id, $preventivo->getReference().' ['.$preventivo->stato->getTranslation('title').']').'</li>';
                }
            } else {
                echo '
                        <li>'.tr('Nessun preventivo attivo per questo cliente').'</li>';
            }
            echo '
                    </ul>
                </div>
            </div>';
        }

        // Informazioni sulle attività
        $modulo_interventi = Module::where('name', 'Interventi')->first();
        if ($modulo_interventi->permission != '-') {
            // Attività recenti
            $interventi = Intervento::where('idanagrafica', '=', $id_anagrafica)
                ->latest()->take($numero_documenti)->get();
            echo '
            <div class="col-md-6 mb-3">
                <div class="alert alert-light">
                    <h6><i class="fa fa-wrench"></i> '.tr('Ultime _NUM_ Attività', ['_NUM_' => $numero_documenti]).'</h6>
                    <ul class="list-unstyled">';
            if (!$interventi->isEmpty()) {
                foreach ($interventi as $intervento) {
                    echo '
                        <li class="mb-1"><i class="fa fa-angle-right"></i> '.Modules::link('Interventi', $intervento->id, $intervento->getReference().' ['.$intervento->stato->getTranslation('title').']').'</li>';
                }
            } else {
                echo '
                        <li>'.tr('Nessun intervento per questo cliente').'</li>';
            }
            echo '
                    </ul>
                </div>
            </div>';
        }

        // Informazioni sulle fatture
        $modulo_fatture_vendita = Module::where('name', 'Fatture di vendita')->first();
        if ($modulo_fatture_vendita->permission != '-') {
            // Fatture attive
            $fatture = Fattura::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $id_bozza = Stato::where('name', 'Bozza')->first()->id;
                    $id_parz_pagato = Stato::where('name', 'Parzialmente pagato')->first()->id;
                    $query->whereIn('id', [$id_bozza, $id_parz_pagato]);
                })
                ->latest()->take($numero_documenti)->get();
            echo '
            <div class="col-md-6 mb-3">
                <div class="alert alert-light">
                    <h6><i class="fa fa-file-pdf-o"></i> '.tr('Ultime _NUM_ Fatture', ['_NUM_' => $numero_documenti]).'</h6>
                    <ul class="list-unstyled">';
            if (!$fatture->isEmpty()) {
                foreach ($fatture as $fattura) {
                    $scadenze = $fattura->scadenze;
                    $da_pagare = $scadenze->sum('da_pagare') - $scadenze->sum('pagato');
                    echo '
                        <li class="mb-1"><i class="fa fa-angle-right"></i> '.Modules::link('Fatture di vendita', $fattura->id, $fattura->getReference().': '.moneyFormat($da_pagare)).'</li>';
                }
            } else {
                echo '
                        <li>'.tr('Nessuna fattura attiva per questo cliente').'</li>';
            }
            echo '
                    </ul>
                </div>
            </div>';
        }

        // Note dell'anagrafica
        $note_anagrafica = $anagrafica->note;
        if (!empty($note_anagrafica)) {
            echo '
            <div class="col-md-12">
                <div class="alert alert-warning">
                    <h6><i class="fa fa-sticky-note-o"></i> '.tr('Note interne sul cliente').'</h6>
                    <p class="mb-0">'.(!empty($note_anagrafica) ? $note_anagrafica : tr('Nessuna nota interna per questo cliente')).'</p>
                </div>
            </div>';
        }

        echo '
        </div>';

        break;
}
