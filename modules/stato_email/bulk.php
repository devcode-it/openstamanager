<?php

include_once __DIR__.'/../../core.php';

use Modules\Emails\Mail;

switch (post('op')) {
    case 'delete-bulk':
        $i = 0;
        foreach ($id_records as $id_record) {
            if (isset($id_record)) {
                $mail = Mail::find($id_record);
                if (empty($mail->sent_at)) {
                    $mail->delete();
                    ++$i;
                }
            }
        }

        if ($i > 0) {
            flash()->info(tr('Email rimosse dalla coda di invio'));
        } else {
            flash()->warning(tr('Nessuna email rimossa dalla coda di invio'));
        }

        break;
}

$operations['delete-bulk'] = [
    'text' => '<span><i class="fa fa-trash"></i> '.tr('Elimina email selezionate e non ancora inviate').'</span>',
    'data' => [
        'msg' => tr('Vuoi davvero eliminare dalla coda di invio le email selezionate?'),
        'button' => tr('Procedi'),
        'class' => 'btn btn-lg btn-danger',
    ],
];

return $operations;
