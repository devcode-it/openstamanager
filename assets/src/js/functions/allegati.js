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

// Disabling autoDiscover, otherwise Dropzone will try to attach twice.
Dropzone.autoDiscover = false;

/**
 * Restituisce filename ed estensione di un file indicato.
 * @param path
 * @returns [string, string]
 */
function getFilenameAndExtension(path) {
    let filename_extension = path.replace(/^.*[\\\/]/, '');
    let filename = filename_extension.substring(0, filename_extension.lastIndexOf('.'));
    let ext = filename_extension.split('.').pop();

    return [filename, ext];
}

/**
 * Inizializza la gestione degli allegati.
 * @param gestione
 */
function initGestioneAllegati(gestione) {
    const dropzone_id = '#' + gestione.attr('id') + ' .dropzone';
    const maxFilesize = gestione.data('max_filesize');
    if ($(dropzone_id).length === 0) {
        return;
    }

    let params = new URLSearchParams({
        op: "aggiungi-allegato",
        id_module: gestione.data('id_module'),
        id_plugin: gestione.data('id_plugin'),
        id_record: gestione.data('id_record'),
        id_category: gestione.data('id_category')
    }).toString();

    let dragdrop = new Dropzone(dropzone_id, {
        dictDefaultMessage: globals.translations.allegati.messaggio + ".<br>(" + globals.translations.allegati.maxFilesize.replace('_SIZE_', maxFilesize) + ")",
        paramName: "file",
        maxFilesize: maxFilesize, // MB
        uploadMultiple: false,
        parallelUploads: 2,
        addRemoveLinks: false,
        autoProcessQueue: true,
        autoQueue: true,
        url: globals.rootdir + "/actions.php?" + params,
        init: function (file, xhr, formData) {
            this.on("success", function (file) {
                dragdrop.removeFile(file);
            });

            this.on("complete", function (file) {
                // Ricarico solo quando ho finito
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    ricaricaAllegati(gestione);
                }
            });
        }
    });
}

/**
 * Funzione per l'apertura della schermata di modifica per una categoria di allegati.
 * @param gestione
 * @param pulsanteModifica
 */
function modificaCategoriaAllegati(gestione, pulsanteModifica) {
    const categoria = $(pulsanteModifica).parent().parent();
    console.log(categoria)

    const nome = categoria.find(".box-title");
    nome.addClass('hidden');
    $(pulsanteModifica).addClass('hidden');

    const pulsanteSalva = categoria.find(".category-save");
    const pulsanteAnnulla = categoria.find(".category-cancel");
    const inputNome = categoria.find(".category-name");
    pulsanteSalva.removeClass("hidden");
    pulsanteAnnulla.removeClass("hidden");
    inputNome.removeClass("hidden");
}

/**
 * Funzione per caricare un nuovo allegato.
 * @param gestione
 */
function aggiungiAllegato(gestione) {
    const id = "#" + gestione.attr('id');
    const form = $(id + " #upload-form");

    form.ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: data,
        type: "post",
        uploadProgress: function (event, position, total, percentComplete) {
            $(id + " #upload").prop("disabled", true).html(percentComplete + "%").removeClass("btn-success").addClass("btn-info");
        },
        success: function (data) {
            ricaricaAllegati(gestione);
        },
        error: function (data) {
            alert(globals.translations.allegati.errore + ": " + data);
        }
    });
}

/**
 * Funzione per mostrare il loader di caricamento per gli allegati.
 * @param gestione
 */
function mostraCaricamentoAllegati(gestione) {
    const id = "#" + gestione.attr('id');

    localLoading($(id + " .panel-body"), true);
}

/**
 * Funzione dedicata al caricamento dinamico degli allegati.
 * @param gestione
 */
function ricaricaAllegati(gestione) {
    const id = "#" + gestione.attr('id');

    let params = new URLSearchParams({
        op: "list_attachments",
        id_module: gestione.data('id_module'),
        id_plugin: gestione.data('id_plugin'),
        id_record: gestione.data('id_record'),
        id_category: gestione.data('id_category')
    }).toString();

    $(id).load(globals.rootdir + "/ajax.php?" + params, function () {
        localLoading($(id + " .panel-body"), false);

        const nuovoAllegato = $(id + " table tr").eq(-1).attr("id");
        if (nuovoAllegato !== undefined) {
            $("#" + nuovoAllegato).effect("highlight", {}, 1500);
        }
    });
}

/**
 * Funzione per l'apertura della pagina di gestione dei dati dell'allegato.
 * @param button
 * @param id
 * @param ids
 */
function modificaAllegato(button, ids) {
    const gestione = $(button).closest(".gestione-allegati");

    let params = new URLSearchParams({
        op: "visualizza-modifica-allegato",
        id_module: gestione.data('id_module'),
        id_plugin: gestione.data('id_plugin'),
        id_record: gestione.data('id_record'),
        id_allegati: ids,
    }).toString();

    openModal(globals.translations.allegati.modifica, globals.rootdir + "/actions.php?" + params);
}

/**
 * Funzione per gestire il download di un allegato.
 * @param button
 */
function scaricaAllegato(button) {
    const gestione = $(button).closest(".gestione-allegati");
    const allegato = $(button).closest("tr").data();

    let params = new URLSearchParams({
        op: "download-allegato",
        id_module: gestione.data('id_module'),
        id_plugin: gestione.data('id_plugin'),
        id_record: gestione.data('id_record'),
        id: allegato.id,
        filename: allegato.filename,
    }).toString();

    window.open(globals.rootdir + "/actions.php?" + params, "_blank")
}

/**
 * Funzione per gestire il download zip di allegati.
 * @param button
 * @param ids
 */
function scaricaZipAllegati(button, ids) {
    const gestione = $(button).closest(".gestione-allegati");

    let params = new URLSearchParams({
        op: "download-zip-allegati",
        id_module: gestione.data('id_module'),
        id_plugin: gestione.data('id_plugin'),
        id_record: gestione.data('id_record'),
        id: ids,
    }).toString();

    window.open(globals.rootdir + "/actions.php?" + params, "_blank")
}

/**
 * Funzione per l'apertura dell'anteprima di visualizzazione allegato.
 * @param button
 */
function visualizzaAllegato(button) {
    const allegato = $(button).closest("tr").data();

    let params = new URLSearchParams({
        file_id: allegato.id,
    }).toString();

    openModal(allegato.nome + ' <small style="color:white"><i>(' + allegato.filename + ')</i></small>', globals.rootdir + "/view.php?" + params);
}

/**
 * Funzione per la gestione della rimozione di un allegato specifico.
 *
 * @param button
 */
function rimuoviAllegato(button) {
    const gestione = $(button).closest(".gestione-allegati");
    const allegato = $(button).closest("tr").data();

    swal({
        title: globals.translations.allegati.elimina,
        type: "warning",
        showCancelButton: true,
        confirmButtonText: globals.translations.allegati.procedi,
    }).then(function () {
        mostraCaricamentoAllegati(gestione);

        // Parametri della richiesta AJAX
        let params = new URLSearchParams({
            op: "rimuovi-allegato",
            id_module: gestione.data('id_module'),
            id_plugin: gestione.data('id_plugin'),
            id_record: gestione.data('id_record'),
            id_allegato: allegato.id,
            filename: allegato.filename,
        }).toString();

        // Richiesta AJAX
        $.ajax(globals.rootdir + "/actions.php?" + params)
            .then(function () {
                ricaricaAllegati(gestione);
            });
    }).catch(swal.noop);
}
