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

// Aggiunta della classe per il modulo
echo '<div class="module-aggiornamenti">';

// Schermata di caricamento delle informazioni
echo '
    <button class="btn btn-lg btn-block btn-primary" onclick="avviaControlli(this);">
        <i class="fa fa-cog"></i> '.tr('Avvia controlli').'
</button>

<div id="controlli"></div>

<div id="progress">
    <div class="progress" data-percentage="0%">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
            <span>0%</span>
        </div>
    </div>
    <hr>
    <p class="text-center">'.tr('Operazione in corso').': <span id="operazione"></span></p>
</div>

<div class="alert alert-info" id="card-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>

<div class="alert alert-success hidden" id="no-problems">
    <i class="fa fa-check"></i> '.tr('Non sono stati rilevati problemi!').'
</div>

<script>
var content = $("#controlli");
var loader = $("#progress");
var progress = $("#card-loading");

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
            let card = initcard(controllo, success, records);
            for(const record of records) {
                addRiga(controllo, card, record);
            }

            // Visualizzazione delle informazioni
            $("#controlli").append(card);
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
* Formatta i bytes in formato leggibile
* @param bytes
* @param decimals
*/
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return "0 Bytes";

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ["Bytes", "KB", "MB", "GB", "TB"];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
}

/**
*
* @param controllo
* @param success
* @param records
* @returns {*|jQuery|HTMLElement}
*/
function initcard(controllo, success, records) {
    let cssClass = "";
    let icon = "minus";
    if (success) {
        cssClass = "card-success";
        icon = "check text-success";
    }

    let card = `<div class="card ` + cssClass + `" id="controllo-` + controllo["id"] + `">
    <div class="card-header with-border">
        <h3 class="card-title">` + controllo["name"];

    // Aggiungi badge inline per il controllo IntegritaFile
    if (controllo["class"] === "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile" && !success && records.length > 0) {
        let orphanFiles = records.filter(r => r.tipo === "file_orfano");
        let orphanRecords = records.filter(r => r.tipo === "file_mancante");

        if (orphanFiles.length > 0 || orphanRecords.length > 0) {
            if (orphanFiles.length > 0) {
                let totalSize = 0;
                orphanFiles.forEach(function(file) {
                    if (file.dimensione_bytes) {
                        totalSize += parseInt(file.dimensione_bytes);
                    }
                });
                let sizeFormatted = totalSize > 0 ? formatBytes(totalSize) : "";
                card += ` <span class="badge badge-danger ml-2">
                    <i class="fa fa-trash mr-1"></i>${orphanFiles.length} file${sizeFormatted ? " - " + sizeFormatted : ""}
                </span>`;
            }

            if (orphanRecords.length > 0) {
                card += ` <span class="badge badge-warning ml-2">
                    <i class="fa fa-database mr-1"></i>${orphanRecords.length} record
                </span>`;
            }
        }
    }

    card += `</h3>
        <div class="card-tools pull-right">`;

    // Aggiungi pulsanti azione globale se il controllo lo supporta e ci sono record
    if (!success && records.length > 0 && hasGlobalActions(controllo)) {
        // Usa la stessa funzione per tutti i controlli, incluso IntegritaFile
        card += `
            <button type="button" class="btn btn-success btn-sm" data-controllo-id="` + controllo["id"] + `" data-controllo-class="` + controllo["class"] + `" onclick="eseguiAzioneGlobale(this)">
                <i class="fa fa-check-circle"></i> '.tr('Risolvi tutti i conflitti').'
            </button>`;
    }

    card += `
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fa fa-` + icon + `"></i>
            </button>
        </div>
    </div>`

    if (!success) {
        // Verifica se ci sono opzioni per le singole righe
        let hasRowOptions = records.length > 0 && records[0].options && records[0].options.length > 0;

        card += `
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm table-bordered">
                <thead>
                    <tr>
                        <th width="30%">'.tr('Record').'</th>
                        <th>'.tr('Descrizione').'</th>`;

        if (hasRowOptions) {
            card += `<th class="text-center" width="12%">'.tr('Opzioni').'</th>`;
        }

        card += `
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>`
    }

        card += `
</div>`;

    return $(card);
}

/**
*
* @param controllo
* @param card
* @param record
*/
function addRiga(controllo, card, record) {
    let body = card.find("tbody");
    let hasOptions = record.options && record.options.length > 0;

    // Generazione riga
    let riga = `<tr class="` + record.type + `" id="controllo-` + controllo["id"] + `-` + record.id + `">
    <td>` + record.nome + `</td>
    <td>` + record.descrizione + `</td>`;

    if (hasOptions) {
        riga += `<td class="text-center"></td>`;
    }

    riga += `</tr>`;
    riga = $(riga);

    // Generazione opzioni solo se presenti
    if (hasOptions) {
        const options_columns = riga.find("td").last();
        record.options.forEach(function (option, id){
             let button = `<button type="button" class="btn btn-` + option.color + ` btn-sm ">
        <i class="` + option.icon + `"></i> ` + option.name + `
    </buttton>`;
            button = $(button);

            button.on("click", function () {
                option.params.id = id;
                eseguiAzione(controllo, record, option.params);
            });

            options_columns.append(button);
        });
    }

    body.append(riga);
}

