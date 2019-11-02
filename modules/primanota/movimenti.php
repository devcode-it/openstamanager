<?php

include_once __DIR__.'/../../core.php';

use Modules\Fatture\Fattura;

function renderRiga($id, $riga)
{
    // Conto
    echo '
    <tr>
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

function renderTabella($nome, $righe, $id_scadenza_default = null)
{
    global $counter;

    echo '
<div class="raggruppamento_primanota" data-id_scadenza="'.$id_scadenza_default.'">
    <button class="btn btn-info btn-xs pull-right" type="button" onclick="addRiga(this)">
        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
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
                <td align="right"><b>'.tr('Totale').':</b></td>';

    // Totale dare
    echo '
                <td align="right">
                    <span class="totale_dare"></span> '.currency().'
                </td>';

    // Totale avere
    echo '
                <td align="right">
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
$righe = collect($righe);

// Elenco per documenti
$scadenze = $righe
    ->where('iddocumento', '<>', '')
    ->groupBy('iddocumento');
foreach ($scadenze as $id_documento => $righe) {
    $documento = Fattura::find($id_documento);

    $nome = tr('Documento num. _NUM_', [
        '_NUM_' => $documento['numero_esterno'] ?: $documento['numero'],
    ]);

    renderTabella($nome, $righe, $righe->first()['id_scadenza']);
}

// Elenco per scadenze
$scadenze = $righe
    ->where('iddocumento', '=', '')
    ->where('id_scadenza', '<>', '')
    ->groupBy('id_scadenza');
foreach ($scadenze as $id_scadenza => $righe) {
    $nome = tr('Scadenza num. _ID_', [
        '_ID_' => $id_scadenza,
    ]);

    renderTabella($nome, $righe, $righe->first()['id_scadenza']);
}

// Elenco generale
$righe_generali = $righe
    ->where('iddocumento', '=', '')
    ->where('id_scadenza', '=', '');
if ($righe_generali->isEmpty()) {
    $righe_generali->push([]);
    $righe_generali->push([]);
}
$nome = tr('Generale');

renderTabella($nome, $righe_generali);

// Nuova riga
echo '
<table class="hide">
    <tbody id="template">';

renderRiga('-id-', [
    'id_scadenza' => '-id_scadenza-',
]);

echo '
    </tbody>
</table>';

echo '
<script>
var formatted_zero = "'.Translator::numberToLocale(0).'";
var n = '.$counter.';

function addRiga(btn) {
    var raggruppamento = $(btn).parent();
    n++;
    cleanup_inputs();

    var tabella = raggruppamento.find("tbody");
    var content = $("#template").html();
    content.replace("-id_scadenza-", raggruppamento.data("id_scadenza"));
    
    var text = replaceAll(content, "-id-", "" + n);
    tabella.append(text);
    
    restart_inputs();
}

/**
* 
* @returns {boolean}
*/
function controllaBilanci() {
    var continuare = true;

    $(".raggruppamento_primanota").each(function() {
        var bilancio = calcolaBilancio(this);
        
        continuare &= bilancio == 0;
    });

    if (continuare) {
        $("#add-submit").removeClass("hide");
        $("#btn_crea_modello").removeClass("hide");
    } else {    
        $("#add-submit").addClass("hide");
        $("#btn_crea_modello").addClass("hide");
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
    var raggruppamento = $(gruppo);
    
    var totale_dare = 0.00;
    var totale_avere = 0.00;

    // Calcolo il totale dare
    raggruppamento.find("input[id*=dare]").each(function() {
        valore = $(this).val() ? $(this).val().toEnglish() : 0;

        totale_dare += valore;
    });
    
    // Calcolo il totale avere
    raggruppamento.find("input[id*=avere]").each(function() {
        valore = $(this).val() ? $(this).val().toEnglish() : 0;

        totale_avere += valore;
    });

    // Visualizzazione dei totali
    raggruppamento.find(".totale_dare").text(totale_dare.toLocale());
    raggruppamento.find(".totale_avere").text(totale_avere.toLocale());

    // Calcolo il bilancio
    var bilancio = totale_dare - totale_avere;
    
    // Visualizzazione dello sbilancio eventuale
    var sbilancio = raggruppamento.find(".sbilancio");
    var valore_sbilancio = sbilancio.find(".money");
    valore_sbilancio.text(bilancio.toLocale());

    if (bilancio == 0) {
        sbilancio.addClass("hide");
    } else {
        sbilancio.removeClass("hide");
    }

    return bilancio;
}

/**
* 
*/
function bloccaZeri(){
    $("input[id*=dare], input[id*=avere]").each(function() {
        if ($(this).val() == formatted_zero) {
            $(this).prop("disabled", true);
        } else {
            $(this).prop("disabled", false);
        }
    });
}

$(document).ready(function() {
    controllaBilanci();
    bloccaZeri();
    
    // Trigger dell"evento keyup() per la prima volta, per eseguire i dovuti controlli nel caso siano predisposte delle righe in prima nota
    $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();

    $("select[id*=idconto]").click(function() {
        $("input[id*=dare][value!=\'\'], input[id*=avere][value!=\'\']").keyup();
    });
});

$(document).on("change", "select", function() {
    if ($(this).parent().parent().find("input[disabled]").length != 1) {
        if ($(this).val()) {
            $(this).parent().parent().find("input").prop("disabled", false);
        } else {
            $(this).parent().parent().find("input").prop("disabled", true);
            $(this).parent().parent().find("input").val("0.00");
        }
    }
});

$(document).on("keyup change", "input[id*=dare]", function() {
    if (!$(this).prop("disabled")) {
        if ($(this).val()) {
            $(this).parent().parent().find("input[id*=avere]").prop("disabled", true);
        } else {
            $(this).parent().parent().find("input[id*=avere]").prop("disabled", false);
        }

        controllaBilanci();
    }
});

$(document).on("keyup change", "input[id*=avere]", function() {
    if (!$(this).prop("disabled")) {
        if ($(this).val()) {
            $(this).parent().parent().find("input[id*=dare]").prop("disabled", true);
        } else {
            $(this).parent().parent().find("input[id*=dare]").prop("disabled", false);
        }

        controllaBilanci();
    }
});
</script>';
