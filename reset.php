<?php

$skip_permissions = true;
include_once __DIR__.'/core.php';

use Models\User;

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

        $utente = User::where('username', $username)->where('email', $email)->first();
        if (!empty($utente)) {
            $utente->reset_token = secure_random_string();
            $utente->save();

            $n = new Notifications\EmailNotification();

            $n->setTemplate('Reset password', $utente->id);
            $n->setReceivers($utente->email);

            $n->send();
        }

        //$message_email = substr($email, 0, 2).str_repeat('*', strlen($email)-8).substr($email, -6);

        flash()->info(tr("Se le informazioni inserite corrispondono ai dati di un utente, riceverai a breve un'email all'indirizzo collegato").'.');

        redirect(ROOTDIR.'/index.php');
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

        redirect(ROOTDIR.'/index.php');
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
            <h3 class="box-title">'.$pageTitle.'</h3>
        </div>

        <div class="box-body">';

if (empty($token)) {
    echo '
            <input type="hidden" name="op" value="reset">

            <p>'.tr("Per richiedere la reimpostazione della password, inserisci l'username e l'indirizzo email con cui hai accesso al gestionale").'.</p>
            <p>'.tr("Una volta inviata e validata la richiesta, riceverai un'email dove sarà indicato un link a cui potrai reimpostare la password di accesso ad OpenSTAManager").'.</p>
            
            {[ "type": "text", "label": "'.tr('Username').'", "placeholder": "'.tr('Username').'", "name": "username", "icon-before": "<i class=\"fa fa-user\"></i>", "required": 1 ]}
            
            {[ "type": "email", "label": "'.tr('Email').'", "placeholder": "'.tr('Email').'", "name": "email", "icon-before": "<i class=\"fa fa-envelope\"></i>", "required": 1 ]}';
} else {
    echo '
            <input type="hidden" name="op" value="update">

            <p>'.tr('Inserisci la nuova password per il tuo account').':</p>
            
            {[ "type": "password", "label": "'.tr('Password').'", "name": "password", "icon-before": "<i class=\"fa fa-lock\"></i>" ]}';
}

echo '
            </div>
            
            <div class="box-footer">
                    <button type="submit" id="login" class="btn btn-success btn-block">
                        <i class="fa fa-arrow-right"></i> '.tr('Procedi').'
            </button>
        </div>
    </form>';

include_once App::filepath('include|custom|', 'bottom.php');
