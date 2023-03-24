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

$incorpora_iva = setting('Utilizza prezzi di vendita comprensivi di IVA');
$intestazione_prezzo = ($options['dir'] == 'uscita' ? tr('Prezzo di acquisto') : ($incorpora_iva ? tr('Prezzo vendita ivato') : tr('Prezzo vendita imponibile')));

// Articolo
echo '
<div class="row">
    <div class="col-md-6">
        {[ "type": "text", "label": "' . tr('Inserisci barcode manualmente') . '", "name": "barcode", "value": "", "icon-before": "<i class=\"fa fa-barcode\"></i>" ]}
    </div>
    <div class="col-md-6">
        {[ "type": "file", "label": "' . tr('Inserisci file') . '", "id": "barcode_file", "name": "barcode_file", "value": "", "icon-before": "<i class=\"fa fa-file\"></i>", "multiple": true ]}
    </div>
</div>

<div class="alert alert-info hidden" id="articolo-missing">
    <i class="fa fa-exclamation-circle"></i> '.tr('Nessuna corrispondenza trovata!').'
</div>

<div class="alert alert-warning hidden" id="articolo-qta">
    <i class="fa fa-warning"></i> '.tr('Articolo con quantità non sufficiente!').'
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-stripped hide" id="articoli_barcode">
            <tr>
                <th>'.tr('Articolo').'</th>
                <th width="25%" class="text-center">'.$intestazione_prezzo.'</th>
                <th width="20%" class="text-center">'.tr('Sconto').'</th>
                <th width="10%" class="text-center">'.tr('Q.tà').'</th>
                <th width="5%" class="text-center">#</th>
            </tr>
        </table>
    </div>
</div>

<input type="hidden" name="permetti_movimenti_sotto_zero" id="permetti_movimenti_sotto_zero" value="'.setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita').'">';

echo '
<script>
var direzione = "'.$options['dir'].'";

$(document).ready(function(){
    init();

    setTimeout(function(){
        $("#barcode").focus();
    }, 300);

    $(".modal-body button").attr("disabled", true);
});


