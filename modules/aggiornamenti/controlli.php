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
<div id="controlli"></div>

<div id="button-container" style="margin-top: 20px;">
    <button class="btn btn-lg btn-block btn-primary" onclick="avviaControlli(this);">
        <i class="fa fa-cog"></i> '.tr('Avvia controlli').'
    </button>
</div>

<script src="'.base_path().'/modules/aggiornamenti/src/utils.js"></script>

<div id="progress" style="display:none;">
    <div class="progress" data-percentage="0%">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
            <span>0%</span>
        </div>
    </div>
    <hr>
    <p class="text-center">'.tr('Operazione in corso').': <span id="operazione"></span></p>
</div>

<div class="alert alert-success hidden" id="no-problems">
    <i class="fa fa-check"></i> '.tr('Non sono stati rilevati problemi!').'
</div>

<script>
var content = $("#controlli");
var loader = $("#progress");
var showCollapsed = 1;

$(document).ready(function () {
    loader.hide();

    // Se showCollapsed è true, carica le card collassate senza eseguire i controlli
    if (showCollapsed) {
        caricaCardCollassate();
    }
})

/**
* Carica le card collassate senza eseguire i controlli
*/
function caricaCardCollassate() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "GET",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-ultima-esecuzione",
        },
        success: function(controlli) {
            // Crea le card collassate senza dati
            for (const controllo of controlli) {
                let card = initcard(controllo, true, []);
                $("#controlli").append(card);
            }
        },
        error: function(xhr, r, error) {
            alert("'.tr('Errore').': " + error);
        }
    });
}

