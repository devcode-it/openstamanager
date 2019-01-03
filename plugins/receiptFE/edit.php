<?php

include_once __DIR__.'/../../core.php';

use Plugins\ReceiptFE\Interaction;

if (!Interaction::isEnabled()) {
    echo '
<p>'.tr('Il sistema di rilevazione automatico è attualmente disabilitato').'. '.tr('Per maggiori informazioni contatta gli sviluppatori ufficiali').'.</p>';

    return;
}

echo '
<p>'.tr("Le ricevute delle Fatture Elettroniche permettono di individuare se una determinata fattura rilasciata è $requesta accettata dal Sistema Di Interscambio e dal cliente relativo").'.</p>

<p>'.tr("Tramite il pulsante _BTN_ è possibile procedere all controllo automatico di queste ricevute, che aggiorneranno di conseguenza lo $requesto dei documenti relativi e verranno allegate ad essi", [
    '_BTN_' => '<b>Ricerca</b>',
]).'.</p>
<br>';

echo '

<div class="text-center">
    <button type="button" class="btn btn-primary btn-lg" onclick="search(this)">
        <i class="fa fa-refresh"></i> '.tr('Ricerca').'...
    </button>
</div>';

echo '
<script>
    function search(btn) {
        var restore = buttonLoading(btn);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            data: {
                op: "list",
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
            },
            type: "post",
            success: function(data){
                data = JSON.parse(data);

                count = data.length;
                buttonRestore(btn, restore);

                swal({
                    title: "'.tr('Ricevute da importare: _COUNT_', [
                        '_COUNT_' => '" + count + "',
                    ]).'",
                    html: "'.tr('Sono state individuate _COUNT_ ricevute da importare', [
                        '_COUNT_' => '" + count + "',
                    ]).'.",
                    showCancelButton: true,
                    confirmButtonText: "'.tr('Procedi').'",
                    type: "info",
                }).then(function (result) {
                    importAll(btn);
                });
            },
            error: function(data) {
                alert("'.tr('Errore').': " + data);

                buttonRestore(btn, restore);
            }
        });
    }

    function importAll(btn) {
        var restore = buttonLoading(btn);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            data: {
                op: "import",
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
            },
            type: "post",
            success: function(data){
                data = JSON.parse(data);

                var html = "'.tr('Le seguenti ricevute sono state considerate in quanto non è stata trovata la fattura di vendita corrispondente nel gestionale:').'";

                console.log(data);
                data.forEach(function(element) {
                    var text = "";
                    if(element.fattura) {
                        text += element.fattura;
                    } else {
                        text += "<i>'.tr('Ricevuta non ottenuta correttamente').'</i>";
                    }

                    text += " (" + element.file + ")";

                    html += "<li>" + text + "</li>";
                });

                html += "<br><small>'.tr("Se si sono verificati degli errori durante la procedura e il problema continua a verificarsi, contatta l'assistenza ufficiale").'</small>";

                swal({
                    title: "'.tr('Importazione completata!').'",
                    html: html,
                    type: "info",
                })

                buttonRestore(btn, restore);
            },
            error: function(data) {
                alert("'.tr('Errore').': " + data);

                buttonRestore(btn, restore);
            }
        });
    }
</script>';
