<?php

use Models\Mail;

include_once __DIR__.'/../../core.php';

$block_edit = $newsletter->state != 'DEV';

$stati = [
    [
        'id' => 'DEV',
        'text' => 'Bozza',
    ],
    [
        'id' => 'WAIT',
        'text' => 'Invio in corso',
    ],
    [
        'id' => 'OK',
        'text' => 'Completata',
    ],
];

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title">'.tr('Dati campagna').'</h3>
		</div>
		
		<div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Template email').'", "name": "id_template", "values": "query=SELECT id, name AS descrizione FROM em_templates", "required": 1, "value": "$id_template$", "disabled": 1 ]}
                </div>
    
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "required": 1, "value": "$name$" ]}
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Stato').'", "name": "state", "values": '.json_encode($stati).', "required": 1, "value": "$state$", "class": "unblockable" ]}
                </div>
    
                <div class="col-md-6">
                    {[ "type": "timestamp", "label": "'.tr('Data di completamento').'", "name": "completed_at", "value": "$completed_at$", "disabled": 1 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "text", "label": "'.tr('Oggetto').'", "name": "subject", "value": "$subject$" ]}
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "ckeditor", "label": "'.tr('Contenuto').'", "name": "content", "value": "$content$" ]}
                </div>
            </div>
            
        </div>
	</div>
</form>

<form action="" method="post" id="receivers-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_receivers">
	
	<!-- Destinatari -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Destinatari').'</h3>
        </div>
        
        <div class="panel-body">
            <div class="row">
                <div class="col-md-9">
                    {[ "type": "select", "label": "'.tr('Destinatari').'", "name": "receivers[]", "ajax-source": "anagrafiche", "multiple": 1 ]}
                </div>
                
                <div class="col-md-3 text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                </div>
            </div>';

$anagrafiche = $newsletter->anagrafiche;
if (!$anagrafiche->isEmpty()) {
    echo '
            <table class="table table-striped table-hover table-condensed table-bordered">
                <thead>
                    <tr>
                        <th>'.tr('Nome').'</th>
                        <th class="text-center">'.tr('Data di invio').'</th>
                        <th class="text-center" width="60">#</th>
                    </tr>
                </thead>
            
                <tbody>';

    foreach ($anagrafiche as $anagrafica) {
        $mail_id = $anagrafica->pivot->id_email;
        $mail = Mail::find($mail_id);
        if (!empty($mail) && !empty($mail->sent_at)) {
            $data = timestampFormat($mail->sent_at);
        } else {
            $data = tr('Non ancora inviata');
        }

        echo '
                        <tr>
                            <td>'.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'</td>
                            <td class="text-center">'.$data.'</td>
                            <td class="text-center">
                                <a class="btn btn-danger ask btn-sm" data-backto="record-edit" data-op="remove_receiver" data-id="'.$anagrafica->id.'">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>';
    }

    echo '
                    </tbody>
            </table>';
} else {
    echo '
            <p>'.tr('Nessuna anagrafica collegata alla campagna').'.</p>';
}

    echo '
        </div>
    </div>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';

if ($block_edit) {
    echo '
<script>
$(document).ready(function() {
    $("#receivers").parent().hide();
    $("#receivers-form .btn").hide();
});
</script>';
}
