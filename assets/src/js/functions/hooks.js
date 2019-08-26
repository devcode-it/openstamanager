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
        success: function(data) {
            hooks = JSON.parse(data);

            $("#hooks-header").text(globals.translations.hooksExecuting);
            $("#hooks-number").text(hooks.length);

            if (hooks.length == 0) {
                $("#hooks-loading").hide();
                $("#hooks-number").text(0);
                $("#hooks-header").text(globals.translations.hookNone);
            }

            hooks.forEach(function(item, index){
                startHook(item);
            });
        },
    });
}

/**
 *
 * @param hook
 */
function startHook(hook){
    var element_id = "hook-" + hook.id;
    $("#hooks").append('<li class="hook-element" id="' + element_id + '"><a href="#">' + globals.translations.hookExecuting.replace('_NAME_', hook.name) + '</a></li>');

    element_id = "#" + element_id;

    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "prepare-hook",
            id: hook.id,
        },
        success: function(data) {
            var result = JSON.parse(data);

            addHookCount("#hooks-counter");

            if (result){
                renderHook(element_id, result);

                if (result.execute){
                    addHookCount("#hooks-notified");

                    executeHook(hook, element_id, true)
                } else {
                    $(element_id).remove();
                }
            } else {
                executeHook(hook, element_id)
            }
        },
    });
}

/**
 *
 * @param hook
 * @param element_id
 */
function executeHook(hook, element_id, is_background){
    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hook",
            id: hook.id,
        },
        success: function(data) {
            var result = JSON.parse(data);

            renderHook(element_id, result);

            if (!is_background) {
                if (result.notify) {
                    addHookCount("#hooks-notified");
                } else {
                    $(element_id).remove();
                }
            }

            // Rimozione eventuale della rotella di caricamento
            var counter = $("#hooks-counter").text();
            var number = $("#hooks-notified").text();
            if(counter == $("#hooks-number").text()) {
                $("#hooks-loading").hide();

                if (number > 1){
                    hookMessage = globals.translations.hookMultiple.replace('_NUM_', number);
                }else if(number == 1){
                    hookMessage = globals.translations.hookSingle;
                }else {
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
function addHookCount(id) {
    var hooks_number = $(id);
    var number = parseInt(hooks_number.text());
    number = isNaN(number) ? 0 : number;

    number++;
    hooks_number.text(number);

    return number;
}

/**
 *
 * @param element_id
 * @param result
 */
function renderHook(element_id, result) {
    $(element_id).html('<a href="' + (result.link ? result.link : "#") + '"><i class="' + result.icon + '"></i><span class="small" > ' + result.message + '</span></a>');
}
