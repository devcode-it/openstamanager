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

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

function renderRiga($id, $riga, $totale_dare = null, $totale_avere = null)
{
    global $id_record;

    // Determina se siamo nell'add (non c'è $id_record) o nell'edit
    $suffix = empty($id_record) ? '_add' : '';

    // Conto
    echo '
    <tr>
        <input type="hidden" name="id_documento'.$suffix.'['.$id.']" value="'.$riga['iddocumento'].'">
        <input type="hidden" name="id_scadenza'.$suffix.'['.$id.']" value="'.$riga['id_scadenza'].'">

        <td class="text-center" style="width:40px;">
            <button type="button" class="btn btn-danger btn-xs" onclick="deleteRiga(this)">
                <i class="fa fa-times"></i>
            </button>
        </td>

        <td>
            {[ "type": "select", "name": "idconto'.$suffix.'['.$id.']", "id": "conto'.$suffix.'_'.$id.'", "value": "'.($riga['id_conto'] ?: '').'", "ajax-source": "conti", "icon-after": '.json_encode('<button type="button" onclick="visualizzaMovimenti(this)" class="btn btn-info '.($riga['id_conto'] ? '' : 'disabled').'"><i class="fa fa-eye"></i></button>').' ]}
        </td>';

    // Dare
    echo '
        <td>
            {[ "type": "number", "name": "dare'.$suffix.'['.$id.']", "id": "dare'.$suffix.'_'.$id.'", "value": "'.($riga['dare'] ?: 0).'" ]}
        </td>';

    // Avere
    echo '
        <td>
            {[ "type": "number", "name": "avere'.$suffix.'['.$id.']", "id": "avere'.$suffix.'_'.$id.'", "value": "'.($riga['avere'] ?: 0).'" ]}
        </td>
    </tr>';

    $totale_dare += ($riga['dare'] ?: 0);
    $totale_avere += ($riga['avere'] ?: 0);
}

function renderTabella($nome, $righe, $totale_dare = null, $totale_avere = null)
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

    <table class="table table-striped table-sm table-hover table-bordered scadenze">
        <thead>
            <tr>
                <th width="40px"></th>
                <th>'.tr('Conto').'</th>
                <th width="20%">'.tr('Dare').'</th>
                <th width="20%">'.tr('Avere').'</th>
            </tr>
        </thead>

        <tbody>';

    foreach ($righe as $riga) {
        renderRiga($counter++, $riga, $totale_dare, $totale_avere);
    }

    // Totale per controllare sbilancio
    echo '
        </tbody>

        <tfoot>
            <tr>
                <td></td>
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
$totale_dare = 0;
$totale_avere = 0;

// Elenco per documenti
$scadenze = $movimenti
    ->where('iddocumento', '<>', 0)
    ->groupBy('iddocumento');
foreach ($scadenze as $id_documento => $righe) {
    $documento = Fattura::find($id_documento);

    $nome = tr('Documento num. _NUM_', [
        '_NUM_' => $documento['numero_esterno'] ?: $documento['numero'],
    ]);

    renderTabella($nome, $righe, $totale_dare, $totale_avere);

    foreach ($righe as $riga) {
        $totale_dare += $riga['dare'];
        $totale_avere += $riga['avere'];
    }
}

// Elenco per scadenze
$scadenze = $movimenti
    ->where('iddocumento', '=', 0)
    ->where('id_scadenza', '<>', 0)
    ->groupBy('id_scadenza');
foreach ($scadenze as $id_scadenza => $righe) {
    $nome = tr('Scadenza num. _ID_', [
        '_ID_' => $id_scadenza,
    ]);

    renderTabella($nome, $righe, $totale_dare, $totale_avere);
    foreach ($righe as $riga) {
        $totale_dare += $riga['dare'];
        $totale_avere += $riga['avere'];
    }
}

// Elenco generale
$movimenti_generali = $movimenti
    ->where('iddocumento', '=', 0)
    ->where('id_scadenza', '=', 0);
if ($movimenti_generali->isEmpty()) {
    $movimenti_generali->push([]);
    $movimenti_generali->push([]);
}
$nome = tr('Generale');

renderTabella($nome, $movimenti_generali, $totale_dare, $totale_avere);

// Somma i totali dei movimenti generali
foreach ($movimenti_generali as $riga) {
    $totale_dare += $riga['dare'] ?? 0;
    $totale_avere += $riga['avere'] ?? 0;
}

// Suffisso per distinguere add da edit
$suffix = empty($id_record) ? '_add' : '';

echo '
<table class="hide">
    <tbody id="template'.$suffix.'">';

renderRiga('-id-',
    [
        'iddocumento' => '-id_documento-',
        'id_scadenza' => '-id_scadenza-',
    ],
    $totale_dare,
    $totale_avere
);

echo '
    </tbody>
</table>

<table class="table table-bordered">
    <tr>
        <th class="text-right">'.tr('Totale').'</th>
        <th id="totale_dare'.$suffix.'" class="text-right" width="20%">'.moneyFormat($totale_dare).'</th>
        <th id="totale_avere'.$suffix.'" class="text-right" width="20%">'.moneyFormat($totale_avere).'</th>
    </tr>
