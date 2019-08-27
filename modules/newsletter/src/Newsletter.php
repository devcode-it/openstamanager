<?php

namespace Modules\Newsletter;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Mail;
use Models\MailAccount;
use Models\MailTemplate;
use Models\User;
use Modules\Anagrafiche\Anagrafica;

class Newsletter extends Model
{
    use SoftDeletes;

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

    // Relazione Eloquent

    public function anagrafiche()
    {
        return $this->belongsToMany(Anagrafica::class, 'em_campaign_anagrafica', 'id_campaign', 'id_anagrafica')->withPivot('id_email');
    }

    public function emails()
    {
        return $this->belongsToMany(Mail::class, 'em_campaign_anagrafica', 'id_campaign', 'id_email')->withPivot('id_anagrafica');
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
