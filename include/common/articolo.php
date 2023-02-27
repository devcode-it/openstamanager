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

$result['idarticolo'] = isset($result['idarticolo']) ? $result['idarticolo'] : null;
$qta_minima = 0;
$id_listino = $dbo->selectOne('an_anagrafiche', 'id_listino', ['idanagrafica' => $options['idanagrafica']])['id_listino'];

// Articolo
$database = database();
$articolo = $database->fetchOne('SELECT mg_articoli.id,
    mg_fornitore_articolo.id AS id_dettaglio_fornitore,
    IFNULL(mg_fornitore_articolo.codice_fornitore, mg_articoli.codice) AS codice,
    IFNULL(mg_fornitore_articolo.descrizione, mg_articoli.descrizione) AS descrizione,
    IFNULL(mg_fornitore_articolo.qta_minima, 0) AS qta_minima
FROM mg_articoli
    LEFT JOIN mg_fornitore_articolo ON mg_fornitore_articolo.id_articolo = mg_articoli.id AND mg_fornitore_articolo.id = '.prepare($result['id_dettaglio_fornitore']).'
WHERE mg_articoli.id = '.prepare($result['idarticolo']));

$qta_minima = $articolo['qta_minima'];

    echo '
    {[ "type": "select", "disabled":"1", "label": "'.tr('Articolo').'", "name": "idarticolo", "value": "'.$result['idarticolo'].'", "ajax-source": "articoli", "select-options": '.json_encode($options['select-options']['articoli']).' ]}

    <script>
        $(document).ready(function (){
            ottieniDettagliArticolo("'.$articolo['id'].'").then(function (){
                verificaPrezzoArticolo();
                verificaScontoArticolo();
                verificaMinimoVendita();
            });
        });
    </script>

    <input type="hidden" name="qta_minima" id="qta_minima" value="'.$qta_minima.'">
    <input type="hidden" name="provvigione_default" id="provvigione_default" value="'.$result['provvigione_default'].'">
    <input type="hidden" name="tipo_provvigione_default" id="provvigione_default" value="'.$result['tipo_provvigione_default'].'">
    <input type="hidden" name="blocca_minimo_vendita" value="'.setting('Bloccare i prezzi inferiori al minimo di vendita').'">';

// Selezione impianto per gli Interventi
if ($module['name'] == 'Interventi') {
    echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Impianto su cui installare').'", "name": "id_impianto", "value": "'.$result['idimpianto'].'", "ajax-source": "impianti-intervento", "select-options": '.json_encode($options['select-options']['impianti']).', "disabled": "'.($result['idimpianto'] ? 1 : 0).'", "help": "'.tr("La selezione di un Impianto in questo campo provocherà l'installazione di un nuovo Componente basato sull'Articolo corrente").'" ]}
    </div>
</div>';
}

echo App::internalLoad('riga.php', $result, $options);

// Informazioni aggiuntive
$disabled = empty($result['idarticolo']);

echo '
<div class="row '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'" id="prezzi_articolo">
    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezziacquisto\').toggleClass(\'hide\'); $(\'#prezziacquisto\').load(\''.base_path()."/ajax_complete.php?module=Articoli&op=getprezziacquisto&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&limit=5".'\');">
            <i class="fa fa-shopping-cart"></i> '.tr('Ultimi prezzi di acquisto').'
        </button>
        <br>
        <div id="prezziacquisto" class="hide"></div>
    </div>

    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezzi\').toggleClass(\'hide\'); $(\'#prezzi\').load(\''.base_path()."/ajax_complete.php?module=Articoli&op=getprezzi&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-handshake-o"></i> '.($options['dir'] == 'entrata' ? tr('Ultimi prezzi al cliente') : tr('Ultimi prezzi dal fornitore')).'
        </button>
        <div id="prezzi" class="hide"></div>
    </div>

    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezzivendita\').toggleClass(\'hide\'); $(\'#prezzivendita\').load(\''.base_path()."/ajax_complete.php?module=Articoli&op=getprezzivendita&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&limit=5".'\');">
            <i class="fa fa-money"></i> '.tr('Ultimi prezzi di vendita').'
        </button>
        <br>
        <div id="prezzivendita" class="hide"></div>
    </div>
</div>
<br>

<script>
var direzione = "'.$options['dir'].'";
globals.aggiunta_articolo = {
};

$(document).ready(function () {
    if (direzione === "uscita") {
        aggiornaQtaMinima();
        $("#qta").keyup(aggiornaQtaMinima);
    }
});

input("tipo_sconto").on("change", function() {
    verificaScontoArticolo();
});

$("#idarticolo").on("change", function() {
    // Operazioni sui prezzi in fondo alla pagina
    let prezzi_precedenti = $("#prezzi_articolo button");
    if (prezzi_precedenti.length) {
        prezzi_precedenti.attr("disabled", !$(this).val());

        if ($(this).val()) {
            prezzi_precedenti.removeClass("disabled");
        } else {
           prezzi_precedenti.addClass("disabled");
        }

        $("#prezzi").html("");
        $("#prezzivendita").html("");
        $("#prezziacquisto").html("");
        $("#prezzo_unitario").val("");
    }

    if (!$(this).val()) {
        return;
    }

    // Autoimpostazione dei campi relativi all\'articolo
    let $data = $(this).selectData();
    ottieniDettagliArticolo($data.id).then(function() {
        if ($("#prezzo_unitario").val().toEnglish() === 0){
            aggiornaPrezzoArticolo();
        } else {
            verificaPrezzoArticolo();
        }

        if ($("#sconto").val().toEnglish() === 0){
            aggiornaScontoArticolo();
        } else {
            verificaScontoArticolo();
        }

        $("#costo_unitario").val($data.prezzo_acquisto);
        $("#descrizione_riga").val($data.descrizione);

        if (direzione === "entrata") {
            if($data.idiva_vendita) {
                $("#idiva").selectSetNew($data.idiva_vendita, $data.iva_vendita, {"percentuale": $data.percentuale});
            }
        }
    
        else {
            $("#id_dettaglio_fornitore").val($data.id_dettaglio_fornitore);
            $("#qta_minima").val($data.qta_minima);
            aggiornaQtaMinima();
        }
    
        let id_conto = $data.idconto_'.($options['dir'] == 'entrata' ? 'vendita' : 'acquisto').';
        let id_conto_title = $data.idconto_'.($options['dir'] == 'entrata' ? 'vendita' : 'acquisto').'_title;
        if(id_conto) {
            $("#idconto").selectSetNew(id_conto, id_conto_title);
        }
    
        $("#um").selectSetNew($data.um, $data.um);

        if ($data.provvigione) {
            input("provvigione").set($data.provvigione);
            input("tipo_provvigione").set($data.tipo_provvigione);
        } else {
            input("provvigione").set(input("provvigione_default").get());
            input("tipo_provvigione").set(input("tipo_provvigione_default").get());
        }
    });
});

$("#idsede").on("change", function() {
    updateSelectOption("idsede_partenza", $(this).val());
    session_set("superselect,idsede_partenza", $(this).val(), 0);
    $("#idarticolo").selectReset();
});

$(document).on("change", "input[name^=qta], input[name^=prezzo_unitario], input[name^=sconto]", function() {
    verificaPrezzoArticolo();
    verificaScontoArticolo();
    verificaMinimoVendita();
});

/**
* Restituisce il dettaglio registrato per una specifica quantità dell\'articolo.
*/
function getDettaglioPerQuantita(qta) {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_predefinito = null;
    let dettaglio_selezionato = null;
    for (const dettaglio of data) {
        if (dettaglio.minimo == null && dettaglio.massimo == null && dettaglio.prezzo_unitario != null) {
            dettaglio_predefinito = dettaglio;
            continue;
        }

        if (qta >= dettaglio.minimo && qta <= dettaglio.massimo) {
            dettaglio_selezionato = dettaglio;
        }
    }

    if (dettaglio_selezionato == null) {
        dettaglio_selezionato = dettaglio_predefinito;
    }

    return dettaglio_selezionato;
}

/**
* Restituisce il prezzo registrato per una specifica quantità dell\'articolo.
*/
function getPrezzoPerQuantita(qta) {
    const dettaglio = getDettaglioPerQuantita(qta);

    return dettaglio ? parseFloat(dettaglio.prezzo_unitario) : 0;
}

/**
* Restituisce il prezzo del listino registrato per l\'articolo-anagrafica.
*/
function getPrezzoListino() {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_listino = null;
    for (const dettaglio of data) {
        if (dettaglio.prezzo_unitario_listino != null) {
            dettaglio_listino = dettaglio;
            continue;
        }
    }

    return dettaglio_listino ? parseFloat(dettaglio_listino.prezzo_unitario_listino) : 0;
}

/**
* Restituisce il prezzo della scheda articolo.
*/
function getPrezzoScheda() {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_scheda = null;
    for (const dettaglio of data) {
        if (dettaglio.prezzo_scheda != null) {
            dettaglio_scheda = dettaglio;
            continue;
        }
    }

    return dettaglio_scheda ? parseFloat(dettaglio_scheda.prezzo_scheda) : 0;
}

/**
* Restituisce l\'ultimo prezzo registrato dell\' articolo.
*/
function getPrezzoUltimo() {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_ultimo = null;
    for (const dettaglio of data) {
        if (dettaglio.prezzo_ultimo != null) {
            dettaglio_ultimo = dettaglio;
            continue;
        }
    }

    return dettaglio_ultimo ? parseFloat(dettaglio_ultimo.prezzo_ultimo) : 0;
}

/**
* Restituisce i prezzi dei listini sempre visibili registrati per l\'articolo.
*/
function getPrezziListinoVisibili(nome = "") {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_prezzi_visibili = [];
    for (const dettaglio of data) {
        if (dettaglio.prezzo_unitario_listino_visibile != null) {
            if (nome != "") {
                if (dettaglio.nome == nome) {
                    dettaglio_prezzi_visibili = parseFloat(dettaglio.prezzo_unitario_listino_visibile);
                    continue;
                }
            } else {                
                dettaglio_prezzi_visibili.push(dettaglio);
            }
        }
    }

    return dettaglio_prezzi_visibili;
}

/**
* Restituisce lo sconto registrato del listino registrato per l\'articolo-anagrafica.
*/
function getScontoListino() {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_listino = null;
    for (const dettaglio of data) {
        if (dettaglio.sconto_percentuale_listino != null) {
            dettaglio_listino = dettaglio;
            continue;
        }
    }

    return dettaglio_listino ? parseFloat(dettaglio_listino.sconto_percentuale_listino) : 0;
}

/**
* Restituisce lo sconto registrato del listino sempre visibile dell\'articolo.
*/
function getScontoListinoVisibile(nome) {
    const data = globals.aggiunta_articolo.dettagli;
    if (!data) return null;

    let dettaglio_listino_visibile = null;
    for (const dettaglio of data) {
        if (dettaglio.nome == nome) {
            dettaglio_listino_visibile = dettaglio;
            continue;
        }
    }

    return dettaglio_listino_visibile ? parseFloat(dettaglio_listino_visibile.sconto_percentuale_listino_visibile) : 0;
}

/**
* Restituisce lo sconto registrato per una specifica quantità dell\'articolo.
*/
function getScontoPerQuantita(qta) {
    const dettaglio = getDettaglioPerQuantita(qta);

    return dettaglio ? parseFloat(dettaglio.sconto_percentuale) : 0;
}

/**
* Funzione per registrare localmente i dettagli definiti per l\'articolo in relazione ad una specifica anagrafica.
*/
function ottieniDettagliArticolo(id_articolo) {
    return $.get(globals.rootdir + "/ajax_complete.php?module=Articoli&op=dettagli_articolo&id_anagrafica='.$options['idanagrafica'].'&id_articolo=" + id_articolo + "&dir=" + direzione, function(response) {
        const data = JSON.parse(response);

        globals.aggiunta_articolo.dettagli = data;
    });
}

/**
* Funzione per verificare se il prezzo unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaPrezzoArticolo() {
    let prezzo_unitario_input = $("#prezzo_unitario");
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();

    let div = $(".prezzi");

    div.css("padding-top", "0");
    div.html("");

    let qta = $("#qta").val().toEnglish();
    let prezzo_anagrafica = getPrezzoPerQuantita(qta);
    let prezzo_listino = getPrezzoListino();
    let prezzo_std = getPrezzoScheda();
    let prezzo_last = getPrezzoUltimo();
    let prezzo_minimo = parseFloat($("#idarticolo").selectData().minimo_vendita);
    let prezzi_visibili = getPrezziListinoVisibili();

    if (prezzo_anagrafica || prezzo_listino || prezzo_std || prezzo_last || prezzo_minimo || prezzi_visibili) {
        div.html(`<table class="table table-extra-condensed table-prezzi" style="background:#eee; margin-top:-13px;"><tbody>`);
    }
    let table = $(".table-prezzi");

    if (prezzo_anagrafica) { 
        table.append(`<tr><td class="pr_anagrafica"><small>'.($options['dir'] == 'uscita' ? tr('Prezzo listino') : tr('Netto cliente')).': '.Plugins::link(($options['dir'] == 'uscita' ? 'Listino Fornitori' : 'Netto Clienti'), $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_anagrafica"><small>` + prezzo_anagrafica.toLocale() + ` ` + globals.currency + `</small></td>`);

        let tr = $(".pr_anagrafica").parent();
        if (prezzo_unitario == prezzo_anagrafica.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(\'anagrafica\')" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }

    if (prezzo_listino) {
        table.append(`<tr><td class="pr_listino"><small>'.tr('Prezzo listino').': '.Modules::link('Listini Cliente', $id_listino, tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_listino"><small>` + prezzo_listino.toLocale() + ` ` + globals.currency + `</small></td>`);

        let tr = $(".pr_listino").parent();
        if (prezzo_unitario == prezzo_listino.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(\'listino\')" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }

    if (prezzo_std) {
        table.append(`<tr><td class="pr_std"><small>'.tr('Prezzo articolo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_std"><small>` + prezzo_std.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

        let tr = $(".pr_std").parent();
        if (prezzo_unitario == prezzo_std.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(\'std\')" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
    }

    if (prezzo_last) {
        table.append(`<tr><td class="pr_last"><small>'.tr('Ultimo prezzo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_last"><small>` + prezzo_last.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

        let tr = $(".pr_last").parent();
        if (prezzo_unitario == prezzo_last.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(\'last\')" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
    }

    if (prezzo_minimo) {
        table.append(`<tr><td class="pr_minimo"><small>'.tr('Prezzo minimo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_minimo"><small>` + prezzo_minimo.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

        let tr = $(".pr_minimo").parent();
        if (prezzo_unitario == prezzo_minimo.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(\'minimo\')" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
    }

    if (prezzi_visibili) {
        let i = 0;
        for (const prezzo_visibile of prezzi_visibili) {
            i++;
            let prezzo_listino_visibile = parseFloat(prezzo_visibile.prezzo_unitario_listino_visibile);
            table.append(`<tr><td class="pr_visibile_`+ i +`"><small>'.tr('Listino visibile ').'(` + prezzo_visibile.nome + `): </small></td><td align="right" class="pr_visibile_`+ i +`"><small>` + prezzo_listino_visibile.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

            let tr = $(".pr_visibile_"+ i).parent();
            if (prezzo_unitario == prezzo_listino_visibile.toFixed(2)) {
                tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
            } else{
                tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(\'` + prezzo_visibile.nome + `\')" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
            }
        }
    }


    if (prezzo_anagrafica || prezzo_listino || prezzo_std || prezzo_last || prezzo_minimo || prezzi_visibili) {
        table.append(`</tbody></table>`);
    }
}

/**
* Funzione per verificare se lo sconto unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaScontoArticolo() {
    let qta = $("#qta").val().toEnglish();
    let prezzo_unitario_input = $("#prezzo_unitario");
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();
    let prezzo_anagrafica = getPrezzoPerQuantita(qta);
    let prezzo_listino = getPrezzoListino();
    let prezzi_visibili = getPrezziListinoVisibili();
    let sconto_previsto = 0;


    if (prezzo_unitario == prezzo_anagrafica.toFixed(2)) {
        sconto_previsto = getScontoPerQuantita(qta);
    } else if (prezzo_unitario == prezzo_listino.toFixed(2)) {
        sconto_previsto = getScontoListino();
    } else {
        for (const prezzo_visibile of prezzi_visibili) {
            let prezzo_listino_visibile = parseFloat(prezzo_visibile.prezzo_unitario_listino_visibile);
            if (prezzo_unitario == prezzo_listino_visibile.toFixed(2)) {
                sconto_previsto = getScontoListinoVisibile(prezzo_visibile.nome);
            }
        }
    }

    let sconto_input = $("#sconto");
    let sconto = sconto_input.val().toEnglish();

    let div = $(".sconto");
    if (sconto_previsto === 0 || sconto_previsto === sconto) {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("margin-top", "-13px");
    div.html(`<small class="label label-info">'.tr('Sconto suggerito').': ` + sconto_previsto.toLocale()  + `%<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaScontoArticolo()"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);
}

/**
* Funzione per aggiornare il prezzo unitario sulla base dei valori automatici.
*/
function aggiornaPrezzoArticolo(aggiorna = "") {
    let prezzo_previsto = 0;
    if (aggiorna == "listino") {
        prezzo_previsto = getPrezzoListino();
    } else if (aggiorna == "anagrafica") {
        let qta = $("#qta").val().toEnglish();
        prezzo_previsto = getPrezzoPerQuantita(qta);
    } else if (aggiorna == "std") {
        prezzo_previsto = getPrezzoScheda();
    } else if (aggiorna == "last") {
        prezzo_previsto = getPrezzoUltimo();
    } else if (aggiorna == "minimo") {
        prezzo_previsto = parseFloat($("#idarticolo").selectData().minimo_vendita);
    } else if (aggiorna != "") {
        prezzo_previsto = getPrezziListinoVisibili(aggiorna);
    } else {
        // Inserisco il prezzo più basso tra listino e netto cliente, se mancanti imposto il prezzo della scheda articolo
        let qta = $("#qta").val().toEnglish();
        prezzo1 = getPrezzoPerQuantita(qta);
        prezzo2 = getPrezzoListino();
        prezzo3 = getPrezzoScheda();
        prezzo_previsto = (!prezzo1 ? prezzo2 : (!prezzo2 ? prezzo1 : (prezzo1 > prezzo2 ? prezzo2 : prezzo1)));
        prezzo_previsto = (prezzo_previsto ? prezzo_previsto : prezzo3);
    } 

    $("#prezzo_unitario").val(prezzo_previsto).trigger("change");
    $("#sconto").val(0).trigger("change");

    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        aggiorna_guadagno();
    }
}

