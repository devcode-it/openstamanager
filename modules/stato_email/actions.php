<?php

include_once __DIR__.'/../../core.php';
use Notifications\EmailNotification;

switch (post('op')) {
    case 'send':

        $email = EmailNotification::build($mail);

        // Invio mail
        if ($email->send()){
            $mail->sent_at = date("Y-m-d H:i:s");
            $mail->save();
            flash()->info(tr('Email inviata.'));
        }else{
            flash()->error(tr('Errore durante invio email.'));
        }

        break;

    case 'retry':
        $mail->attempt = 0;

        $mail->save();
        break;

    case 'delete':
        if (empty($mail->sent_at)) {
            $mail->delete();

            flash()->info(tr('Email rimossa dalla coda di invio.'));
        }

        break;
}
