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

$result['idarticolo'] = isset($result['idarticolo']) ? $result['idarticolo'] : null;
$qta_minima = 0;

// Articolo
if (empty($result['idarticolo'])) {
    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$result['idarticolo'].'", "ajax-source": "articoli", "select-options": '.json_encode($options['select-options']['articoli']).', "icon-after": "add|'.Modules::get('Articoli')['id'].'" ]}
        </div>
    </div>

    <input type="hidden" name="id_dettaglio_fornitore" id="id_dettaglio_fornitore" value="">';
} else {
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
    <p><strong>'.tr('Articolo').':</strong> '.$articolo['codice'].' - '.$articolo['descrizione'].'.</p>
    <input type="hidden" name="idarticolo" id="idarticolo" value="'.$articolo['id'].'">

    <script>
        $(document).ready(function (){
            ottieniDettagliArticolo("'.$articolo['id'].'").then(function (){
                verificaPrezzoArticolo();
                verificaScontoArticolo();
            });
        });
    </script>';
}

echo '
    <input type="hidden" name="qta_minima" id="qta_minima" value="'.$qta_minima.'">';

// Selezione impianto per gli Interventi
if ($module['name'] == 'Interventi') {
    echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Impianto su cui installare').'", "name": "idimpianto", "value": "'.$idimpianto.'", "ajax-source": "impianti-intervento", "select-options": '.json_encode($options['select-options']['impianti']).' ]}
    </div>
</div>';
}

echo App::internalLoad('riga.php', $result, $options);

// Informazioni aggiuntive
if ($module['name'] != 'Contratti' && $module['name'] != 'Preventivi') {
    $disabled = empty($result['idarticolo']);

    echo '
<div class="row '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'" id="prezzi_articolo">
    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezziacquisto\').toggleClass(\'hide\'); $(\'#prezziacquisto\').load(\''.base_path()."/ajax_complete.php?module=Articoli&op=getprezziacquisto&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-search"></i> '.tr('Ultimi prezzi di acquisto').'
        </button>
        <div id="prezziacquisto" class="hide"></div>
    </div>

    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezzi\').toggleClass(\'hide\'); $(\'#prezzi\').load(\''.base_path()."/ajax_complete.php?module=Articoli&op=getprezzi&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-search"></i> '.tr('Ultimi prezzi al cliente').'
        </button>
        <div id="prezzi" class="hide"></div>
    </div>

    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezzivendita\').toggleClass(\'hide\'); $(\'#prezzivendita\').load(\''.base_path()."/ajax_complete.php?module=Articoli&op=getprezzivendita&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-search"></i> '.tr('Ultimi prezzi di vendita').'
        </button>
        <div id="prezzivendita" class="hide"></div>
    </div>
</div>
<br>';
}

echo '
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

$("#tipo_sconto").on("change", function() {
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
    });

    $("#costo_unitario").val($data.prezzo_acquisto);
    $("#descrizione_riga").val($data.descrizione);

    if (direzione === "entrata") {
        if($data.idiva_vendita) {
            $("#idiva").selectSetNew($data.idiva_vendita, $data.iva_vendita);
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
    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        aggiorna_guadagno();
    }
});

$(document).on("change", "input[name^=qta], input[name^=prezzo_unitario], input[name^=sconto]", function() {
    verificaPrezzoArticolo();
    verificaScontoArticolo();
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
        if (dettaglio.minimo == null && dettaglio.massimo == null) {
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
    let qta = $("#qta").val().toEnglish();
    let prezzo_previsto = getPrezzoPerQuantita(qta);

    let prezzo_unitario_input = $("#prezzo_unitario");
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();

    let div = prezzo_unitario_input.closest("div").parent().find("div[id*=errors]");

    if (prezzo_previsto === prezzo_unitario) {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("padding-top", "5px");
    div.html(`<small class="label label-warning" >'.tr('Prezzo suggerito').': ` + prezzo_previsto.toLocale() + globals.currency + `<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo()"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);
}

/**
* Funzione per verificare se lo sconto unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaScontoArticolo() {
    let qta = $("#qta").val().toEnglish();
    let sconto_previsto = getScontoPerQuantita(qta);

    let sconto_input = $("#sconto");
    let sconto = sconto_input.val().toEnglish();

    let div = sconto_input.parent().next();
    if (sconto_previsto === 0 || sconto_previsto === sconto || $("#tipo_sconto").val() === "UNT") {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("padding-top", "5px");
    div.html(`<small class="label label-warning" >'.tr('Sconto suggerito').': ` + sconto_previsto.toLocale()  + `%<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaScontoArticolo()"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);
}

/**
* Funzione per aggiornare il prezzo unitario sulla base dei valori automatici.
*/
function aggiornaPrezzoArticolo() {
    let qta = $("#qta").val().toEnglish();
    let prezzo_previsto = getPrezzoPerQuantita(qta);

    $("#prezzo_unitario").val(prezzo_previsto).trigger("change");
}

/**
* Funzione per aggiornare lo sconto unitario sulla base dei valori automatici.
*/
function aggiornaScontoArticolo() {
    let qta = $("#qta").val().toEnglish();
    let sconto_previsto = getScontoPerQuantita(qta);

    $("#sconto").val(sconto_previsto).trigger("change");
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
</script>';
