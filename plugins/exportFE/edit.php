<?php

include_once __DIR__.'/init.php';

use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;

if (!empty($fattura_pa)) {
    $disabled = false;
    $generated = $fattura_pa->isGenerated();
} else {
    echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i>
    <b>'.tr('Attenzione!').'</b> '.tr('Per generare la fattura elettronica è necessario che sia in stato "Emessa"').'.
</div>';

    $disabled = true;
    $generated = false;
}

// Campi obbligatori per il pagamento
$pagamento = $database->fetchOne('SELECT * FROM `co_pagamenti` WHERE `id` = '.prepare($record['idpagamento']));
$fields = [
    'codice_modalita_pagamento_fe' => 'Codice di pagamento FE',
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
    <p><i class="fa fa-warning"></i> '.tr("Prima di procedere alla generazione della fattura elettronica completa i seguenti campi del tipo di pagamento: _FIELDS_", [
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
$cliente = FatturaElettronica::getAnagrafica($record['idanagrafica']);
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

<p>'.tr("Tutti gli allegati inseriti all'interno della categoria \"Fattura Elettronica\" saranno inclusi come allegati dell'XML").'.</p>
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

echo '
    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <a href="'.ROOTDIR.'/plugins/exportFE/download.php?id_record='.$id_record.'" class="btn btn-success btn-lg '.($generated ? '' : 'disabled').'" target="_blank" '.($generated ? '' : 'disabled').'>
        <i class="fa fa-download"></i> '.tr('Scarica').'
    </a>';

echo '

    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <a href="'.ROOTDIR.'/plugins/exportFE/view.php?id_record='.$id_record.'" class="btn btn-info btn-lg '.($generated ? '' : 'disabled').'" target="_blank" '.($generated ? '' : 'disabled').'>
        <i class="fa fa-eye"></i> '.tr('Visualizza').'
    </a>';

if (Interaction::isEnabled()) {
    $send = $generated && $record['codice_stato_fe'] == 'GEN';

    echo '

    <i class="fa fa-arrow-right fa-fw text-muted"></i>

    <button onclick="send(this)" class="btn btn-success btn-lg '.($send ? '' : 'disabled').'" target="_blank" '.($send ? '' : 'disabled').'>
        <i class="fa fa-paper-plane"></i> '.tr('Invia').'
    </button>

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

                    if (data.sent) {
                        swal("'.tr('Fattura inviata!').'", "'.tr('Fattura inoltrata con successo').'", "success");

                        $(btn).attr("disabled", true).addClass("disabled");
                    } else {
                        swal("'.tr('Invio fallito').'", "'.tr("L'invio della fattura è fallito").'", "error");
                    }
                },
                error: function(data) {
                    swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio').'", "error");

                    buttonRestore(btn, restore);
                }
            });
        }
    </script>';
}

echo '

</div>';

if ($generated) {
    echo '
<script>
    $("#genera").click(function(event){
        event.preventDefault();

        swal({
            title: "Sei sicuro di rigenerare la fattura?",
            text: "Attenzione: sarà generato un nuovo progressivo invio.",
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
