<?php

namespace Util;

/**
 * Classe dedicata alla gestione dei messaggi per l'utente.
 *
 * @since 2.4.2
 */
class Messages extends \Slim\Flash\Messages
{
    public function info($message)
    {
        return $this->addMessage('info', $message);
    }

    public function warning($message)
    {
        return $this->addMessage('warning', $message);
    }

    public function error($message)
    {
        return $this->addMessage('error', $message);
    }
}
