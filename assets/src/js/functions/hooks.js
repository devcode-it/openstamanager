
function executeHook(hook, length){
    $("#hooks").append('<li id="hook-loader-' + hook.id + '"><a href="#">' + globals.translations.hookExecuting.replace('_NAME_', hook.name) + '</a></li>');

    $.ajax({
        url: globals.rootdir + "/ajax.php",
        type: "get",
        data: {
            op: "hook",
            id: hook.id,
        },
        success: function(data) {
            result = JSON.parse(data);

            $("#hook-loader-" + hook.id).remove();

            notification = '<li class="hook-element"><a href="' + (result.link ? result.link : "#") + '"><i class="' + result.icon + '"></i><span class="small" > ' + result.message + '</span></a></li>';

            // Inserimento della notifica
            hooks_number = $("#hooks-number");
            number = parseInt(hooks_number.text());
            number = isNaN(number) ? 0 : number;

            if(result.notify) {
                number++;

                $("#hooks").prepend(notification);
            } else {
                //$("#hooks").append(notification);
            }

            hooks_number.text(number);

            // Contatore dell'esecuzione degli hook
            hooks_counter = $("#hooks-counter");
            counter = parseInt(hooks_counter.text());
            counter++;
            hooks_counter.text(counter);

            // Rimozione eventuale della rotella di caricamento
            if(counter == hooks.length) {
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
