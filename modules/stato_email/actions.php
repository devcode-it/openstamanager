<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'retry':
        $mail->attempt = 0;

        $mail->save();
        break;

    case 'delete':
        if (empty($mail->sent_at)) {
            $mail->delete();

            flash()->info(tr('Email rimossa dalla coda di invio!'));
        }

        break;
}
