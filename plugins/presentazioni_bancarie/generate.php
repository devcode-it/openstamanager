<?php

use Plugins\PresentazioniBancarie\Gestore;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Scadenzario\Scadenza;

include_once __DIR__.'/init.php';

echo '
<style>
.select2-selection__rendered {
    line-height: 21px !important;
}
.select2-container .select2-selection--single {
    height: 25px !important;
}
.select2-selection__arrow {
    height: 24px !important;
    position: absolute !important;
    top: -1px !important;
}
</style>
<script>
$(document).ready(function () {
    $("#pulsanti .pull-right").hide();
})
</script>
<p>'.tr('Riepilogo di esportazione dei pagamenti').'.</p>';

// Azienda predefinita
$azienda = Anagrafica::find(setting('Azienda predefinita'));
$banca_azienda = Gestore::getBancaPredefinitaAzienda();
if (empty($banca_azienda)) {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    '.tr("La banca dell'azienda non è definita o non ha impostati i campi Codice IBAN e BIC").'.
    '.Modules::link('Banche', $azienda->id, tr('Imposta'), null, null).'
</div>';
}

$scadenze = Scadenza::with('documento')->whereIn('id', $records);

// Filtro per scadenze pagate
$esporta_pagati = get('pagati');
if (!$esporta_pagati) {
    $scadenze = $scadenze->whereRaw('ABS(pagato) < ABS(da_pagare)');
}

// Filtro per scadenze esportate in precedenza
$esporta_processati = get('processati');
if (!$esporta_processati) {
    $scadenze = $scadenze->whereNull('presentazioni_exported_at');
}

// Lettura delle informazioni
$scadenze = $scadenze->get();
$id_scadenze = $scadenze->pluck('id');

$raggruppamento = $scadenze->groupBy('idanagrafica');
if ($raggruppamento->isEmpty()) {
    echo '
<p>'.tr('Nessun pagamento disponibile secondo la selezione effettuata').'.</p>';

    return;
}

foreach ($raggruppamento as $id_anagrafica => $scadenze_anagrafica) {
    $anagrafica = $scadenze_anagrafica->first()->anagrafica;

    echo '
<h3>
    '.$anagrafica->ragione_sociale;

    $banca_controparte = Banca::where('id_anagrafica', $anagrafica->id)
        ->where('predefined', 1)
        ->first();
    if (empty($banca_controparte)) {
        echo '
</h3>
<p>'.tr('Banca predefinita non impostata').'.</p>';
        continue;
    }

    echo '
</h3>
<table class="table table-condensed table-striped">
    <thead>
        <tr>
            <th>'.tr('Causale').'</th>
            <th class="text-center">'.tr('Data').'</th>
            <th class="text-center">'.tr('Totale').'</th>
        </tr>
    </thead>

    <tbody>';

    $scadenze = $scadenze_anagrafica->sortBy('scadenza');
    foreach ($scadenze as $scadenza) {
        $totale = abs($scadenza->da_pagare) - abs($scadenza->pagato);

        echo '
        <tr>
            <td>
                <span>
                    '.$scadenza->descrizione.'
                </span>';

        $data_esportazione = $scadenza->presentazioni_exported_at;
        if (!empty($data_esportazione)) {
            echo '
                <span class="badge pull-right">'.tr('Esportato in data: _DATE_', [
                    '_DATE_' => timestampFormat($data_esportazione),
                ]).'</span>';
        }

        $banca_controparte = Gestore::getBancaControparte($scadenza);

        if ($database->tableExists('co_mandati_sepa')) {
            $rs_mandato = $dbo->fetchArray('SELECT * FROM co_mandati_sepa WHERE id_banca = '.prepare($banca_controparte->id));
        } else{
            $rs_mandato = false;
        }
        
        $is_rid = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'],["MP09", "MP10", "MP11"]);
        $is_riba = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'],["MP12"]);
	    $is_sepa = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'],["MP19", "MP20", "MP21"]);
        $is_bonifico = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'],["MP05"]);

        $documento = $scadenza->documento;
        $pagamento = $documento->pagamento;

        if ($is_rid) {
            if(!$rs_mandato){
                echo '
                <span class="label label-danger">'.tr('Id mandato mancante').'</span>';
            }

            if(!$banca_azienda->creditor_id){
                echo '
                <span class="label label-danger">'.tr('Id creditore mancante').'</span>';
            }
        } else if(($is_riba && empty($banca_azienda->codice_sia)) || ($is_bonifico && empty($banca_azienda->codice_sia))){
            echo '
                <span class="label label-danger">'.tr('Codice SIA banca emittente mancante').'</span>';
        } 
        
	    if ($is_sepa) {

            //Prima, successiva, singola

            $scadenze_antecedenti = $dbo->fetchArray("SELECT * FROM co_scadenziario INNER JOIN co_documenti ON co_scadenziario.iddocumento=co_documenti.id INNER JOIN co_pagamenti ON co_documenti.idpagamento=co_pagamenti.id WHERE co_documenti.idanagrafica=".prepare($id_anagrafica)." AND codice_modalita_pagamento_fe IN('MP19','MP20','MP21') AND data_emissione<".prepare($scadenza->data_emissione));

            $check_successiva = '';
            $check_prima = '';
            $check_singola = '';

            if(sizeof($scadenze_antecedenti)>0){
                $check_successiva = 'selected';
            }else{
                $check_prima = 'selected';
            }

            if (sizeof($rs_mandato)>0) {
                if($rs_mandato[0]['singola_disposizione']=='1'){
                    $check_singola = 'selected';

                    $check_successiva = '';
                    $check_prima = '';
                }
            }

            echo '
                <span class="pull-right" style="margin-right:15px;">
                    <select class="sequenza" name="sequenza[]" data-idscadenza="'.$scadenza->id.'" style="width:300px;">
                        <option value="">- Seleziona una sequenza -</option>
                        <option value="FRST" '.$check_prima.'>Prima di una serie di disposizioni</option>
                        <option value="RCUR" '.$check_successiva.'>Successiva di una serie di disposizioni di incasso</option>
                        <option value="FNAL">Ultima di una serie di disposizioni</option>
                        <option value="OOFF" '.$check_singola.'>Singola diposizione non ripetuta</option>
                    </select>
                </span>';

        }

        echo '
            </td>
            <td class="text-center">
                '.dateFormat($scadenza->scadenza).'
            </td>
            <td class="text-right">
                '.moneyFormat($totale).'
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
}

