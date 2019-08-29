<?php

include_once __DIR__.'/../../core.php';

use Modules\Emails\Mail;

if (isset($id_record)) {
    $mail = Mail::find($id_record);

    $record = $mail->toArray();
}
