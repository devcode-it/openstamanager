<?php

namespace Notifications;

use Mail;
use Prints;
use Uploads;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailNotification extends Notification
{
    protected $subject = null;
    protected $readNotify = false;

    protected $template = null;
    protected $account = null;
    protected $attachments = null;

    protected $logs = [];

    /**
     * Restituisce l'account email della notifica.
     *
     * @return array
     */
    public function getAccount()
    {
        return Mail::get($this->account);
    }

    /**
     * Imposta l'account email della notifica.
     *
     * @param string|int $value
     */
    public function setAccount($value)
    {
        $this->account = $value;
    }

    /**
     * Restituisce il template della notifica.
     *
     * @return array
     */
    public function getTemplate()
    {
        return Mail::getTemplate($this->template);
    }

    /**
     * Imposta il template della notifica.
     *
     * @param string|int $value
     * @param int        $id_record
     */
    public function setTemplate($value, $id_record = null)
    {
        $this->template = $value;

        $template = $this->getTemplate();

        $this->setReadNotify($template['read_notify']);
        $this->setAccount($template['id_smtp']);

        if (!empty($id_record)) {
            $variables = Mail::getTemplateVariables($template['id'], $id_record);

            // Sostituzione delle variabili di base
            $replaces = [];
            foreach ($variables as $key => $value) {
                $replaces['{'.$key.'}'] = $value;
            }
            $body = replace($template['body'], $replaces);
            $subject = replace($template['subject'], $replaces);

            $this->setContent($body);
            $this->setSubject($subject);
        }
    }

    /**
     * Include le stampe selezionate dal template.
     *
     * @param int $id_record
     */
    public function includeTemplatePrints($id_record)
    {
        $template = $this->getTemplate();

        $prints = $dbo->fetchArray('SELECT id_print FROM zz_email_print WHERE id_email = '.prepare($template['id']));
        foreach ($prints as $print) {
            $this->addPrint($print['id_print'], $id_record);
        }
    }

    /**
     * Restituisce gli allegati della notifica.
     *
     * @return array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Imposta gli allegati della notifica.
     *
     * @param array $values
     */
    public function setAttachments(array $values)
    {
        $this->attachments = [];

        foreach ($values as $value) {
            $path = is_array($value) ? $value['path'] : $value;
            $name = is_array($value) ? $value['name'] : null;
            $this->addAttachment($path, $name);
        }
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

        $this->logs['attachments'][] = $attachment['id'];
    }

    /**
     * Aggiunge un allegato alla notifica.
     *
     * @param string $path
     * @param string $name
     */
    public function addAttachment($path, $name = null)
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path),
        ];
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

        if (empty($name)) {
            $name = $print['title'].'.pdf';
        }

        // Utilizzo di una cartella particolare per il salvataggio temporaneo degli allegati
        $path = DOCROOT.'/files/notifications/'.$print['title'].' - '.$id_record.'.pdf';

        Prints::render($print['id'], $id_record, $path);

        $this->addAttachment($path, $name);

        $this->logs['prints'][] = $print['id'];
    }

    /**
     * Restituisce il titolo della notifica.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Imposta il titolo della notifica.
     *
     * @param string $value
     */
    public function setSubject($value)
    {
        $this->subject = $value;
    }

    /**
     * Restituisce il titolo della notifica.
     *
     * @return bool
     */
    public function getReadNotify()
    {
        return $this->readNotify;
    }

    /**
     * Imposta il titolo della notifica.
     *
     * @param bool $value
     */
    public function setReadNotify($value)
    {
        $this->readNotify = boolval($value);
    }

    /**
     * Aggiunge un destinatario alla notifica.
     *
     * @param string $value
     * @param string $type
     */
    public function addReceiver($value, $type = null)
    {
        if (empty($value)) {
            return;
        }

        $this->receivers[] = [
            'email' => $value,
            'type' => $type,
        ];

        $this->logs['receivers'][] = $value;
    }

    public function send($exceptions = false)
    {
        $account = $this->getAccount();
        $mail = new Mail($account['id'], true);

        // Template
        $template = $this->getTemplate();
        if (!empty($template)) {
            $mail->setTemplate($template);
        }

        // Destinatari
        $receivers = $this->getReceivers();
        foreach ($receivers as $receiver) {
            $mail->addReceiver($receiver['email'], $receiver['type']);
        }

        // Allegati
        $attachments = $this->getAttachments();
        foreach ($attachments as $attachment) {
            $mail->AddAttachment($attachment['path'], $attachment['name']);
        }

        // Conferma di lettura
        if (!empty($this->getReadNotify())) {
            $mail->ConfirmReadingTo = $mail->From;
        }

        // Oggetto
        $mail->Subject = $this->getSubject();

        // Contenuto
        $mail->Body = $this->getContent();

        // Invio mail
        try {
            $mail->send();

            operationLog('send-email', [
                'id_email' => $template['id'],
            ], $this->logs);

            return true;
        } catch (PHPMailerException $e) {
            if ($exceptions) {
                throw $e;
            }

            return false;
        }
    }
}
