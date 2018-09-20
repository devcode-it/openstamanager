<?php

namespace Notifications;

use Mail;
use Prints;

class EmailNotification extends Notification
{
    protected $template = null;
    protected $account = null;
    protected $attachments = null;
    protected $subject = null;

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
     */
    public function setTemplate($value, $id_record)
    {
        $this->template = $value;

        $template = $this->getTemplate();
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
        $this->setAccount($template['id_smtp']);
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
        };
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
     * Aggiunge un allegato alla notifica.
     *
     * @param string $value
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
     * @param int $id_record
     * @param string $name
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

    public function send()
    {
        $account = $this->getAccount();
        $mail = new Mail($account['id']);

        // Template
        $template = $this->getTemplate();
        if (!empty($template)) {
            $mail->setTemplate($template);
        }

        // Destinatari
        $mail->addReceivers($this->getReceivers());

        // Allegati
        $attachments = $this->getAttachments();
        foreach ($attachments as $attachment) {
            $this->AddAttachment($attachment['path'], $attachment['name']);
        }

        // Oggetto
        $mail->Subject = $this->getSubject();

        // Contenuto
        $mail->Body = $this->getContent();

        return $mail->send();
    }
}
