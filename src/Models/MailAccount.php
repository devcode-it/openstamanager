<?php

namespace Models;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\StoreTrait;

class MailAccount extends Model
{
    use StoreTrait, SoftDeletes;

    protected $table = 'zz_smtps';

    /* Relazioni Eloquent */

    public function account()
    {
        return $this->belongsTo(MailTemplate::class, 'id_smtp');
    }
}
