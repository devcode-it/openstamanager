<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Emails\Account;
use Modules\Emails\Mail;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Prints;
use Uploads;

class EmailNotification extends PHPMailer implements NotificationInterface
{
    protected $mail;
    protected $directory;

    protected $infos = [];

    public function __construct($account = null, $exceptions = null)
    {
        parent::__construct($exceptions);

        $this->CharSet = 'UTF-8';

        // Configurazione di base
        $config = Account::find($account);
        if (empty($config)) {
            $config = Account::where('predefined', true)->first();
        }

        // Preparazione email
        $this->IsHTML(true);

        if (!empty($config['server'])) {
            $this->IsSMTP(true);

            // Impostazioni di debug
            $this->SMTPDebug = \App::debug() ? 2 : 0;
            $this->Debugoutput = function ($str, $level) {
                $this->infos[] = $str;
            };

            // Impostazioni dell'host
            $this->Host = $config['server'];
            $this->Port = $config['port'];

            // Impostazioni di autenticazione
            if (!empty($config['username'])) {
                $this->SMTPAuth = true;
                $this->Username = $config['username'];
                $this->Password = $config['password'];
            }

            // Impostazioni di sicurezza
            if (in_array(strtolower($config['encryption']), ['ssl', 'tls'])) {
                $this->SMTPSecure = strtolower($config['encryption']);
            }

            if (!empty($config['ssl_no_verify'])) {
                $this->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }
        }

        $this->From = $config['from_address'];
        $this->FromName = $config['from_name'];

        $this->WordWrap = 78;
    }

    public static function build(Mail $mail, $exceptions = null)
    {
        $result = new self($mail->account->id, $exceptions);

        $result->setMail($mail);

        return $result;
    }

    public function setMail($mail)
    {
        $this->mail = $mail;

        // Registazione della processazione
        if (!empty($this->mail)) {
            $this->mail->processing_at = date('Y-m-d H:i:s');
            $this->mail->save();
        }

        // Destinatari
        $receivers = $mail->receivers;
        foreach ($receivers as $receiver) {
            $this->addReceiver($receiver['address'], $receiver['type']);
        }

        // Allegati
        $uploads = $mail->uploads;
        foreach ($uploads as $upload) {
            $this->addUpload($upload->id);
        }

        // Stampe
        $prints = $mail->prints;
        foreach ($prints as $print) {
            $this->addPrint($print['id'], $mail->id_record);
        }

        // Conferma di lettura
        if (!empty($mail->read_notify)) {
            $this->ConfirmReadingTo = $mail->From;
        }

        // Reply To
        if (!empty($mail->options['reply_to'])) {
            $this->AddReplyTo($mail->options['reply_to']);
        }

        // Oggetto
        $this->Subject = $mail->subject;

        // Contenuto
        $this->Body = $mail->content;
    }

    public function getMail()
    {
        return $this->mail;
    }

    /**
     * Invia l'email impostata.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function send()
    {
        if (empty($this->AltBody)) {
            $this->AltBody = strip_tags($this->Body);
        }

        $exception = null;
        try {
            $result = parent::send();
        } catch (Exception $e) {
            $result = false;
            $exception = $e;
        }

        // Registazione invio
        if (!empty($this->mail)) {
            if ($result) {
                $this->mail->sent_at = date('Y-m-d H:i:s');
            } else {
                $this->mail->failed_at = date('Y-m-d H:i:s');
            }

            // Salvataggio del numero di tentativi
            $this->mail->attempt = $this->mail->attempt + 1;

            $this->mail->save();
        }

        $this->SmtpClose();

        // Pulizia file generati
        //delete($this->getTempDirectory());

        // Segnalazione degli errori
        if (!$result) {
            $logger = logger();
            foreach ($this->infos as $info) {
                $logger->addRecord(\Monolog\Logger::ERROR, $info);
            }
        }

        if (!empty($exception)) {
            throw $exception;
        }

        return $result;
    }

    /**
     * Testa la connessione al server SMTP.
     *
     * @return bool
     */
    public function testSMTP()
    {
        if ($this->smtpConnect()) {
            $this->smtpClose();

            return true;
        }

        return false;
    }

    /**
     * Aggiunge un allegato del gestionale alla notifica.
     *
     * @param string $file_id
     */
    public function addUpload($file_id)
    {
        $attachment = database()->fetchOne('SELECT * FROM zz_files WHERE id = '.prepare($file_id));

        $this->addAttachment(DOCROOT.'/'.Uploads::getDirectory($attachment['id_module'], $attachment['id_plugin']).'/'.$attachment['filename']);
    }

    /**
     * Aggiunge una stampa alla notifica.
     *
     * @param string|int $print
     * @param int        $id_record
     * @param string     $name
     */
    public function addPrint($print, $id_record, $name = null)
    {
        $print = Prints::get($print);

        $info = Prints::render($print['id'], $id_record, null, true);
        $name = $name ?: $info['path'];

        $this->AddStringAttachment($info['pdf'], $name);
    }

    /**
     * Aggiunge un destinatario.
     *
     * @param array $receiver
     * @param array $type
     */
    public function addReceiver($receiver, $type = null)
    {
        $pieces = explode('<', $receiver);
        $count = count($pieces);

        $name = null;
        if ($count > 1) {
            $email = substr(end($pieces), 0, -1);
            $name = substr($receiver, 0, strpos($receiver, '<'.$email));
        } else {
            $email = $receiver;
        }

        if (!empty($email)) {
            if ($type == 'cc') {
                $this->AddCC($email, $name);
            } elseif ($type == 'bcc') {
                $this->AddBCC($email, $name);
            } else {
                $this->AddAddress($email, $name);
            }
        }
    }

    protected function getTempDirectory()
    {
        if (!isset($this->directory)) {
            $this->directory = DOCROOT.'/files/notifications/'.rand(0, 999);

            directory($this->directory);
        }

        return $this->directory;
    }
}
