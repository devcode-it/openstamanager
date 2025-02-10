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
        echo '
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
            <div class="col-md-6">
                <b>'.tr('Ultimi _NUM_ Contratti', ['_NUM_' => $numero_documenti]).':</b><ul>';
            if (!$contratti->isEmpty()) {
                foreach ($contratti as $contratto) {
                    echo '
                    <li>'.Modules::link('Contratti', $contratto->id, $contratto->getReference().' ['.$contratto->stato->getTranslation('title').']: '.dateFormat($contratto->data_accettazione).' - '.dateFormat($contratto->data_conclusione)).'</li>';
                }
            } else {
                echo '
                    <li>'.tr('Nessun contratto attivo per questo cliente').'</li>';
            }
            echo '
                </ul>
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
            <div class="col-md-6">
                <b>'.tr('Ultimi _NUM_ Preventivi', ['_NUM_' => $numero_documenti]).':</b><ul>';
            if (!$preventivi->isEmpty()) {
                foreach ($preventivi as $preventivo) {
                    echo '
                    <li>'.Modules::link('Preventivi', $preventivo->id, $preventivo->getReference().' ['.$preventivo->stato->getTranslation('title').']').'</li>';
                }
            } else {
                echo '
                    <li>'.tr('Nessun preventivo attivo per questo cliente').'</li>';
            }
            echo '
                </ul>
            </div>';
        }
        echo '
        </div>
        
        <div class="row">';

        // Informazioni sulle attività
        $modulo_interventi = Module::where('name', 'Interventi')->first();
        if ($modulo_interventi->permission != '-') {
            // Preventivi attivi
            $interventi = Intervento::where('idanagrafica', '=', $id_anagrafica)
                ->latest()->take($numero_documenti)->get();
            echo '
            <div class="col-md-6">
                <b>'.tr('Ultime _NUM_ Attività', ['_NUM_' => $numero_documenti]).':</b><ul>';
            if (!$interventi->isEmpty()) {
                foreach ($interventi as $intervento) {
                    echo '
                    <li>'.Modules::link('Interventi', $intervento->id, $intervento->getReference().' ['.$intervento->stato->getTranslation('title').']').'</li>';
                }
            } else {
                echo '
                    <li>'.tr('Nessun intervento per questo cliente').'</li>';
            }
            echo '
                </ul>
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
            <div class="col-md-6">
                <b>'.tr('Ultime _NUM_ Fatture', ['_NUM_' => $numero_documenti]).':</b><ul>';
            if (!$fatture->isEmpty()) {
                foreach ($fatture as $fattura) {
                    $scadenze = $fattura->scadenze;
                    $da_pagare = $scadenze->sum('da_pagare') - $scadenze->sum('pagato');
                    echo '
                    <li>'.Modules::link('Fatture di vendita', $fattura->id, $fattura->getReference().': '.moneyFormat($da_pagare)).'</li>';
                }
            } else {
                echo '
                    <li>'.tr('Nessuna fattura attiva per questo cliente').'</li>';
            }
            echo '
                </ul>
            </div>';
        }

        // Note dell'anagrafica
        $anagrafica = Anagrafica::find($id_anagrafica);
        $note_anagrafica = $anagrafica->note;
        echo '
            <div class="col-md-12">
                <p><b>'.tr('Note interne sul cliente').':</b></p>
                '.(!empty($note_anagrafica) ? $note_anagrafica : tr('Nessuna nota interna per questo cliente')).'
            </div>';

        echo '
        </div>';

        break;
}