/**
* Verifica se un controllo supporta azioni globali
* @param controllo
* @returns {boolean}
*/
function hasGlobalActions(controllo) {
    // Lista dei controlli che supportano azioni globali
    const controlliConAzioniGlobali = [
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoContiRagioneSociale",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\ReaValidi",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\ColonneDuplicateViste",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PluginDuplicati",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\TabelleLanguage",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile"
    ];

    return controlliConAzioniGlobali.includes(controllo["class"]);
}

/**
* Restituisce il messaggio di conferma specifico per ogni controllo
* @param controlloClass
* @returns {object}
*/
function getMessaggioConferma(controlloClass) {
    const messaggi = {
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoContiRagioneSociale": {
            titolo: "'.tr('Conferma risoluzione conflitti').'",
            descrizione: "'.tr('Sei sicuro di voler risolvere tutti i conflitti?').'",
            operazioni: [
                "'.tr('Creerà nuovi conti per le anagrafiche con conflitti multipli').'",
                "'.tr('Aggiornerà i movimenti contabili collegati').'",
                "'.tr('Eliminerà i conti vuoti non più utilizzati').'",
                "'.tr('Non può essere annullata').'"
            ]
        },
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\ReaValidi": {
            titolo: "'.tr('Conferma rimozione codici REA').'",
            descrizione: "'.tr('Sei sicuro di voler rimuovere tutti i codici REA non validi?').'",
            operazioni: [
                "'.tr('Rimuoverà tutti i codici REA che non rispettano il formato corretto (XX-NNNNNN)').'",
                "'.tr('I codici REA verranno svuotati per le anagrafiche interessate').'",
                "'.tr('Le anagrafiche rimarranno invariate, solo il campo REA verrà pulito').'",
                "'.tr('Non può essere annullata').'"
            ]
        },
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\ColonneDuplicateViste": {
            titolo: "'.tr('Conferma risoluzione conflitti viste').'",
            descrizione: "'.tr('Sei sicuro di voler risolvere tutti i conflitti delle viste duplicate?').'",
            operazioni: [
                "'.tr('Eliminerà i record duplicati nelle tabelle zz_views e zz_views_lang').'",
                "'.tr('Manterrà sempre il record più recente (con ID maggiore) ed eliminerà gli altri').'",
                "'.tr('I record eliminati non potranno essere recuperati').'",
                "'.tr('Le viste duplicate verranno rimosse definitivamente dal database').'",
                "'.tr('Non può essere annullata').'"
            ]
        },
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PluginDuplicati": {
            titolo: "'.tr('Conferma risoluzione conflitti plugin').'",
            descrizione: "'.tr('Sei sicuro di voler risolvere tutti i conflitti dei plugin duplicati?').'",
            operazioni: [
                "'.tr('Eliminerà i record duplicati nelle tabelle zz_plugins e zz_plugins_lang').'",
                "'.tr('Manterrà sempre il record più recente (con ID maggiore) ed eliminerà gli altri').'",
                "'.tr('I record eliminati non potranno essere recuperati').'",
                "'.tr('I plugin duplicati verranno rimossi definitivamente dal database').'",
                "'.tr('Non può essere annullata').'"
            ]
        },
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\TabelleLanguage": {
            titolo: "'.tr('Conferma correzione tabelle multilingua').'",
            descrizione: "'.tr('Sei sicuro di voler correggere tutti i record mancanti nelle tabelle multilingua?').'",
            operazioni: [
                "'.tr('Inserirà i record mancanti nelle tabelle _lang per tutte le lingue disponibili').'",
                "'.tr('Popolerà i campi con valori di default presi dalle tabelle principali quando possibile').'",
                "'.tr('Non modificherà i record esistenti, aggiungerà solo quelli mancanti').'",
                "'.tr('Migliorerà la coerenza del database multilingua').'",
                "'.tr('Operazione sicura e reversibile').'"
            ]
        },
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile": {
            titolo: "'.tr('Conferma operazioni di pulizia').'",
            descrizione: "'.tr('Sei sicuro di voler procedere con le operazioni di pulizia selezionate?').'",
            operazioni: [
                "'.tr('RIMOZIONE FILE ORFANI: Rimuoverà definitivamente tutti i file presenti nel filesystem ma non registrati nel database').'",
                "'.tr('RIMOZIONE RECORD ORFANI: Rimuoverà dal database tutti i record che puntano a file fisici inesistenti').'",
                "'.tr('I file e record eliminati non potranno essere recuperati').'",
                "'.tr('Libererà spazio su disco e pulirà il database da riferimenti non validi').'",
                "'.tr('Non può essere annullata').'"
            ]
        }
    };

    return messaggi[controlloClass] || messaggi["Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoContiRagioneSociale"];
}

