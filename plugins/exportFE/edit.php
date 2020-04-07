<?php

include_once __DIR__.'/init.php';

use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;

$abilita_genera = empty($fattura->codice_stato_fe) || in_array($fattura->codice_stato_fe, ['GEN', 'NS', 'EC02', 'ERR']);

if (!empty($fattura_pa)) {
    $disabled = false;
    $generated = $fattura_pa->isGenerated();
} else {
    $disabled = true;
    $generated = false;
}

$checks = FatturaElettronica::controllaFattura($fattura);
if (!empty($checks)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr('Prima di procedere alla generazione della fattura elettronica completa le seguenti informazioni').':</p>';

    foreach ($checks as $check) {
        echo '
    <p><b>'.$check['name'].' '.$check['link'].'</b></p>
    <ul>';

        foreach ($check['errors'] as $error) {
            if (!empty($error)) {
                echo '
        <li>'.$error.'</li>';
            }
        }

        echo '
    </ul>';
    }

    echo '
</div>';
}

echo '
<p>'.tr("Per effettuare la generazione dell'XML della fattura elettronica clicca sul pulsante _BTN_", [
    '_BTN_' => '<b>Genera</b>',
]).'. '.tr('Successivamente sarà possibile procedere alla visualizzazione e al download della fattura generata attraverso i pulsanti dedicati').'.</p>

<p>'.tr("Tutti gli allegati inseriti all'interno della categoria \"Allegati Fattura Elettronica\" saranno inclusi nell'XML").'.</p>
<br>';

echo '
<div class="text-center">
    <form action="" method="post" role="form" style="display:inline-block" id="form-xml">
        <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
        <input type="hidden" name="id_record" value="'.$id_record.'">
        <input type="hidden" name="backto" value="record-edit">
        <input type="hidden" name="op" value="generate">

        <button id="genera" type="submit" class="btn btn-primary btn-lg '.($disabled || !$abilita_genera ? 'disabled' : '').'" '.($disabled || !$abilita_genera ? ' disabled' : null).'>
            <i class="fa fa-file"></i> '.tr('Genera').'
        </button>
    </form>';

    $file = $generated ? Models\Upload::where('filename', $fattura_pa->getFilename())->where('id_record', $id_record)->first() : null;

echo '

    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <a href="'.ROOTDIR.'/view.php?file_id='.($file ? $file->id : null).'" class="btn btn-info btn-lg '.($generated ? '' : 'disabled').'" target="_blank" '.($generated ? '' : 'disabled').'>
        <i class="fa fa-eye"></i> '.tr('Visualizza').'
    </a>';

    // Scelgo quando posso inviarla
    $send = Interaction::isEnabled() && $generated && in_array($record['codice_stato_fe'], ['GEN', 'ERVAL', 'ERR']);

echo '
    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <a href="'.$structure->fileurl('download.php').'?id_record='.$id_record.'" class="btn btn-primary btn-lg '.($generated ? '' : 'disabled').'" target="_blank" '.($generated ? '' : 'disabled').'>
        <i class="fa fa-download"></i> '.tr('Scarica').'
    </a>';

echo '

    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <button onclick="if( confirm(\''.tr('Inviare la fattura al SDI?').'\') ){ send(this); }" class="btn btn-success btn-lg '.($send ? '' : 'disabled').'" target="_blank" '.($send ? '' : 'disabled').'>
        <i class="fa fa-paper-plane"></i> '.tr('Invia').'
    </button><br><br>';

// Messaggio esito invio
if (!empty($record['codice_stato_fe'])) {
    if ($record['codice_stato_fe'] == 'GEN') {
        echo '
		<div class="alert alert-info text-left"><i class="fa fa-info-circle"></i> '.tr("La fattura è stata generata ed è pronta per l'invio").'.</div>
		';
    } else {
        $stato_fe = database()->fetchOne('SELECT codice, descrizione, icon FROM fe_stati_documento WHERE codice='.prepare($record['codice_stato_fe']));

        if (in_array($stato_fe['codice'], ['EC01', 'RC'])) {
            $class = 'success';
        } elseif (in_array($stato_fe['codice'], ['ERVAL', 'GEN', 'MC', 'WAIT'])) {
            $class = 'warning';
        } else {
            $class = 'danger';
        }

        echo '
		<div class="alert text-left alert-'.$class.'">
		    <big><i class="'.$stato_fe['icon'].'" style="color:#fff;"></i> 
		    <b>'.$stato_fe['codice'].'</b> - '.$stato_fe['descrizione'].'</big> '.(!empty($record['descrizione_ricevuta_fe']) ? '<br><b>NOTE:</b> '.$record['descrizione_ricevuta_fe'] : '').'
		    <div class="pull-right">
		        <i class="fa fa-clock-o tip" title="'.tr('Data e ora ricezione').'" ></i> '.Translator::timestampToLocale($record['data_stato_fe']).'
            </div>
        </small>
		';
    }
}

echo '
    <script>
        function send(btn) {
            var restore = buttonLoading(btn);

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "post",
                data: {
                    op: "send",
                    id_module: "'.$id_module.'",
                    id_plugin: "'.$id_plugin.'",
                    id_record: "'.$id_record.'",
                },
                success: function(data) {
                    data = JSON.parse(data);
                    buttonRestore(btn, restore);

                    if (data.code == "200") {
                        swal("'.tr('Fattura inviata!').'", data.message, "success");

                        $(btn).attr("disabled", true).addClass("disabled");
                    } else {
                        swal("'.tr('Invio fallito').'", data.code + " - " + data.message, "error");
                    }
                },
                error: function(data) {
                    swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio').'", "error");

                    buttonRestore(btn, restore);
                }
            });
        }
    </script>';

echo '

</div>';

echo '
<script>
    $("#genera").click(function(event){
        event.preventDefault();

        var form = $("#edit-form");
        form.find("*").prop("disabled", false);
        valid = submitAjax(form);
        
        if (valid) {';

if ($generated) {
    echo '
            swal({
                title: "'.tr('Sei sicuro di rigenerare la fattura?').'",
                html: "<p>'.tr('Attenzione: sarà generato un nuovo progressivo invio').'.</p><p class=\"text-danger\">'.tr('Se stai attendendo una ricevuta dal sistema SdI, rigenerando la fattura elettronica non sarà possibile corrispondere la ricevuta una volta emessa').'.</p>",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#30d64b",
                cancelButtonColor: "#d33",
                confirmButtonText: "Genera"
            }).then((result) => {
                if (result) {
                    $("#form-xml").submit();
                }
            });';
} else {
    echo '
    
            $("#form-xml").submit();';
}
echo '
        } else {
            swal({
                type: "error",
                title: "'.tr('Errore').'",
                text:  "'.tr('Alcuni campi obbligatori non sono stati compilati correttamente').'.",
            });
        }
    });
</script>';
