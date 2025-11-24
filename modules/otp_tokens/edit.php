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
                                            <i class="fa fa-info-circle"></i> <?php echo tr('Seleziona prima il tipo di gestione permessi. "Accesso utente" per ereditare i permessi da un utente esistente, oppure "Personalizzato" per specificare modulo, record e permessi specifici.'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        {[ "type": "select", "label": "<?php echo tr('Tipo di gestione permessi'); ?>", "name": "tipo_gestione_permessi", "required": 1, "values": "list=\"\":\"<?php echo tr('Seleziona tipo di gestione'); ?>\",\"utente\":\"<?php echo tr('Accesso utente'); ?>\",\"personalizzato\":\"<?php echo tr('Personalizzato'); ?>\"", "value": "<?php echo !empty($record['id_utente']) ? 'utente' : (!empty($record['id_module_target']) ? 'personalizzato' : ''); ?>", "extra": "onchange=\"togglePermissionFields()\"" ]}
                                    </div>
                                </div>

                                <div class="row" id="utente-fields" style="display: <?php echo !empty($record['id_utente']) ? 'block' : 'none'; ?>;">
                                    <div class="col-md-12">
                                        {[ "type": "select", "label": "<?php echo tr('Utente'); ?>", "name": "id_utente", "ajax-source": "utenti", "value": "$id_utente$" ]}
                                    </div>
                                </div>

                                <div class="row" id="personalizzato-fields" style="display: <?php echo !empty($record['id_module_target']) ? 'block' : 'none'; ?>;">
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

function togglePermissionFields() {
    var tipoGestione = $('select[name="tipo_gestione_permessi"]').val();
    var utenteFields = $('#utente-fields');
    var personalizzatoFields = $('#personalizzato-fields');

    if (tipoGestione === 'utente') {
        // Mostra campi utente, nascondi campi personalizzati
        utenteFields.show();
        personalizzatoFields.hide();

        // Imposta required per utente
        $('select[name="id_utente"]').prop('required', true);

        // Rimuovi required dai campi personalizzati
        $('select[name="id_module_target"]').prop('required', false);
        $('select[name="id_record_target"]').prop('required', false);
        $('select[name="permessi"]').prop('required', false);

        // Pulisci i valori dei campi personalizzati
        $('select[name="id_module_target"]').val('').trigger('change');
        $('select[name="id_record_target"]').val('').trigger('change');
        $('select[name="permessi"]').val('').trigger('change');

    } else if (tipoGestione === 'personalizzato') {
        // Mostra campi personalizzati, nascondi campi utente
        utenteFields.hide();
        personalizzatoFields.show();

        // Rimuovi required dall'utente
        $('select[name="id_utente"]').prop('required', false);

        // Imposta required per campi personalizzati
        $('select[name="id_module_target"]').prop('required', true);
        $('select[name="id_record_target"]').prop('required', true);
        $('select[name="permessi"]').prop('required', true);

        // Pulisci il valore dell'utente
        $('select[name="id_utente"]').val('').trigger('change');

    } else {
        // Nessuna selezione - nascondi tutti i campi
        utenteFields.hide();
        personalizzatoFields.hide();

        // Rimuovi required da tutti i campi
        $('select[name="id_utente"]').prop('required', false);
        $('select[name="id_module_target"]').prop('required', false);
        $('select[name="id_record_target"]').prop('required', false);
        $('select[name="permessi"]').prop('required', false);

        // Pulisci tutti i valori
        $('select[name="id_utente"]').val('').trigger('change');
        $('select[name="id_module_target"]').val('').trigger('change');
        $('select[name="id_record_target"]').val('').trigger('change');
        $('select[name="permessi"]').val('').trigger('change');
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
    togglePermissionFields();
});

$('select[name="tipo_gestione_permessi"]').change(function() {
    togglePermissionFields();
});

$('#id_module_target').change(function() {
    session_set("superselect,id_module_target2", $(this).val(), 0);
    $('#id_record_target').val('').trigger('change');
    $('#permessi').val('').trigger('change');
});


</script>
