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
$destinatari_senza_consenso = $newsletter->getNumeroDestinatariSenzaConsenso();

echo '
<!-- Destinatari -->
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            '.tr('Destinatari').'
            <span> ('.$numero_destinatari.')</span>
            <div class="float-right d-none d-sm-inline">
                '.(($destinatari_senza_mail > 0) ? ' <span title="'.tr('Indirizzi e-mail mancanti').'" class="tip badge badge-danger clickable" id="numero_mail_mancanti">'.$destinatari_senza_mail.'</span>' : '')
                .(($destinatari_senza_consenso > 0) ? ' <span title="'.tr('Indirizzi e-mail senza consenso per newsletter').'" class="tip badge badge-warning clickable" id="numero_consenso_disabilitato">'.$destinatari_senza_consenso.'</span>' : '<span title="'.tr('Indirizzi e-mail senza consenso per newsletter').'" class="tip badge badge-warning clickable" id="numero_consenso_disabilitato" style="display:none;"></span>').'
            </div>
        </h3>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered" id="destinatari" style="width: 100%;">
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
        </div>

        <div class="table-destinatari-actions clearfix">';
if ($newsletter->state == 'DEV') {
    echo '
            <a class="btn btn-danger ask float-right" data-backto="record-edit" data-op="remove_all_receivers">
                <i class="fa fa-trash"></i> '.tr('Elimina tutti').'
            </a>';
} else {
    $msg_disabled_receivers = ($newsletter->state == 'WAIT')
        ? tr('Impossibile eliminare i destinatari con invio in corso')
        : tr('Impossibile eliminare i destinatari di una newsletter completata');
    echo '
            <div class="tip float-right" title="'.$msg_disabled_receivers.'">
                <span class="btn btn-danger disabled" style="cursor: not-allowed;">
                    <i class="fa fa-trash"></i> '.tr('Elimina tutti').'
                </span>
            </div>';
}
echo '
        </div>
    </div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}';

if ($newsletter->state == 'DEV') {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
} else {
    $msg_disabled_delete = ($newsletter->state == 'WAIT')
        ? tr('Impossibile eliminare la newsletter con invio in corso')
        : tr('Impossibile eliminare una newsletter già completata');
    echo '
<div class="tip d-inline-block" title="'.$msg_disabled_delete.'">
    <span class="btn btn-danger disabled" style="cursor: not-allowed;">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </span>
</div>';
}

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
<style>
#destinatari_wrapper {
    margin-bottom: 15px;
    width: 100% !important;
    max-width: 100% !important;
}

#destinatari {
    width: 100% !important;
    max-width: 100% !important;
}

#destinatari_wrapper .dataTables_info {
    padding-top: 14px;
    font-size: 13px;
    color: #6c757d;
    float: left;
}

#destinatari_wrapper .dataTables_paginate {
    float: right;
    padding-top: 10px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 4px;
}

#destinatari_wrapper .dataTables_paginate .paginate_button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    margin: 0 2px;
    font-size: 13px;
    font-weight: 500;
    line-height: 1;
    color: #495057 !important;
    background-color: #ffffff;
    border: 1px solid #ced4da;
    border-radius: 4px;
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    user-select: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

#destinatari_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
    color: #007bff !important;
    background-color: #f1f3f5;
    border-color: #007bff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#destinatari_wrapper .dataTables_paginate .paginate_button.current,
#destinatari_wrapper .dataTables_paginate .paginate_button.current:hover {
    color: #ffffff !important;
    background-color: #007bff !important;
    border-color: #007bff !important;
    cursor: default;
    box-shadow: 0 2px 4px rgba(0,123,255,0.25);
}

#destinatari_wrapper .dataTables_paginate .paginate_button.disabled,
#destinatari_wrapper .dataTables_paginate .paginate_button.disabled:hover {
    color: #adb5bd !important;
    background-color: #e9ecef !important;
    border-color: #dee2e6 !important;
    cursor: not-allowed;
    opacity: 0.65;
    box-shadow: none;
}
</style>

<script>
globals.newsletter = {
    senza_consenso: "'.$destinatari_senza_consenso.'",
    table_url: "'.$structure->fileurl('ajax/table.php').'?id_newsletter='.$id_record.'",
};

$(document).ready(function() {
    const senza_consenso = $("#numero_consenso_disabilitato");
    if (globals.newsletter.senza_consenso > 0) {
        senza_consenso.text(globals.newsletter.senza_consenso).show();
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
        autoWidth: false,
        ajax: {
            url: globals.newsletter.table_url,
            type: "GET",
            dataSrc: "data",
        },
        searchDelay: 500,
        pageLength: 50,
        initComplete: function () {
            const api = this.api();
            setTimeout(function () {
                api.columns.adjust();
            }, 1000);
        }
    });

    table.on("processing.dt", function (e, settings, processing) {
        if (processing) {
            $("#mini-loader").show();
        } else {
            $("#mini-loader").hide();
        }
    });

    setTimeout(function () {
        table.columns.adjust();
    }, 1000);

    $(window).on("load resize", function () {
        setTimeout(function () {
            table.columns.adjust();
        }, 1000);
    });
});

