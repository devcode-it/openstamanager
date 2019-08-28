/**
 *
 */
function startHooks() {
    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hooks",
        },
        success: function (data) {
            hooks = JSON.parse(data);

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

                executeHook(item, true);
            });
        },
    });
}

/**
 * Esegue l'hook e lo visualizza.
 * Considerare l'utilizzo di localStorage per bloccare l'esecuzione locale multipla dell'hook nel caso di problemi.
 *
 * @param hook
 * @param element_id
 */
function executeHook(hook, init) {
    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hook",
            id: hook.id,
            init: init,
        },
        success: function (data) {
            var result = JSON.parse(data);

            renderHook(hook, result);

            var timeout;
            if (result.execute) {
                timeout = 1;
            } else {
                timeout = 30;
            }

            setTimeout(function () {
                executeHook(hook);
            }, timeout * 1000);

            if (init) {
                hookCount("#hooks-counter");
            }

            // Rimozione eventuale della rotella di caricamento
            var counter = $("#hooks-counter").text();
            var number = $("#hooks > li").length;
            $("#hooks-notified").text(number);

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
        $("#hooks").append('<li class="hook-element" id="' + element_id + '"></li>');

        element = $("#" + element_id);
    }

    // Rimozione
    if (!result.show) {
        element.remove();

        return;
    }

    // Contenuto
    var content = '<a href="' + (result.link ? result.link : "#") + '"><i class="' + result.icon + '"></i><span class="small"> ' + result.message + '</span>';

    if (result.progress) {
        var current = result.progress.current;
        var total = result.progress.total;
        var percentage = total == 0 ? current / total * 100 : 100;

        percentage = Math.round(percentage * 100) / 100;

        content += '<div class="progress" style="margin-bottom: 0px;"><div class="progress-bar" role="progressbar" aria-valuenow="' + percentage + '" aria-valuemin="0" aria-valuemax="100" style="width:' + percentage + '%">' + percentage + '% (' + current + '/' + total + ')</div></div>';
    }

    content += '</a>';

    element.html(content);
}
