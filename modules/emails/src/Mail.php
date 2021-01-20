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

    protected $options = null;

    public static function build(User $user, $template = null, $id_record = null, $account = null)
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

    /**
     * Aggiunge una stampa alla notifica.
     *
     * @param string|int $print
     * @param string     $name
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
        $prints = $template->prints;
        foreach ($prints as $print) {
            $this->addPrint($print['id']);
        }
    }
}
