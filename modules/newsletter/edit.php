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

use Modules\Emails\Mail;

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
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">'.tr('Aggiunta destinatari').'</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Destinatari').'", "name": "receivers[]", "ajax-source": "anagrafiche_newsletter", "multiple": 1 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Lista').'", "name": "id_list", "ajax-source": "liste_newsletter" ]}
                </div>
            </div>

            <div class="row pull-right">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
$(document).ready(function() {
    $("#receivers").on("change", function() {
        if ($(this).selectData()) {
            $("#id_list").attr("disabled", true).addClass("disabled")
        } else {
            $("#id_list").attr("disabled", false).removeClass("disabled")
        }
    })

    $("#id_list").on("change", function() {
        if ($(this).selectData()) {
            $("#receivers").attr("disabled", true).addClass("disabled")
        } else {
            $("#receivers").attr("disabled", false).removeClass("disabled")
        }
    })
})
</script>';

$anagrafiche = $newsletter->anagrafiche;

echo '
<!-- Destinatari -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            '.tr('Destinatari').'
            <span class="badge">'.$anagrafiche->count().'</span>
        </h3>
    </div>

    <div class="panel-body">';

if (!$anagrafiche->isEmpty()) {
    echo '
        <table class="table table-hover table-condensed table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Nome').'</th>
                    <th class="text-center">'.tr('Indirizzo').'</th>
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
                <tr '.((empty($anagrafica->email) || empty($anagrafica->enable_newsletter)) ? 'class="bg-danger"' : '').'>
                    <td>'.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'</td>
                    <td class="text-center">'.$anagrafica->email.'</td>
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
