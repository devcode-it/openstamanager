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

namespace Modules\Emails;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Models\PrintTemplate;
use Models\Upload;
use Models\User;
use Modules\Newsletter\Newsletter;

class Mail extends Model
{
    use SimpleModelTrait;

    protected $table = 'em_emails';

    protected $options;

    public static function build(?User $user = null, $template = null, $id_record = null, $account = null)
    {
        $model = new static();

        $model->created_by = $user->id;

        if (!empty($template)) {
            $model->id_template = $template->id;
            $model->id_account = $template->account->id;
        }

        $model->id_record = $id_record;

        $model->save();

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
    public function addUpload($file_id, $name = null)
    {
        $this->uploads()->attach($file_id, ['id_email' => $this->id, 'name' => $name]);
    }

    public function resetUploads()
    {
        $this->uploads()->detach();
    }

    /**
     * Aggiunge una stampa alla notifica.
     *
     * @param string $name
     */
    public function addPrint($print_id, $name = null)
    {
        $this->prints()->attach($print_id, ['id_email' => $this->id, 'name' => $name]);
    }

    public function resetPrints()
    {
        $this->prints()->detach();
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

        $list = explode(';', $value);
        foreach ($list as $address) {
            if (!empty($address)) {
                $receiver = Receiver::build($this, $address, $type);
            }
        }
    }

    public function save(array $options = [])
    {
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

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value);
    }

    public function getOptionsAttribute()
    {
        return json_decode((string) $this->attributes['options'], true);
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

    /**
     * Imposta il titolo della notifica.
     *
     * @param bool $value
     */
    public function setReadNotifyAttribute($value)
    {
        $this->options['read-notify'] = boolval($value);
    }

    public function setSubjectAttribute($value)
    {
        if (isset($this->template)) {
            $module = $this->template->module;

            $value = $module->replacePlaceholders($this->id_record, $value, ['is_pec' => intval($this->account->pec)]);
        }

        $this->attributes['subject'] = $value;
    }

    public function setContentAttribute($value)
    {
        if (isset($this->template)) {
            $module = $this->template->module;

            $value = $module->replacePlaceholders($this->id_record, $value, ['is_pec' => intval($this->account->pec)]);
        }

        $this->attributes['content'] = $value;
    }

    /**
     * Rimuove tutte le mail di un determinato modulo/plugin e record.
     *
     * @param array $data
     */
    public static function deleteLinked($data)
    {
        $templates = database()->table('em_templates')->where('id_module', $data['id_module'])->get();

        $id_templates = [];

        foreach ($templates as $template) {
            $id_templates[] = $template->id;
        }

        database()->table('em_emails')->where('id_record', $data['id_record'])->whereIn('id_template', $id_templates)->delete();
    }

    /* Relazioni Eloquent */

    public function account()
    {
        return $this->belongsTo(Account::class, 'id_account')->withTrashed();
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'id_template')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function newsletter()
    {
        return $this->belongsTo(Newsletter::class, 'id_newsletter');
    }

    public function receivers()
    {
        return $this->hasMany(Receiver::class, 'id_email');
    }

    public function uploads()
    {
        return $this->belongsToMany(Upload::class, 'em_email_upload', 'id_email', 'id_file')->withPivot('name');
    }

    public function prints()
    {
        return $this->belongsToMany(PrintTemplate::class, 'em_email_print', 'id_email', 'id_print')->withPivot('name');
    }

    protected function resetFromTemplate()
    {
        $template = $this->template;

        $this->id_account = $template->account->id;
        $this->read_notify = $template->read_notify;

        // Contentuto e oggetto
        $this->content = $template->getTranslation('body');
        $this->subject = $template->getTranslation('subject');

        // Reply To
        if (!empty($template['tipo_reply_to'])) {
            $reply_to = '';
            if ($template['tipo_reply_to'] == 'email_fissa') {
                $reply_to = $template['reply_to'];
            } else {
                $user = \Auth::user();
                $reply_to = $user->email;
            }

            if (!empty($reply_to)) {
                $this->options['reply_to'] = $reply_to;
            }
        }

        // CC
        if (!empty($template['cc'])) {
            $this->addReceiver($template['cc'], 'cc');
        }

        // BCC
        if (!empty($template['bcc'])) {
            $this->addReceiver($template['bcc'], 'bcc');
        }

        // Inclusione stampe predefinite
        $prints = $template->prints;
        foreach ($prints as $print) {
            $this->addPrint($print['id']);
        }

        // Inclusione allegati predefiniti
        $uploads = $template->uploads;
        foreach ($uploads as $upload) {
            $this->addUpload($upload['id']);
        }
    }
}
