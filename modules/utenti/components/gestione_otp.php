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

include_once __DIR__.'/../../../core.php';

use Models\User;

$id_utente = filter('id_utente');
$utente = User::find($id_utente);

if (empty($utente)) {
    echo '<div class="alert alert-danger">'.tr('Utente non trovato').'</div>';
    return;
}

// Recupero o creazione del token per l'utente
$token_record = $dbo->fetchOne('SELECT * FROM `zz_otp_tokens` WHERE `id_utente` = '.prepare($id_utente));

if (empty($token_record)) {
    // Creo un nuovo token se non esiste
    $token = secure_random_string(32);
    $dbo->insert('zz_otp_tokens', [
        'id_utente' => $id_utente,
        'token' => $token,
        'descrizione' => 'Token OTP per '.$utente->username,
        'tipo_accesso' => 'otp',
        'valido_dal' => null,
        'valido_al' => null,
        'id_module_target' => 0,
        'id_record_target' => 0,
        'permessi' => null,
        'email' => $utente->email,
        'enabled' => 0,
        'last_otp' => '',
    ]);

    $token_record = $dbo->fetchOne('SELECT * FROM `zz_otp_tokens` WHERE `id_utente` = '.prepare($id_utente));
}

$otp_url = base_url().'/?token='.$token_record['token'];

// Verifica se il token è scaduto
if (!empty($token_record['valido_dal']) && !empty($token_record['valido_al'])) {
    $is_not_active = strtotime($token_record['valido_dal']) > time() || strtotime($token_record['valido_al']) < time();
}
if (!empty($token_record['valido_dal']) && empty($token_record['valido_al'])) {
    $is_not_active = strtotime($token_record['valido_dal']) > time();
}
if (empty($token_record['valido_dal']) && !empty($token_record['valido_al'])) {
    $is_not_active = strtotime($token_record['valido_al']) < time();
}

$is_otp_enabled = $token_record['enabled'];

// Verifica se l'utente ha un'email configurata
$has_email = !empty($utente->email) && filter_var($utente->email, FILTER_VALIDATE_EMAIL);

echo '
<form action="" method="post" id="otp-form">
    <input type="hidden" name="op" value="update_otp">
    <input type="hidden" name="id_utente" value="'.$id_utente.'">
    <input type="hidden" name="id_token" value="'.$token_record['id'].'">

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">'.tr('Gestione login tramite OTP').'</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>'.tr('URL per accesso OTP').':</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="otp_url" value="'.$otp_url.'" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="copy_url_btn" title="'.tr('Copia URL').'">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">'.tr('Utilizza questo URL per accedere direttamente al gestionale tramite token OTP').'</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        {[ "type": "timestamp", "label": "'.tr('Valido dal').'", "name": "valido_dal", "value": "'.$token_record['valido_dal'].'", "help": "'.tr('Data e ora di inizio validità del token. Lasciare vuoto per token permanente').'" ]}
                    </div>
                    <div class="col-md-4">
                        {[ "type": "timestamp", "label": "'.tr('Valido al').'", "name": "valido_al", "value": "'.$token_record['valido_al'].'", "help": "'.tr('Data e ora di fine validità del token. Lasciare vuoto per token permanente').'" ]}
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>'.tr('Stato OTP').':</label>
                            <div class="d-block">
                                <span style="padding:9px;font-size:9pt;" class="badge badge-'.($is_otp_enabled ? ($is_not_active ? 'warning' : 'success') : 'danger' ).'">
                                    '.($is_otp_enabled ? ($is_not_active ? tr('Non attivo') : tr('Abilitato')) : tr('Disabilitato')).'
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            '.($has_email ? '
            <div class="card-footer text-right">
                <button type="button" class="btn btn-success" id="save_expiry_btn">
                    <i class="fa fa-save"></i> '.tr('Salva').'
                </button>
                '.($token_record['enabled'] ?
                    '<button type="button" class="btn btn-danger ml-2" id="disable_otp_btn">
                        <i class="fa fa-times"></i> '.tr('Disattiva OTP').'
                    </button>' :
                    '<button type="button" class="btn btn-success ml-2" id="enable_otp_btn">
                        <i class="fa fa-check"></i> '.tr('Attiva OTP').'
                    </button>'
                ).'
            </div>
            ' : '
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle mr-2"></i>
                            <strong>'.tr('Attenzione!').'</strong> '.tr('Per attivare l\'OTP è necessario configurare un indirizzo email valido per questo utente.').'
                            <br><small>'.tr('Modifica l\'utente e inserisci un indirizzo email valido prima di procedere con l\'attivazione dell\'OTP.').'</small>
                        </div>
                    </div>
                </div>
            </div>').'
        </div>
    </div>
