<?php

namespace Models;

use Traits\StoreTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
