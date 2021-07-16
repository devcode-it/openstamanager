<?php

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;
use Modules\Preventivi\Preventivo;

include_once __DIR__.'/../../core.php';

$id_anagrafica = get('id_anagrafica');
$op = get('op');
$numero_documenti = 5;

switch ($op) {
    case 'dettagli':
        // Informazioni sui contratti
        $modulo_contratti = Module::get('Contratti');
        if ($modulo_contratti->permission != '-') {
            // Contratti attivi per l'anagrafica
            $contratti = Contratto::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();

            echo '
        <div class="row">
            <div class="col-md-4">
                <b>'.tr('Contratti').':</b><ul>';
            if (!$contratti->isEmpty()) {
                foreach ($contratti as $contratto) {
                    echo '
                    <li>'.$contratto->getReference().' ['.$contratto->stato->descrizione.']: '.dateFormat($contratto->data_accettazione).' - '.dateFormat($contratto->data_conclusione).'</li>';
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
        $modulo_preventivi = Module::get('Preventivi');
        if ($modulo_preventivi->permission != '-') {
            // Preventivi attivi
            $preventivi = Preventivo::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->where('is_pianificabile', '=', 1);
                })
                ->latest()->take($numero_documenti)->get();
            echo '
            <div class="col-md-4">
                <b>'.tr('Preventivi').':</b><ul>';
            if (!$preventivi->isEmpty()) {
                foreach ($preventivi as $preventivo) {
                    echo '
                    <li>'.$preventivo->getReference().' ['.$preventivo->stato->descrizione.']</li>';
                }
            } else {
                echo '
                    <li>'.tr('Nessun preventivo attivo per questo cliente').'</li>';
            }
            echo '
                </ul>
            </div>';
        }

        // Informazioni sui preventivi
        $modulo_fatture_vendita = Module::get('Fatture di vendita');
        if ($modulo_fatture_vendita->permission != '-') {
            // Fatture attive
            $fatture = Fattura::where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('stato', function ($query) {
                    $query->whereIn('descrizione', ['Emessa', 'Parzialmente pagato']);
                })
                ->latest()->take($numero_documenti)->get();
            echo '
            <div class="col-md-4">
                <b>'.tr('Fatture').':</b><ul>';
            if (!$fatture->isEmpty()) {
                foreach ($fatture as $fattura) {
                    $scadenze = $fattura->scadenze;
                    $da_pagare = $scadenze->sum('da_pagare') - $scadenze->sum('pagato');
                    echo '
                    <li>'.$fattura->getReference().': '.moneyFormat($da_pagare).'</li>';
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
