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

include_once __DIR__.'/../../core.php';

// Generazione URL per i token
$base_url = base_url();
$url = '';
$otp_url = '';

if (!empty($record['token'])) {
    $url = $base_url.'/?token='.$record['token'];
}

// Verifica se il token è abilitato e attivo
$is_enabled = !empty($record['enabled']);
$token_status = '';
if (!$is_enabled) {
    $token_status = 'Disattivato';
} elseif (isset($is_not_active) && $is_not_active) {
    $token_status = 'Non attivo';
} else {
    $token_status = 'Attivo';
}

$status_class = !$is_enabled ? 'danger' : (isset($is_not_active) && $is_not_active ? 'warning' : 'success');

?>

<form action="" method="post" id="edit-form">
    <fieldset>
        <input type="hidden" name="backto" value="record-edit">
        <input type="hidden" name="op" value="update">

        <!-- DATI PRINCIPALI -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><?php echo tr('Generali'); ?></h3>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "text", "label": "<?php echo tr('Token'); ?>", "name": "token", "value": "$token$", "readonly": 1, "class": "text-center", "icon-after": "<span class=\"badge badge-<?php echo $status_class; ?>\" ><?php echo tr($token_status); ?></span>" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "timestamp", "label": "<?php echo tr('Valido dal'); ?>", "name": "valido_dal", "value": "$valido_dal$", "help": "Compila per limitare le date di utilizzo del token di accesso" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "timestamp", "label": "<?php echo tr('Valido al'); ?>", "name": "valido_al", "value": "$valido_al$", "help": "Compila per limitare le date di utilizzo del token di accesso" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                {[ "type": "select", "label": "<?php echo tr('Tipo di accesso'); ?>", "name": "tipo_accesso", "required": 1, "values": "list=\"token\":\"<?php echo tr('Token diretto'); ?>\",\"otp\":\"<?php echo tr('Token con OTP email'); ?>\"", "value": "$tipo_accesso$", "extra": "onchange=\"toggleEmailField()\"" ]}
                            </div>

                            <div class="col-md-6" id="email-field">
                                {[ "type": "email", "label": "<?php echo tr('Email a cui inviare OTP'); ?>", "name": "email", "value": "$email$", "validation": "email" ]}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-10">
                                <div class="form-group">
                                    <label><?php echo tr('URL'); ?></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="one_time_url" value="<?php echo $url; ?>" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('one_time_url')">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fa fa-users mr-2"></i> Gestione dei permessi
                                </h3>
                            </div>
                            <div class="card-body">
    
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> <?php echo tr('Se si seleziona l\'utente i permessi verranno ereditati da quell\'utente, in altrenativa è possibile indicare l\'accesso ad un modulo e un record con dei permessi specifici.'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-12">
                                                {[ "type": "select", "label": "<?php echo tr('Utente'); ?>", "name": "id_utente", "ajax-source": "utenti", "value": "$id_utente$" ]}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-12">
                                                {[ "type": "select", "label": "<?php echo tr('Modulo'); ?>", "name": "id_module_target", "ajax-source": "moduli_token", "value": "$id_module_target$" ]}
                                            </div>

                                            <div class="col-md-12">
                                                {[ "type": "select", "label": "<?php echo tr('ID record'); ?>", "name": "id_record_target", "ajax-source": "record_token", "select-options": <?php echo json_encode(['id_module_target' => $record['id_module_target']]); ?>, "value": "$id_record_target$"]}
                                            </div>

                                            <div class="col-md-12">
                                                {[ "type": "select", "label": "<?php echo tr('Permessi'); ?>", "name": "permessi", "values": "list=\"r\":\"<?php echo tr('Lettura'); ?>\",\"rw\":\"<?php echo tr('Lettura e scrittura'); ?>\",\"ra\":\"<?php echo tr('Caricamento allegati'); ?>\",\"rwa\":\"<?php echo tr('Caricamento e modifica allegati'); ?>\"", "value": "$permessi$" ]}
                                            </div>    
                                        </div>
                                    </div>
                                </div>
                                    
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
</form>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
function toggleEmailField() {
    var tipoAccesso = $('select[name="tipo_accesso"]').val();
    var emailField = $('#email-field');

    if (tipoAccesso === 'otp') {
        emailField.show();
        $('input[name="email"]').prop('required', true);
    } else {
        emailField.hide();
        $('input[name="email"]').prop('required', false);
    }
}

function togglePermissionField() {
    var idutente = $('select[name="id_utente"]').val();
    var idmodule = $('#id_module_target');
    var idrecord = $('#id_record_target');
    var permessi = $('#permessi');

    if (idutente) {
        idmodule.prop('required', false);
        idrecord.prop('required', false);
        permessi.prop('required', false);
    } else {
        idmodule.prop('required', true);
        idrecord.prop('required', true);
        permessi.prop('required', true);
    }
}

function copyToClipboard(elementId) {
    var element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    toastr.success('<?php echo tr('URL copiato negli appunti!'); ?>');
}

$(document).ready(function() {
    toggleEmailField();
    togglePermissionField();
});

$('#id_utente').change(function() {
    $('#id_module_target').val('').trigger('change');
    $('#id_record_target').val('');
    $('#permessi').val('').trigger('change');
    togglePermissionField();
});

$('#id_module_target').change(function() {
    session_set("superselect,id_module_target2", $(this).val(), 0);
    $('#id_record_target').val('').trigger('change');
    $('#permessi').val('').trigger('change');
});


</script>
