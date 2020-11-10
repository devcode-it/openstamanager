<?php
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

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Modules\Fatture\Fattura;
use Plugins\ReceiptFE\Interaction;

echo '
<p>'.tr('Le ricevute delle Fatture Elettroniche permettono di individuare se una determinata fattura tramessa è stata accettata dal Sistema Di Interscambio').'.</p>';

if (Interaction::isEnabled()) {
    echo '
<p>'.tr('Tramite il pulsante _BTN_ è possibile procedere al recupero delle ricevute, aggiornando automaticamente lo stato delle relative fatture e allegandole ad esse', [
    '_BTN_' => '<i class="fa fa-refresh"></i> <b>'.tr('Ricerca ricevute').'</b>',
]).'.</p>';

    
    $fatture_generate_errore = Fattura::vendita()
        ->whereIn('codice_stato_fe', ['NS','ERR'])
        ->where('data_stato_fe', '>=', $_SESSION['period_start'])
        ->orderBy('data_stato_fe')
        ->get();

        if (!empty($fatture_generate_errore->count())) {
        echo '
        <div class="alert alert-warning alert-dismissible" role="alert"><button class="close" type="button" data-dismiss="alert" aria-hidden="true"><span aria-hidden="true">×</span><span class="sr-only">'.tr('Chiudi').'</span></button>
        <p><i class="fa fa-warning"></i> '.tr('Attenzione: le seguenti fatture hanno ricevuto uno scartato o hanno presentano errori in fase di trasmissione').'.</p>
        <ul>';

        foreach ($fatture_generate_errore as $fattura_generata) {
            echo '<li>'.reference($fattura_generata, $fattura_generata->getReference()).' ['.$fattura_generata['codice_stato_fe'].'] ['.timestampFormat($fattura_generata['data_stato_fe']).']</li>';
        }

        echo '
        </ul>
        </div>';
    }


    // Controllo se ci sono fatture in elaborazione da più di 7 giorni per le quali non ho ancora una ricevuta
    $data_limite = (new Carbon())->subDays(7);
    $fatture_generate = Fattura::vendita()
        ->where('codice_stato_fe', 'WAIT')
        ->where('data_stato_fe', '>=', $_SESSION['period_start'])
        ->where('data_stato_fe', '<', $data_limite)
        ->orderBy('data_stato_fe')
        ->get();

    if (!empty($fatture_generate->count())) {
        echo '
    <div class="alert alert-info info-dismissible" role="alert"><button class="close" type="button" data-dismiss="alert" aria-hidden="true"><span aria-hidden="true">×</span><span class="sr-only">'.tr('Chiudi').'</span></button>
        <p><i class="fa fa-info"></i> '.tr('Informazione: le seguenti fatture sono in attesa di una ricevuta da più di 7 giorni').'.</p>
        <ul>';

        foreach ($fatture_generate as $fattura_generata) {
            echo '<li>'.reference($fattura_generata, $fattura_generata->getReference()).' ['.timestampFormat($fattura_generata['data_stato_fe']).']</li>';
        }

        echo '
        </ul>
    </div>';
    }    

}

echo '
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">
            '.tr('Carica un XML').'

            <span class="tip" title="'.tr('Formati supportati: XML e P7M').'.">
                <i class="fa fa-question-circle-o"></i>
            </span>

        </h3>
    </div>
    <div class="box-body" id="upload">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "file", "name": "blob", "required": 1 ]}
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-primary pull-right" onclick="upload(this)">
                    <i class="fa fa-upload"></i> '.tr('Carica ricevuta').'
                </button>
            </div>
        </div>
    </div>
</div>';

echo '
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">
            '.tr('Ricevute da importare').'</span>
        </h3>';

// Ricerca automatica
if (Interaction::isEnabled()) {
    echo '
        <div class="pull-right">
            <button type="button" class="btn btn-warning" onclick="importAll(this)">
                <i class="fa fa-cloud-download"></i> '.tr('Importa tutte le ricevute').'
            </button>

            <button type="button" class="btn btn-primary" onclick="search(this)">
                <i class="fa fa-refresh"></i> '.tr('Ricerca ricevute').'
            </button>
        </div>';
}

echo '
    </div>
    <div class="box-body" id="list">';

if (Interaction::isEnabled()) {
    echo '
        <p>'.tr('Per vedere le ricevute da importare utilizza il pulsante _BUTTON_', [
            '_BUTTON_' => '<i class="fa fa-refresh"></i> <b>'.tr('Ricerca ricevute').'</b>',
        ]).'.</p>';
} else {
    include $structure->filepath('list.php');
}

    echo '

    </div>
</div>';

echo '
<script>
function search(button) {
    var restore = buttonLoading(button);

    $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
        buttonRestore(button, restore);
    });
}
function upload(btn) {
    if ($("#blob").val()) {
        var restore = buttonLoading(btn);

        $("#upload").ajaxSubmit({
            url: globals.rootdir + "/actions.php",
            data: {
                op: "save",
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
            },
            type: "post",
            success: function(data){
                importMessage(data);

                buttonRestore(btn, restore);
            },
            error: function(xhr) {
                alert("'.tr('Errore').': " + xhr.responseJSON.error.message);

                buttonRestore(btn, restore);
            }
        });
    } else {
        swal({
            title: "'.tr('Selezionare un file!').'",
            type: "error",
        })
    }
}

function importMessage(data) {
    data = JSON.parse(data);

    var ricevuta = "<br>'.tr('Ricevuta').': " + data.file;

    if(data.fattura) {
        swal({
            title: "'.tr('Importazione completata!').'",
            html: "'.tr('Fattura aggiornata correttamente').':" + data.fattura + ricevuta,
            type: "success",
        });
    } else {
        swal({
            title: "'.tr('Importazione fallita!').'",
            html: "<i>'.tr('Fattura relativa alla ricevuta non rilevata. Controlla che esista una fattura di vendita corrispondente caricata a gestionale.').'</i>" + ricevuta,
            type: "error",
        });
    }
}

function importAll(btn) {
    swal({
        title: "'.tr('Importare tutte le ricevute?').'",
        html: "'.tr('Importando le ricevute, verranno aggiornati gli stati di invio delle fatture elettroniche. Continuare?').'",
        showCancelButton: true,
        confirmButtonText: "'.tr('Procedi').'",
        type: "info",
    }).then(function (result) {
        var restore = buttonLoading(btn);
        $("#main_loading").show();

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

                if(data.length == 0){
                    var html = "'.tr('Non sono state trovate ricevute da importare').'.";
                } else {
                    var html = "'.tr('Sono state elaborate le seguenti ricevute:').'";

                    data.forEach(function(element) {
                        var text = "";
                        if(element.fattura) {
                            text += element.fattura;
                        } else {
                            text += "<i>'.tr('Fattura relativa alla ricevuta non rilevata. Controlla che esista una fattura di vendita corrispondente caricata a gestionale.').'</i>";
                        }

                        text += " (" + element.file + ")";

                        html += "<small><li>" + text + "</li></small>";
                    });

                    html += "<br><small>'.tr("Se si sono verificati degli errori durante la procedura e il problema continua a verificarsi, contatta l'assistenza ufficiale").'</small>";
                }

                $("#list").load("'.$structure->fileurl('list.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'", function() {
                    swal({
                        title: "'.tr('Operazione completata!').'",
                        html: html,
                        type: "info",
                    });

                    buttonRestore(btn, restore);
                    $("#main_loading").fadeOut();
                });

            },
            error: function(data) {
                alert("'.tr('Errore').': " + data);

                buttonRestore(btn, restore);
            }
        });
    });
}
</script>';
