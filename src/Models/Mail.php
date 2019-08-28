<?php

namespace Models;

use Common\Model;
use Modules\Newsletter\Newsletter;

class Mail extends Model
{
    protected $table = 'em_emails';

    protected $receivers = null;

    protected $attachments = null;
    protected $prints = null;

    protected $options = null;

    public static function build(User $user, $template = null, $id_record = null, $account = null)
    {
        $model = parent::build();

        $model->created_by = $user->id;

        $model->id_template = $template->id;
        $model->id_record = $id_record;

        if (!empty($template)) {
            $model->resetFromTemplate();
        }

        if (!empty($account)) {
            $model->id_account = $account->id;
        }

        $model->save();

        return $model;
    }

    /**
     * Aggiunge un allegato del gestionale alla notifica.
     *
     * @param string $file_id
     */
    public function addAttachment($file_id)
    {
        if (!isset($this->attachments)) {
            $this->attachments = [];
        }

        $this->attachments[] = $file_id;
    }

    public function resetAttachments()
    {
        $this->attachments = [];
    }

    /**
     * Aggiunge una stampa alla notifica.
     *
     * @param string|int $print
     * @param string     $name
     */
    public function addPrint($print_id, $name = null)
    {
        if (!isset($this->prints)) {
            $this->prints = [];
        }

        $print = PrintTemplate::find($print_id);

        if (empty($name)) {
            $name = $print['title'].'.pdf';
        }

        $this->prints[] = [
            'id' => $print['id'],
            'name' => $name,
        ];
    }

    public function resetPrints()
    {
        $this->prints = [];
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

        if (!isset($this->receivers)) {
            $this->receivers = [];
        }

        $list = explode(';', $value);
        foreach ($list as $element) {
            $this->receivers[] = [
                'email' => $element,
                'type' => $type,
            ];
        }
    }

    public function save(array $options = [])
    {
        if (isset($this->receivers)) {
            $this->setReceiversAttribute($this->receivers);
        }

        if (isset($this->attachments)) {
            $this->setAttachmentsAttribute($this->attachments);
        }

        if (isset($this->prints)) {
            $this->setPrintsAttribute($this->prints);
        }

        if (isset($this->options)) {
            $this->setOptionsAttribute($this->options);
        }

        $newsletter = $this->newsletter;
        if (!empty($newsletter)) {
            $newsletter->fixStato();
        }

        return parent::save($options);
    }

    // Attributi Eloquent

    public function setReceiversAttribute($value)
    {
        $this->attributes['receivers'] = json_encode($value);
    }

    public function getReceiversAttribute()
    {
        return json_decode($this->attributes['receivers'], true);
    }

    public function setAttachmentsAttribute($value)
    {
        $this->attributes['attachments'] = json_encode($value);
    }

    public function getAttachmentsAttribute()
    {
        return json_decode($this->attributes['attachments'], true);
    }

    public function setPrintsAttribute($value)
    {
        $this->attributes['prints'] = json_encode($value);
    }

    public function getPrintsAttribute()
    {
        return json_decode($this->attributes['prints'], true);
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value);
    }

    public function getOptionsAttribute()
    {
        return json_decode($this->attributes['options'], true);
    }

    /**
     * Restituisce il titolo della notifica.
     *
     * @return bool
     */
    public function getReadNotifyAttribute()
    {
        return $this->options['read-notify'];
    }

    public function setSubjecyAttribute($value)
    {
        if (isset($this->template)) {
            $module = $this->template->module;

            $value = $module->replacePlaceholders($this->id_record, $value);
        }

        $this->attributes['subject'] = $value;
    }

    public function setContentAttribute($value)
    {
        if (isset($this->template)) {
            $module = $this->template->module;

            $value = $module->replacePlaceholders($this->id_record, $value);
        }

        $this->attributes['content'] = $value;
    }

    /**
     * Imposta il titolo della notifica.
     *
     * @param bool $value
     */
    public function setReadNotifyAttribute($value)
    {
        $this->options['read-notify'] = boolval($value);
    }

    /* Relazioni Eloquent */

    public function account()
    {
        return $this->belongsTo(MailAccount::class, 'id_account')->withTrashed();
    }

    public function template()
    {
        return $this->belongsTo(MailTemplate::class, 'id_template')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function newsletter()
    {
        return $this->belongsTo(Newsletter::class, 'id_campaign');
    }

    protected function resetFromTemplate()
    {
        $template = $this->template;

        $this->id_account = $template->account->id;
        $this->read_notify = $template->read_notify;

        // Contentuto e oggetto
        $this->content = $template->body;
        $this->subject = $template->subject;

        // Reply To
        if (!empty($template['reply_to'])) {
            $this->options['reply_to'] = $template['reply_to'];
        }

        // CC
        if (!empty($template['cc'])) {
            $this->addReceiver($template['cc'], 'cc');
        }

        // BCC
        if (!empty($template['bcc'])) {
            $this->addReceiver($template['bcc'], 'bcc');
        }

        // Incluesione stampe predefinite
        $prints = database()->fetchArray('SELECT id_print FROM em_template_print WHERE id_email = '.prepare($template['id']));
        foreach ($prints as $print) {
            $this->addPrint($print['id_print']);
        }
    }
}
