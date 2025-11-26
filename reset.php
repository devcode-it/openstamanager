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

use Models\User;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Notifications\EmailNotification;

$token = get('reset_token');

switch (post('op')) {
    case 'reset':
        $username = post('username');
        $email = post('email');

        $database->insert('zz_logs', [
            'username' => $username,
            'ip' => get_client_ip(),
            'stato' => Auth::getStatus()['failed']['code'],
        ]);

        try {
            $utente = User::where('username', $username)->where('email', $email)->first();
            if (!empty($utente)) {
                $utente->reset_token = secure_random_string();
                $utente->save();

                $template = Template::where('name', 'Reset password')->first();

                $mail = Mail::build($utente, $template, $utente->id);
                $mail->addReceiver($utente->email);
                $mail->save();

                $email = EmailNotification::build($mail);
                $email->send();
            }

            flash()->info(tr("Se le informazioni inserite corrispondono ai dati di un utente, riceverai a breve un'email all'indirizzo collegato").'.');
        } catch (Exception $e) {
            flash()->error(tr("Errore durante la gestione della richiesta: si prega di contattare l'amministratore").'.');
        }

        redirect_url(base_path_osm().'/index.php');
        exit;

    case 'update':
        $password = post('password');

        $utente = User::where('reset_token', $token)->first();
        if (!empty($utente)) {
            $utente->password = $password;
            $utente->reset_token = null;

            $utente->save();
        }

        flash()->info(tr('Password cambiata!'));

        redirect_url(base_path_osm().'/index.php');
        exit;
}

$pageTitle = tr('Reimpostazione password');

include_once App::filepath('include|custom|', 'top.php');

