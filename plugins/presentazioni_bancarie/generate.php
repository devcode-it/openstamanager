<?php

use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Banche\Banca;
use Modules\Scadenzario\Scadenza;
use Plugins\PresentazioniBancarie\Gestore;

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

// Filtro per note di credito e gestione storno
$scadenze_filtrate = collect();
$scadenze_da_escludere = collect();
$scadenze_modificate = collect(); // Mappa id_scadenza => scadenza_modificata
$note_credito_escluse = collect();
$fatture_stornate = collect();
$storni_parziali = collect(); // Per tracciare gli storni parziali

// Prima passata: identifichiamo note di credito e calcoliamo storni
foreach ($scadenze as $scadenza) {
    $documento = $scadenza->documento;

    // Se è una nota di credito, gestiamo lo storno
    if (!empty($documento) && $documento->isNota()) {
        $note_credito_escluse->push($documento);

        // Verifica se corrisponde all'importo della fattura originale
        $fattura_originale = $documento->getFatturaOriginale();
        if (!empty($fattura_originale)) {
            $importo_nota = abs($documento->netto);
            $importo_fattura_originale = abs($fattura_originale->netto);

            // Se gli importi corrispondono (tolleranza di 1 centesimo), escludiamo anche la fattura originale
            if (abs($importo_nota - $importo_fattura_originale) < 0.01) {
                // Trova tutte le scadenze della fattura originale e le esclude
                $scadenze_fattura_originale = $scadenze->where('iddocumento', $fattura_originale->id);
                foreach ($scadenze_fattura_originale as $scad_orig) {
                    $scadenze_da_escludere->push($scad_orig->id);
                }
                $fatture_stornate->push($fattura_originale);
            } else {
                // Importi diversi: storniamo il valore della nota di credito dalla fattura originale
                $scadenze_fattura_originale = $scadenze->where('iddocumento', $fattura_originale->id);
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
}

// Seconda passata: costruiamo la collezione finale
foreach ($scadenze as $scadenza) {
    $documento = $scadenza->documento;

    // Se la scadenza non ha un documento associato, la includiamo
    if (empty($documento)) {
        $scadenze_filtrate->push($scadenza);
        continue;
    }

    // Saltiamo le note di credito
    if ($documento->isNota()) {
        continue;
    }

    // Se non è da escludere, la includiamo (eventualmente modificata)
    if (!$scadenze_da_escludere->contains($scadenza->id)) {
        if ($scadenze_modificate->has($scadenza->id)) {
            $scadenze_filtrate->push($scadenze_modificate->get($scadenza->id));
        } else {
            $scadenze_filtrate->push($scadenza);
        }
    }
}

// Rimuoviamo le scadenze da escludere dalla collezione filtrata
$scadenze = $scadenze_filtrate->filter(function ($scadenza) use ($scadenze_da_escludere) {
    return !$scadenze_da_escludere->contains($scadenza->id);
});

$id_scadenze = $scadenze->pluck('id');

// Messaggi informativi per l'utente
if ($note_credito_escluse->isNotEmpty()) {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> <strong>'.tr('Gestione Note di Credito').'</strong><br>';

    echo tr('Sono state escluse _NUM_ note di credito dall\'esportazione', [
        '_NUM_' => $note_credito_escluse->count(),
    ]).':';

    echo '<ul>';
    foreach ($note_credito_escluse as $nota) {
        $numero_nota = $nota->numero_esterno ?: $nota->numero;
        echo '<li>'.tr('Nota di credito n. _NUM_ del _DATE_ - Importo: _AMOUNT_', [
            '_NUM_' => $numero_nota,
            '_DATE_' => dateFormat($nota->data),
            '_AMOUNT_' => moneyFormat(abs($nota->netto))
        ]).'</li>';
    }
    echo '</ul>';

    if ($fatture_stornate->isNotEmpty()) {
        echo '<strong>'.tr('Fatture completamente stornate (escluse dall\'esportazione)').':</strong>';
        echo '<ul>';
        foreach ($fatture_stornate as $fattura) {
            $numero_fattura = $fattura->numero_esterno ?: $fattura->numero;
            echo '<li>'.tr('Fattura n. _NUM_ del _DATE_ - Importo: _AMOUNT_', [
                '_NUM_' => $numero_fattura,
                '_DATE_' => dateFormat($fattura->data),
                '_AMOUNT_' => moneyFormat(abs($fattura->netto))
            ]).'</li>';
        }
        echo '</ul>';
    }

    if ($storni_parziali->isNotEmpty()) {
        echo '<strong>'.tr('Storni parziali applicati').':</strong>';
        echo '<ul>';
        foreach ($storni_parziali as $storno) {
            $numero_nota = $storno['nota_credito']->numero_esterno ?: $storno['nota_credito']->numero;
            $numero_fattura = $storno['fattura_originale']->numero_esterno ?: $storno['fattura_originale']->numero;
            $importo_fattura_originale = abs($storno['fattura_originale']->netto);
            $importo_residuo = $importo_fattura_originale - $storno['importo_stornato'];

            echo '<li>'.tr('Fattura n. _FATTURA_ (€ _IMPORTO_FATTURA_): stornato € _IMPORTO_STORNO_ dalla nota di credito n. _NOTA_ - Residuo da esportare: € _RESIDUO_', [
                '_FATTURA_' => $numero_fattura,
                '_IMPORTO_FATTURA_' => moneyFormat($importo_fattura_originale),
                '_IMPORTO_STORNO_' => moneyFormat($storno['importo_stornato']),
                '_NOTA_' => $numero_nota,
                '_RESIDUO_' => moneyFormat($importo_residuo)
            ]).'</li>';
        }
        echo '</ul>';
    }

    echo '
</div>';
}

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
<table class="table table-sm table-striped">
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
        $totale = abs($scadenza->da_pagare - $scadenza->pagato);

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
        } else {
            $rs_mandato = 0;
        }

        $is_rid = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'], ['MP09', 'MP10', 'MP11']);
        $is_riba = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'], ['MP12']);
        $is_sepa = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'], ['MP19', 'MP20', 'MP21']);
        $is_bonifico = in_array($scadenza->documento->pagamento['codice_modalita_pagamento_fe'], ['MP05']);

        $documento = $scadenza->documento;
        $pagamento = $documento->pagamento;

        if ($is_rid) {
            if (!$rs_mandato) {
                echo '
                <span class="badge badge-danger">'.tr('Id mandato mancante').'</span>';
            }

            if (!$banca_azienda->creditor_id) {
                echo '
                <span class="badge badge-danger">'.tr('Id creditore mancante').'</span>';
            }
        } elseif (($is_riba && empty($banca_azienda->codice_sia)) || ($is_bonifico && empty($banca_azienda->codice_sia))) {
            echo '
                <span class="badge badge-danger">'.tr('Codice SIA banca emittente mancante').'</span>';
        }

        if ($is_sepa) {
            // Prima, successiva, singola

            $scadenze_antecedenti = $dbo->fetchArray('SELECT * FROM `co_scadenziario` INNER JOIN `co_documenti` ON `co_scadenziario`.`iddocumento`=`co_documenti`.`id` INNER JOIN `co_pagamenti` ON `co_documenti`.`idpagamento`=`co_pagamenti`.`id` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_documenti`.`idanagrafica`='.prepare($id_anagrafica)." AND `codice_modalita_pagamento_fe` IN('MP19','MP20','MP21') AND `data_emissione`<".prepare($scadenza->data_emissione));

            $check_successiva = '';
            $check_prima = '';
            $check_singola = '';

            if (sizeof($scadenze_antecedenti) > 0) {
                $check_successiva = 'selected';
            } else {
                $check_prima = 'selected';
            }

            if ($rs_mandato) {
                if ($rs_mandato[0]['singola_disposizione'] == '1') {
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

$modulo_prima_nota = Module::where('name', 'Prima nota')->first()->id;
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