function testInvio(button) {
    const destinatario_id = $(button).data("id");
    const destinatario_type = $(button).data("type");
    const email = $(button).data("email");

    Swal.fire({
        title: "'.tr('Inviare la newsletter?').'",
        html: `'.tr("Vuoi effettuare un invio all'indirizzo _EMAIL_?", ['_EMAIL_' => '${email}']).' '.tr("L'email non sarà registrata come inviata, e l'invio della newsletter non escluderà questo indirizzo se impostato come invio di test").'.<br><br>
        {[ "type": "checkbox", "label": "'.tr('Invio di test').'", "name": "test" ]}`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Invia').'",
        customClass: { 
            confirmButton: "btn btn-lg btn-success",
            cancelButton: "btn btn-lg btn-secondary"
        },
        buttonsStyling: false
    }).then(function(result) {
        if (result.isConfirmed) {
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
                        Swal.fire("'.tr('Invio completato').'", "", "success");
                    } else {
                        Swal.fire("'.tr('Invio fallito').'", "", "error");
                    }
                },
                error: function() {
                    buttonRestore(button, restore);

                    Swal.fire("'.tr('Errore').'", "'.tr("Errore durante l'invio dell'email").'", "error");
                }
            });
        }
    });
}

function avviaInvioNewsletter(button) {
    Swal.fire({
        title: "'.tr('Procedere ad inviare la newsletter?').'",
        text: "'.tr('I destinatari verranno inseriti nella coda di invio.').'",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Invia').'",
        cancelButtonText: "'.tr('Annulla').'",
        customClass: {
            confirmButton: "btn btn-lg btn-warning mr-2",
            cancelButton: "btn btn-lg btn-secondary"
        },
        buttonsStyling: false
    }).then(function(result) {
        if (!result.isConfirmed) {
            return;
        }

        const restore = buttonLoading(button);

        Swal.fire({
            title: "'.tr('Preparazione invio newsletter...').'",
            html: `
                <div class="progress mt-3 mb-2" style="height: 25px;">
                    <div id="newsletter-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; font-size: 13px; font-weight: bold; line-height: 25px;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <div id="newsletter-progress-status" class="mt-2 text-muted" style="font-size: 14px;">
                    '.tr('Inizializzazione in corso...').'
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });

        let last_id = 0;
        let processed = 0;

        function eseguiBatch() {
            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "JSON",
                data: {
                    id_module: globals.id_module,
                    id_record: globals.id_record,
                    op: "send_batch",
                    last_id: last_id,
                    processed: processed,
                    batch_size: 50
                },
                success: function(response) {
                    if (!response || response.error) {
                        buttonRestore(button, restore);
                        Swal.fire("'.tr('Errore').'", response ? response.error : "'.tr('Errore durante la preparazione').'", "error");
                        return;
                    }

                    last_id = response.last_id;
                    processed = response.processed;
                    const total = response.total;
                    const pct = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 100;

                    $("#newsletter-progress-bar").css("width", pct + "%").attr("aria-valuenow", pct).text(pct + "%");
                    $("#newsletter-progress-status").text(processed + " / " + total + " '.tr('destinatari elaborati').'");

                    if (response.completed) {
                        buttonRestore(button, restore);
                        Swal.fire({
                            title: "'.tr('Completato!').'",
                            text: "'.tr('Campagna newsletter inserita nella coda di invio!').'",
                            icon: "success",
                            confirmButtonText: "'.tr('OK').'",
                            customClass: {
                                confirmButton: "btn btn-lg btn-success"
                            },
                            buttonsStyling: false
                        }).then(function() {
                            window.location.reload();
                        });
                    } else {
                        setTimeout(eseguiBatch, 50);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: "'.tr('Errore di connessione').'",
                        text: error || "'.tr('Impossibile completare l\'operazione. Vuoi riprovare?').'",
                        icon: "error",
                        showCancelButton: true,
                        confirmButtonText: "'.tr('Riprova').'",
                        cancelButtonText: "'.tr('Annulla').'",
                        customClass: {
                            confirmButton: "btn btn-lg btn-danger mr-2",
                            cancelButton: "btn btn-lg btn-secondary"
                        },
                        buttonsStyling: false
                    }).then(function(retryResult) {
                        if (retryResult.isConfirmed) {
                            eseguiBatch();
                        } else {
                            buttonRestore(button, restore);
                            window.location.reload();
                        }
                    });
                }
            });
        }

        eseguiBatch();
    });
}
</script>';
