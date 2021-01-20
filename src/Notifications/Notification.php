<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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
