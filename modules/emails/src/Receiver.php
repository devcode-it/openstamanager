<?php

namespace Modules\Emails;

use Common\Model;

class Receiver extends Model
{
    protected $table = 'em_email_receiver';

    /* Relazioni Eloquent */

    public static function build(Mail $mail, $address, $type = null)
    {
        $model = parent::build();

        $model->email()->associate($mail);

        $model->address = $address;
        $model->type = $type ?: 'a';

        $model->save();
    }

    public function email()
    {
        return $this->belongsTo(Mail::class, 'id_email');
    }
}