/**
* Funzione per aggiornare lo sconto unitario sulla base dei valori automatici.
*/
function aggiornaScontoArticolo() {
    let qta = $("#qta").val().toEnglish();
    let prezzo_unitario_input = $("#prezzo_unitario");
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();
    let prezzo_anagrafica = getPrezzoPerQuantita(qta);
    let prezzo_listino = getPrezzoListino();
    let prezzi_visibili = getPrezziListinoVisibili();
    let sconto_previsto = 0;


    if (prezzo_unitario == prezzo_anagrafica.toFixed(2)) {
        sconto_previsto = getScontoPerQuantita(qta);
    } else if (prezzo_unitario == prezzo_listino.toFixed(2)) {
        sconto_previsto = getScontoListino();
    } else {
        for (const prezzo_visibile of prezzi_visibili) {
            let prezzo_listino_visibile = parseFloat(prezzo_visibile.prezzo_unitario_listino_visibile);
            if (prezzo_unitario == prezzo_listino_visibile.toFixed(2)) {
                sconto_previsto = getScontoListinoVisibile(prezzo_visibile.nome);
            }
        }
    }

    $("#sconto").val(sconto_previsto).trigger("change");
    input("tipo_sconto").set("PRC").trigger("change");

    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        aggiorna_guadagno();
    }
}