// Add inline styles for reset password page enhancement
echo '
<style>
    .card-center-large {
        margin: 0 auto;
        max-width: 450px;
        width: 100%;
    }
    .card-outline.card-primary {
        border-top: 3px solid #007bff;
        border-radius: 8px;
        overflow: hidden;
    }
    .form-control {
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .input-group-text {
        border-top-right-radius: 4px !important;
        border-bottom-right-radius: 4px !important;
    }
    .btn-primary {
        transition: all 0.3s ease;
    }
    /* Allineamento larghezza input field e icon button */
    .card-center-large .input-group {
        display: flex;
    }
    .card-center-large .input-group .form-control,
    .card-center-large .input-group .form-control-lg {
        flex: 1;
    }
    .card-center-large .input-group-append .input-group-text {
        width: auto;
        min-width: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    @media (max-width: 576px) {
        .card-center-large {
            width: 90%;
            margin: 0 auto;
        }
    }
</style>';

// Controllo se è una beta e in caso mostro un warning
if (Auth::isBrute()) {
    echo '
    <div class="card card-danger shadow-lg card-center-large" id="brute">
        <div class="card-header text-center">
            <h3 class="card-title"><i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione').'</h3>
        </div>

        <div class="card-body text-center">
            <p class="lead">'.tr('Accesso temporaneamente bloccato').'</p>
            <p>'.tr('Per motivi di sicurezza, sono stati rilevati troppi tentativi di accesso consecutivi.').'</p>
            <div class="alert alert-warning">
                <p>'.tr('Potrai riprovare tra').':</p>
                <h3><span id="brute-timeout" class="badge badge-danger">'.(Auth::getBruteTimeout() + 1).'</span> '.tr('secondi').'</h3>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function(){
        $("#reset").fadeOut();
        brute();
    });

    function brute() {
        var value = parseFloat($("#brute-timeout").text()) - 1;
        $("#brute-timeout").text(value);

        if(value > 0){
            setTimeout(brute, 1000);
        } else{
            $("#brute").fadeOut(500, function() {
                $("#reset").fadeIn(500);
            });
        }
    }
    </script>';
}

echo '
    <form action="" method="post" id="reset">
        <div class="card-center-large">
            <div class="card card-outline card-primary shadow-lg">
                <div class="card-header text-center bg-light py-4">
                    <img src="'.App::getPaths()['img'].'/logo_completo.png" alt="'.tr('OpenSTAManager, il software gestionale open source per assistenza tecnica e fatturazione elettronica').'" class="img-fluid">
                </div>

                <div class="card-body pt-4">';

if (empty($token)) {
    echo '
                    <p class="text-center text-secondary mb-4"><i class="fa fa-key mr-2"></i>'.tr('Recupero password').'</p>
                    <input type="hidden" name="op" value="reset">

                    <div class="alert alert-info mb-4">
                        <p class="mb-2">'.tr("Per recuperare l'accesso al tuo account, inserisci:").'</p>
                        <ul class="mb-0">
                            <li>'.tr('Il tuo nome utente (username)').'</li>
                            <li>'.tr("L'indirizzo email registrato nel sistema").'</li>
                        </ul>
                    </div>

                    <p class="text-muted mb-3">'.tr("Se le informazioni inserite sono corrette, riceverai un'email con un link per reimpostare la tua password.").'</p>
                    <p class="text-danger mb-4"><i class="fa fa-exclamation-circle mr-1"></i> '.tr("Nota: l'email deve corrispondere esattamente a quella associata al tuo account.").'</p>

                    <div class="input-group mb-4">
                        <input type="text" name="username" class="form-control form-control-lg" placeholder="'.tr('Username').'" required>
                        <div class="input-group-append">
                            <div class="input-group-text after">
                                <i class="fa fa-user"></i>
                            </div>
                        </div>
                    </div>

                    <div class="input-group mb-4">
                        <input type="email" name="email" class="form-control form-control-lg email-mask" placeholder="'.tr('Email').'" required>
                        <div class="input-group-append">
                            <div class="input-group-text after">
                                <i class="fa fa-envelope"></i>
                            </div>
                        </div>
                    </div>';
} else {
    echo '
                    <p class="text-center text-secondary mb-4"><i class="fa fa-key mr-2"></i>'.tr('Crea nuova password').'</p>
                    <input type="hidden" name="op" value="update">

                    <div class="alert alert-info mb-4">
                        <p class="mb-0">'.tr('Scegli una nuova password sicura per il tuo account.').'</p>
                    </div>

                    <p class="text-muted mb-4">'.tr('Ti consigliamo di utilizzare una password:').'</p>
                    <ul class="text-muted mb-4">
                        <li>'.tr('Di almeno 8 caratteri').'</li>
                        <li>'.tr('Con lettere maiuscole e minuscole').'</li>
                        <li>'.tr('Con numeri e caratteri speciali').'</li>
                        <li>'.tr('Diversa dalle password precedenti').'</li>
                    </ul>

                    <div class="mb-4">
                        {[ "type": "password", "name": "password", "required": 1, "strength": "#submit-button", "class": "form-control-lg", "placeholder": "'.tr('Nuova password').'" ]}
                    </div>';
}

echo '
                    <button type="submit" id="submit-button" class="btn btn-primary btn-block btn-lg shadow-sm">
                        <i class="fa fa-'.(!empty($token) ? 'check' : 'paper-plane').' mr-2"></i> '.tr(!empty($token) ? 'Salva nuova password' : 'Invia richiesta di recupero').'
                    </button>

                    <div class="text-center mt-4">
                        <a href="'.base_path_osm().'/index.php" class="text-secondary">
                            <i class="fa fa-sign-in mr-1"></i>'.tr('Torna alla pagina di accesso').'
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
    $(document).ready(function(){
        // Focus on first field
        $("input:visible:first").focus();

        // Add hover effect to submit button
        $("#submit-button").hover(
            function() {
                $(this).removeClass("shadow-sm").addClass("shadow");
            },
            function() {
                $(this).removeClass("shadow").addClass("shadow-sm");
            }
        );

        // Show loading text on button click
        $("#submit-button").click(function(){
            if($("#reset").parsley().isValid()) {
                $(this).html(\'<i class="fa fa-circle-o-notch fa-spin mr-2"></i> '.tr('Elaborazione').'...\');
            }
        });

        // Add subtle animation to input fields on focus
        $("input").focus(function(){
            $(this).parent().animate({marginLeft: "5px"}, 200).animate({marginLeft: "0px"}, 200);
        });
    });
    </script>';

include_once App::filepath('include|custom|', 'bottom.php');
