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
use Plugins\DettagliArticolo\DettaglioPrezzo;

include_once __DIR__.'/../../core.php';

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

// Informazioni di base
$id_articolo = get('id_articolo');
$id_anagrafica = get('id_anagrafica');
$direzione = get('direzione') == 'uscita' ? 'uscita' : 'entrata';

// Modelli di interesse
$articolo = Articolo::find($id_articolo);
$anagrafica = Anagrafica::find($id_anagrafica);

$prezzo_predefinito = $prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita;

// Individuazione dei prezzi registrati
$dettagli = DettaglioPrezzo::dettagli($id_articolo, $id_anagrafica, $direzione)
    ->get();

$dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
    ->first();
$prezzo_dettaglio_predefinito = $prezzo_predefinito;
if (!empty($dettaglio_predefinito)) {
    $prezzo_dettaglio_predefinito = $prezzi_ivati ? $dettaglio_predefinito->prezzo_unitario_ivato : $dettaglio_predefinito->prezzo_unitario;
}

echo '
<p>'.tr('Informazioni relative al fornitore _NAME_', [
    '_NAME_' => $anagrafica->ragione_sociale,
]).'.</p>

<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_prezzi">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    <input type="hidden" name="dir" value="'.$direzione.'">
    <input type="hidden" name="id_anagrafica" value="'.$id_anagrafica.'">
    <input type="hidden" name="id_articolo" value="'.$id_articolo.'">

    <div class="row">
        <div class="col-md-6">
            <p>'.tr('Prezzo unitario predefinito: _TOT_', [
                '_TOT_' => moneyFormat($prezzo_predefinito),
            ]).'</p>
        </div>

        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr("Modifica prezzo per l'anagrafica").'", "name": "modifica_prezzi", "value": "'.intval(!$dettagli->isEmpty() || !empty($dettaglio_predefinito)).'" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
                {[ "type": "number", "label": "'.tr('Prezzo unitario predefinito').'", "name": "prezzo_unitario_fisso", "value": "'.($prezzi_ivati ? $dettaglio_predefinito->prezzo_unitario_ivato : $dettaglio_predefinito->prezzo_unitario).'", "icon-after": "'.currency().'", "help": "'.($prezzi_ivati ? tr('Importo IVA inclusa') : '').'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Imposta un prezzo unitario fisso').'", "name": "prezzo_fisso", "value": "'.intval($dettagli->count() == 0).'" ]}
        </div>
    </div>

    <div class="box" id="prezzi">
        <div class="box-header">
            <h3 class="box-title">
                '.tr('Prezzi per quantità').'
            </h3>

             <button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiungiPrezzo(this)">
                <i class="fa fa-plus"></i> '.tr('Aggiungi range').'
            </button>
        </div>

        <div class="box-body">
            <p>'.tr("Inserire i prezzi da associare all'articolo e all'anagrafica in relazione alla quantità di acquisto").'.</p>
            <p>'.tr('Per impostare un prezzo generale per quantità non incluse in questi limiti, utilizzare il campo sopra indicato').'.</p>

            <table class="table table-condensed">
                <thead>
                    <tr>
                        <th class="text-center">'.tr('Quantità minima').'</th>
                        <th class="text-center">'.tr('Quantità massima').'</th>
                        <th class="text-center tip" title="'.($prezzi_ivati ? tr('Importo IVA inclusa') : '').'">
                            '.tr('Prezzo unitario').' <i class="fa fa-question-circle-o"></i>
                        </th>
                        <th>#</th>
                    </tr>
                </thead>

                <tbody>';

foreach ($dettagli as $key => $dettaglio) {
    echo '
                    <tr>
                        <td>
                        <input type="hidden" name="dettaglio['.$key.']" value="'.$dettaglio->id.'">
                           {[ "type": "number", "name": "minimo['.$key.']", "min-value": 0, "value": "'.$dettaglio->minimo.'" ]}
                        </td>

                        <td>
                           {[ "type": "number", "name": "massimo['.$key.']", "min-value": 0, "value": "'.$dettaglio->massimo.'" ]}
                        </td>

                        <td>
                           {[ "type": "number", "name": "prezzo_unitario['.$key.']", "icon-after": "'.currency().'", "value": "'.($prezzi_ivati ? $dettaglio->prezzo_unitario_ivato : $dettaglio->prezzo_unitario).'" ]}
                        </td>

                        <td>
                            <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviPrezzo(this)">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>';
}

echo '
                </tbody>
            </table>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-md-12">
            <button class="btn btn-primary pull-right">
                <i class="fa fa-edit"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>

<table class="hide">
    <tbody id="prezzi-template">
        <tr>
            <td>
               {[ "type": "number", "name": "minimo[-id-]", "min-value": 0 ]}
            </td>

            <td>
               {[ "type": "number", "name": "massimo[-id-]", "min-value": 0 ]}
            </td>

            <td>
               {[ "type": "number", "name": "prezzo_unitario[-id-]", "icon-after": "'.currency().'" ]}
            </td>

            <td>
                <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviPrezzo(this)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>

<script>$(document).ready(init);</script>

<script>
var key = '.$dettagli->count().';
function aggiungiPrezzo(button) {
    cleanup_inputs();

    let text = replaceAll($("#prezzi-template").html(), "-id-", "" + key);
    key++;

    let body = $(button).closest(".box").find("table > tbody");
    let lastRow = body.find("tr").last();
    if (lastRow.length) {
        lastRow.after(text);
    } else {
        body.html(text);
    }

    restart_inputs();
}

function rimuoviPrezzo(button) {
    $(button).closest("tr").remove();
}

function cambioImpostazioni() {
    let modifica_prezzi = input("modifica_prezzi");
    let prezzo_fisso = input("prezzo_fisso");
    let prezzo_fisso_input = input("prezzo_unitario_fisso");

    let prezzi_variabili = $("#prezzi");

    if (!modifica_prezzi.get()){
        prezzo_fisso.disable();
        prezzo_fisso_input.disable();
    } else {
        modifica_prezzi.disable();

        prezzo_fisso.enable();
        prezzo_fisso_input.enable();
    }

    if (!prezzo_fisso.get()) {
        prezzi_variabili.removeClass("hidden");
    } else {
        prezzi_variabili.addClass("hidden");
    }
}

input("modifica_prezzi").change(function () {
    cambioImpostazioni();
})

input("prezzo_fisso").change(function () {
    cambioImpostazioni();
})

$(document).ready(cambioImpostazioni);
</script>';
