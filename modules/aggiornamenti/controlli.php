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

// Schermata di caricamento delle informazioni
echo '
    <button class="btn btn-lg btn-block" onclick="avviaControlli(this);">
        <i class="fa fa-cog"></i> '.tr('Avvia controlli').'
</button>

<div id="controlli"></div>

<div id="progress">
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
            <span>0%</span>
        </div>
    </div>
    <hr>
    <p class="text-center">'.tr('Operazione in corso').': <span id="operazione"></span></p>
</div>

<div class="alert alert-info" id="box-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>

<div class="alert alert-success hidden" id="no-problems">
    <i class="fa fa-check"></i> '.tr('Non sono stati rilevati problemi!').'
</div>

<script>
var content = $("#controlli");
var loader = $("#progress");
var progress = $("#box-loading");

$(document).ready(function () {
    loader.hide();
    progress.hide();
})

/**
*
* @param button
*/
function avviaControlli(button) {
    var restore = buttonLoading(button);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "GET",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-disponibili",
        },
        success: async function(controlli) {
            // Ripristino pulsante
            buttonRestore(button, restore);
            $(button).hide();

            // Visualizzazione loader
            loader.show();
            progress.show();

            let number = 0;
            let total = controlli.length;
            for (const controllo of controlli) {
                // Informazione grafica di progressione
                number ++;
                let percentage = Math.round(number / total * 100);
                percentage = percentage > 100 ? 100 : percentage;
                setPercentage(percentage);

                $("#operazione").text(controllo["name"]);

                // Avvio dei controlli
                await avviaControllo(controllo);
            }

            setPercentage(100);

            // Visualizzazione loader
            loader.hide();
            progress.hide();

            // Messaggio informativo in caso di nessun problema
            if ($("#controlli").html() === "") {
                $("#no-problems").removeClass("hidden");
            }
        },
        error: function(xhr) {
            alert("'.tr('Errore').': " + xhr.responseJSON.error.message);

            buttonRestore(button, restore);
        }
    });
}

/**
*
* @param controllo
*/
function avviaControllo(controllo) {
    return $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-check",
            controllo: controllo["class"],
        },
        success: function(records) {
            let success = false;
            if (records.length === 0) {
                success = true;
            }

            // Creazione pannello informativo e aggiunta righe
            let panel = initPanel(controllo, success);
            for(const record of records) {
                addRiga(controllo, panel, record);
            }

            // Visualizzazione delle informazioni
            $("#controlli").append(panel);
        },
        error: function(xhr, r, error) {
            alert("'.tr('Errore').': " + error);
        }
    });
}

/**
*
* @param controllo
* @param records
* @param params
*/
function eseguiAzione(controllo, records, params) {
    return $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-action",
            controllo: controllo["class"],
            records: records,
            params: params,
        },
        success: function(results) {
            $("#controllo-" + controllo["id"] + "-" + records.id).remove();
        },
        error: function(xhr, r, error) {
            alert("'.tr('Errore').': " + error);
        }
    });
}

/**
*
* @param percent
*/
function setPercentage(percent) {
    $("#progress .progress-bar").width(percent + "%");
    $("#progress .progress-bar span").text(percent + "%");
}

/**
*
* @param controllo
* @param success
* @returns {*|jQuery|HTMLElement}
*/
function initPanel(controllo, success) {
    let cssClass = "";
    let icon = "minus";
    if (success) {
        cssClass = "box-success";
        icon = "check text-success";
    }

    let panel = `<div class="box ` + cssClass + `" id="controllo-` + controllo["id"] + `">
    <div class="box-header with-border">
        <h3 class="box-title">` + controllo["name"] + `</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-` + icon + `"></i>
            </button>
        </div>
    </div>`

    if (!success) {
        panel += `
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed table-bordered">
                <thead>
                    <tr>
                        <th width="15%">'.tr('Record').'</th>
                        <th>'.tr('Descrizione').'</th>
                        <th class="text-center" width="15%">'.tr('Opzioni').'</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>`
    }

        panel += `
</div>`;

    return $(panel);
}

/**
*
* @param controllo
* @param panel
* @param record
*/
function addRiga(controllo, panel, record) {
    let body = panel.find("tbody");

    // Generazione riga
    let riga = `<tr class="` + record.type + `" id="controllo-` + controllo["id"] + `-` + record.id + `">
    <td>` + record.nome + `</td>
    <td>` + record.descrizione + `</td>
    <td></td>
</tr>`;
    riga = $(riga);

    // Generazione opzioni
    const options_columns = riga.find("td").last();
    record.options.forEach(function (option, id){
         let button = `<button type="button" class="btn btn-` + option.color + `">
    <i class="` + option.icon + `"></i> ` + option.name + `
</buttton>`;
        button = $(button);

        button.on("click", function () {
            option.params.id = id;
            eseguiAzione(controllo, record, option.params);
        });

        options_columns.append(button);
    });

    body.append(riga);
}
</script>';
