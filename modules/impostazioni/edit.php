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
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="search-container mb-4">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-primary border-primary text-white">
                            <i class="fa fa-cog"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control form-control-lg" id="ricerca_impostazioni" placeholder="'.tr('Cerca impostazioni').'...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="search">
                            <span class="fa fa-search"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 text-right">
            <button class="btn btn-warning hidden" type="button" id="riprova_salvataggi" onclick="riprovaSalvataggio()">
                <span class="fa fa-save"></span> '.tr('Riprova salvataggi falliti').'
            </button>
        </div>
    </div>
</div>';

echo '<div class="row">
    <div class="col-md-12">
        <div class="settings-container modules-impostazioni">
';

foreach ($gruppi as $key => $gruppo) {
    echo '
<!-- Impostazioni della singola sezione -->
<div class="card card-primary collapsed-card mb-3">
    <div class="card-header clickable" data-title="'.$gruppo->nome.'" id="impostazioni-'.$key.'">
        <div class="card-title">
            <i class="fa fa-sliders mr-2"></i>
            '.tr('_SEZIONE_', [
            '_SEZIONE_' => $gruppo->nome,
        ]).'
        </div>
        <div class="card-tools pull-right">
            <div class="badge">'.$gruppo->numero.'</div>
        </div>
    </div>

    <div class="card-body row"></div>
</div>';
}

echo '
        </div>
    </div>
</div>

<script>

$(document).ready(function() {
    // Animazione per le card al caricamento
    $(".card").each(function(index) {
        $(this).delay(100 * index).animate({opacity: 1}, 300);
    });

    // Gestione ricerca con tasto invio
    $("#ricerca_impostazioni").keyup(function(key) {
        if (key.which == 13) {
            $("#search").click();
        }
    });

    // Focus automatico sul campo di ricerca
    $("#ricerca_impostazioni").focus();
});

globals.impostazioni = {
    errors: {},
    numero_ricerche: 0,
    filtered_settings: null
};

$("[id^=impostazioni]").click(function() {
    caricaSezione(this);
});