echo '
<div class="row">
    <div class="col-md-12 text-right">
        <button type="button" class="btn btn-primary '.(!empty($banca_azienda) ? '' : 'disabled').'" onclick="esporta(this)">
            <i class="fa fa-download"></i> '.tr('Esporta').'
        </button>
    </div>
</div>

<div class="row hidden" id="info">
    <div class="col-md-12">
        <p>'.tr('Le scadenze selezionate sono state esportate nei seguenti file').':</p>
        <ul id="files"></ul>
    </div>

    <div class="col-md-12 text-right">
        <button type="button" id="registrazione_contabile" class="btn btn-primary" onclick="registraPagamenti(this)">
            <i class="fa fa-save"></i> '.tr('Registrazione contabile').'
        </button>
    </div>
</div>';

$modulo_prima_nota = Modules::get('Prima nota');
echo '
<script>

$(".sequenza").select2();

function esporta(button) {
    let restore = buttonLoading(button);

    //Creo un array con i valori di sequenza
    var inputs = $(".sequenza");
    var sequenze = new Array();
    for(var i = 0; i < inputs.length; i++){
        if($(inputs[i]).val()){
            sequenze[i] = $(inputs[i]).data("idscadenza")+"-"+$(inputs[i]).val();
        }
    }

    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "POST",
        dataType: "json",
        data: {
            id_module: globals.id_module,
            id_plugin: "'.$id_plugin.'",
            scadenze: ['.implode(',', $id_scadenze->toArray()).'],
            sequenze: sequenze,
            op: "generate",
        },
    }).then(function(response) {
        buttonRestore(button, restore);

        if (response.scadenze.length) {
            $(button).addClass("hidden");
            $("#info").removeClass("hidden");

            // Salvataggio delle scadenze esportate correttamente
            $("#registrazione_contabile").data("scadenze", response.scadenze);

            // Creazione dei link per il download dei file
            let fileList = $("#files");
            for(const file of response.files){
                fileList.append(`<li>
    <a href="` + file + `" target="_blank">` + file + `</a>
    <button type="button" class="btn btn-xs btn-info" onclick="scaricaFile(\'` + file + `\')">
        <i class="fa fa-download"></i>
    </button>
</li>`)
            }
        } else {
             swal({
                title: "'.tr('Impossibile esportare le scadenze indicate!').'",
                type: "error",
            })
        }
    });
}

function scaricaFile(file) {
    fetch(file)
        .then(resp => resp.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.style.display = "none";
            a.href = url;

            // the filename you want
            a.download = file.split("/").pop();
            document.body.appendChild(a);
            a.click();

            window.URL.revokeObjectURL(url);
        })
      .catch(() => swal("'.tr('Errore').'", "'.tr('Errore durante il download').'", "error"));
}

function registraPagamenti(button) {
    let scadenze = $(button).data("scadenze");

    openModal("'.tr('Registrazione contabile pagamento').'", globals.rootdir + "/add.php?id_module='.$modulo_prima_nota['id'].'&id_records=" + scadenze.join(";"));
}
</script>';