// gestione drop file
$("#barcode_file").on("change", function (event) {
    $("#articolo-missing").addClass("hidden");
    $("#articolo-qta").addClass("hidden");

    var barcodes = {};

    let files = event.target.files;
    //read csv file
    let reader = new FileReader();
    reader.readAsText(files[0]);
    reader.onload = function (event) {
        var csv = event.target.result;
        var lines = csv.split("\n");
        lines.forEach(function (line) {
            if (line === "") {
                return;
            }
            var qta = line.split(";")[1];
            var barcode = line.split(";")[0];
            barcode = barcode.replace(/\//g, "-");

            //add barcode and qta in barcodes
            if (barcodes[barcode] !== undefined) {
                barcodes[barcode] += parseInt(qta);
            } else {
                barcodes[barcode] = parseInt(qta);
            }
        });

        let permetti_movimenti_sotto_zero = $("#permetti_movimenti_sotto_zero").val();

        $.getJSON(globals.rootdir + "/ajax_complete.php?op=articoli_barcode_file&barcodes=" + JSON.stringify(barcodes) + "&id_anagrafica='.$options['idanagrafica'].'", function(response) {
            results = response["barcodeTrovati"];

            $.each(results, function (i, barcode) {
                dettaglio = barcode.dettaglio;
                qta = barcode.qta;

                let qta_input = $("#riga_barcode_" + dettaglio.id).find("[name^=qta]");
                if (parseInt(dettaglio.qta) <= 0 && permetti_movimenti_sotto_zero == 0 && direzione === "entrata") {
                    $("#articolo-qta").html("'.tr("Articolo ").'dettaglio.codice'.tr(" presente in quantità non sufficiente").'");
                    $("#articolo-qta").removeClass("hidden");
                    barcodeFileReset();
                    return;
                }

                // Controllo se è già presente l\'articolo, in tal caso incremento la quantità, altrimenti inserisco la riga nuova
                if (qta_input.length) {
                    let nuova_qta = qta_input.val().toEnglish() + qta;

                    if (dettaglio.qta < nuova_qta) {
                        $("#articolo-qta").html("'.tr("Articolo ").'" + dettaglio.codice + "'.tr(" presente in quantità non sufficiente").'");
                        $("#articolo-qta").removeClass("hidden");
                        barcodeFileReset();
                        return;
                    }

                    qta_input.val(nuova_qta).trigger("change");
                } else {
                    if (dettaglio.qta < qta) {
                        $("#articolo-qta").html("'.tr("Articolo ").'" + dettaglio.codice + "'.tr(" presente in quantità non sufficiente").'");
                        $("#articolo-qta").removeClass("hidden");
                    } else {
                        let prezzo_unitario = (direzione === "uscita") ? dettaglio.prezzo_acquisto : dettaglio.prezzo_vendita;
                        prezzo_acquisto = parseFloat(dettaglio.prezzo_acquisto, 10).toLocale();
                        prezzo_vendita = parseFloat(dettaglio.prezzo_vendita, 10).toLocale();

                        let info_prezzi;
                        if(direzione === "entrata") {
                            info_prezzi = "Acquisto: " + (prezzo_acquisto) + " &euro;";
                        }else{
                            info_prezzi = "Vendita: " + (prezzo_vendita) + " &euro;";
                        }

                        $("#articoli_barcode").removeClass("hide");
                        cleanup_inputs();

                        var text = replaceAll($("#barcode-template").html(), "-id-", dettaglio.id);
                        text = text.replace("|prezzo_unitario|", prezzo_unitario)
                            .replace("|info_prezzi|", info_prezzi)
                            .replace("|descrizione|", dettaglio.descrizione)
                            .replace("|codice|", dettaglio.codice)
                            .replace("|qta|", qta)
                            .replace("|sconto_unitario|", 0)
                            .replace("|tipo_sconto|", "")
                            .replace("|id_dettaglio_fornitore|", dettaglio.id_dettaglio_fornitore ? dettaglio.id_dettaglio_fornitore : "")

                        $("#articoli_barcode tbody").first().append(text);
                        restart_inputs();

                        $(".modal-body button").attr("disabled", false);

                        // Gestione dinamica dei prezzi
                        let tr = $("#riga_barcode_" + dettaglio.id);
                        ottieniDettagliArticolo(dettaglio.id, tr).then(function() {
                            if ($(tr).find("input[name^=prezzo_unitario]").val().toEnglish() === 0){
                                aggiornaPrezzoArticolo(tr);
                            } else {
                                verificaPrezzoArticolo(tr);
                            }

                            var listino = getPrezzoListino(tr);

                            if (listino) {
                                $(tr).find("input[name^=prezzo_unitario]").val(listino).trigger("change");
                            }

                            if ($(tr).find("input[name^=sconto]").val().toEnglish() === 0){
                                aggiornaScontoArticolo();
                            } else {
                                verificaScontoArticolo();
                            }
                        });
                    }
                }

                barcodeFileReset();
            });
        });
    };
});

// Gestione dell\'invio da tastiera
$(document).keypress(function(event){
    let key = window.event ? event.keyCode : event.which; // IE vs Netscape/Firefox/Opera
    if (key == "13") {
        event.preventDefault();
        $("#barcode").blur()
            .focus();
    }
});

$("#barcode").off("keyup").on("keyup", function (event) {
    $("#articolo-qta").html("'.tr("Articolo con quantità non sufficiente").'");

    let key = window.event ? event.keyCode : event.which; // IE vs Netscape/Firefox/Opera
    $("#articolo-missing").addClass("hidden");
    $("#articolo-qta").addClass("hidden");

    if (key !== 13) {
        return;
    }

    $("#barcode").attr("disabled", true);
    var barcode = $("#barcode").val();
    if (!barcode){
        barcodeReset();
        return;
    }

    barcodeAdd(barcode, 1);
});

function barcodeAdd(barcode, qta) {
    $.getJSON(globals.rootdir + "/ajax_select.php?op=articoli_barcode&search=" + barcode + "&id_anagrafica='.$options['idanagrafica'].'", function(response) {
        let result = response.results[0];
        if(!result){
            $("#articolo-missing").removeClass("hidden");
            barcodeReset();

            return;
        }

        let qta_input = $("#riga_barcode_" + result.id).find("[name^=qta]");
        let permetti_movimenti_sotto_zero = $("#permetti_movimenti_sotto_zero").val();
        if (result.qta <= 0 && permetti_movimenti_sotto_zero == 0 && direzione === "entrata") {
            $("#articolo-qta").removeClass("hidden");
            barcodeReset();
            return;
        }

        // Controllo se è già presente l\'articolo, in tal caso incremento la quantità, altrimenti inserisco la riga nuova
        if (qta_input.length) {
            let qta = qta_input.val().toEnglish();
            let nuova_qta = qta + 1;

            if (result.qta < nuova_qta) {
                $("#articolo-qta").removeClass("hidden");
                barcodeReset();
                return;
            }

            qta_input.val(nuova_qta).trigger("change");
        } else {
            let prezzo_unitario = (direzione === "uscita") ? result.prezzo_acquisto : result.prezzo_vendita;
            prezzo_acquisto = parseFloat(result.prezzo_acquisto, 10).toLocale();
            prezzo_vendita = parseFloat(result.prezzo_vendita, 10).toLocale();

            let info_prezzi;
            if(direzione === "entrata") {
                info_prezzi = "Acquisto: " + (prezzo_acquisto) + " &euro;";
            }else{
                info_prezzi = "Vendita: " + (prezzo_vendita) + " &euro;";
            }

            $("#articoli_barcode").removeClass("hide");
            cleanup_inputs();

            var text = replaceAll($("#barcode-template").html(), "-id-", result.id);
            text = text.replace("|prezzo_unitario|", prezzo_unitario)
                .replace("|info_prezzi|", info_prezzi)
                .replace("|descrizione|", result.descrizione)
                .replace("|codice|", result.codice)
                .replace("|qta|", qta)
                .replace("|sconto_unitario|", 0)
                .replace("|tipo_sconto|", "")
                .replace("|id_dettaglio_fornitore|", result.id_dettaglio_fornitore ? result.id_dettaglio_fornitore : "")

            $("#articoli_barcode tbody").first().append(text);
            restart_inputs();

            $(".modal-body button").attr("disabled", false);

            // Gestione dinamica dei prezzi
            let tr = $("#riga_barcode_" + result.id);
            ottieniDettagliArticolo(result.id, tr).then(function() {
                if ($(tr).find("input[name^=prezzo_unitario]").val().toEnglish() === 0){
                    aggiornaPrezzoArticolo(tr);
                } else {
                    verificaPrezzoArticolo(tr);
                }

                var listino = getPrezzoListino(tr);

                if (listino) {
                    $(tr).find("input[name^=prezzo_unitario]").val(listino).trigger("change");
                }

                if ($(tr).find("input[name^=sconto]").val().toEnglish() === 0){
                    aggiornaScontoArticolo();
                } else {
                    verificaScontoArticolo();
                }
            });
        }

        barcodeReset();
        $("#barcode").val("");
    }, function(){
        $("#articolo-missing").removeClass("hidden");
        barcodeReset();
    });
}

$(document).on("change", "input[name^=qta], input[name^=prezzo_unitario]", function() {
    let tr = $(this).closest("tr");
    verificaPrezzoArticolo(tr);
});

function barcodeReset() {
    setTimeout(function(){
        $("#barcode")
            .attr("disabled",false)
            .focus();
    },200);
}

function barcodeFileReset() {
    $("#barcode_file").val("");
}

function rimuoviRigaBarcode(id) {
    if (confirm("'.tr('Eliminare questo articolo?').'")) {
        $("#riga_barcode_" + id).remove();

        //get rows number of #articoli_barcode
        var num = $("#articoli_barcode tbody tr").length;

        // Disabilito il pulsante di aggiunta se non ci sono articoli inseriti
        if (num <= 1) {
            $(".modal-body button").attr("disabled", true);
            $("#articoli_barcode").addClass("hide");
        }
    }
}

/**
* Restituisce il prezzo registrato per una specifica quantità dell\'articolo.
*/
function getDettaglioPerQuantita(qta, tr) {
    const data = $(tr).data("dettagli");
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
function getPrezzoPerQuantita(qta, tr) {
    const dettaglio = getDettaglioPerQuantita(qta, tr);

    return dettaglio ? parseFloat(dettaglio.prezzo_unitario) : 0;
}

/**
* Restituisce lo sconto registrato per una specifica quantità dell\'articolo.
*/
function getScontoPerQuantita(qta, tr) {
    const dettaglio = getDettaglioPerQuantita(qta, tr);

    return dettaglio ? parseFloat(dettaglio.sconto_percentuale) : 0;
}

/**
* Funzione per registrare localmente i dettagli definiti per l\'articolo in relazione ad una specifica anagrafica.
*/
function ottieniDettagliArticolo(id_articolo, tr) {
    return $.get(globals.rootdir + "/ajax_complete.php?module=Articoli&op=dettagli_articolo&id_anagrafica='.$options['idanagrafica'].'&id_articolo=" + id_articolo + "&dir=" + direzione, function(response) {
        const data = JSON.parse(response);

        $(tr).data("dettagli", data);
    });
}

/**
* Funzione per verificare se il prezzo unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaPrezzoArticolo(tr) {
    let qta = $(tr).find("input[name^=qta]").val().toEnglish();
    let prezzo_previsto = getPrezzoPerQuantita(qta, tr);

    let id = tr.attr("id");
    let id_split = id.split("_");
    let id_art = id_split[id_split.length - 1];

    let prezzo_unitario_input = $(tr).find("#prezzo_unitario" + id_art);
    let prezzo_unitario = prezzo_unitario_input.val().toEnglish();

    let div = prezzo_unitario_input.closest(".input-group").parent().find("div[id*=errors]");

    if (prezzo_previsto === prezzo_unitario || prezzo_previsto === 0) {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("padding-top", "20px");
    //div.html(`<small class="label label-info" >'.tr('Prezzo suggerito').': ` + prezzo_previsto.toLocale() + " " + globals.currency + `<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);

    //div.css("padding-top", "0");
    div.html("");

    let prezzo_anagrafica = prezzo_previsto;
    let prezzo_listino = getPrezzoListino(tr);
    let prezzo_std = getPrezzoScheda(tr);
    let prezzo_last = getPrezzoUltimo(tr);
    //let prezzo_minimo = 0; //parseFloat($("#idarticolo").selectData().minimo_vendita);
    let prezzi_visibili = getPrezziListinoVisibili(tr);

    if (prezzo_anagrafica || prezzo_listino || prezzo_std || prezzo_last || prezzi_visibili) {
        div.html(`<table class="table table-extra-condensed table-prezzi` + id_art + `" style="background:#eee; margin-top:-13px;"><tbody>`);
    }
    let table = $(".table-prezzi" + id_art);

    if (prezzo_anagrafica) {
        table.append(`<tr><td class="pr_anagrafica"><small>'.($options['dir'] == 'uscita' ? tr('Prezzo listino') : tr('Netto cliente')).': '.Plugins::link(($options['dir'] == 'uscita' ? 'Listino Fornitori' : 'Netto Clienti'), $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_anagrafica"><small>` + prezzo_anagrafica.toLocale() + ` ` + globals.currency + `</small></td>`);

        let tr = table.find(".pr_anagrafica").parent();
        if (prezzo_unitario == prezzo_anagrafica.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }

    if (prezzo_listino) {
        table.append(`<tr><td class="pr_listino"><small>'.tr('Prezzo listino').': '.Modules::link('Listini Cliente', $id_listino, tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_listino"><small>` + prezzo_listino.toLocale() + ` ` + globals.currency + `</small></td>`);

        let tr = table.find(".pr_listino").parent();
        if (prezzo_unitario == prezzo_listino.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }

    if (prezzo_std) {
        table.append(`<tr><td class="pr_std"><small>'.tr('Prezzo articolo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_std"><small>` + prezzo_std.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

        let tr = table.find(".pr_std").parent();
        if (prezzo_unitario == prezzo_std.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }

    if (prezzo_last) {
        table.append(`<tr><td class="pr_last"><small>'.tr('Ultimo prezzo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_last"><small>` + prezzo_last.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

        let tr = table.find(".pr_last").parent();
        if (prezzo_unitario == prezzo_last.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }

    /*if (prezzo_minimo) {
        table.append(`<tr><td class="pr_minimo"><small>'.tr('Prezzo minimo').': '.Modules::link('Articoli', $result['idarticolo'], tr('Visualizza'), null, '').'</small></td><td align="right" class="pr_minimo"><small>` + prezzo_minimo.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

        let tr = table.find(".pr_minimo").parent();
        if (prezzo_unitario == prezzo_minimo.toFixed(2)) {
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
        } else{
            tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
        }
        table.append(`</tr>`);
    }*/

    if (prezzi_visibili) {
        let i = 0;
        for (const prezzo_visibile of prezzi_visibili) {
            i++;
            let prezzo_listino_visibile = parseFloat(prezzo_visibile.prezzo_unitario_listino_visibile);
            table.append(`<tr><td class="pr_visibile_`+ i +`"><small>'.tr('Listino visibile ').'(` + prezzo_visibile.nome + `): </small></td><td align="right" class="pr_visibile_`+ i +`"><small>` + prezzo_listino_visibile.toLocale() + ` ` + globals.currency + `</small></td></tr>`);

            let tr = table.find(".pr_visibile_"+ i).parent();
            if (prezzo_unitario == prezzo_listino_visibile.toFixed(2)) {
                tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right disabled" style="font-size:10px;"><i class="fa fa-check"></i> '.tr('Aggiorna').'</button></td>`);
            } else{
                tr.append(`<td><button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaPrezzoArticolo(this)" style="font-size:10px;"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></td>`);
            }
            table.append(`</tr>`);
        }
    }

    if (prezzo_anagrafica || prezzo_listino || prezzo_std || prezzo_last || prezzi_visibili) {
        table.append(`</tbody></table>`);
    }
}

/**
* Restituisce il prezzo del listino registrato per l\'articolo-anagrafica.
*/
function getPrezzoListino(tr) {
    const data = $(tr).data("dettagli");
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
function getPrezzoScheda(tr) {
    const data = $(tr).data("dettagli");
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
function getPrezzoUltimo(tr) {
    const data = $(tr).data("dettagli");
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
function getPrezziListinoVisibili(tr, nome = "") {
    const data = $(tr).data("dettagli");
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
* Funzione per aggiornare il prezzo unitario sulla base dei valori automatici.
*/
function aggiornaPrezzoArticolo(button) {
    let fist_tr = $(button).closest("tr");
    let tr = $(button).closest("table").closest("tr");

    let prezzo_previsto = fist_tr.find("td:eq(1)").text();
    split_prezzo_previsto = prezzo_previsto.split(" ");
    prezzo_previsto = split_prezzo_previsto[0];

    //get tr id
    let id = tr.attr("id");
    let id_split = id.split("_");
    let id_art = id_split[id_split.length - 1];

    tr.find("#prezzo_unitario" + id_art).val(prezzo_previsto).trigger("change");

    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        //aggiorna_guadagno();
    }
}

/**
* Funzione per verificare se lo sconto unitario corrisponde a quello registrato per l\'articolo, e proporre in automatico una correzione.
*/
function verificaScontoArticolo(tr) {
    let qta = $(tr).find("input[name^=qta]").val().toEnglish();
    let sconto_previsto = getScontoPerQuantita(qta, tr);

    let sconto_input = $(tr).find("input[name^=sconto]");
    let sconto = sconto_input.val().toEnglish();

    let div = sconto_input.parent().next();
    if (sconto_previsto === 0 || sconto_previsto === sconto || $(tr).find("input[name^=tipo_sconto]").val() === "UNT") {
        div.css("padding-top", "0");
        div.html("");

        return;
    }

    div.css("padding-top", "5px");
    div.html(`<small class="label label-info" >'.tr('Sconto suggerito').': ` + sconto_previsto.toLocale()  + `%<button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiornaScontoArticolo(this)"><i class="fa fa-refresh"></i> '.tr('Aggiorna').'</button></small>`);
}

/**
* Funzione per aggiornare lo sconto unitario sulla base dei valori automatici.
*/
function aggiornaScontoArticolo(button) {
    let tr = $(button).closest("tr");
    let qta = tr.find("input[name^=qta]").val(); //.toEnglish();
    let sconto_previsto = getScontoPerQuantita(qta, tr);

    $("#sconto").val(sconto_previsto).trigger("change");

    // Aggiornamento automatico di guadagno e margine
    if (direzione === "entrata") {
        //aggiorna_guadagno();
    }
}
</script>

<table class="hidden">
    <tbody id="barcode-template">
        <tr id="riga_barcode_-id-">
            <td>
                |codice| - |descrizione|
                <br><small>|info_prezzi|</small>
                <input type="hidden" name="id_dettaglio_fornitore[-id-]" value="|id_dettaglio_fornitore|">
            </td>
            <td>
                {[ "type": "number", "name": "prezzo_unitario[-id-]", "value": "|prezzo_unitario|", "required": 0, "icon-after": "'.currency().'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "sconto[-id-]", "value": "|sconto_unitario|", "icon-after": "choice|untprc||tipo_sconto|", "help": "'.tr('Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'" ]}
            </td>

            <td>
                {[ "type": "number", "name": "qta[-id-]", "required": 0, "value": "|qta|", "decimals": "qta" ]}
            </td>

            <td width="5%" class="text-center">
                <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviRigaBarcode(\'-id-\')">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>';
