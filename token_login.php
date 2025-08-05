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

$skip_permissions = true;
include_once __DIR__.'/core.php';

use Carbon\Carbon;
use Models\User;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Notifications\EmailNotification;

$op = filter('op');
$token = filter('token');

// Verifica che sia stata fornita un token
if (empty($token)) {
    flash()->warning(tr('Token mancante'));
    redirect(base_path().'/index.php');
    exit;
}

// Verifica connessione database
if (!$dbo->isConnected() || !$dbo->isInstalled()) {
    redirect(base_path().'/index.php');
    exit;
}

// LOGIN TOKEN/OTP
switch ($op) {
    case 'otp_login':
        $token_param = post('token');
        $otp_code = post('otp_code');

        // Utilizza il metodo della classe Auth per gestire il login OTP
        $result = auth()->attemptOTPLogin($token_param, $otp_code);

        if ($result['success']) {
            // Imposta variabili di sessione per il calendario
            if (intval(setting('Inizio periodo calendario'))) {
                $_SESSION['period_start'] = Carbon::createFromFormat('d/m/Y', setting('Inizio periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_start'] = date('Y').'-01-01';
            }

            if (intval(setting('Fine periodo calendario'))) {
                $_SESSION['period_end'] = Carbon::createFromFormat('d/m/Y', setting('Fine periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_end'] = date('Y').'-12-31';
            }

            $_SESSION['keep_alive'] = true;

            // Login riuscito, determina il redirect basato sulla presenza dell'utente
            $token_info = $result['token_info'];
            if (!empty($token_info['id_utente'])) {
                // Token con utente: usa il redirect normale
                $redirect_url = Permissions::getTokenRedirectURL();
                if ($redirect_url) {
                    redirect($redirect_url);
                } else {
                    redirect(base_path().'/index.php');
                }
            } else {
                // Token senza utente: redirect a shared_editor.php
                if (!empty($token_info['id_module_target']) && !empty($token_info['id_record_target'])) {
                    redirect(base_path().'/shared_editor.php?id_module='.$token_info['id_module_target'].'&id_record='.$token_info['id_record_target']);
                } else {
                    flash()->warning(tr('Token non configurato correttamente per l\'accesso diretto'));
                    redirect(base_path().'/index.php');
                }
            }
            exit;
        } else {
            // Login fallito, mostra errore e torna al form OTP
            flash()->warning($result['message']);
            redirect(base_path().'/token_login.php?token='.urlencode($token_param).'&otp_requested=1');
            exit;
        }

        break;

    case 'token_login':
        // Login diretto tramite token (senza OTP)
        $token_param = post('token') ?: $token;

        if (!empty($token_param)) {
            // Utilizza il metodo della classe Auth per gestire il login tramite token
            $result = auth()->attemptTokenLogin($token_param);

            if ($result['success']) {
                // Imposta variabili di sessione per il calendario
                if (intval(setting('Inizio periodo calendario'))) {
                    $_SESSION['period_start'] = Carbon::createFromFormat('d/m/Y', setting('Inizio periodo calendario'))->format('Y-m-d');
                } else {
                    $_SESSION['period_start'] = date('Y').'-01-01';
                }

                if (intval(setting('Fine periodo calendario'))) {
                    $_SESSION['period_end'] = Carbon::createFromFormat('d/m/Y', setting('Fine periodo calendario'))->format('Y-m-d');
                } else {
                    $_SESSION['period_end'] = date('Y').'-12-31';
                }

                $_SESSION['keep_alive'] = true;

                // Login riuscito, redirect al modulo/record specifico se configurato
                $redirect_url = Permissions::getTokenRedirectURL();
                if ($redirect_url) {
                    redirect($redirect_url);
                } else {
                    redirect(base_path().'/index.php');
                }
                exit;
            } else {
                // Login fallito, mostra errore
                flash()->warning($result['message']);
                redirect(base_path().'/index.php');
                exit;
            }
        } else {
            flash()->error(tr('Token non valido'));
            redirect(base_path().'/index.php');
            exit;
        }

        break;

    case 'request_otp':
        // Gestisce la richiesta iniziale del codice OTP
        $token_param = post('token') ?: $token;

        if (!empty($token_param)) {
            // Genera e invia il primo codice OTP
            redirect(base_path().'/token_login.php?token='.urlencode($token_param).'&otp_requested=1');
            exit;
        } else {
            flash()->error(tr('Token non valido'));
            redirect(base_path().'/index.php');
            exit;
        }

        break;
}

// Gestione accesso tramite token
$token_record = null;
$show_otp_form = false;
$show_request_button = false;
$show_token_confirm = false;
$cooldown_period = 60;
$should_send_email = false;
$time_since_last_sent = 0;
$otp_requested = filter('otp_requested');

// Verifica validità del token
$token_record = $dbo->fetchOne('SELECT * FROM `zz_otp_tokens` WHERE `token` = '.prepare($token).' AND `enabled` = 1');

if (empty($token_record)) {
    // Registra il tentativo di accesso fallito nel log
    $dbo->insert('zz_logs', [
        'username' => $token_record['token'],
        'ip' => get_client_ip(),
        'stato' => Auth::getStatus()['failed']['code'],
        'id_utente' => null,
        'user_agent' => Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']),
    ]);

    flash()->warning(tr('Token non valido o non abilitato'));
    redirect(base_path().'/index.php');
    exit;
}

// Verifica se il token ha delle date impostate e se è attivo
$is_not_active = false;
if (!empty($token_record['valido_dal']) && !empty($token_record['valido_al'])) {
    $is_not_active = strtotime($token_record['valido_dal']) > time() || strtotime($token_record['valido_al']) < time();
}
if (!empty($token_record['valido_dal']) && empty($token_record['valido_al'])) {
    $is_not_active = strtotime($token_record['valido_dal']) > time();
}
if (empty($token_record['valido_dal']) && !empty($token_record['valido_al'])) {
    $is_not_active = strtotime($token_record['valido_al']) < time();
}
if ($is_not_active) {
    // Registra il tentativo di accesso con token scaduto nel log
    $dbo->insert('zz_logs', [
        'username' => $token_record['token'],
        'ip' => get_client_ip(),
        'stato' => Auth::getStatus()['failed']['code'],
        'id_utente' => $token_record['id_utente'] ?: null,
        'user_agent' => Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']),
    ]);

    flash()->warning(tr('Token non attivo'));
    redirect(base_path().'/index.php');
    exit;
}

if (!empty($token_record['id_utente'])) {
    $utente = User::find($token_record['id_utente']);

    if (!$utente || !$utente->enabled) {
        // Registra il tentativo di accesso con utente non abilitato nel log
        $dbo->insert('zz_logs', [
            'username' => $utente->username,
            'ip' => get_client_ip(),
            'stato' => Auth::getStatus()['failed']['code'],
            'id_utente' => $token_record['id_utente'] ?: null,
            'user_agent' => Filter::getPurifier()->purify($_SERVER['HTTP_USER_AGENT']),
        ]);

        flash()->warning(tr('Utente non abilitato'));
        redirect(base_path().'/index.php');
        exit;
    }
}

// Determina il tipo di accesso in base alla configurazione del token
$tipo_accesso = $token_record['tipo_accesso'];

// Se il tipo di accesso è 'token' (token diretto), mostra schermata di conferma
if ($tipo_accesso == 'token') {
    $confirm_access = filter('confirm_access');

    if ($confirm_access == '1') {
        // L'utente ha confermato, procedi con il login
        $result = auth()->attemptTokenLogin($token);

        if ($result['success']) {
            // Imposta variabili di sessione per il calendario
            if (intval(setting('Inizio periodo calendario'))) {
                $_SESSION['period_start'] = Carbon::createFromFormat('d/m/Y', setting('Inizio periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_start'] = date('Y').'-01-01';
            }

            if (intval(setting('Fine periodo calendario'))) {
                $_SESSION['period_end'] = Carbon::createFromFormat('d/m/Y', setting('Fine periodo calendario'))->format('Y-m-d');
            } else {
                $_SESSION['period_end'] = date('Y').'-12-31';
            }

            $_SESSION['keep_alive'] = true;

            // Determina il redirect basato sulla presenza dell'utente
            if (!empty($token_record['id_utente'])) {
                // Token con utente: usa il redirect normale
                $redirect_url = Permissions::getTokenRedirectURL();
                if ($redirect_url) {
                    redirect($redirect_url);
                } else {
                    redirect(base_path().'/index.php');
                }
            } else {
                // Token senza utente: redirect a editor_lite.php
                if (!empty($token_record['id_module_target']) && !empty($token_record['id_record_target'])) {
                    redirect(base_path().'/shared_editor.php?id_module='.$token_record['id_module_target'].'&id_record='.$token_record['id_record_target']);
                } else {
                    flash()->warning(tr('Token non configurato correttamente per l\'accesso diretto'));
                    redirect(base_path().'/index.php');
                }
            }
            exit;
        } else {
            flash()->warning($result['message']);
            redirect(base_path().'/index.php');
            exit;
        }
    } else {
        // Mostra schermata di conferma
        $show_token_confirm = true;
    }
}

// Gestione OTP solo per token di tipo 'otp'
if ($tipo_accesso == 'otp') {
    // Controlla se è stata richiesta la generazione dell'OTP
    if ($otp_requested) {
        // Genera nuovo codice OTP sicuro
        $otp_code = auth()->getOTP();

        // Salva l'OTP nel database
        $dbo->query('UPDATE `zz_otp_tokens` SET `last_otp` = '.prepare($otp_code).' WHERE `id` = '.prepare($token_record['id']));

        // Gestione cooldown per evitare spam di email
        $last_sent = $_SESSION['otp_last_sent_'.$token_record['id']] ?? 0;
        $time_since_last_sent = time() - $last_sent;
        $should_send_email = $time_since_last_sent >= $cooldown_period;

        if ($should_send_email) {
            // Invia email con OTP
            try {
                $template_id = setting('Template email richiesta codice OTP');
                $template = Template::find($template_id);

                if ($template) {
                    $mail = Mail::build(!empty($utente) ? $utente : null, $template, !empty($utente) ? $utente->id : null);
                    $mail->addReceiver($token_record['email']);

                    // Sostituisci la variabile {codice_otp} nel subject e body
                    $subject = str_replace('{codice_otp}', $otp_code, $template->getTranslation('subject'));
                    $body = str_replace('{codice_otp}', $otp_code, $template->getTranslation('body'));

                    $mail->subject = $subject;
                    $mail->content = $body;
                    $mail->save();

                    $email = EmailNotification::build($mail);
                    if ($email->send()) {
                        $_SESSION['otp_last_sent_'.$token_record['id']] = time();
                    }
                }
            } catch (Exception $e) {
                flash()->error(tr('Errore durante l\'invio dell\'email OTP: _MSG_', [
                    '_MSG_' => $e->getMessage(),
                ]));
                redirect(base_path().'/token_login.php?token='.urlencode($token));
                exit;
            }
        } else {
            // Non inviare email, mostra tempo rimanente
            $remaining_time = $cooldown_period - $time_since_last_sent;
        }

        $show_otp_form = true;
    } else {
        // Prima volta che si accede con il token - mostra il pulsante per richiedere l'OTP
        $show_request_button = true;

        // Controlla se c'è già un cooldown attivo
        $last_sent = $_SESSION['otp_last_sent_'.$token_record['id']] ?? 0;
        $time_since_last_sent = time() - $last_sent;
        $should_send_email = $time_since_last_sent >= $cooldown_period;
    }
}

// Controllo se l'utente è già autenticato
if (Auth::check()) {
    $module = Auth::firstModule();

    if (!empty($module)) {
        redirect(base_path().'/controller.php?id_module='.$module);
    } else {
        redirect(base_path().'/index.php?op=logout');
    }
    exit;
}

$pageTitle = tr('Login OTP');

include_once App::filepath('include|custom|', 'top.php');

// Controllo se è una beta e in caso mostro un warning
if (Update::isBeta()) {
    echo '
            <div class="clearfix"></div>
        <div class="alert alert-warning alert-dismissible col-md-6 offset-md-3 text-center show">
            <i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Attenzione!').'</strong> '.tr('Stai utilizzando una versione <b>non stabile</b> di OSM.').'
            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
        </div>';
}

// Controllo brute force
if (Auth::isBrute()) {
    echo '
    <div class="card card-danger shadow-lg col-md-6 offset-md-3 mt-5" id="brute">
        <div class="card-header text-center">
            <h3 class="card-title"><i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione').'</h3>
        </div>
        <div class="card-body text-center">
            <p class="lead">'.tr('Sono stati effettuati troppi tentativi di accesso consecutivi!').'</p>
            <div class="alert alert-warning">
                <p>'.tr('Tempo rimanente').':</p>
                <h3><span id="brute-timeout" class="badge badge-danger">'.(Auth::getBruteTimeout() + 1).'</span> '.tr('secondi').'</h3>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function(){
        $(".login-box").hide();
        brute();
    });

    function brute() {
        var value = parseFloat($("#brute-timeout").text()) - 1;
        $("#brute-timeout").text(value);

        if(value > 0){
            setTimeout(brute, 1000);
        } else {
            $("#brute").fadeOut(500, function() {
                $(".login-box").fadeIn(500);
            });
        }
    }
    </script>';
}

if ($show_request_button && !empty($token_record) && $tipo_accesso == 'otp') {
    // Pulsante per richiedere OTP (solo per token di tipo OTP)
    echo '
    <div class="login-box card-center-medium">
        <div class="card card-primary shadow-lg">
            <div class="card-header text-center bg-light py-4">
                <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid" style="max-width: 85%;">
            </div>

            <div class="card-body pt-4">
                <p class="login-box-msg text-secondary"><i class="fa fa-shield mr-2"></i>'.tr('Accesso con codice OTP').'</p>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-2"></i>'.tr('Per accedere al gestionale è necessario un codice di verifica che verrà inviato all\'indirizzo email: <strong>_EMAIL_</strong>', [
        '_EMAIL_' => blurEmail($token_record['email']),
    ]).'
                </div>';

    // Mostra informazioni sul token se disponibili
    if (!empty($token_record['id_module_target'])) {
        $module_info = $dbo->fetchOne('SELECT name FROM zz_modules WHERE id = '.prepare($token_record['id_module_target']));
        if ($module_info) {
            echo '
                <div class="alert alert-light border">
                    <strong>'.tr('Modulo di destinazione').':</strong> '.tr($module_info['name']).'<br>';

            if (!empty($token_record['id_record_target'])) {
                // Recupera il nome del record utilizzando la stessa logica di modules/otp_tokens/ajax/select.php
                $record_name = 'ID '.$token_record['id_record_target'];

                if ($module_info['name'] == 'Anagrafiche') {
                    $anagrafica = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare($token_record['id_record_target']).' AND deleted_at IS NULL');
                    if ($anagrafica) {
                        $record_name = $anagrafica['ragione_sociale'].' (ID '.$token_record['id_record_target'].')';
                    }
                } elseif ($module_info['name'] == 'Gestione documentale') {
                    $documento = $dbo->fetchOne('SELECT nome FROM do_documenti WHERE id = '.prepare($token_record['id_record_target']));
                    if ($documento) {
                        $record_name = $documento['nome'].' (ID '.$token_record['id_record_target'].')';
                    }
                }

                echo '<strong>'.tr('Record specifico').':</strong> '.$record_name.'<br>';
            }

            if (!empty($token_record['permessi'])) {
                switch ($token_record['permessi']) {
                    case 'r':
                        $permessi_label = tr('Sola lettura');
                        break;
                    case 'rw':
                        $permessi_label = tr('Lettura e scrittura');
                        break;
                    case 'ra':
                        $permessi_label = tr('Caricamento allegati');
                        break;
                    case 'rwa':
                        $permessi_label = tr('Caricamento e modifica allegati');
                        break;
                    default:
                        $permessi_label = tr('Non definito');
                        break;
                }
                echo '<strong>'.tr('Permessi').':</strong> '.$permessi_label.'<br>';
            }

            // Mostra la data di scadenza se disponibile
            if (!empty($token_record['valido_al'])) {
                $data_scadenza = date('d/m/Y H:i', strtotime($token_record['valido_al']));
                echo '<strong>'.tr('Scade il').':</strong> '.$data_scadenza;
            }

            echo '
                </div>';
        }
    }

    // Controlla se c'è un cooldown attivo
    if (!$should_send_email && $time_since_last_sent > 0) {
        $remaining_time = $cooldown_period - $time_since_last_sent;
        echo '
                <div class="alert alert-warning">
                    <i class="fa fa-clock-o mr-2"></i>'.tr('Puoi richiedere un nuovo codice OTP tra').' <span id="countdown-timer" class="font-weight-bold">'.ceil($remaining_time).'</span> '.tr('secondi').'
                </div>';
    }

    echo '
                <form action="?op=request_otp" method="post" autocomplete="off">
                    <input type="hidden" name="token" value="'.htmlspecialchars($token).'">

                    <button type="submit" class="btn btn-primary btn-block btn-lg shadow-sm" id="request-otp-button" '.($should_send_email && $time_since_last_sent > 0 ?: 'disabled').'>
                        <i class="fa fa-envelope mr-2"></i>'.tr('Richiedi codice OTP').'
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="'.base_path().'/index.php" class="text-secondary">
                        <i class="fa fa-arrow-left mr-1"></i>'.tr('Torna al login classico').'
                    </a>
                </div>
            </div>
        </div>
    </div>';

    // JavaScript per il countdown
    if (!$should_send_email && $time_since_last_sent > 0) {
        echo '
        <script>
        $(document).ready(function(){
            startRequestCountdown();
        });

        function startRequestCountdown() {
            var countdown = parseInt($("#countdown-timer").text());

            if (countdown <= 0) {
                return;
            }

            var timer = setInterval(function() {
                countdown--;
                $("#countdown-timer").text(countdown);

                if (countdown <= 0) {
                    clearInterval(timer);

                    // Riabilita il pulsante e aggiorna la pagina
                    $("#request-otp-button").prop("disabled", false);
                    $(".alert-warning").fadeOut(300);
                }
            }, 1000);
        }
        </script>';
    }
} elseif ($show_otp_form && !empty($token_record) && $tipo_accesso == 'otp') {
    // Form OTP (solo per token di tipo OTP)
    echo '
    <form action="?op=otp_login" method="post" autocomplete="off">
        <input type="hidden" name="token" value="'.htmlspecialchars($token).'">
        <div class="login-box card-center-medium">
            <div class="card card-primary shadow-lg">
                <div class="card-header text-center bg-light py-4">
                    <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid" style="max-width: 85%;">
                </div>

                <div class="card-body pt-4">
                    <p class="login-box-msg text-secondary"><i class="fa fa-shield mr-2"></i>'.tr('Inserisci il codice OTP').'</p>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle mr-2"></i>'.tr('È stato inviato un codice di verifica all\'indirizzo email <strong>_EMAIL_</strong>, inseriscilo qui sotto.', [
        '_EMAIL_' => blurEmail($token_record['email']),
    ]).'
                    </div>';

    // Mostra informazioni sul token se disponibili
    if (!empty($token_record['id_module_target'])) {
        $module_info = $dbo->fetchOne('SELECT name FROM zz_modules WHERE id = '.prepare($token_record['id_module_target']));
        if ($module_info) {
            echo '
                <div class="alert alert-light border">
                    <strong>'.tr('Modulo di destinazione').':</strong> '.tr($module_info['name']).'<br>';

            if (!empty($token_record['id_record_target'])) {
                // Recupera il nome del record utilizzando la stessa logica di modules/otp_tokens/ajax/select.php
                $record_name = 'ID '.$token_record['id_record_target'];

                if ($module_info['name'] == 'Anagrafiche') {
                    $anagrafica = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare($token_record['id_record_target']).' AND deleted_at IS NULL');
                    if ($anagrafica) {
                        $record_name = $anagrafica['ragione_sociale'].' (ID '.$token_record['id_record_target'].')';
                    }
                } elseif ($module_info['name'] == 'Gestione documentale') {
                    $documento = $dbo->fetchOne('SELECT nome FROM do_documenti WHERE id = '.prepare($token_record['id_record_target']));
                    if ($documento) {
                        $record_name = $documento['nome'].' (ID '.$token_record['id_record_target'].')';
                    }
                }

                echo '<strong>'.tr('Record specifico').':</strong> '.$record_name.'<br>';
            }

            if (!empty($token_record['permessi'])) {
                switch ($token_record['permessi']) {
                    case 'r':
                        $permessi_label = tr('Sola lettura');
                        break;
                    case 'rw':
                        $permessi_label = tr('Lettura e scrittura');
                        break;
                    case 'ra':
                        $permessi_label = tr('Caricamento allegati');
                        break;
                    case 'rwa':
                        $permessi_label = tr('Caricamento e modifica allegati');
                        break;
                    default:
                        $permessi_label = tr('Non definito');
                        break;
                }
                echo '<strong>'.tr('Permessi').':</strong> '.$permessi_label.'<br>';
            }

            // Mostra la data di scadenza se disponibile
            if (!empty($token_record['valido_al'])) {
                $data_scadenza = date('d/m/Y H:i', strtotime($token_record['valido_al']));
                echo '<strong>'.tr('Scade il').':</strong> '.$data_scadenza;
            }

            echo '
                </div>';
        }
    }

    echo '
                    <div class="input-group mb-4">
                        <input type="text" name="otp_code" class="form-control form-control-lg text-center" placeholder="'.tr('Codice OTP').'" maxlength="6" pattern="[A-Z0-9]{6}" required autocomplete="off">
                        <div class="input-group-append">
                            <div class="input-group-text bg-light">
                                <i class="fa fa-key text-primary"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg shadow-sm" id="otp-button">
                        <i class="fa fa-sign-in mr-2"></i>'.tr('Verifica e Accedi').'
                    </button>

                    <div class="text-center mt-4">
                        <div class="mb-2">
                            <a href="?token='.urlencode($token).'" class="text-primary">
                                <i class="fa fa-refresh mr-1"></i>'.tr('Invia nuovo codice OTP').'
                            </a>
                        </div>
                        <a href="'.base_path().'/index.php" class="text-secondary">
                            <i class="fa fa-arrow-left mr-1"></i>'.tr('Torna al login classico').'
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>';

    // JavaScript per il form OTP
    echo '
    <script>
    $(document).ready(function(){
        // Focus sul campo OTP
        $("input[name=otp_code]").focus();

        // Avvia countdown per reinvio OTP se necessario
        if ($("#countdown-timer").length > 0) {
            startOTPCountdown();
        }

        // Effetto hover sui pulsanti
        $("#otp-button").hover(
            function() {
                $(this).removeClass("shadow-sm").addClass("shadow");
            },
            function() {
                $(this).removeClass("shadow").addClass("shadow-sm");
            }
        );

        // Testo di caricamento sui pulsanti
        $("#otp-button").click(function(){
            $(this).html(\'<i class="fa fa-circle-o-notch fa-spin mr-2"></i> '.tr('Verifica').'...\');
        });

        // Animazione sui campi input
        $("input").focus(function(){
            $(this).parent().animate({marginLeft: "5px"}, 200).animate({marginLeft: "0px"}, 200);
        });

        // Formattazione automatica del codice OTP (solo caratteri alfanumerici maiuscoli)
        $("input[name=otp_code]").on("input", function(){
            // Rimuove caratteri non alfanumerici e converte in maiuscolo
            this.value = this.value.replace(/[^A-Z0-9]/gi, "").toUpperCase();

            // Limita a 6 caratteri
            if (this.value.length > 6) {
                this.value = this.value.substring(0, 6);
            }
        });
    });

    // Funzione per gestire il countdown del reinvio OTP
    function startOTPCountdown() {
        var countdown = parseInt($("#countdown-timer").text());

        if (countdown <= 0) {
            return;
        }

        var timer = setInterval(function() {
            countdown--;
            $("#countdown-timer").text(countdown);

            if (countdown <= 0) {
                clearInterval(timer);

                // Sostituisci countdown con link
                $("#resend-countdown").html(\'<a href="?op=resend_otp&token='.urlencode($token).'" class="text-primary"><i class="fa fa-refresh mr-1"></i>'.tr('Invia nuovo codice OTP').'</a>\');
            }
        }, 1000);
    }
    </script>';
} elseif ($show_token_confirm && !empty($token_record) && $tipo_accesso == 'token') {
    // Schermata di conferma per accesso token
    echo '
    <div class="login-box card-center-medium">
        <div class="card card-info shadow-lg">
            <div class="card-header text-center bg-light py-4">
                <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid" style="max-width: 85%;">
            </div>

            <div class="card-body pt-4">
                <p class="login-box-msg text-secondary"><i class="fa fa-key mr-2"></i>'.tr('Conferma accesso tramite token').'</p>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle mr-2"></i>'.tr('Stai per accedere al gestionale tramite un token di accesso.').'
                </div>';

    // Mostra informazioni sul token se disponibili
    if (!empty($token_record['id_module_target'])) {
        $module_info = $dbo->fetchOne('SELECT name FROM zz_modules WHERE id = '.prepare($token_record['id_module_target']));
        if ($module_info) {
            echo '
                <div class="alert alert-light border">
                    <strong>'.tr('Modulo di destinazione').':</strong> '.tr($module_info['name']).'<br>';

            if (!empty($token_record['id_record_target'])) {
                // Recupera il nome del record utilizzando la stessa logica di modules/otp_tokens/ajax/select.php
                $record_name = 'ID '.$token_record['id_record_target'];

                if ($module_info['name'] == 'Anagrafiche') {
                    $anagrafica = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare($token_record['id_record_target']).' AND deleted_at IS NULL');
                    if ($anagrafica) {
                        $record_name = $anagrafica['ragione_sociale'].' (ID '.$token_record['id_record_target'].')';
                    }
                } elseif ($module_info['name'] == 'Gestione documentale') {
                    $documento = $dbo->fetchOne('SELECT nome FROM do_documenti WHERE id = '.prepare($token_record['id_record_target']));
                    if ($documento) {
                        $record_name = $documento['nome'].' (ID '.$token_record['id_record_target'].')';
                    }
                }

                echo '<strong>'.tr('Record specifico').':</strong> '.$record_name.'<br>';
            }

            if (!empty($token_record['permessi'])) {
                switch ($token_record['permessi']) {
                    case 'r':
                        $permessi_label = tr('Sola lettura');
                        break;
                    case 'rw':
                        $permessi_label = tr('Lettura e scrittura');
                        break;
                    case 'ra':
                        $permessi_label = tr('Caricamento allegati');
                        break;
                    case 'rwa':
                        $permessi_label = tr('Caricamento e modifica allegati');
                        break;
                    default:
                        $permessi_label = tr('Non definito');
                        break;
                }
                echo '<strong>'.tr('Permessi').':</strong> '.$permessi_label.'<br>';
            }

            // Mostra la data di scadenza se disponibile
            if (!empty($token_record['valido_al'])) {
                $data_scadenza = date('d/m/Y H:i', strtotime($token_record['valido_al']));
                echo '<strong>'.tr('Scade il').':</strong> '.$data_scadenza;
            }

            echo '
                </div>';
        }
    }

    echo '
                <form action="?confirm_access=1" method="post" autocomplete="off">
                    <input type="hidden" name="token" value="'.htmlspecialchars($token).'">
                    <button type="submit" class="btn btn-success btn-block btn-lg shadow-sm mb-3" id="token-confirm-button">
                        <i class="fa fa-check mr-2"></i>'.tr('Conferma e Accedi').'
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="'.base_path().'/index.php" class="text-secondary">
                        <i class="fa fa-arrow-left mr-1"></i>'.tr('Torna al login classico').'
                    </a>
                </div>
            </div>
        </div>
    </div>';

    // JavaScript per la schermata di conferma
    echo '
    <script>
    $(document).ready(function(){
        // Effetto hover sui pulsanti
        $(".btn-success").hover(
            function() {
                $(this).removeClass("shadow-sm").addClass("shadow");
            },
            function() {
                $(this).removeClass("shadow").addClass("shadow-sm");
            }
        );

        // Testo di caricamento sul pulsante di conferma
        $("#token-confirm-button").click(function(){
            $(this).html(\'<i class="fa fa-circle-o-notch fa-spin mr-2"></i> '.tr('Accesso in corso').'...\');
            $(this).addClass("disabled");
        });

        // Animazione di entrata
        $(".login-box").hide().fadeIn(500);
    });
    </script>';
} else {
    // Fallback per token non riconosciuti o configurati male
    echo '
    <div class="login-box card-center-medium">
        <div class="card card-warning shadow-lg">
            <div class="card-header text-center bg-light py-4">
                <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid" style="max-width: 85%;">
            </div>

            <div class="card-body pt-4">
                <p class="login-box-msg text-secondary"><i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Token non configurato').'</p>
                <div class="alert alert-warning">
                    <i class="fa fa-warning mr-2"></i>'.tr('Il token fornito non è configurato correttamente per l\'accesso. Contatta l\'amministratore del sistema.').'
                </div>

                <div class="text-center mt-4">
                    <a href="'.base_path().'/index.php" class="text-secondary">
                        <i class="fa fa-arrow-left mr-1"></i>'.tr('Torna al login classico').'
                    </a>
                </div>
            </div>
        </div>
    </div>';
}

$custom_css = $dbo->isInstalled() ? html_entity_decode(setting('CSS Personalizzato')) : '';
if (!empty($custom_css)) {
    echo '
    <style>'.$custom_css.'</style>';
}

include_once App::filepath('include|custom|', 'bottom.php');
