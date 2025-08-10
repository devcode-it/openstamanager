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
        renderHook(item);

        //startHooks(item, true);
        completedRequests++;
    });

    // Rimozione eventuale della rotella di caricamento
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

var timeout = 60;

setInterval(function () {
    startHooks();
}, timeout * 1000);

/**
 * Genera l'HTML per la visualizzazione degli hook.
 *
 * @param element_id
 * @param result
 */
function renderHook(hook) {
    var result = hook.content;
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
