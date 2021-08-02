<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Modules\Emails\Mail;
use Modules\Emails\Template;

include_once __DIR__.'/../../core.php';

//Controllo se il template è ancora attivo
if (empty($template)) {
    echo '
    <div class="alert alert-danger">'.tr('ATTENZIONE! Questa newsletter risulta collegata ad un template non più presente a sistema').'</div>';
}

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
                    '.Modules::link('Template email', $record['id_template'], null, null, 'class="pull-right"').'
                    {[ "type": "select", "label": "'.tr('Template email').'", "name": "id_template", "values": "query=SELECT id, name AS descrizione FROM em_templates WHERE deleted_at IS NULL ORDER BY descrizione", "required": 1, "value": "$id_template$", "readonly": 1 ]}
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
                    {[ "type": "timestamp", "label": "'.tr('Data di completamento').'", "name": "completed_at", "value": "$completed_at$", "readonly": 1 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "text", "label": "'.tr('Oggetto').'", "name": "subject", "value": "$subject$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "ckeditor", "use_full_ckeditor": 1, "label": "'.tr('Contenuto').'", "name": "content", "value": "$content$" ]}
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
                    {[ "type": "select", "label": "'.tr('Destinatari').'", "name": "receivers[]", "ajax-source": "destinatari_newsletter", "multiple": 1 ]}
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

$destinatari = $newsletter->getDestinatari();

echo '
<!-- Destinatari -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">
            '.tr('Destinatari').'
            <span> ('.$destinatari->count().')</span> <div class="pull-right" >
            '.(($destinatari->where('email', '')->count() > 0) ? ' <span title="'.tr('Indirizzi e-mail mancanti').'" class="tip label label-danger clickable">'.$destinatari->where('email', '')->count().'</span>' : '')
            .'<span title="'.tr('Indirizzi e-mail senza consenso per newsletter').'" class="tip label label-warning clickable" id="numero_consenso_disabilitato"></span></div>
        </h3>
    </div>

    <div class="panel-body" style="max-height:700px;overflow-y:auto;">';

$senza_consenso = 0;
if (!$destinatari->isEmpty()) {
    echo '
        <table class="table table-hover table-condensed table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Ragione sociale').'</th>
                    <th>'.tr('Tipo').'</th>
                    <th>'.tr('Tipologia').'</th>
                    <th class="text-center">'.tr('E-mail').'</th>
                    <th class="text-center">'.tr('Data di invio').'</th>
                    <th class="text-center">'.tr('Newsletter').'</th>
                    <th class="text-center" width="60">#</th>
                </tr>
            </thead>

            <tbody>';

    $senza_consenso = 0;
    foreach ($destinatari as $destinatario) {
        $anagrafica = $destinatario instanceof Anagrafica ? $destinatario : $destinatario->anagrafica;
        $descrizione = $anagrafica->ragione_sociale;

        if ($destinatario instanceof Sede) {
            $descrizione .= ' ['.$destinatario->nomesede.']';
        } elseif ($destinatario instanceof Referente) {
            $descrizione .= ' ['.$destinatario->nome.']';
        }

        if (empty($anagrafica->enable_newsletter)){
            $senza_consenso ++;
        }

        $mail_id = $destinatario->pivot->id_email;
        $mail = Mail::find($mail_id);
        if (!empty($mail) && !empty($mail->sent_at)) {
            $data = '

    <span class="text-success">
        <i class="fa fa-paper-plane"></i> '.timestampFormat($mail->sent_at).'
     </span>';
        } else {
            $data = '
    <span class="text-info">
        <i class="fa fa-clock-o"></i> '.tr('Non ancora inviata').'
     </span>';
        }

        echo '
                <tr '.(empty($destinatario->email) ? 'class="bg-danger"' : (empty($anagrafica->enable_newsletter) ? 'class="bg-warning"' : '')).'>
                    <td>'.Modules::link('Anagrafiche', $anagrafica->id, $descrizione).'</td>
                    <td class="text-left">'.$database->fetchOne('SELECT GROUP_CONCAT(an_tipianagrafiche.descrizione) AS descrizione FROM an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica = an_tipianagrafiche.idtipoanagrafica  WHERE an_tipianagrafiche_anagrafiche.idanagrafica='.prepare($anagrafica->id))['descrizione'].'</td>
                    <td class="text-left">'.$anagrafica->tipo.'</td>
                    <td class="text-left">
                    '.((!empty($destinatario->email) ? '
                    {[ "type": "text", "name": "email", "id": "email_'.rand(0, 99999).'", "readonly": "1", "class": "email-mask", "value": "'.$destinatario->email.'", "validation": "email" ]}' : '<span class="text-danger"><i class="fa fa-close"></i> '.tr('Indirizzo e-mail mancante').'</span>')).'</td>
                    <td class="text-center">'.$data.'</td>
                    <td class="text-left">
                    '.((!empty($anagrafica->enable_newsletter)) ? '<span class="text-success"><i class="fa fa-check"></i> '.tr('Abilitato').'</span>' : '<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Disabilitato').'</span>').'
                    </td>
                    <td class="text-center">
                        <a class="btn btn-danger ask btn-xs" data-backto="record-edit" data-op="remove_receiver" data-type="'.get_class($destinatario).'" data-id="'.$destinatario->id.'">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>

        <a class="btn btn-danger ask pull-right" data-backto="record-edit" data-op="remove_all_receivers">
            <i class="fa fa-trash"></i> '.tr('Elimina tutti').'
        </a>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessuna anagrafica collegata alla campagna').'.
</div>';
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
else {
    echo '
<script>
globals.newsletter = {
    senza_consenso: "'.$senza_consenso.'",
};

$(document).ready(function() {
    const senza_consenso = $("#numero_consenso_disabilitato");
    if (globals.newsletter.senza_consenso > 0) {
        senza_consenso.text(globals.newsletter.senza_consenso);
    } else {
        senza_consenso.hide();
    }
});
</script>';
}