</table>

<script>
var formatted_zero = "'.numberFormat(0).'";
var n = '.$counter.';

function addRiga(btn) {
    var raggruppamento = $(btn).parent();
    var isInModal = $(btn).closest("#modals").length > 0;
    cleanup_inputs();

    var tabella = raggruppamento.find("tbody");
    var templateId = isInModal ? "#template_add" : "#template";
    var content = $(templateId).html()
        .replace("-id_scadenza-", raggruppamento.data("id_scadenza"))
        .replace("-id_documento-", raggruppamento.data("id_documento"));

    tabella.append(replaceAll(content, "-id-", "" + n));
    restart_inputs();
    n++;
}

/**
* Controlla lo stato dei conti della prima nota e abilita/disabilita i bottoni di submit.
*/
function controllaConti(element) {
    let continuare = true;
    let isInModal = element ? $(element).closest("#modals").length > 0 : $("#modals > div").length > 0;
    let container = isInModal ? $("#modals > div") : $(document);

    // Controlli sullo stato dei raggruppamenti nel container corrente
    container.find(".raggruppamento_primanota").each(function() {
        continuare &= calcolaBilancio(this) === 0;
    });

    // Blocco degli input senza conto selezionato
    container.find("input[id*=dare], input[id*=avere]").each(function() {
        let conto = $(this).closest("tr").find("select").val();
        if (!conto) $(this).prop("disabled", true);
        if ($(this).val().toEnglish()) continuare &= !!conto;
    });

    // Gestione bottoni submit
    if (isInModal) {
        $("#modals > div #add-submit, #modals > div #modello-button").prop("disabled", !continuare);
    } else {
        $("#save, #save-close").prop("disabled", !continuare).toggleClass("disabled", !continuare);
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

    // Inizializzazione input: disabilita quelli a zero
    $("input[id*=dare], input[id*=avere]").each(function() {
        $(this).prop("disabled", input(this).get() === 0);
    });

    // Trigger iniziale per controlli
    $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();

    $("select[id*=conto]").click(function() {
        $(this).closest(".raggruppamento_primanota").find("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
    });
});

$(document).on("change", "select[id*=conto]", function() {
    let row = $(this).closest("tr");

    if (row.find("input[disabled]").length > 1) {
        row.find("input").prop("disabled", !$(this).val());
    }

    $(this).closest(".raggruppamento_primanota").find("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
    controllaConti(this);

    $(this).closest("td").find("button").toggleClass("disabled", !$(this).val());
});

// Handler unificato per input dare/avere - disabilita il campo opposto e aggiorna totali
$(document).on("keyup change", "input[id*=dare], input[id*=avere]", function() {
    if ($(this).prop("disabled")) return;

    let row = $(this).parent().parent().parent();
    let isDare = this.id.includes("dare");
    let oppositeField = isDare ? "input[id*=avere]" : "input[id*=dare]";

    row.find(oppositeField).prop("disabled", $(this).val().toEnglish());
    controllaConti(this);
    aggiornaTotali(this);
});

// Funzione unificata per aggiornare i totali in base al contesto
function aggiornaTotali(element) {
    var isInModal = $(element).closest("#modals").length > 0;
    var totalDare = 0;
    var totalAvere = 0;

    if (isInModal) {
        $("#modals [id*=dare_add_]").each(function() {
            totalDare += parseFloat($(this).val().toEnglish()) || 0;
        });
        $("#modals [id*=avere_add_]").each(function() {
            totalAvere += parseFloat($(this).val().toEnglish()) || 0;
        });
        $("#modals #totale_dare_add").text(totalDare.toLocale());
        $("#modals #totale_avere_add").text(totalAvere.toLocale());
    } else {
        $("[id*=dare]:not([id*=_add_])").not("#modals *").each(function() {
            totalDare += parseFloat($(this).val().toEnglish()) || 0;
        });
        $("[id*=avere]:not([id*=_add_])").not("#modals *").each(function() {
            totalAvere += parseFloat($(this).val().toEnglish()) || 0;
        });
        $("#totale_dare").text(totalDare.toLocale());
        $("#totale_avere").text(totalAvere.toLocale());
    }
}

function visualizzaMovimenti(button) {
    let id_conto = $(button).parent().parent().parent().find("select").val();
    openModal("'.tr('Ultimi 25 movimenti').'", "'.$module->fileurl('dettagli.php').'?id_module=" + globals.id_module + "&id_conto=" + id_conto);
}

function deleteRiga(button) {
    let row = $(button).closest("tr");
    let isInModal = $(button).closest("#modals").length > 0;

    row.remove();
    controllaConti(button);

    // Aggiorna i totali usando un elemento fittizio per determinare il contesto
    aggiornaTotali(isInModal ? $("#modals [id*=dare]").first() : $("[id*=dare]:not([id*=_add_])").first());
}
</script>';
