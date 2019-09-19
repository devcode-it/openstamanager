<?php

include_once __DIR__.'/../../core.php';

echo '
<!-- DATI -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Informazioni').'</h3>
    </div>
    
    <div class="panel-body">
        
        <div class="row">
            <div class="col-md-3">
                <h4>'.tr('Template').'</h4>
                '.Modules::link('Template email', $mail->template->id, $mail->template->name).'
            </div>
            
            <div class="col-md-3">
                <h4>'.tr('Utente').'</h4>
                '.Modules::link('Anagrafiche', $mail->user->anagrafica->id, $mail->user->nome_completo).'
            </div>

            <div class="col-md-3">
                <h4>'.tr('Data di invio').'</h4>
                '.($mail->sent_at ? timestampFormat($mail->sent_at) : '-').'
            </div>
            
            <div class="col-md-3">
                <h4>'.tr('Ultimo tentativo').'</h4>
                '.($mail->failed_at ? timestampFormat($mail->failed_at) : '-').'
            </div>
        </div>

    </div>
</div>';

echo '
<h4>'.tr('Oggetto').'</h4>
<div class="well">
'.$mail->subject.'
</div>';

// Destinatari
$receivers = $mail->receivers;
echo '
<div class="row">
    <div class="col-md-4">
        <h4>'.tr('Destinatari').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Tipo').'</th>
                    <th>'.tr('Indirizzo').'</th>
                </tr>
            </thead>
            
            <tbody>';

foreach ($receivers as $receiver) {
    echo '
                <tr>
                    <td>'.$receiver->type.'</td>
                    <td>'.$receiver->address.'</td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>';

// Stampe
$prints = $mail->prints;
echo '
    <div class="col-md-4">
        <h4>'.tr('Stampe').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Stampa').'</th>
                    <th>'.tr('Nome').'</th>
                </tr>
            </thead>
            
            <tbody>';

foreach ($prints as $print) {
    echo '
                <tr>
                    <td>
                        <a href="'.Prints::getHref($print->name, $mail->id_record).'" target="_blank">'.$print->name.'</a>
                    </td>
                    <td>'.$print->pivot->name.'</td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>';

// Stampe
$uploads = $mail->uploads;
echo '
    <div class="col-md-4">
        <h4>'.tr('Allegati').'</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>'.tr('Allegato').'</th>
                    <th>'.tr('Nome').'</th>
                </tr>
            </thead>
            
            <tbody>';

foreach ($uploads as $upload) {
    echo '
                <tr>
                    <td>
                        <a href="'.ROOTDIR.'/view.php?file_id='.$upload->id.'" target="_blank">'.$upload->name.'</a>
                    </td>
                    <td>'.$upload->pivot->name.'</td>
                </tr>';
}

echo '
            </tbody>
        </table>
    </div>';

echo '
</div>';

echo '
<h4>'.tr('Contenuto').'</h4>
<div class="well">
    '.$mail->content.'
</div>';
