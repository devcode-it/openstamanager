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

/**
 * Modal gestito da versioni precedenti.
 * @param title
 * @param href
 * @param init_modal
 */
function launch_modal(title, href, init_modal) {
    openModal(title, href);
}

/**
 * Modal.
 * @param title
 * @param href
 */
function openModal(title, href) {
    // Fix - Select2 does not function properly when I use it inside a Bootstrap modal.
    $.fn.modal.Constructor.prototype.enforceFocus = function () {
    };

    // Generazione dinamica modal
    do {
        id = '#bs-popup-' + Math.floor(Math.random() * 100);
    } while ($(id).length !== 0);

    if ($(id).length === 0) {
        $('#modals').append('<div class="modal fade" id="' + id.replace("#", "") + '" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true"></div>');
    }

    $(id).on('hidden.bs.modal', function () {
        if ($('.modal-backdrop').length < 1) {
            $(this).html('');
            $(this).data('modal', null);
        }
    });

    var content = '<div class="modal-dialog modal-lg">\
    <div class="modal-content">\
        <div class="modal-header bg-light-blue">\
            <button type="button" class="close" data-dismiss="modal">\
                <span aria-hidden="true">&times;</span><span class="sr-only">' + globals.translations.close + '</span>\
            </button>\
            <h4 class="modal-title">\
                <i class="fa fa-pencil"></i> ' + title + '\
            </h4>\
        </div>\
        <div class="modal-body">|data|</div>\
    </div>\
</div>';

    // Lettura contenuto div
    if (href.substr(0, 1) === '#') {
        var data = $(href).html();

        $(id).html(content.replace("|data|", data));
        $(id).modal('show');
    } else {
        $.get(href, function (data, response) {
            if (response === 'success') {
                $(id).html(content.replace("|data|", data));
                $(id).modal('show');
            }
        });
    }
}

/**
 *
 * @param event
 * @param link
 */
function openLink(event, link) {
    if (event.ctrlKey) {
        window.open(link);
    } else {
        location.href = link;
    }
}

/**
 * Funzione per far scrollare la pagina fino a un offset
 * @param offset
 */
function scrollToOffset(offset) {
    $('html,body').animate({
        scrollTop: offset
    }, 'slow');
}

/**
 * Ritorna un array associativo con i parametri passati via GET
 */
function getUrlVars() {
    var search = window.location.search.substring(1);
    if (!search) return {};

    return JSON.parse('{"' + search.replace(/&/g, '","').replace(/=/g, '":"') + '"}', function (key, value) {
        return key === "" ? value : decodeURIComponent(value)
    });
}

/**
 * Data e ora (orologio)
 */
function clock() {
    $('#datetime').html(moment().formatPHP(globals.timestamp_format));
    setTimeout('clock()', 1000);
}

/**
 * Funzione per impostare un valore ad un array in $_SESSION
 */
function session_set_array(session_array, value, inversed) {
    if (inversed == undefined) {
        inversed = 1;
    }

    return $.get(globals.rootdir + "/ajax.php?op=session_set_array&session=" + session_array + "&value=" + value + "&inversed=" + inversed);
}

/**
 * Funzione per impostare un valore ad una sessione
 */
function session_set(session_array, value, clear, reload) {
    if (clear === undefined) {
        clear = 1;
    }

    if (reload === undefined) {
        reload = 0;
    }

    return $.get(globals.rootdir + "/ajax.php?op=session_set&session=" + session_array + "&value=" + value + "&clear=" + clear, function (data, status) {
        if (reload === 1) {
            location.reload();
        }
    });
}

function session_keep_alive() {
    $.get(globals.rootdir + '/core.php');
}

