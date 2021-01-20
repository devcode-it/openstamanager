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

if ($direzione == 'entrata') {
    $prezzo_predefinito = $prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita;
} else {
    $prezzo_predefinito = $articolo->prezzo_acquisto;
}
// Individuazione dei prezzi registrati
$dettagli = DettaglioPrezzo::dettagli($id_articolo, $id_anagrafica, $direzione)
    ->get();

$dettaglio_predefinito = DettaglioPrezzo::dettaglioPredefinito($id_articolo, $id_anagrafica, $direzione)
    ->first();
if ($articolo->id_fornitore == $anagrafica->idanagrafica) {
    $color = 'success';
    $icon = 'check';
    $text = tr('Sì');
} else {
    $color = 'danger';
    $icon = 'times';
    $text = tr('No');
}
echo '
<table class="table table-striped table-condensed table-bordered">
    <tr>
        <th class="text-center col-md-4">'.($direzione == 'entrata' ? tr('Cliente') : tr('Fornitore')).'</th>
        <th class="text-center col-md-4">'.tr('Prezzo predefinito').'</th>';
        if ($direzione == 'uscita') {
            echo '<th class="text-center col-md-4">'.tr('Fornitore predefinito').'</th>';
        } else {
            echo '<th class="text-center col-md-4"></th>';
        }
    echo '      
    </tr>
    <tr>
        <td class="text-center">'.$anagrafica->ragione_sociale.'</td>
        <td class="text-center">'.moneyFormat($prezzo_predefinito).'</td>';
        if ($direzione == 'uscita') {
            echo '<td class="text-center"><i class="fa fa-'.$icon.' text-'.$color.'"></i> '.$text.'</td>';
        } else {
            echo '<td></td>';
        }
    echo '   
    </tr>
</table>

<form action="" method="post">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update_prezzi">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    <input type="hidden" name="dir" value="'.$direzione.'">
    <input type="hidden" name="id_anagrafica" value="'.$id_anagrafica.'">
    <input type="hidden" name="id_articolo" value="'.$id_articolo.'">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Imposta prezzo per questa anagrafica').'", "name": "modifica_prezzi", "value": "'.intval(!empty($dettaglio_predefinito)).'" ]}
        </div>
    </div>

    <div class="row">
        <div class="info_prezzi">
            <div class="col-md-4">
                {[ "type": "number", "label": "'.tr('Prezzo specifico').'", "name": "prezzo_unitario_fisso", "value": "'.($prezzi_ivati ? $dettaglio_predefinito->prezzo_unitario_ivato : $dettaglio_predefinito->prezzo_unitario).'", "icon-after": "'.currency().'", "help": "'.($prezzi_ivati ? tr('Importo IVA inclusa') : '').'" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "number", "label": "'.tr('Sconto specifico').'", "name": "sconto_fisso", "value": "'.$dettaglio_predefinito->sconto_percentuale.'", "icon-after": "%"]}
            </div>
        </div>
    </div>

    <div class="row">
        <div id="imposta_prezzo_qta" class="col-md-4">
            {[ "type": "checkbox", "label": "'.tr('Imposta un prezzo in base alla quantità').'", "name": "prezzo_qta", "value": "'.intval($dettagli->count() != 0).'" ]}
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
                            '.tr('Prezzo unitario').($prezzi_ivati ? '<i class="fa fa-question-circle-o"></i>' : '').'
                        </th>
                        <th class="text-center">'.tr('Sconto').'</th>
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
                           {[ "type": "number", "name": "sconto['.$key.']", "min-value": 0, "value": "'.$dettaglio->sconto.'", "icon-after":"%" ]}
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
               {[ "type": "number", "name": "sconto[-id-]", "min-value": 0, "icon-after": "%" ]}
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
    let prezzo_qta = input("prezzo_qta");
    let prezzo_unitario_fisso = input("prezzo_unitario_fisso");
    let sconto_fisso = input("sconto_fisso");

    let prezzi_variabili = $("#prezzi");

    if (!modifica_prezzi.get()){     
        $(".info_prezzi").hide();
    } else {
        $(".info_prezzi").show();
    }

    if (prezzo_qta.get()) {
        prezzi_variabili.removeClass("hidden");
    } else {
        prezzi_variabili.addClass("hidden");
    }
}

input("modifica_prezzi").change(function () {
    cambioImpostazioni();
})

input("prezzo_qta").change(function () {
    cambioImpostazioni();
})

$(document).ready(cambioImpostazioni);
</script>';
