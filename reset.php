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

                $template = Template::pool('Reset password');

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

        redirect(base_path().'/index.php');
        exit();
        break;

    case 'update':
        $password = post('password');

        $utente = User::where('reset_token', $token)->first();
        if (!empty($utente)) {
            $utente->password = $password;
            $utente->reset_token = null;

            $utente->save();
        }

        flash()->info(tr('Password cambiata!'));

        redirect(base_path().'/index.php');
        exit();
        break;
}

$pageTitle = tr('Reimpostazione password');

include_once App::filepath('include|custom|', 'top.php');

// Controllo se è una beta e in caso mostro un warning
if (Auth::isBrute()) {
    echo '
    <div class="box box-danger box-center" id="brute">
        <div class="box-header with-border text-center">
            <h3 class="box-title">'.tr('Attenzione').'</h3>
        </div>

        <div class="box-body text-center">
        <p>'.tr('Sono stati effettuati troppi tentativi di accesso consecutivi!').'</p>
        <p>'.tr('Tempo rimanente (in secondi)').': <span id="brute-timeout">'.(Auth::getBruteTimeout() + 1).'</span></p>
        </div>
    </div>
    <script>
    $(document).ready(function(){
        $("#reset").fadeOut();
        brute();
    });

    function brute() {
        var value = parseFloat($("#brute-timeout").html()) - 1;
        $("#brute-timeout").html(value);

        if(value > 0){
            setTimeout("brute()", 1000);
        } else{
            $("#brute").fadeOut();
            $("#reset").fadeIn();
        }
    }
    </script>';
}

echo '
    <form action="" method="post" class="box box-center-large box-warning" id="reset">
        <div class="box-header with-border text-center">
            <a href="'.base_path().'/index.php"><i  class="fa fa-arrow-left btn btn-xs btn-warning pull-left tip" title="'.tr('Torna indietro').'" ></i></a>
            <h3 class="box-title">'.$pageTitle.'</h3>
        </div>

        <div class="box-body">';

if (empty($token)) {
    echo '
            <input type="hidden" name="op" value="reset">

            <p>'.tr("Per reimpostare password, inserisci l'username con cui hai accesso al gestionale e l'indirizzo email associato all'utente").'.<br>
            '.tr("Se i dati inseriti risulteranno corretti riceverai un'email dove sarà indicato il link da cui potrai reimpostare la tua password").'.</p>

            {[ "type": "text", "label": "'.tr('Username').'", "placeholder": "'.tr('Username').'", "name": "username", "icon-before": "<i class=\"fa fa-user\"></i>", "required": 1 ]}

            {[ "type": "email", "label": "'.tr('Email').'", "placeholder": "'.tr('Email').'", "name": "email", "icon-before": "<i class=\"fa fa-envelope\"></i>", "required": 1 ]}';
} else {
    echo '
            <input type="hidden" name="op" value="update">

            <p>'.tr('Inserisci la nuova password per il tuo account').':</p>

            {[ "type": "password", "label": "'.tr('Password').'", "name": "password", "required": 1, "strength": "#submit-button", "icon-before": "<i class=\"fa fa-lock\"></i>" ]}';
}

echo '
            </div>

            <div class="box-footer">
                    <button type="submit" id="submit-button" class="btn btn-success btn-block">
                        <i class="fa fa-arrow-right"></i> '.tr('Invia richiesta').'
            </button>
        </div>
    </form>';

include_once App::filepath('include|custom|', 'bottom.php');