function setContrast(backgroundcolor) {
    var rgb = [];
    var bg = String(backgroundcolor);

    // ex. backgroundcolor = #ffc400
    rgb[0] = bg.substr(1, 2);
    rgb[1] = bg.substr(2, 2);
    rgb[2] = bg.substr(5, 2);

    var R1 = parseInt(rgb[0], 16);
    var G1 = parseInt(rgb[1], 16);
    var B1 = parseInt(rgb[2], 16);

    var R2 = 255;
    var G2 = 255;
    var B2 = 255;

    var L1 = 0.2126 * Math.pow(R1 / 255, 2.2) + 0.7152 * Math.pow(G1 / 255, 2.2) + 0.0722 * Math.pow(B1 / 255, 2.2);
    var L2 = 0.2126 * Math.pow(R2 / 255, 2.2) + 0.7152 * Math.pow(G2 / 255, 2.2) + 0.0722 * Math.pow(B2 / 255, 2.2);

    if (L1 > L2) {
        var lum = (L1 + 0.05) / (L2 + 0.05);
    } else {
        var lum = (L2 + 0.05) / (L1 + 0.05);
    }

    if (lum >= 9) {
        return "#ffffff";
    } else {
        return "#000000";
    }
}

function confirmDelete(button, title, message) {
    return swal({
        title: title ? title : globals.translations.deleteTitle,
        html: message ? message : globals.translations.deleteMessage,
        type: "warning",
        showCancelButton: true,
        confirmButtonText: globals.translations.delete,
        confirmButtonClass: "btn btn-lg btn-danger",
    })
}

function message(element) {
    data = $.extend({}, $(element).data());

    var title = globals.translations.deleteTitle;
    if (data["title"] != undefined) title = data["title"];

    var msg = globals.translations.deleteMessage;
    if (data["msg"] != undefined) msg = data["msg"];

    var button = globals.translations.delete;
    if (data["button"] != undefined) button = data["button"];

    var btn_class = "btn btn-lg btn-danger";
    if (data["class"] != undefined) btn_class = data["class"];

    swal({
        title: title,
        html: '<div id="swal-form" data-parsley-validate>' + msg + '</div>',
        type: "warning",
        showCancelButton: true,
        confirmButtonText: button,
        confirmButtonClass: btn_class,
        onOpen: function () {
            restart_inputs();
        },
        preConfirm: function () {
            $form = $('#swal-form');
            $form.find(':input').each(function () {
                data[$(this).attr('name')] = $(this).val();
            });

            if ($form.parsley().validate()) {
                return new Promise(function (resolve) {
                    resolve();
                });
            } else {
                $('.swal2-buttonswrapper button').each(function () {
                    $(this).prop('disabled', false);
                });
            }
        }
    }).then(
        function () {
            if (data["op"] == undefined) data["op"] = "delete";

            var href = window.location.href.split("#")[0];
            if (data["href"] != undefined) {
                href = data["href"];
                delete data.href;
            }

            var hash = window.location.href.split("#")[1];
            if (hash) {
                data["hash"] = hash;
            }

            method = "post";
            if (data["method"] != undefined) {
                if (data["method"] == "post" || data["method"] == "get") {
                    method = data["method"];
                }
                delete data.method;
            }

            blank = data.blank != undefined && data.blank;
            delete data.blank;

            if (data.callback) {
                $.ajax({
                    type: method,
                    crossDomain: true,
                    url: href,
                    data: data,
                    beforeSend: function (response) {
                        var before = window[data.before];

                        if (typeof before === 'function') {
                            before(response);
                        }
                    },
                    success: function (response) {
                        var callback = window[data.callback];

                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    },
                    error: function (xhr, ajaxOptions, error) {
                        swal({
                            title: globals.translations.errorTitle,
                            html: globals.translations.errorMessage,
                            type: "error",
                        })
                    },
                });
            } else {
                redirect(href, data, method, blank);
            }
        },
        function (dismiss) {
        }
    );
}

