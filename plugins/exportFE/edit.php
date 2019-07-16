<?php

include_once __DIR__.'/init.php';

use Modules\Anagrafiche\Anagrafica;
use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;

if (!empty($fattura_pa)) {
    $disabled = false;
    $generated = $fattura_pa->isGenerated();
} else {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    <b>'.tr('Attenzione').':</b> '.tr('Per generare la fattura elettronica è necessario che sia in stato "Emessa"').'.
</div>';

    $disabled = true;
    $generated = false;
}

// Natura obbligatoria per iva con esenzione
$iva = $database->fetchOne('SELECT * FROM `co_iva` WHERE `id` IN (SELECT idiva FROM co_righe_documenti WHERE iddocumento = '.prepare($id_record).') AND esente = 1');
$fields = [
    'codice_natura_fe' => 'Natura IVA',
];
if (!empty($iva)) {
    $missing = [];
    foreach ($fields as $key => $name) {
        if (empty($iva[$key])) {
            $missing[] = $name;
        }
    }
}

if (!empty($missing) && !$generated) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr('Prima di procedere alla generazione della fattura elettronica completa i seguenti campi per IVA: _FIELDS_', [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
</div>';

    //$disabled = true;
}

// Campi obbligatori per il pagamento
$pagamento = $database->fetchOne('SELECT * FROM `co_pagamenti` WHERE `id` = '.prepare($record['idpagamento']));
$fields = [
    'codice_modalita_pagamento_fe' => 'Codice modalità pagamento FE',
];

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($pagamento[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing) && !$generated) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr('Prima di procedere alla generazione della fattura elettronica completa i seguenti campi per il Pagamento: _FIELDS_', [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
</div>';

    $disabled = true;
}

// Campi obbligatori per l'anagrafica Azienda
$azienda = FatturaElettronica::getAzienda();
$fields = [
    'piva' => 'Partita IVA',
    // 'codice_fiscale' => 'Codice Fiscale',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'cap' => 'C.A.P.',
    'nazione' => 'Nazione',
];

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($azienda[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi dell'anagrafica Azienda: _FIELDS_", [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
    <p>'.Modules::link('Anagrafiche', $azienda['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</p>
</div>';
}

// Campi obbligatori per l'anagrafica Cliente
$cliente = Anagrafica::find($record['idanagrafica']);
$fields = [
    // 'piva' => 'Partita IVA',
    // 'codice_fiscale' => 'Codice Fiscale',
    'citta' => 'Città',
    'indirizzo' => 'Indirizzo',
    'cap' => 'C.A.P.',
    'nazione' => 'Nazione',
];

// se privato/pa o azienda
if ($cliente['tipo'] == 'Privato' or $cliente['tipo'] == 'Ente pubblico') {
    // se privato/pa chiedo obbligatoriamente codice fiscale
    $fields['codice_fiscale'] = 'Codice Fiscale';
    // se pa chiedo codice unico ufficio
    ($cliente['tipo'] == 'Ente pubblico' && empty($cliente['codice_destinatario'])) ? $fields['codice_destinatario'] = 'Codice unico ufficio' : '';
} else {
    // se azienda chiedo partita iva
    $fields['piva'] = 'Partita IVA';
    // se italiana e non ho impostato ne il codice destinatario ne indirizzo PEC chiedo la compilazione di almeno uno dei due
    (empty($cliente['codice_destinatario']) and empty($cliente['pec']) && intval($cliente['nazione'] == 'IT')) ? $fields['codice_destinatario'] = 'Codice destinatario o indirizzo PEC' : '';
}

$missing = [];
foreach ($fields as $key => $name) {
    if (empty($cliente[$key])) {
        $missing[] = $name;
    }
}

if (!empty($missing)) {
    echo '
<div class="alert alert-warning">
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi dell'anagrafica Cliente: _FIELDS_", [
        '_FIELDS_' => '<b>'.implode(', ', $missing).'</b>',
    ]).'</p>
    <p>'.Modules::link('Anagrafiche', $record['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</p>
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

        <button id="genera" type="submit" class="btn btn-primary btn-lg '.($disabled ? 'disabled' : '').'" '.($disabled ? ' disabled' : null).'>
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
    $send = Interaction::isEnabled() && $generated && in_array($record['codice_stato_fe'], ['GEN', 'ERVAL']);

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
		    <b>'.$stato_fe['codice'].'</b> - '.$stato_fe['descrizione'].'</big> '.(!empty($record['descrizione_ricevuta_fe']) ? '<br><b>NOTE:</b><br>'.$record['descrizione_ricevuta_fe'] : '').'
		    <div class="pull-right">
		        <i class="fa fa-clock-o"></i> '.Translator::timestampToLocale($record['data_stato_fe']).'
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

if ($generated) {
    echo '
<script>
    $("#genera").click(function(event){
        event.preventDefault();

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
        });
    });
</script>';
}