/**
* Funzione per l\'aggiornamento dinamico della quantità minima per l\'articolo.
*/
function aggiornaQtaMinima() {
    let qta_minima = parseFloat($("#qta_minima").val());
    let qta = $("#qta").val().toEnglish();

    if (qta_minima === 0) {
        return;
    }

    let parent = $("#qta").closest("div").parent();
    let div = parent.find("div[id*=errors]");

    div.html("<small>'.tr('Quantità minima').': " + qta_minima.toLocale() + "</small>");
    if (qta < qta_minima) {
        parent.addClass("has-error");
        div.addClass("text-danger").removeClass("text-success");
    } else {
        parent.removeClass("has-error");
        div.removeClass("text-danger").addClass("text-success");
    }
}

function verificaMinimoVendita() {
    let prezzo_unitario_input = $("#prezzo_unitario");
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();
    let minimo_vendita = parseFloat($("#idarticolo").selectData().minimo_vendita);

    let div = $(".minimo_vendita");
    div.css("margin-top", "-13px");
    if (prezzo_unitario < minimo_vendita) {
        if (input("blocca_minimo_vendita").get() == "0") {
            div.html(`<p class="label-warning">'.tr('Attenzione:<br>valore inferiore al prezzo minimo di vendita ').'` + minimo_vendita.toLocale() + ` ` + globals.currency + `</p>`);
        }
    } else {
        div.html("");
    }
    if (prezzo_unitario <= minimo_vendita) {
        if (input("blocca_minimo_vendita").get() == "1") {
            prezzo_unitario_input.val(minimo_vendita);
            div.html(`<p class="label-warning">'.tr('Attenzione:<br>non è possibile inserire un prezzo inferiore al prezzo minimo di vendita ').'` + minimo_vendita.toLocale() + ` ` + globals.currency + `</p>`);
        } 
    }
}
</script>';
