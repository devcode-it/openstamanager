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
        if (\Whoops\Util\Misc::isAjaxRequest()) {
            return;
        }

        $message = tr("Si è verificato un'errore").' <i>[uid: '.$record['extra']['uid'].']</i>.';

        if (auth()->check()) {
            $message .= '
            '.tr('Se il problema persiste siete pregati di chiedere assistenza tramite il forum apposito (_LINK_FORUM_)', [
                '_LINK_FORUM_' => '<a href="https://forum.openstamanager.com/">https://forum.openstamanager.com/</a>',
            ]).'.</a>';

            if (auth()->isAdmin()) {
                $message .= '
            <br><small>'.$record['message'].'</small>';
            }
        }

        // Messaggio nella sessione
        try {
            flash()->error($message);
        } catch (\Exception $e) {
        }

        // Messaggio visivo immediato
        echo '
    <div class="alert alert-danger push">
        <i class="fa fa-times"></i> '.$message.'
    </div>';
    }
}