$("#search").on("click", function(){
    const ricerca = $("#ricerca_impostazioni").val();
    const icon = $(this).parent().find("span");

    // Segnalazione ricerca in corso
    globals.impostazioni.numero_ricerche = globals.impostazioni.numero_ricerche + 1;

    // Impostazione icona di caricamento
    icon
        .addClass("fa-spinner fa-spin")
        .removeClass("fa-search");

    // Mostra tutte le card prima di filtrare
    $(".card").removeClass("hidden");

    if (ricerca) {
        // Aggiungi un effetto di transizione durante la ricerca
        $(".settings-container").css("opacity", "0.7");

        $.get("'.$structure->fileurl('actions.php').'?id_module='.$id_module.'&op=ricerca&search=" + ricerca, function(data) {
            // Segnalazione ricerca completata
            globals.impostazioni.numero_ricerche = globals.impostazioni.numero_ricerche - 1;

            // Impostazione icona di ricerca
            if (globals.impostazioni.numero_ricerche === 0){
                icon
                    .removeClass("fa-spinner fa-spin")
                    .addClass("fa-search");
            }

            // Nascondi tutte le card
            $(".card").addClass("hidden");

            // Mostra solo le card corrispondenti alla ricerca
            let risultati = JSON.parse(data);
            let sezioni = Object.keys(risultati);

            // Se non ci sono risultati, mostra un messaggio
            if (sezioni.length === 0) {
                let searchText = $("#ricerca_impostazioni").val();
                $(".settings-container").html(\'<div class="alert alert-info text-center mt-4"><i class="fa fa-info-circle mr-2"></i> \' +
                    tr("Nessun risultato trovato per") + \' <strong>"\' + searchText + \'"</strong></div>\');
            } else {
                // Salva gli ID delle impostazioni da mostrare per ogni sezione
                globals.impostazioni.filtered_settings = risultati;

                // Mostra le sezioni trovate con un effetto di fade-in
                for(const sezione of sezioni){
                    const card = $(`.card-header[data-title="` + sezione + `"]`).closest(".card");
                    card.removeClass("hidden")
                        .css("opacity", "0")
                        .animate({opacity: 1}, 300);

                    // Carica la sezione e mostra solo le impostazioni corrispondenti
                    caricaSezioneConFiltro(card.find(".card-header"), risultati[sezione]);
                }
            }

            // Ripristina l\'opacità del container
            $(".settings-container").css("opacity", "1");
        });
    } else {
        // Se la ricerca è vuota, mostra tutte le card
        $(".card").removeClass("hidden")
            .css("opacity", "0")
            .each(function(index) {
                $(this).delay(50 * index).animate({opacity: 1}, 200);
            });

        // Rimuovi eventuali filtri precedenti
        globals.impostazioni.filtered_settings = null;

        // Ripristina l\'icona di ricerca
        icon
            .removeClass("fa-spinner fa-spin")
            .addClass("fa-search");
    }
})

function caricaSezione(header) {
    let card = $(header).closest(".card");
    let sezione = card.find(".card-header").data("title");

    // Controllo sul caricamento già effettuato
    let container = card.find(".card-body");

    // Animazione per l\'apertura/chiusura della card
    if (card.hasClass("collapsed-card")) {
        // Se c\'è una ricerca attiva, usa la funzione di caricamento con filtro
        if (globals.impostazioni.filtered_settings && globals.impostazioni.filtered_settings[sezione]) {
            return caricaSezioneConFiltro(header, globals.impostazioni.filtered_settings[sezione]);
        }

        card.removeClass("collapsed-card");
        card.find(".card-header").addClass("bg-light");

        // Se il contenuto è già caricato, mostra con animazione
        if (container.html() && container.html().trim() !== "") {
            container.slideDown(300);
            return;
        }

        // Caricamento della sezione di impostazioni
        localLoading(container, true);
        return $.get("'.$structure->fileurl('sezione.php').'?id_module='.$id_module.'&sezione=" + sezione, function(data) {
            container.hide();
            container.html(data);
            localLoading(container, false);

            // Inizializza i tooltip per gli elementi appena caricati
            container.find("[data-toggle=\'tooltip\']").tooltip();

            // Mostra il contenuto con animazione
            container.slideDown(300);

            // Evidenzia brevemente i nuovi elementi
            container.find(".form-group").each(function(index) {
                $(this).delay(50 * index).animate({backgroundColor: "#f8f9fa"}, 300).delay(300).animate({backgroundColor: "transparent"}, 300);
            });
        });
    } else {
        // Chiudi la card con animazione
        container.slideUp(300, function() {
            card.addClass("collapsed-card");
            card.find(".card-header").removeClass("bg-light");
        });
    }
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

// Funzione per caricare una sezione e mostrare solo le impostazioni filtrate
function caricaSezioneConFiltro(header, impostazioni_filtrate) {
    let card = $(header).closest(".card");

    // Rimuovi la classe collapsed-card per espandere la sezione
    card.removeClass("collapsed-card");
    card.find(".card-header").addClass("bg-light");

    // Controllo sul caricamento già effettuato
    let container = card.find(".card-body");

    // Caricamento della sezione di impostazioni
    let sezione = card.find(".card-header").data("title");
    localLoading(container, true);

    return $.get("'.$structure->fileurl('sezione.php').'?id_module='.$id_module.'&sezione=" + sezione, function(data) {
        container.hide();
        container.html(data);
        localLoading(container, false);

        // Inizializza i tooltip per gli elementi appena caricati
        container.find("[data-toggle=\'tooltip\']").tooltip();

        // Filtra le impostazioni: nascondi quelle che non corrispondono alla ricerca
        const ids_da_mostrare = impostazioni_filtrate.map(item => item.id);

        container.find(".col-md-4").each(function() {
            const setting_id = $(this).find("[name^=\'setting[\']").attr("name");
            if (setting_id) {
                const id = parseInt(setting_id.match(/setting\[(\d+)\]/)[1]);

                // Nascondi le impostazioni che non corrispondono alla ricerca
                if (!ids_da_mostrare.includes(id)) {
                    $(this).hide();
                    $(this).next("script").hide();
                } else {
                    // Evidenzia le impostazioni trovate
                    $(this).addClass("found-setting");
                    $(this).find(".form-group").css({
                        "border-left": "3px solid #007bff",
                        "padding-left": "10px",
                        "background-color": "#f8f9fa"
                    });
                }
            }
        });

        // Mostra il contenuto con animazione
        container.slideDown(300);
    });
}
</script>';

if (!empty($ricerca)) {
    echo '
<script>
    $(document).ready(function() {
        $("#ricerca_impostazioni").val("'.$ricerca.'");
        $("#search").click();
    });
</script>';
}
