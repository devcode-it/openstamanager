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

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

function renderRiga($id, $riga)
{
    // Conto
    echo '
    <tr>
        <input type="hidden" name="id_documento['.$id.']" value="'.$riga['iddocumento'].'">
        <input type="hidden" name="id_scadenza['.$id.']" value="'.$riga['id_scadenza'].'">

        <td>
            {[ "type": "select", "name": "idconto['.$id.']", "id": "conto'.$id.'", "value": "'.($riga['id_conto'] ?: '').'", "ajax-source": "conti" ]}
        </td>';

    // Dare
    echo '
        <td>
            {[ "type": "number", "name": "dare['.$id.']", "id": "dare'.$id.'", "value": "'.($riga['dare'] ?: 0).'" ]}
        </td>';

    // Avere
    echo '
        <td>
            {[ "type": "number", "name": "avere['.$id.']", "id": "avere'.$id.'", "value": "'.($riga['avere'] ?: 0).'" ]}
        </td>
    </tr>';
}

function renderTabella($nome, $righe)
{
    global $counter;

    $prima_riga = $righe->first();
    $id_documento = $prima_riga ? $prima_riga['iddocumento'] : null;
    $id_scadenza = $prima_riga ? $prima_riga['id_scadenza'] : null;

    echo '
<div class="raggruppamento_primanota" data-id_scadenza="'.$id_scadenza.'" data-id_documento="'.$id_documento.'">
    <button class="btn btn-info btn-xs pull-right" type="button" onclick="addRiga(this)">
        <i class="fa fa-plus"></i> '.tr('Aggiungi riga').'
    </button>

    <h4>'.$nome.'</h4>

    <table class="table table-striped table-condensed table-hover table-bordered scadenze">
        <thead>
            <tr>
                <th>'.tr('Conto').'</th>
                <th width="20%">'.tr('Dare').'</th>
                <th width="20%">'.tr('Avere').'</th>
            </tr>
        </thead>

        <tbody>';

    foreach ($righe as $riga) {
        renderRiga($counter++, $riga);
    }

    // Totale per controllare sbilancio
    echo '
        </tbody>

        <tfoot>
            <tr>
                <td class="text-right"><b>'.tr('Totale').':</b></td>';

    // Totale dare
    echo '
                <td class="text-right">
                    <span class="totale_dare"></span> '.currency().'
                </td>';

    // Totale avere
    echo '
                <td class="text-right">
                    <span class="totale_avere"></span> '.currency().'
                </td>
            </tr>';

    echo '
        </tfoot>
    </table>';

    // Verifica dello sbilancio
    echo '
    <div class="alert alert-warning hide sbilancio">
        <i class="fa fa-warning"></i> '.tr('Sbilancio di _MONEY_', [
            '_MONEY_' => '<span class="money"></span> '.currency(),
        ]).'
    </div>
</div>';
}

$counter = 0;
$movimenti = collect($movimenti);

// Elenco per documenti
$scadenze = $movimenti
    ->where('iddocumento', '<>', '')
    ->groupBy('iddocumento');
foreach ($scadenze as $id_documento => $righe) {
    $documento = Fattura::find($id_documento);

    $nome = tr('Documento num. _NUM_', [
        '_NUM_' => $documento['numero_esterno'] ?: $documento['numero'],
    ]);

    renderTabella($nome, $righe);
}

// Elenco per scadenze
$scadenze = $movimenti
    ->where('iddocumento', '=', '')
    ->where('id_scadenza', '<>', '')
    ->groupBy('id_scadenza');
foreach ($scadenze as $id_scadenza => $righe) {
    $nome = tr('Scadenza num. _ID_', [
        '_ID_' => $id_scadenza,
    ]);

    renderTabella($nome, $righe);
}

// Elenco generale
$movimenti_generali = $movimenti
    ->where('iddocumento', '=', '')
    ->where('id_scadenza', '=', '');
if ($movimenti_generali->isEmpty()) {
    $movimenti_generali->push([]);
    $movimenti_generali->push([]);
}
$nome = tr('Generale');

renderTabella($nome, $movimenti_generali);

// Nuova riga
echo '
<table class="hide">
    <tbody id="template">';

renderRiga('-id-', [
    'iddocumento' => '-id_documento-',
    'id_scadenza' => '-id_scadenza-',
]);

