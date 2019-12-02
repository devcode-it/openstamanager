<?php

namespace Notifications;

abstract class Notification implements NotificationInterface
{
    public $receivers = [];
    public $content = null;

    public function __construct($receivers = null, $content = null)
    {
        $this->setReceivers($receivers);
        $this->setContent($content);
    }

    /**
     * Restituisce i destinatari della notifica.
     *
     * @return array
     */
    public function getReceivers()
    {
        return $this->receivers;
    }

    /**
     * Imposta i destinatari della notifica.
     *
     * @param string|array $value
     */
    public function setReceivers($value)
    {
        $this->receivers = [];

        $values = is_array($value) ? $value : explode(';', $value);
        foreach ($values as $value) {
            $this->addReceiver($value);
        }
    }

    /**
     * Aggiunge un destinataro alla notifica.
     *
     * @param string $value
     */
    public function addReceiver($value)
    {
        $this->receivers[] = $value;
    }

    /**
     * Restituisce i contenuti della notifica.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Imposta i contenuti della notifica.
     *
     * @param string $value
     */
    public function setContent($value)
    {
        $this->content = $value;
    }

    abstract public function send();
}