</div>
</form>

<script>
$(document).ready(function() {
    init();

    // Funzione per copiare negli appunti
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                toastr.success("'.tr('Copiato negli appunti!').'");
            }).catch(function(err) {
                console.error("Errore nella copia: ", err);
                fallbackCopyTextToClipboard(text);
            });
        } else {
            fallbackCopyTextToClipboard(text);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand("copy");
            if (successful) {
                toastr.success("'.tr('Copiato negli appunti!').'");
            } else {
                toastr.error("'.tr('Errore nella copia').'");
            }
        } catch (err) {
            toastr.error("'.tr('Errore nella copia').'");
        }

        document.body.removeChild(textArea);
    }

    // Copia URL
    $("#copy_url_btn").click(function() {
        var url = $("#otp_url").val();
        copyToClipboard(url);
    });

    // Salva data di scadenza
    $("#save_expiry_btn").click(function() {
        $.post(globals.rootdir + "/actions.php", {
            id_module: "'.$id_module.'",
            id_record: "'.$id_record.'",
            op: "update_otp_expiry",
            id_utente: "'.$id_utente.'",
            valido_dal: $("input[name=\'valido_dal\']").val(),
            valido_al: $("input[name=\'valido_al\']").val()
        }).done(function(data) {
            if (data.trim() === "ok") {
                toastr.success("'.tr('Data di scadenza salvata con successo!').'");
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                toastr.error("'.tr('Errore durante il salvataggio della data di scadenza').'");
            }
        }).fail(function() {
            toastr.error("'.tr('Errore durante il salvataggio della data di scadenza').'");
        });
    });

    // Attiva OTP (solo se il pulsante esiste)
    $("#enable_otp_btn").click(function() {
        '.($has_email ? '
        $.post(globals.rootdir + "/actions.php", {
            id_module: "'.$id_module.'",
            id_record: "'.$id_record.'",
            op: "enable_otp",
            id_utente: "'.$id_utente.'",
            id_token : "'.$token_record['id'].'"
        }).done(function(data) {
            if (data.trim() === "ok") {
                toastr.success("'.tr('OTP attivato con successo!').'");
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                toastr.error("'.tr('Errore durante l\'attivazione OTP').'");
            }
        }).fail(function() {
            toastr.error("'.tr('Errore durante l\'attivazione OTP').'");
        });' : '
        toastr.error("'.tr('Configura prima un indirizzo email per l\'utente').'");').'
    });

    // Disattiva OTP
    $("#disable_otp_btn").click(function() {
        $.post(globals.rootdir + "/actions.php", {
            id_module: "'.$id_module.'",
            id_record: "'.$id_record.'",
            op: "disable_otp",
            id_utente: "'.$id_utente.'",
            id_token : "'.$token_record['id'].'"
        }).done(function(data) {
            if (data.trim() === "ok") {
                toastr.success("'.tr('OTP disattivato con successo!').'");
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                toastr.error("'.tr('Errore durante la disattivazione OTP').'");
            }
        }).fail(function() {
            toastr.error("'.tr('Errore durante la disattivazione OTP').'");
        });
    });
});
</script>';