echo '
    </tbody>
</table>';

echo '
<script>
var formatted_zero = "'.numberFormat(0).'";
var n = '.$counter.';

function addRiga(btn) {
    var raggruppamento = $(btn).parent();
    cleanup_inputs();

    var tabella = raggruppamento.find("tbody");
    var content = $("#template").html();
    content = content.replace("-id_scadenza-", raggruppamento.data("id_scadenza"))
        .replace("-id_documento-", raggruppamento.data("id_documento"));

    var text = replaceAll(content, "-id-", "" + n);
    tabella.append(text);

    restart_inputs();
    n++;
}

/**
* Funzione per controllare lo stato dei conti della prima nota.
*
* @returns {boolean}
*/
function controllaConti() {
    let continuare = true;

    // Controlli sullo stato dei raggruppamenti
    $(".raggruppamento_primanota").each(function() {
        let bilancio = calcolaBilancio(this);

        continuare &= bilancio === 0;
    });

    // Blocco degli input con valore non impostato
    $("input[id*=dare], input[id*=avere]").each(function() {
        let conto_relativo = $(this).parent().parent().find("select").val();

        if (!conto_relativo) {
            $(this).prop("disabled", true);
        }

        if ($(this).val().toEnglish()){
            continuare &= !!conto_relativo;
        }
    });

    if (continuare) {
        $("#add-submit").prop("disabled", false);
        $("#modello-button").prop("disabled", false);
        $("#save").removeAttr("disabled");
    } else {
        $("#add-submit").prop("disabled", true);
        $("#modello-button").prop("disabled", true);
        $("#save").attr("disabled", "true");
    }

    return continuare;
}

/**
* Ad ogni modifica dell\'importo verifica che siano stati selezionati: il conto, la causale, la data.
* Inoltre aggiorna lo sbilancio.
*
* @param gruppo
* @returns {number}
*/
function calcolaBilancio(gruppo) {
    let raggruppamento = $(gruppo);

    let totale_dare = 0.00;
    let totale_avere = 0.00;

    // Calcolo il totale dare
    raggruppamento.find("input[id*=dare]").each(function() {
        totale_dare += input(this).get();
    });

    // Calcolo il totale avere
    raggruppamento.find("input[id*=avere]").each(function() {
        totale_avere += input(this).get();
    });

    totale_dare =  parseFloat(totale_dare);
    totale_avere =  parseFloat(totale_avere);

    // Visualizzazione dei totali
    raggruppamento.find(".totale_dare").text(totale_dare.toLocale());
    raggruppamento.find(".totale_avere").text(totale_avere.toLocale());

    // Calcolo il bilancio
    let bilancio = totale_dare.toFixed(2) - totale_avere.toFixed(2);

    // Visualizzazione dello sbilancio eventuale
    let sbilancio = raggruppamento.find(".sbilancio");
    let valore_sbilancio = sbilancio.find(".money");
    valore_sbilancio.text(bilancio.toLocale());

    if (bilancio === 0) {
        sbilancio.addClass("hide");
    } else {
        sbilancio.removeClass("hide");
    }

    return bilancio;
}

$(document).ready(function() {
    controllaConti();

    // Fix per l\'inizializzazione degli input
    $("input[id*=dare], input[id*=avere]").each(function() {
        if (input(this).get() === 0) {
            $(this).prop("disabled", true);
        } else {
            $(this).prop("disabled", false);
        }
    });

    // Trigger dell\'evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
    $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();

    $("select[id*=idconto]").click(function() {
        $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
    });
});

$(document).on("change", "select", function() {
    let row = $(this).parent().parent();

    if (row.find("input[disabled]").length > 1) {
        row.find("input").prop("disabled", !$(this).val());
    }

    controllaConti();
});

$(document).on("keyup change", "input[id*=dare]", function() {
    let row = $(this).parent().parent();

    if (!$(this).prop("disabled")) {
        row.find("input[id*=avere]").prop("disabled", !!$(this).val());

        controllaConti();
    }
});

$(document).on("keyup change", "input[id*=avere]", function() {
    let row = $(this).parent().parent();

    if (!$(this).prop("disabled")) {
        row.find("input[id*=dare]").prop("disabled", !!$(this).val());

        controllaConti();
    }
});
</script>';
