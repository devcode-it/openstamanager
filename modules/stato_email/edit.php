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
                '.($mail->processing_at ? timestampFormat($mail->processing_at) : '-').' ('.tr('totale: _TOT_', [
                    '_TOT_' => $mail->attempt,
    ]).')
            </div>
        </div>

    </div>
</div>';

echo '
<h4>'.tr('Account mittente').'</h4>
<div class="well">
'.$mail->template->account->from_name.' &lt;'.$mail->template->account->from_address.'&gt; - '.Modules::link('Account email', $mail->template->account->id, $mail->template->account->name).'
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

if (empty($mail->sent_at)) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';

    if ($mail->attempt >= 10) {
        echo '
<a class="btn btn-warning ask pull-right" data-backto="record-edit" data-msg="'.tr("Rimettere in coda l'email?").'" data-op="retry" data-button="'.tr('Rimetti in coda').'" data-class="btn btn-lg btn-warning" >
    <i class="fa fa-refresh"></i> '.tr('Rimetti in coda').'
</a>';
    }
}