/**
* Ricarica i dati dell\'ultima esecuzione per un controllo specifico
* @param id
*/
function ricaricaEsecuzione(id) {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "GET",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-ultima-esecuzione",
        },
        success: function(controlli) {
            // Trova il controllo con l\'ID corrispondente
            let controlloData = controlli.find(c => c.id == id);
            if (controlloData) {
                let cardElement = $("#controllo-" + id);
                let cardToolsDiv = cardElement.find(".card-tools");

                // Rimuovi tutte le informazioni di ultima esecuzione precedenti (div con stile font-size)
                cardToolsDiv.find("div[style*=\"font-size\"]").remove();

                // Aggiungi le nuove informazioni di ultima esecuzione
                if (controlloData["last_execution"] || controlloData["last_user"]) {
                    let infoDiv = `<div style="font-size: 0.85rem; color: #666;">`;
                    if (controlloData["last_execution"]) {
                        let dataOra = new Date(controlloData["last_execution"]);
                        let dataFormattata = dataOra.toLocaleDateString("it-IT") + " " + dataOra.toLocaleTimeString("it-IT", {hour: "2-digit", minute: "2-digit"});
                        infoDiv += `<i class="fa fa-calendar mr-2"></i>${dataFormattata}`;
                    }
                    if (controlloData["last_user"]) {
                        infoDiv += `&nbsp;&nbsp;&nbsp;<i class="fa fa-user mr-2"></i>${controlloData["last_user"]}`;
                    }
                    infoDiv += `</div>`;

                    // Inserisci prima del primo pulsante
                    cardToolsDiv.find("button").first().before(infoDiv);
                }
            }
        },
        error: function(xhr, r, error) {
            console.log("Errore nel caricamento dell\'ultima esecuzione: " + error);
        }
    });
}

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
            op: "controlli-ultima-esecuzione",
        },
        success: async function(controlli) {
            // Ripristino pulsante
            buttonRestore(button, restore);
            $(button).hide();

            // Visualizzazione loader
            loader.show();

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
                console.log("Avvio controllo:", controllo["name"]);
                await avviaControllo(controllo);
                console.log("Controllo completato:", controllo["name"]);
            }

            console.log("Tutti i controlli completati");
            setPercentage(100);

            // Visualizzazione loader
            loader.hide();

            // Ricarica i dati dell\'ultima esecuzione per tutti i controlli
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "GET",
                dataType: "JSON",
                data: {
                    id_module: globals.id_module,
                    op: "controlli-ultima-esecuzione",
                },
                success: function(controlliData) {
                    // Aggiorna i dati di ultima esecuzione per ogni controllo
                    for (const controlloData of controlliData) {
                        ricaricaEsecuzione(controlloData.id);
                    }
                },
                error: function(xhr, r, error) {
                    console.log("Errore nel caricamento dell\'ultima esecuzione: " + error);
                }
            });

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

            // Ottieni la card esistente
            let cardElement = $("#controllo-" + controllo["id"]);
            let headerElement = cardElement.find(".card-header");
            let titleElement = cardElement.find(".card-title");
            let bodyElement = cardElement.find(".card-body");
            let cardTools = cardElement.find(".card-tools");

            // Nascondi il pulsante "Avvia"
            let avviaBtn = cardTools.find("button").filter(function() {
                return $(this).text().includes("'.tr('Avvia').'");
            });
            avviaBtn.hide();

            // Aggiorna il colore della card header in base ai risultati
            removeColorClasses(headerElement);
            removeTitleColorClasses(titleElement);
            removeCardColorClasses(cardElement);

            if (success) {
                // Se il test è positivo (nessun avviso), colore success
                let colorClasses = determineCardClasses(0, 0, 0);
                applyCardColorClasses(headerElement, titleElement, cardElement, colorClasses);

                // Pulisci il body e mostra il messaggio di successo
                bodyElement.css(\'padding\', \'10px 12px\').html(`<p class="text-muted">'.tr('Nessun problema rilevato').'</p>`);

                // Rimuovi la badge se presente
                titleElement.find(".badge").remove();
            } else if (records.length > 0) {
                // Rimuovi la badge vecchia se presente
                titleElement.find(".badge").remove();

                // Gestione speciale per IntegritaFile
                if (controllo["class"] === "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile") {
                    let orphanFiles = records.filter(r => r.tipo === "file_orfano");
                    let orphanRecords = records.filter(r => r.tipo === "file_mancante");

                    let colorClasses = determineCardClasses(0, orphanRecords.length > 0 ? 1 : 0, orphanFiles.length > 0 ? 1 : 0);
                    applyCardColorClasses(headerElement, titleElement, cardElement, colorClasses);

                    // Ordina per gravita: warning prima di info
                    if (orphanRecords.length > 0) {
                        titleElement.append(` <span class="badge badge-warning ml-2">${orphanRecords.length}</span>`);
                    }

                    if (orphanFiles.length > 0) {
                        let totalSize = 0;
                        orphanFiles.forEach(function(file) {
                            if (file.dimensione_bytes) {
                                totalSize += parseInt(file.dimensione_bytes);
                            }
                        });
                        let sizeFormatted = totalSize > 0 ? formatBytes(totalSize) : "";
                        titleElement.append(` <span class="badge badge-info ml-2">${sizeFormatted}</span>`);
                    }
                } else if (controllo["class"] === "Modules\\\\Aggiornamenti\\\\Controlli\\\\DatiFattureElettroniche") {
                    // Gestione speciale per DatiFattureElettroniche
                    let dangerCount = 0;
                    let warningCount = 0;
                    let infoCount = 0;
                    let hasDanger = false;
                    let hasWarning = false;

                    records.forEach(function(record) {
                        let tempDiv = $("<div>").html(record.descrizione);
                        tempDiv.find("span[data-badge-type]").each(function() {
                            let type = $(this).attr("data-badge-type");
                            let count = parseInt($(this).text().trim());

                            if (type === "danger") {
                                dangerCount += count;
                                hasDanger = true;
                            } else if (type === "warning") {
                                warningCount += count;
                                hasWarning = true;
                            } else if (type === "info") {
                                infoCount += count;
                            }
                        });
                    });

                    // Aggiungi le nuove classi in base al tipo di badge piu grave
                    let colorClasses = determineCardClasses(hasDanger ? 1 : 0, hasWarning ? 1 : 0, 0);
                    applyCardColorClasses(headerElement, titleElement, cardElement, colorClasses);

                    // Mostra le badge per tipo di avviso
                    if (dangerCount > 0) {
                        titleElement.append(` <span class="badge badge-danger ml-2">${dangerCount}</span>`);
                    }
                    if (warningCount > 0) {
                        titleElement.append(` <span class="badge badge-warning ml-2">${warningCount}</span>`);
                    }
                    if (infoCount > 0) {
                        titleElement.append(` <span class="badge badge-info ml-2">${infoCount}</span>`);
                    }
                } else {
                    // Se ci sono avvisi, determina il tipo di badge piu grave
                    let hasDanger = records.some(r => r.type === "danger");
                    let hasWarning = records.some(r => r.type === "warning");

                    // Aggiungi le nuove classi in base al tipo di badge
                    let iconElement = titleElement.find(".requirements-icon");
                    iconElement.removeClass("fa-info-circle fa-exclamation-circle fa-warning fa-times-circle fa-exclamation-triangle");

                    if (hasDanger) {
                        headerElement.addClass("requirements-card-header-danger");
                        titleElement.addClass("requirements-card-title-danger");
                        cardElement.addClass("card-danger");
                        iconElement.addClass("fa-exclamation-triangle");
                        iconElement.css("color", "#dc3545");
                    } else if (hasWarning) {
                        headerElement.addClass("requirements-card-header-warning");
                        titleElement.addClass("requirements-card-title-warning");
                        cardElement.addClass("card-warning");
                        iconElement.addClass("fa-exclamation-triangle");
                    } else {
                        iconElement.addClass("fa-info-circle");
                    }

                    // Per altri controlli, mostra il contatore di errori
                    let badgeClass = hasDanger ? "badge-danger" : (hasWarning ? "badge-warning" : "badge-info");
                    titleElement.append(` <span class="badge ` + badgeClass + ` ml-2">${records.length}</span>`);
                }

                // Ricrea il body con la tabella
                let hasRowOptions = records.length > 0 && records[0].options && records[0].options.length > 0;
                let bodyHTML = `<div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th width="30%">'.tr('Record').'</th>
                                <th>'.tr('Descrizione').'</th>`;

                if (hasRowOptions) {
                    bodyHTML += `<th class="text-center" width="12%">'.tr('Opzioni').'</th>`;
                }

                bodyHTML += `</tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>`;

                bodyElement.css(\'padding\', \'10px 12px\').html(bodyHTML);

                // Pulisci la tabella e aggiungi i nuovi record
                let tbody = bodyElement.find("tbody");

                for(const record of records) {
                    let riga = `<tr class="` + record.type + `" id="controllo-` + controllo["id"] + `-` + record.id + `">
                        <td>` + record.nome + `</td>
                        <td>` + record.descrizione + `</td>`;

                    if (record.options && record.options.length > 0) {
                        riga += `<td class="text-center"></td>`;
                    }

                    riga += `</tr>`;
                    let rigaElement = $(riga);
                    tbody.append(rigaElement);

                    // Aggiungi i pulsanti delle opzioni
                    if (record.options && record.options.length > 0) {
                        const options_columns = rigaElement.find("td").last();
                        record.options.forEach(function (option, idx){
                            let button = `<button type="button" class="btn btn-primary btn-sm ">
                                <i class="fa fa-check"></i> '.tr('Correggi').'
                            </button>`;
                            button = $(button);
                            button.on("click", function () {
                                option.params.id = idx;
                                eseguiAzione(controllo, record, option.params);
                            });
                            options_columns.append(button);
                        });
                    }
                }

                // Mostra il pulsante "Risolvi tutti i conflitti" se il controllo lo supporta
                let risolviBtnHTML = cardTools.find(".risolvi-btn");
                if (risolviBtnHTML.length > 0) {
                    risolviBtnHTML.show();
                }
            } else {
                // Se nessun problema, mantieni il colore info
                headerElement.addClass("requirements-card-header-info");
                titleElement.addClass("requirements-card-title-info");
                cardElement.addClass("card-info");

                // Pulisci il body e mostra il messaggio di successo
                bodyElement.html(`<p class="text-muted">'.tr('Nessun problema rilevato').'</p>`);

                // Nascondi il pulsante "Risolvi tutti i conflitti" se presente
                let risolviBtnHTML = cardTools.find(".risolvi-btn");
                risolviBtnHTML.hide();
            }
        },
        error: function(xhr, r, error) {
            alert("'.tr('Errore').': " + error);
        }
    });
}

