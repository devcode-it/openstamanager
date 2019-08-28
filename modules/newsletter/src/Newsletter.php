<?php

namespace Modules\Newsletter;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Mail;
use Models\MailAccount;
use Models\MailTemplate;
use Models\User;
use Modules\Anagrafiche\Anagrafica;
use Traits\RecordTrait;

class Newsletter extends Model
{
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_campaigns';

    public static function build(User $user, MailTemplate $template, $name)
    {
        $model = parent::build();

        $model->user()->associate($user);
        $model->template()->associate($template);
        $model->name = $name;

        $model->subject = $template->subject;
        $model->content = $template->body;

        $model->state = 'DEV';

        $model->save();

        return $model;
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Newsletter';
    }

    public function fixStato()
    {
        $mails = $this->emails;

        $completed = true;
        foreach ($mails as $mail) {
            if (empty($mail->sent_at)) {
                $completed = false;
                break;
            }
        }

        $this->state = $completed ? 'OK' : $this->state;
        $this->completed_at = $completed ? date('Y-m-d H:i:s') : $this->completed_at;
        $this->save();
    }

    // Relazione Eloquent

    public function anagrafiche()
    {
        return $this->belongsToMany(Anagrafica::class, 'em_campaign_anagrafica', 'id_campaign', 'id_anagrafica')->withPivot('id_email');
    }

    public function emails()
    {
        return $this->hasMany(Mail::class, 'id_campaign');
    }

    public function account()
    {
        return $this->belongsTo(MailAccount::class, 'id_account');
    }

    public function template()
    {
        return $this->belongsTo(MailTemplate::class, 'id_template');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
