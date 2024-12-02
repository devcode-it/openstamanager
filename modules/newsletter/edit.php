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

use Modules\Emails\Template;

include_once __DIR__.'/../../core.php';

// Controllo se il template è ancora attivo
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
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title">'.tr('Dati campagna').'</h3>
		</div>

		<div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    '.Modules::link('Template email', $record['id_template'], null, null, 'class="pull-right"').'
                    {[ "type": "select", "label": "'.tr('Template email').'", "name": "id_template", "values": "query=SELECT `em_templates`.`id`, `em_templates_lang`.`title` AS descrizione FROM `em_templates` LEFT JOIN `em_templates_lang` ON (`em_templates`.`id` = `em_templates_lang`.`id_record` AND `em_templates_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL ORDER BY `title`", "required": 1, "value": "$id_template$", "readonly": 1 ]}
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
                <div class="col-md-12">';
echo input([
    'type' => 'ckeditor',
    'use_full_ckeditor' => 1,
    'label' => tr('Contenuto'),
    'name' => 'content',
    'value' => $record['content'],
]);
echo '
                    </div>
            </div>

        </div>
	</div>
</form>

<form action="" method="post" id="receivers-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_receivers">

	<!-- Destinatari -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">'.tr('Aggiunta destinatari').'</h3>
        </div>

        <div class="card-body">
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

$numero_destinatari = $newsletter->destinatari()->count();
$destinatari_senza_mail = $newsletter->getNumeroDestinatariSenzaEmail();

echo '
<!-- Destinatari -->
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            '.tr('Destinatari').'
            <span> ('.$numero_destinatari.')</span>
            <div class="float-right d-none d-sm-inline">
                '.(($destinatari_senza_mail > 0) ? ' <span title="'.tr('Indirizzi e-mail mancanti').'" class="tip badge badge-danger clickable">'.$destinatari_senza_mail.'</span>' : '')
                .'<span title="'.tr('Indirizzi e-mail senza consenso per newsletter').'" class="tip badge badge-warning clickable" id="numero_consenso_disabilitato"></span>
            </div>
        </h3>
    </div>

    <div class="card-body">
        <table class="table table-hover table-sm table-bordered" id="destinatari">
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
        </table>

        <a class="btn btn-danger ask pull-right" data-backto="record-edit" data-op="remove_all_receivers">
            <i class="fa fa-trash"></i> '.tr('Elimina tutti').'
        </a>
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

echo '
<script>
globals.newsletter = {
    senza_consenso: "'.$newsletter->getNumeroDestinatariSenzaConsenso().'",
    table_url: "'.$structure->fileurl('ajax/table.php').'?id_newsletter='.$id_record.'",
};

$(document).ready(function() {
    const senza_consenso = $("#numero_consenso_disabilitato");
    if (globals.newsletter.senza_consenso > 0) {
        senza_consenso.text(globals.newsletter.senza_consenso);
    } else {
        senza_consenso.hide();
    }

    const table = $("#destinatari").DataTable({
        language: globals.translations.datatables,
        retrieve: true,
        ordering: false,
        searching: true,
        paging: true,
        order: [],
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: globals.newsletter.table_url,
            type: "GET",
            dataSrc: "data",
        },
        searchDelay: 500,
        pageLength: 50,
    });

    table.on("processing.dt", function (e, settings, processing) {
        if (processing) {
            $("#mini-loader").show();
        } else {
            $("#mini-loader").hide();
        }
    });
});

function testInvio(button) {
    const destinatario_id = $(button).data("id");
    const destinatario_type = $(button).data("type");
    const email = $(button).data("email");

    swal({
        title: "'.tr('Inviare la newsletter?').'",
        html: `'.tr("Vuoi effettuare un invio all'indirizzo _EMAIL_?", ['_EMAIL_' => '${email}']).' '.tr("L'email non sarà registrata come inviata, e l'invio della newsletter non escluderà questo indirizzo se impostato come invio di test").'.<br><br>
        {[ "type": "checkbox", "label": "'.tr('Invio di test').'", "name": "test" ]}`,
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Invia').'",
        confirmButtonClass: "btn btn-lg btn-success",
    }).then(function() {
        const restore = buttonLoading(button);
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "send-line",
                id: destinatario_id,
                type: destinatario_type,
                test: input("test").get(),
            },
            success: function (response) {
                buttonRestore(button, restore);

                if (response.result) {
                    swal("'.tr('Invio completato').'", "", "success");
                } else {
                    swal("'.tr('Invio fallito').'", "", "error");
                }
            },
            error: function() {
                buttonRestore(button, restore);

                swal("'.tr('Errore').'", "'.tr("Errore durante l'invio dell'email").'", "error");
            }
        });
    });
}
</script>';
