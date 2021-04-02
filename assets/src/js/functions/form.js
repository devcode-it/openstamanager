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

/**
 * @deprecated
 * @param form
 * @param data
 * @param callback
 * @param errorCallback
 * @returns {*|jQuery}
 */
function submitAjax(form, data, callback, errorCallback) {
    let valid = $(form).parsley().validate();
    if (!valid) {
        return valid;
    }

    if (!data) data = {};

    // Lettura dei contenuti degli input
    data = {...getInputsData(form), ...data};

    $("#main_loading").show();
    content_was_modified = false;

    // Fix per gli id di default
    data.id_module = data.id_module ? data.id_module : globals.id_module;
    data.id_record = data.id_record ? data.id_record : globals.id_record;
    data.id_plugin = data.id_plugin ? data.id_plugin : globals.id_plugin;
    data.ajax = 1;

    prepareForm(form);

    // Invio dei dati
    $(form).ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: data,
        type: "post",
        success: function (data) {
            let response = data.trim();

            // Tentativo di conversione da JSON
            try {
                response = JSON.parse(response);
            } catch (e) {
            }

            callback(response);

            $("#main_loading").fadeOut();

            renderMessages();
        },
        error: function (data) {
            $("#main_loading").fadeOut();

            toastr["error"](data);

            if (errorCallback) errorCallback(data);
        }
    });

    return valid;
}

/**
 *
 * @param form
 */
function prepareForm(form) {
    $(form).find('input:disabled, select:disabled').prop('disabled', false);

    let hash = window.location.hash;
    if (hash) {
        var input = $('<input/>', {
            type: 'hidden',
            name: 'hash',
            value: hash,
        });

        $(form).append(input);
    }
}

/**
 * Funzione per la gestione delle animazioni di caricamento sui pulsanti cliccati e appositamente predisposti,
 *
 * @param button
 * @returns {[*, *]}
 */
function buttonLoading(button) {
    let $this = $(button);

    let result = [
        $this.html(),
        $this.attr("class")
    ];

    $this.html('<i class="fa fa-spinner fa-pulse fa-fw"></i> Attendere...');
    $this.addClass("btn-warning");
    $this.prop("disabled", true);

    return result;
}

/**
 * Funzione per ripristinare un pulsante con animazioni allo stato precedente.
 *
 * @param button
 * @param loadingResult
 */
function buttonRestore(button, loadingResult) {
    let $this = $(button);

    $this.html(loadingResult[0]);

    $this.attr("class", "");
    $this.addClass(loadingResult[1]);
    $this.prop("disabled", false);
}

/**
 * Funzione per salvare i contenuti di un form via AJAX, utilizzando una struttura più recente fondata sull'utilizzo di Promise.
 *
 * @param button
 * @param form
 * @param data
 * @returns {Promise<unknown>}
 */
function salvaForm(form, data = {}, button = null) {
    return new Promise(function (resolve, reject) {
        // Caricamento visibile nel pulsante
        let restore = buttonLoading(button);

        // Messaggio in caso di eventuali errori
        let valid = $(form).parsley().validate();
        if (!valid) {
            swal({
                type: "error",
                title: globals.translations.ajax.missing.title,
                text: globals.translations.ajax.missing.text,
            });
            buttonRestore(button, restore);

            reject();
        }

        // Gestione grafica di salvataggio
        $("#main_loading").show();
        content_was_modified = false;

        // Lettura dei contenuti degli input
        data = {...getInputsData(form), ...data};
        data.ajax = 1;

        // Fix per gli id di default
        data.id_module = data.id_module ? data.id_module : globals.id_module;
        data.id_record = data.id_record ? data.id_record : globals.id_record;
        data.id_plugin = data.id_plugin ? data.id_plugin : globals.id_plugin;

        // Invio dei dati
        $.ajax({
            url: globals.rootdir + "/actions.php",
            data: data,
            type: "POST",
            success: function (data) {
                let response = data.trim();

                // Tentativo di conversione da JSON
                try {
                    response = JSON.parse(response);
                } catch (e) {
                }

                // Gestione grafica del successo
                $("#main_loading").fadeOut();
                renderMessages();
                buttonRestore(button, restore);

                resolve(response);
            },
            error: function (data) {
                toastr["error"](data);

                // Gestione grafica dell'errore
                $("#main_loading").fadeOut();
                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
                buttonRestore(button, restore);

                reject(data);
            }
        });
    });
}

/**
 * Funzione per recuperare come oggetto i contenuti degli input interni a un tag HTML.
 *
 * @param {HTMLElement|string|jQuery} form
 * @returns {{}}
 */
function getInputsData(form) {
    let place = $(form);
    let data = {};

    // Gestione input previsti con sistema JS integrato
    let inputs = place.find('.openstamanager-input');
    for (const x of inputs) {
        const i = input(x);
        const name = i.getElement().attr('name');
        const value = i.get();

        data[name] = value === undefined || value === null ? undefined : value;
    }

    // Gestione input HTML standard
    let standardInputs = place.find(':input').not('.openstamanager-input').serializeArray();
    for (const x of standardInputs) {
        data[x.name] = x.value;
    }

    // Gestione hash dell'URL
    let hash = window.location.hash;
    if (hash) {
        data['hash'] = hash;
    }

    return data;
}
