<?php

namespace Extensions;

use Monolog\Handler\AbstractProcessingHandler;

/**
 * Gestore dei messaggi di avvertenza in caso di malfunzionamento del gestionale.
 *
 * @since 2.4.6
 */
class MessageHandler extends AbstractProcessingHandler
{
    protected function write(array $record)
    {
        $message = tr("Si è verificato un'errore").'.';

        if (auth()->check()) {
            $message .= '
            '.tr('Se il problema persiste siete pregati di chiedere assistenza tramite la sezione Bug').'. <a href="'.ROOTDIR.'/bug.php"><i class="fa fa-external-link"></i></a>
            <br><small>'.$record['message'].'</small>';
        }

        //flash()->error($message);

        echo '
    <div class="alert alert-danger push">
        <i class="fa fa-times"></i> '.$message.'
    </div>';
    }
}