/**
* Esegue un\'azione globale su tutti i record di un controllo
* @param buttonElement
*/
function eseguiAzioneGlobale(buttonElement) {
    let button = $(buttonElement);
    let controlloId = button.data("controllo-id");
    let controlloClass = button.data("controllo-class");

    // Ottieni il messaggio specifico per questo controllo
    let messaggio = getMessaggioConferma(controlloClass);

    // Genera la lista delle operazioni
    let operazioniHtml = "";
    messaggio.operazioni.forEach(function(operazione) {
        operazioniHtml += `<li>${operazione}</li>`;
    });

    // Crea modal di conferma con lo stile del gestionale
    let modalHtml = `
        <div class="modal fade" id="modal-conferma-risoluzione" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-exclamation-triangle text-warning"></i>
                            ${messaggio.titolo}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>${messaggio.descrizione}</p>
                        <div class="alert alert-warning">
                            <i class="fa fa-info-circle"></i>
                            '.tr('Questa operazione:').'
                            <ul class="mb-0 mt-2">
                                ${operazioniHtml}
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" style="float: left;">
                            <i class="fa fa-times"></i> '.tr('Annulla').'
                        </button>
                        <button type="button" class="btn btn-warning" id="conferma-risoluzione" style="float: right;">
                            <i class="fa fa-check"></i> '.tr('Procedi').'
                        </button>
                        <div style="clear: both;"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Rimuovi modal esistente se presente
    $("#modal-conferma-risoluzione").remove();

    // Aggiungi modal al DOM
    $("body").append(modalHtml);

    // Mostra modal con configurazione per evitare chiusura accidentale
    $("#modal-conferma-risoluzione").modal({
        backdrop: "static",
        keyboard: false,
        show: true
    });

    // Gestisci click su conferma
    $("#conferma-risoluzione").on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();

        let confirmButton = $(this);
        let restoreConfirm = buttonLoading(confirmButton);

        // Disabilita il pulsante di annulla durante l\'operazione
        $("#modal-conferma-risoluzione .btn-default").prop("disabled", true);

        // Determina i parametri da passare in base al tipo di controllo
        let params = {};
        if (controlloClass === "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile") {
            params = {action: "remove_all_both"};
        }

        eseguiRisoluzioneGlobale(button, controlloId, controlloClass, function() {
            // Callback di successo: ripristina pulsante di conferma e chiudi modal
            buttonRestore(confirmButton, restoreConfirm);
            $("#modal-conferma-risoluzione .btn-default").prop("disabled", false);
            $("#modal-conferma-risoluzione").modal("hide");
        }, function() {
            // Callback di errore: ripristina pulsanti
            buttonRestore(confirmButton, restoreConfirm);
            $("#modal-conferma-risoluzione .btn-default").prop("disabled", false);
        }, params);

        return false;
    });
}

/**
* Esegue effettivamente la risoluzione globale
*/
function eseguiRisoluzioneGlobale(button, controlloId, controlloClass, successCallback, errorCallback, params = {}) {
    let restore = buttonLoading(button);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-action-global",
            controllo: controlloClass,
            params: params,
        },
        success: function(results) {
            // Rimuovi tutte le righe del controllo
            $("#controllo-" + controlloId + " tbody tr").remove();

            // Nascondi il pulsante di azione globale
            button.hide();

            // Mostra messaggio di successo discreto
            let successMessage = `
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-check"></i> '.tr('Conflitti risolti con successo').'
                </div>
            `;

            $("#controllo-" + controlloId + " .card-body").html(successMessage);

            buttonRestore(button, restore);

            // Chiama il callback di successo
            if (typeof successCallback === "function") {
                successCallback();
            }
        },
        error: function(xhr, r, error) {
            let errorMessage = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error.message : error;

            let errorHtml = `
                <div class="alert alert-danger">
                    <h4><i class="fa fa-exclamation-triangle"></i> '.tr('Errore durante la risoluzione').'</h4>
                    <p>'.tr('Si è verificato un errore').': ${errorMessage}</p>
                </div>
            `;

            $("#controllo-" + controlloId + " .card-body").prepend(errorHtml);
            buttonRestore(button, restore);

            // Chiama il callback di errore
            if (typeof errorCallback === "function") {
                errorCallback();
            }
        }
    });
}
</script>

</div>';