function redirect(href, data, method, blank) {
    method = method ? method : "get";
    blank = blank ? blank : false;

    if (method == "post") {
        var text = '<form action="' + href + window.location.hash + '" method="post"' + (blank ? ' target="_blank"' : '') + '>';

        for (var name in data) {
            text += '<input type="hidden" name="' + name + '" value="' + data[name] + '"/>';
        }

        text += '</form>';

        var form = $(text);
        $('body').append(form);

        form.submit();
    } else {
        var values = [];

        for (var name in data) {
            values.push(name + '=' + data[name]);
        }

        var link = href + (href.indexOf('?') !== -1 ? '&' : '?') + values.join('&') + window.location.hash;

        if (!blank) {
            location.href = link;
        } else {
            window.open(link);
        }
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function buttonLoading(button) {
    var $this = $(button);

    var result = [
        $this.html(),
        $this.attr("class")
    ];

    $this.html('<i class="fa fa-spinner fa-pulse fa-fw"></i> Attendere...');
    $this.addClass("btn-warning");
    $this.prop("disabled", true);

    return result;
}

function buttonRestore(button, loadingResult) {
    var $this = $(button);

    $this.html(loadingResult[0]);

    $this.attr("class", "");
    $this.addClass(loadingResult[1]);
    $this.prop("disabled", false);
}

function submitAjax(form, data, callback, errorCallback) {
    let valid = $(form).parsley().validate();
    if (!valid) {
        return valid;
    }

    if (!data) data = {};

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

function prepareForm(form) {
    $(form).find('input:disabled, select:disabled').prop('disabled', false);

    var hash = window.location.hash;
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
 * Visualizzazione dei messaggi attivi tramite toastr.
 */
function renderMessages() {
    $.ajax({
        url: globals.rootdir + '/ajax.php',
        type: 'get',
        data: {
            op: 'flash',
        },
        success: function (flash) {
            messages = JSON.parse(flash);

            info = messages.info ? messages.info : [];
            info.forEach(function (element) {
                if (element) toastr["success"](element);
            });

            warning = messages.warning ? messages.warning : [];
            warning.forEach(function (element) {
                if (element) toastr["warning"](element);
            });

            error = messages.error ? messages.error : [];
            error.forEach(function (element) {
                if (element) toastr["error"](element);
            });

        }
    });
}

function removeHash() {
    history.replaceState(null, null, ' ');
}

function replaceAll(str, find, replace) {
    return str.replace(new RegExp(find, "g"), replace);
}

/**
 * @deprecated
 */
function cleanup_inputs() {
    $('.bound').removeClass("bound");

    $('.superselect, .superselectajax').each(function () {
        let $this = $(this);

        if ($this.data('select2')) {
            $this.select2().select2("destroy")
        }
    });
}

/**
 * @deprecated
 */
function restart_inputs() {
    // Generazione degli input
    $('.openstamanager-input').each(function () {
        input(this);
    });
    /*
    start_datepickers();
    start_inputmask();

    initNumbers();
    start_superselect();

    // Autosize per le textarea
    initTextareaInput($('.autosize'));
     */
}

/**
 * Messaggio di avviso salvataggio a comparsa sulla destra solo nella versione a desktop intero
 */
function alertPush() {
    if ($(window).width() > 1023) {
        var i = 0;

        $('.alert-success.push').each(function () {
            i++;
            tops = 60 * i + 95;

            $(this).css({
                'position': 'fixed',
                'z-index': 3000,
                'right': '10px',
                'top': -100,
            }).delay(1000).animate({
                'top': tops,
            }).delay(3000).animate({
                'top': -100,
            });
        });
    }

    // Nascondo la notifica se passo sopra col mouse
    $('.alert-success.push').on('mouseover', function () {
        $(this).stop().animate({
            'top': -100,
            'opacity': 0
        });
    });
}

/**
 *
 * @param button
 * @param form
 * @param data
 * @returns {Promise<unknown>}
 */
function salvaForm(button, form, data = {}) {
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

            resolve(false);
        }

        submitAjax(form, data, function (response) {
            buttonRestore(button, restore);
            resolve(true);
        }, function (data) {
            swal({
                type: "error",
                title: globals.translations.ajax.error.title,
                text: globals.translations.ajax.error.text,
            });

            buttonRestore(button, restore);
            resolve(false);
        });
    });
}

/**
 * Nasconde una specifica colonna di una tabella indicata.
 *
 * @param table
 * @param column
 */
function hideTableColumn(table, column) {
    column = "" + column; // Cast a stringa

    // Verifica sulle colonne nascoste in precedenza
    let hiddenColumns = table.getAttribute("hidden-columns");
    hiddenColumns = hiddenColumns ? hiddenColumns.split(",") : [];
    if (hiddenColumns.includes(column)) {
        return;
    }

    // Salvataggio delle colonne nascoste
    hiddenColumns.push(column);
    table.setAttribute("hidden-columns", hiddenColumns.join(","));

    let rows = table.rows;
    for (let row of rows) {
        let currentColumn = 1;
        for (let i = 0; i < row.cells.length; i++) {
            let cell = row.cells[i];

            // Individuazione del colspan
            let colspan = parseInt(cell.getAttribute("colspan"));
            let hiddenColspan = cell.getAttribute("colspan-hidden");
            hiddenColspan = parseInt(hiddenColspan ? hiddenColspan : 0);
            let totalColspan = colspan + hiddenColspan;

            // Gestione dell'operazione nel caso di cella multipla
            if (totalColspan && totalColspan > 1) {
                if (column >= currentColumn && column <= currentColumn + totalColspan - 1) {
                    cell.setAttribute("colspan", colspan - 1);
                    cell.setAttribute("colspan-hidden", hiddenColspan + 1);

                    // Cella nascosta nel caso colspan sia nullo
                    if (colspan - 1 === 0) {
                        cell.classList.add("hidden");
                    }
                }

                currentColumn += totalColspan;
            }
            // Gestione di una cella normale
            else {
                if (column === "" + currentColumn) {
                    cell.classList.add("hidden");
                }
                currentColumn++;
            }
        }
    }
}

/**
 * Loads a JavaScript file and returns a Promise for when it is loaded
 */
function loadScript(src, async = true, defer = true) {
    if (!globals.dynamicScripts) {
        globals.dynamicScripts = {};
    }

    return new Promise((resolve, reject) => {
        // Caricamento già completato
        if (globals.dynamicScripts[src] && globals.dynamicScripts[src] === "done") {
            resolve();
            return;
        }

        // Aggiunta del resolve all'elenco per lo script
        if (!globals.dynamicScripts[src]) {
            globals.dynamicScripts[src] = [];
        }
        globals.dynamicScripts[src].push(resolve);

        // Ricerca dello script esistente
        let found = Array.prototype.slice.call(document.scripts).find(el => el.getAttribute("src") === src);
        if (found) {
            return;
        }

        // Caricamento dinamico dello script
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = async;
        script.defer = defer;

        script.onload = function () {
            for (resolve of globals.dynamicScripts[src]) {
                resolve();
            }

            globals.dynamicScripts[src] = "done";
        }

        script.onerror = reject;
        script.src = src;
        document.head.append(script);
    })
}

function aggiungiContenuto(endpoint_selector, template_selector, replaces = {}) {
    let template = $(template_selector);
    let endpoint = $(endpoint_selector);

    // Distruzione degli input interni
    template.find('.openstamanager-input').each(function () {
        input(this).destroy();
    });

    // Contenuto da sostituire
    let content = template.html();
    for ([key, value] of Object.entries(replaces)) {
        content = replaceAll(content, key, value);
    }

    // Aggiunta del contenuto
    let element = $(content);
    endpoint.append(element);

    // Rigenerazione degli input interni
    element.find('.openstamanager-input').each(function () {
        input(this).trigger("change");
    });

    return element;
}
