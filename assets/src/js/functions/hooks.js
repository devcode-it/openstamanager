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
 *
 */

// Funzione per richiesta AJAX hooks completata con successo
function handleHooksSuccess(hooks) {

    completedRequests = 0;

    $("#hooks-header").text(globals.translations.hooksExecuting);
    $("#hooks-number").text(hooks.length);

    if (hooks.length == 0) {
        $("#hooks-loading").hide();
        $("#hooks-number").text(0);
        $("#hooks-header").text(globals.translations.hookNone);
    }

    hooks.forEach(function (item, index) {
        renderHook(item, {
            show: true,
            message: globals.translations.hookExecuting.replace('_NAME_', item.name)
        });

        startHook(item, true);
        completedRequests++;
    });

    totalRequests = hooks.length; 
    if (completedRequests === totalRequests) {
        // Verifica se tutte le richieste sono state completate con successo
        //console.log("Tutte le richieste AJAX sono state eseguite con successo.");
    }else{
        console.log("Alcune richieste AJAX non sono state eseguite.");
    }

}

function startHooks() {
    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hooks",
        },
        success: function (data) {
            hooks = JSON.parse(data);
            handleHooksSuccess(hooks);
        }, 
        error: function (xhr, status, error) {
            console.error("Errore durante la richiesta AJAX relativa agli Hooks");
        }
    });
}

/**
 * Richiama l'hook per l'esecuzione.
 *
 * @param hook
 */
function startHook(hook, init) {
    if (document.hasFocus()) {
        $.ajax({
            url: globals.rootdir + "/ajax.php",
            type: "get",
            data: {
                op: "hook-lock",
                id: hook.id,
            },
            success: function (data) {
                var token = JSON.parse(data);

                if (init) {
                    hookCount("#hooks-counter");

                    updateHook(hook);
                }

                if (token) {
                    executeHook(hook, token);
                } else {
                    //Rallentamento esecuzione hooks
                    var timeout = 30;

                    setTimeout(function () {
                        startHook(hook);
                    }, timeout * 1000);
                }
            },
        });
    } else {
        var timeout = 30;

        setTimeout(function () {
            startHook(hook);
        }, timeout * 1000);
    }
}

/**
 * Richiama l'hook per l'esecuzione.
 *
 * @param hook
 * @param token
 */
function executeHook(hook, token) {
    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hook-execute",
            id: hook.id,
            token: token,
        },
        success: function (data) {
            var result = JSON.parse(data);
            updateHook(hook);

            var timeout;
            if (result.execute) {
                startHook(hook);
            } else {
                timeout = 60;

                setTimeout(function () {
                    startHook(hook);
                }, timeout * 1000);
            }
        },
    });
}

/**
 * Aggiorna le informazioni dell'hook.
 *
 * @param hook
 * @param init
 */
function updateHook(hook) {
    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hook-response",
            id: hook.id,
        },
        success: function (data) {
            var result = JSON.parse(data);
            renderHook(hook, result);

            // Rimozione eventuale della rotella di caricamento
            var counter = $("#hooks-counter").text();
            var number = $("#hooks > div").length;

            if (number == 0) {
                $("#hooks-notified").html('<i class="fa fa-check" aria-hidden="true"></i>');
                $("#hooks-badge").removeClass();
                $("#hooks-badge").addClass('badge').addClass('badge-success');
            } else {
                $("#hooks-notified").text(number);
                $("#hooks-badge").removeClass();
                $("#hooks-badge").addClass('badge').addClass('badge-danger');
            }

            if (counter == $("#hooks-number").text()) {
                $("#hooks-loading").hide();

                var hookMessage;
                if (number > 1) {
                    hookMessage = globals.translations.hookMultiple.replace('_NUM_', number);
                } else if (number == 1) {
                    hookMessage = globals.translations.hookSingle;
                } else {
                    hookMessage = globals.translations.hookNone;
                }

                $("#hooks-header").text(hookMessage);
            }
        },
    });
}

/**
 * Aggiunta dell'hook al numero totale.
 */
function hookCount(id, value) {
    value = value ? value : 1;

    var element = $(id);
    var number = parseInt(element.text());
    number = isNaN(number) ? 0 : number;

    number += value;
    element.text(number);

    return number;
}

/**
 * Genera l'HTML per la visualizzazione degli hook.
 *
 * @param element_id
 * @param result
 */
function renderHook(hook, result) {
    if (result.length == 0) return;

    var element_id = "hook-" + hook.id;

    // Inizializzazione
    var element = $("#" + element_id);
    if (element.length == 0) {
        $("#hooks").append('<div class="dropdown-item hook-element" id="' + element_id + '"></div>');

        element = $("#" + element_id);
    }

    // Rimozione
    if (!result.show) {
        element.remove();

        return;
    }

    // Contenuto
    var content = '';

    if (result.link) {
        content += '<a href="' + result.link + '">';
    } else {
        content += '<div class="hooks-header">';
    }


    content += '<i class="' + result.icon + '"></i> <span class="small"> ' + result.message + '</span>';

    if (result.progress) {
        var current = result.progress.current;
        var total = result.progress.total;
        var percentage = current / total * 100;
        percentage = isNaN(percentage) ? 100 : percentage;

        percentage = Math.round(percentage * 100) / 100;

        content += '<div class="progress" style="margin-bottom: 0px;"><div class="progress-bar" role="progressbar" aria-valuenow="' + percentage + '" aria-valuemin="0" aria-valuemax="100" style="width:' + percentage + '%">' + percentage + '% (' + current + '/' + total + ')</div></div>';
    }

    if (result.link) {
        content += '</a>';
    } else {
        content += '</div>';
    }

    element.html(content);
}
