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

use Models\Setting;

include_once __DIR__.'/../../core.php';

$ricerca = get('search');
$gruppi = Setting::selectRaw('sezione AS nome, COUNT(id) AS numero')
    ->groupBy(['sezione'])
    ->orderBy('sezione')
    ->get();

echo '
<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="input-group">
            <input type="text" class="form-control form-control-lg text-center" id="ricerca_impostazioni" placeholder="'.tr('Cerca').'...">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" id="search">
                        <span class="fa fa-search"></span>
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <button class="btn btn-warning hidden" type="button" id="riprova_salvataggi" onclick="riprovaSalvataggio()">
                <span class="fa fa-save"></span> '.tr('Riprova salvataggi falliti').'
            </button>
        </div>
    </div>
</div>

<hr>';

foreach ($gruppi as $key => $gruppo) {
    echo '
<!-- Impostazioni della singola sezione -->
<div class="card card-primary collapsed-card" title="'.$gruppo->nome.'">
    <div class="card-header clickable" title="'.$gruppo->nome.'" id="impostazioni-'.$key.'">
        <div class="card-title">'.tr('_SEZIONE_', [
        '_SEZIONE_' => $gruppo->nome,
    ]).'</div>
        <div class="card-tools pull-right">
            <div class="badge">'.$gruppo->numero.'</div>
        </div>
    </div>

    <div class="card-body row"></div>
</div>';
}

echo '
<script>

$(document).ready(function() {
    $("#ricerca_impostazioni").keyup(function(key) {
        if (key.which == 13) {
            $("#search").click();
        }
    });
});

globals.impostazioni = {
    errors: {},
    numero_ricerche: 0,
};

$("[id^=impostazioni]").click(function() {
    caricaSezione(this);
});

$("#search").on("click", function(){
    const ricerca = $("#ricerca_impostazioni").val();
    const icon = $(this).parent().find("span");
    $(".card").removeClass("hidden");

    // Segnalazione ricerca in corso
    globals.impostazioni.numero_ricerche = globals.impostazioni.numero_ricerche + 1;
    // Impostazione icona di caricamento
    icon
        .addClass("fa-spinner fa-spin")
        .removeClass("fa-search")

    if (ricerca) {
        $.get("'.$structure->fileurl('actions.php').'?id_module='.$id_module.'&op=ricerca&search=" + ricerca, function(data) {
            // Segnalazione ricerca completata
            globals.impostazioni.numero_ricerche = globals.impostazioni.numero_ricerche - 1;

            // Impostazione icona di ricerca
            if (globals.impostazioni.numero_ricerche === 0){
                icon
                    .removeClass("fa-spinner fa-spin")
                    .addClass("fa-search")
            }

            $(".card-header").addClass("hidden");

            let sezioni = JSON.parse(data);
            for(const sezione of sezioni){
                $(`.card-header[title="` + sezione + `"]`).removeClass("hidden")
                let card = $(`.card-header[title="` + sezione + `"]`).removeClass("hidden")
                caricaSezione(card);
            }
        });
    }
})

function caricaSezione(header) {
    let card = $(header).closest(".card");
    card.toggleClass("collapsed-card");

    // Controllo sul caricamento già effettuato
    let container = card.find(".card-body");
    if (container.html()){
        return ;
    }

    // Caricamento della sezione di impostazioni
    let sezione = card.attr("title");
    localLoading(container, true);
    return $.get("'.$structure->fileurl('sezione.php').'?id_module='.$id_module.'&sezione=" + sezione, function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

function salvaImpostazione(id, valore){
    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "POST",
        dataType: "JSON",
        data: {
            op: "salva",
            id_module: globals.id_module,
            id: id,
            valore: valore,
        },
        success: function(data) {
            renderMessages();

            if(!data.result) {
                globals.impostazioni.errors[id] = valore;
                $("#riprova_salvataggi").removeClass("hidden");
            }
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio dei dati').'", "error");
        }
    });
}

function riprovaSalvataggio() {
    const impostazioni = JSON.parse(JSON.stringify(globals.impostazioni.errors));;
    globals.impostazioni.errors = {};

    $("#riprova_salvataggi").addClass("hidden");
    for ([id, valore] of Object.entries(impostazioni)) {
        salvaImpostazione(id, valore);
    }
}
</script>';

if (!empty($ricerca)) {
    echo '
<script>$("#ricerca_impostazioni").change();</script>';
}