/**
* Avvia un singolo controllo
* @param id
* @param controlloClass
*/
function avviaControlloSingolo(id) {
    // Mostra il loader
    $("#progress").show();

    // Recupera la card e i dati dal data attribute
    let cardElement = $("#controllo-" + id);
    let nomeControllo = cardElement.data("controllo-name");
    let controlloClass = cardElement.data("controllo-class");

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "controlli-check",
            controllo: controlloClass,
            controllo_name: nomeControllo,
        },
        success: function(records) {
            let success = false;
            if (records.length === 0) {
                success = true;
            }

            // Ottieni la card esistente
            let cardElement = $("#controllo-" + id);
            let headerElement = cardElement.find(".card-header");
            let titleElement = cardElement.find(".card-title");
            let bodyElement = cardElement.find(".card-body");

            // Aggiorna il colore della card header in base ai risultati
            // Rimuovi le classi precedenti
            headerElement.removeClass("requirements-card-header-success requirements-card-header-info requirements-card-header-warning requirements-card-header-danger");
            titleElement.removeClass("requirements-card-title-success requirements-card-title-info requirements-card-title-warning requirements-card-title-danger");
            cardElement.removeClass("card-success card-info card-warning card-danger");

            // Ottieni i pulsanti della card-tools
            let cardTools = cardElement.find(".card-tools");

            if (success) {
                // Se il test è positivo (nessun avviso), colore success
                headerElement.addClass("requirements-card-header-success");
                titleElement.addClass("requirements-card-title-success");
                cardElement.addClass("card-success");

                // Pulisci il body e mostra il messaggio di successo
                bodyElement.html(`<p class="text-muted">'.tr('Nessun problema rilevato').'</p>`);

                // Nascondi il pulsante "Avvia"
                let avviaBtn = cardTools.find("button").filter(function() {
                    return $(this).text().includes("'.tr('Avvia').'");
                });
                avviaBtn.hide();

                // Nascondi il pulsante "Risolvi tutti i conflitti" se presente
                let risolviBtnHTML = cardTools.find(".risolvi-btn");
                risolviBtnHTML.hide();
            } else if (records.length > 0) {
                // Rimuovi la badge vecchia se presente
                titleElement.find(".badge").remove();

                // Gestione speciale per IntegritaFile
                if (id === "IntegritaFile" || controlloClass === "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile") {
                    let orphanFiles = records.filter(r => r.tipo === "file_orfano");
                    let orphanRecords = records.filter(r => r.tipo === "file_mancante");

                    let headerColorCls = "requirements-card-header-info";
                    let titleColorCls = "requirements-card-title-info";
                    let cardColorCls = "card-info";

                    if (orphanRecords.length > 0) {
                        headerColorCls = "requirements-card-header-warning";
                        titleColorCls = "requirements-card-title-warning";
                        cardColorCls = "card-warning";
                    }

                    // Aggiungi le nuove classi in base al tipo di badge
                    headerElement.addClass(headerColorCls);
                    titleElement.addClass(titleColorCls);
                    cardElement.addClass(cardColorCls);

                    // Ordina per gravità: warning prima di info
                    if (orphanRecords.length > 0) {
                        titleElement.append(` <span class="badge badge-warning ml-2">${orphanRecords.length}</span>`);
                    }

                    if (orphanFiles.length > 0) {
                        let totalSize = 0;
                        orphanFiles.forEach(function(file) {
                            if (file.dimensione_bytes) {
                                totalSize += parseInt(file.dimensione_bytes);
                            }
                        });
                        let sizeFormatted = totalSize > 0 ? formatBytes(totalSize) : "";
                        titleElement.append(` <span class="badge badge-info ml-2">${sizeFormatted}</span>`);
                    }
                } else if (controlloClass === "Modules\\\\Aggiornamenti\\\\Controlli\\\\DatiFattureElettroniche") {
                    // Gestione speciale per DatiFattureElettroniche
                    let dangerCount = 0;
                    let warningCount = 0;
                    let infoCount = 0;
                    let hasDanger = false;
                    let hasWarning = false;

                    records.forEach(function(record) {
                        let tempDiv = $("<div>").html(record.descrizione);
                        tempDiv.find("span[data-badge-type]").each(function() {
                            let type = $(this).attr("data-badge-type");
                            let count = parseInt($(this).text().trim());

                            if (type === "danger") {
                                dangerCount += count;
                                hasDanger = true;
                            } else if (type === "warning") {
                                warningCount += count;
                                hasWarning = true;
                            } else if (type === "info") {
                                infoCount += count;
                            }
                        });
                    });

                    // Aggiungi le nuove classi in base al tipo di badge più grave
                    if (hasDanger) {
                        headerElement.addClass("requirements-card-header-danger");
                        titleElement.addClass("requirements-card-title-danger");
                        cardElement.addClass("card-danger");
                    } else if (hasWarning) {
                        headerElement.addClass("requirements-card-header-warning");
                        titleElement.addClass("requirements-card-title-warning");
                        cardElement.addClass("card-warning");
                    }

                    // Mostra le badge per tipo di avviso
                    if (dangerCount > 0) {
                        titleElement.append(` <span class="badge badge-danger ml-2">${dangerCount}</span>`);
                    }
                    if (warningCount > 0) {
                        titleElement.append(` <span class="badge badge-warning ml-2">${warningCount}</span>`);
                    }
                    if (infoCount > 0) {
                        titleElement.append(` <span class="badge badge-info ml-2">${infoCount}</span>`);
                    }
                } else {
                    // Se ci sono avvisi, determina il tipo di badge più grave
                    let hasDanger = records.some(r => r.type === "danger");
                    let hasWarning = records.some(r => r.type === "warning");

                    // Aggiungi le nuove classi in base al tipo di badge
                    if (hasDanger) {
                        headerElement.addClass("requirements-card-header-danger");
                        titleElement.addClass("requirements-card-title-danger");
                        cardElement.addClass("card-danger");
                    } else if (hasWarning) {
                        headerElement.addClass("requirements-card-header-warning");
                        titleElement.addClass("requirements-card-title-warning");
                        cardElement.addClass("card-warning");
                    }

                    // Per altri controlli, mostra il contatore di errori
                    let badgeClass = hasDanger ? "badge-danger" : (hasWarning ? "badge-warning" : "badge-info");
                    titleElement.append(` <span class="badge ` + badgeClass + ` ml-2">${records.length}</span>`);
                }

                // Ricrea il body con la tabella
                let hasRowOptions = records.length > 0 && records[0].options && records[0].options.length > 0;
                let bodyHTML = `<div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th width="30%">'.tr('Record').'</th>
                                <th>'.tr('Descrizione').'</th>`;

                if (hasRowOptions) {
                    bodyHTML += `<th class="text-center" width="12%">'.tr('Opzioni').'</th>`;
                }

                bodyHTML += `</tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>`;

                bodyElement.css(\'padding\', \'10px 12px\').html(bodyHTML);

                // Pulisci la tabella e aggiungi i nuovi record
                let tbody = bodyElement.find("tbody");

                for(const record of records) {
                    let riga = `<tr class="` + record.type + `" id="controllo-` + id + `-` + record.id + `">
                        <td>` + record.nome + `</td>
                        <td>` + record.descrizione + `</td>`;

                    if (record.options && record.options.length > 0) {
                        riga += `<td class="text-center"></td>`;
                    }

                    riga += `</tr>`;
                    let rigaElement = $(riga);
                    tbody.append(rigaElement);

                    // Aggiungi i pulsanti delle opzioni
                    if (record.options && record.options.length > 0) {
                        const options_columns = rigaElement.find("td").last();
                        record.options.forEach(function (option, idx){
                            let button = `<button type="button" class="btn btn-primary btn-sm ">
                                <i class="fa fa-check"></i> '.tr('Correggi').'
                            </button>`;
                            button = $(button);
                            button.on("click", function () {
                                option.params.id = idx;
                                // Recupera l oggetto controllo dalla card
                                let cardElement = $("#controllo-" + id);
                                let controlloClass = cardElement.data("controllo-class");
                                let controllo = {id: id, class: controlloClass};
                                eseguiAzione(controllo, record, option.params);
                            });
                            options_columns.append(button);
                        });
                    }
                }

                // Aggiorna i pulsanti nella card-tools
                let cardTools = cardElement.find(".card-tools");

                // Nascondi il pulsante "Avvia"
                let avviaBtn = cardTools.find("button").filter(function() {
                    return $(this).text().includes("'.tr('Avvia').'");
                });
                avviaBtn.hide();

                // Mostra il pulsante "Risolvi tutti i conflitti" se il controllo lo supporta
                let risolviBtnHTML = cardTools.find(".risolvi-btn");
                if (risolviBtnHTML.length > 0) {
                    risolviBtnHTML.show();
                }
            } else {
                // Se nessun problema, mantieni il colore info
                headerElement.addClass("requirements-card-header-info");
                titleElement.addClass("requirements-card-title-info");
                cardElement.addClass("card-info");

                // Pulisci il body e mostra il messaggio di successo
                bodyElement.html(`<p class="text-muted">'.tr('Nessun problema rilevato').'</p>`);

                // Nascondi il pulsante "Risolvi tutti i conflitti" se presente
                let risolviBtnHTML = cardTools.find(".risolvi-btn");
                risolviBtnHTML.hide();
            }

            // Ricarica i dati dell\'ultima esecuzione
            ricaricaEsecuzione(id);

            // Nascondi il loader
            $("#progress").hide();
        },
        error: function(xhr, r, error) {
            $("#progress").hide();
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
            // Rimuovi la riga dalla tabella
            $("#controllo-" + controllo["id"] + "-" + records.id).remove();

            // Controlla se ci sono ancora righe nella tabella
            let cardElement = $("#controllo-" + controllo["id"]);
            let tbody = cardElement.find("tbody");
            let remainingRows = tbody.find("tr").length;

            // Se non ci sono piu righe, mostra il messaggio di successo
            if (remainingRows === 0) {
                let headerElement = cardElement.find(".card-header");
                let titleElement = cardElement.find(".card-title");
                let bodyElement = cardElement.find(".card-body");

                // Rimuovi le classi di colore precedenti
                headerElement.removeClass("requirements-card-header-success requirements-card-header-info requirements-card-header-warning requirements-card-header-danger");
                titleElement.removeClass("requirements-card-title-success requirements-card-title-info requirements-card-title-warning requirements-card-title-danger");
                cardElement.removeClass("card-success card-info card-warning card-danger");

                // Aggiungi le classi di successo
                headerElement.addClass("requirements-card-header-success");
                titleElement.addClass("requirements-card-title-success");
                cardElement.addClass("card-success");

                // Rimuovi le badge
                titleElement.find(".badge").remove();

                // Mostra il messaggio di successo
                bodyElement.html("<p class=\"text-muted\">'.tr('Nessun problema rilevato').'</p>");

                // Nascondi il pulsante "Risolvi tutti i conflitti" se presente
                let risolviBtnHTML = cardElement.find(".risolvi-btn");
                risolviBtnHTML.hide();

                // Collassa la card
                cardElement.addClass("collapsed-card");
                bodyElement.slideUp();
            }
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
    let cssClass = "card-outline card-info";
    let headerClass = "requirements-card-header requirements-card-header-info";
    let titleClass = "requirements-card-title requirements-card-title-info";
    let icon = "info-circle";

    if (!success) {
        cssClass = "card-outline card-danger";
        headerClass = "requirements-card-header requirements-card-header-danger";
        titleClass = "requirements-card-title requirements-card-title-danger";
        icon = "times-circle";
    }

    // Usa i colori determinati sopra
    let finalHeaderClass = headerClass;
    let finalTitleClass = titleClass;
    let finalCssClass = cssClass + " requirements-card mb-2 collapsable collapsed-card";

    // Determina il colore della card in base al tipo di controllo
    let cardColorClass = "card-info";
    let headerColorClass = "requirements-card-header-info";
    let titleColorClass = "requirements-card-title-info";
    let finalIcon = icon;

    if (records.length > 0) {
        // Determina il colore più grave
        let hasDanger = false;
        let hasWarning = false;

        if (controllo["class"] === "Modules\\\\Aggiornamenti\\\\Controlli\\\\DatiFattureElettroniche") {
            // Per DatiFattureElettroniche, controlla le badge nel contenuto
            records.forEach(function(record) {
                let tempDiv = $("<div>").html(record.descrizione);
                tempDiv.find("span[data-badge-type]").each(function() {
                    let type = $(this).attr("data-badge-type");
                    if (type === "danger") {
                        hasDanger = true;
                    } else if (type === "warning") {
                        hasWarning = true;
                    }
                });
            });
        } else {
            // Per altri controlli, usa il campo type
            hasDanger = records.some(r => r.type === "danger");
            hasWarning = records.some(r => r.type === "warning");
        }

        if (hasDanger) {
            cardColorClass = "card-danger";
            headerColorClass = "requirements-card-header-danger";
            titleColorClass = "requirements-card-title-danger";
            finalIcon = "times-circle";
        } else if (hasWarning) {
            cardColorClass = "card-warning";
            headerColorClass = "requirements-card-header-warning";
            titleColorClass = "requirements-card-title-warning";
            finalIcon = "warning";
        } else {
            finalIcon = "info-circle";
        }
    }

    if (records.length > 0) {
        finalHeaderClass = "requirements-card-header " + headerColorClass;
        finalTitleClass = "requirements-card-title " + titleColorClass;
        finalCssClass = cardColorClass + " requirements-card mb-2 collapsable collapsed-card";
    }

    let card = `<div class="card ` + finalCssClass + `" id="controllo-` + controllo["id"] + `" data-controllo-name="` + controllo["name"] + `" data-controllo-class="` + controllo["class"] + `">
    <div class="card-header with-border ` + finalHeaderClass + `">
        <h3 class="card-title ` + finalTitleClass + `">
            <i class="fa fa-` + finalIcon + ` mr-2 requirements-icon"></i>` + controllo["name"];

    // Aggiungi badge inline per il controllo IntegritaFile
    if (controllo["class"] === "Modules\\\\Aggiornamenti\\\\Controlli\\\\IntegritaFile" && !success && records.length > 0) {
        let orphanFiles = records.filter(r => r.tipo === "file_orfano");
        let orphanRecords = records.filter(r => r.tipo === "file_mancante");

        if (orphanFiles.length > 0 || orphanRecords.length > 0) {
            // Ordina per gravità: warning prima di info
            if (orphanRecords.length > 0) {
                card += ` <span class="badge badge-warning ml-2">${orphanRecords.length}</span>`;
            }

            if (orphanFiles.length > 0) {
                let totalSize = 0;
                orphanFiles.forEach(function(file) {
                    if (file.dimensione_bytes) {
                        totalSize += parseInt(file.dimensione_bytes);
                    }
                });
                let sizeFormatted = totalSize > 0 ? formatBytes(totalSize) : "";
                card += ` <span class="badge badge-info ml-2">${sizeFormatted}</span>`;
            }
        }
    } else if (!success && records.length > 0) {
        // Per altri controlli, mostra il contatore di errori
        card += ` <span class="badge badge-danger ml-2">${records.length}</span>`;
    }

    // Aggiungi le date di filtro per il controllo DatiFattureElettroniche
    if (controllo["period_start"] && controllo["period_end"]) {
        let dataInizio = new Date(controllo["period_start"]);
        let dataFine = new Date(controllo["period_end"]);
        let dataInizioFormattata = dataInizio.toLocaleDateString("it-IT");
        let dataFineFormattata = dataFine.toLocaleDateString("it-IT");
        card += ` <span style="color: #999; font-size: 0.9rem; margin-left: 10px;">${dataInizioFormattata} - ${dataFineFormattata}</span>`;
    }

    card += `</h3>
        <div class="card-tools pull-right" style="display: flex; align-items: center; gap: 10px;">`;

    // Aggiungi informazioni di ultima esecuzione se disponibili
    if (controllo["last_execution"] || controllo["last_user"]) {
        card += `<div style="font-size: 0.85rem; color: #666;">`;
        if (controllo["last_execution"]) {
            // Formatta la data e ora
            let dataOra = new Date(controllo["last_execution"]);
            let dataFormattata = dataOra.toLocaleDateString("it-IT") + " " + dataOra.toLocaleTimeString("it-IT", {hour: "2-digit", minute: "2-digit"});
            card += `<i class="fa fa-calendar mr-2"></i>${dataFormattata}`;
        }
        if (controllo["last_user"]) {
            card += `&nbsp;&nbsp;&nbsp;<i class="fa fa-user mr-2"></i>${controllo["last_user"]}`;
        }
        card += `</div>`;
    }

    // Aggiungi pulsante per avviare il singolo controllo
    card += `<button type="button" class="btn btn-primary btn-sm" onclick=\'avviaControlloSingolo("` + controllo["id"] + `")\'>
                <i class="fa fa-play"></i> '.tr('Avvia').'
            </button>`;

    // Aggiungi pulsanti azione globale se il controllo lo supporta
    if (hasGlobalActions(controllo)) {
        // Usa la stessa funzione per tutti i controlli, incluso IntegritaFile
        // Nascondi il pulsante se success è true (nessun problema rilevato)
        let displayStyle = (!success && records.length > 0) ? "inline-block" : "none";
        card += `
            <button type="button" class="btn btn-primary btn-sm risolvi-btn" data-controllo-id="` + controllo["id"] + `" data-controllo-class="` + controllo["class"] + `" onclick="eseguiAzioneGlobale(this)" style="display: ` + displayStyle + `;">
                <i class="fa fa-check-circle"></i> '.tr('Risolvi tutti i conflitti').'
            </button>`;
    }

    card += `
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>`

    if (!success) {
        // Verifica se ci sono opzioni per le singole righe
        let hasRowOptions = records.length > 0 && records[0].options && records[0].options.length > 0;

        card += `
    <div class="card-body" style="padding: 10px 12px;">
        <div class="table-responsive">
            <table class="table table-sm">
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
    } else {
        // Aggiungi un body vuoto per le card di tipo info (collassate)
        card += `
    <div class="card-body" style="padding: 10px 12px;">
        <p class="text-muted">'.tr('Nessun problema rilevato').'</p>
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
    let isDatiFattureElettroniche = controllo["class"] === "Modules\\\\Aggiornamenti\\\\Controlli\\\\DatiFattureElettroniche";

    // Per DatiFattureElettroniche, crea una riga collassabile
    if (isDatiFattureElettroniche) {
        let rowId = "controllo-" + controllo["id"] + "-" + record.id;
        let detailsId = rowId + "-details";

        // Conta gli avvisi per gravità dal contenuto HTML
        let dangerCount = 0;
        let warningCount = 0;
        let infoCount = 0;

        // Estrai i contatori dal report HTML cercando le badge nello stile inline
        let tempDiv = $("<div>").html(record.descrizione);
        tempDiv.find("span").each(function() {
            let text = $(this).text().trim();
            let bgColor = $(this).css("background-color");
            let style = $(this).attr("style") || "";

            // Controlla se è un numero e ha uno stile di background
            if (text.match(/^\d+$/) && bgColor && bgColor !== "rgba(0, 0, 0, 0)") {
                let count = parseInt(text);
                // Colore danger: #dc3545 o rgb(220, 53, 69)
                if (style.includes("dc3545") || (bgColor.includes("220") && bgColor.includes("53"))) {
                    dangerCount = count;
                }
                // Colore warning: #ffc107 o rgb(255, 193, 7)
                else if (style.includes("ffc107") || (bgColor.includes("255") && bgColor.includes("193"))) {
                    warningCount = count;
                }
                // Colore info: #17a2b8 o rgb(23, 162, 184)
                else if (style.includes("17a2b8") || (bgColor.includes("23") && bgColor.includes("162"))) {
                    infoCount = count;
                }
            }
        });

        // Riga principale collassabile
        let riga = `<tr class="` + record.type + `" id="` + rowId + `" style="cursor: pointer;">
    <td>
        <i class="fa fa-chevron-right" style="margin-right: 8px; transition: transform 0.2s;"></i>
        ` + record.nome;

        // Aggiungi badge per gli avvisi
        if (dangerCount > 0) {
            riga += ` <span class="badge badge-danger">` + dangerCount + `</span>`;
        }
        if (warningCount > 0) {
            riga += ` <span class="badge badge-warning">` + warningCount + `</span>`;
        }
        if (infoCount > 0) {
            riga += ` <span class="badge badge-info">` + infoCount + `</span>`;
        }

        riga += `
    </td>
    <td>
        <span class="text-muted">'.tr('Clicca per visualizzare i dettagli').'</span>
    </td>`;

        if (hasOptions) {
            riga += `<td class="text-center"></td>`;
        }

        riga += `</tr>`;
        riga = $(riga);

        // Aggiungi evento click per collassare/espandere
        riga.on("click", function(e) {
            if ($(e.target).closest("button").length === 0) {
                let detailsRow = $("#" + detailsId);
                let icon = riga.find("i.fa-chevron-right");

                if (detailsRow.length === 0) {
                    // Crea la riga di dettagli
                    let detailsHtml = `<tr id="` + detailsId + `" style="display: none;">
        <td colspan="` + (hasOptions ? 3 : 2) + `">
            <div style="padding: 15px; background: #f8f9fa; border-radius: 4px;">
                ` + record.descrizione + `
            </div>
        </td>
    </tr>`;
                    detailsRow = $(detailsHtml);
                    riga.after(detailsRow);
                }

                detailsRow.slideToggle(200);
                icon.css("transform", detailsRow.is(":visible") ? "rotate(90deg)" : "rotate(0deg)");
            }
        });

        body.append(riga);
    } else {
        // Generazione riga standard per altri controlli
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
                 let button = `<button type="button" class="btn btn-primary btn-sm ">
            <i class="fa fa-check"></i> '.tr('Correggi').'
        </button>`;
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
}

/**
* Verifica se un controllo supporta azioni globali
* @param controllo
* @returns {boolean}
*/
function hasGlobalActions(controllo) {
    // Lista dei controlli che supportano azioni globali
    const controlliConAzioniGlobali = [
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoConti",
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
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoConti": {
            titolo: "'.tr('Conferma risoluzione Piano dei Conti').'",
            descrizione: "'.tr('Sei sicuro di voler risolvere tutti i conflitti del Piano dei Conti?').'",
            operazioni: [
                "'.tr('Creerà automaticamente i conti mancanti per le anagrafiche Cliente e Fornitore').'",
                "'.tr('Assegnerà i conti appropriati alle anagrafiche interessate').'",
                "'.tr('Le anagrafiche verranno aggiornate con i nuovi conti').'",
                "'.tr('Non può essere annullata').'"
            ]
        },
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

    // Determina il tipo di avviso e icona in base al controllo
    let controlliInfo = [
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoConti",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\PianoContiRagioneSociale",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\ReaValidi",
        "Modules\\\\Aggiornamenti\\\\Controlli\\\\TabelleLanguage"
    ];

    let isInfo = controlliInfo.includes(controlloClass);
    let alertClass = isInfo ? "alert-info" : "alert-warning";
    let iconClass = isInfo ? "fa-info-circle text-info" : "fa-exclamation-triangle text-warning";

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
                            <i class="fa ${iconClass}"></i>
                            ${messaggio.titolo}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>${messaggio.descrizione}</p>
                        <div class="alert ${alertClass}">
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
                        <button type="button" class="btn btn-primary" id="conferma-risoluzione" style="float: right;">
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
            let cardElement = $("#controllo-" + controlloId);
            let headerElement = cardElement.find(".card-header");
            let titleElement = cardElement.find(".card-title");
            let bodyElement = cardElement.find(".card-body");

            // Rimuovi tutte le righe del controllo
            cardElement.find("tbody tr").remove();

            // Nascondi il pulsante di azione globale
            button.hide();

            // Aggiorna i colori della card a success
            headerElement.removeClass("requirements-card-header-success requirements-card-header-info requirements-card-header-warning requirements-card-header-danger");
            titleElement.removeClass("requirements-card-title-success requirements-card-title-info requirements-card-title-warning requirements-card-title-danger");
            cardElement.removeClass("card-success card-info card-warning card-danger");

            headerElement.addClass("requirements-card-header-success");
            titleElement.addClass("requirements-card-title-success");
            cardElement.addClass("card-success");

            // Mostra messaggio "Nessun conflitto rilevato"
            bodyElement.html(`<p class="text-muted">'.tr('Nessun problema rilevato').'</p>`);

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
                    <h4><i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i> '.tr('Errore durante la risoluzione').'</h4>
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
