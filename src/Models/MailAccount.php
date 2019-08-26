<?php

namespace Models;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\StoreTrait;

class MailAccount extends Model
{
    use StoreTrait;
    use SoftDeletes;

    protected $table = 'em_accounts';

    /* Relazioni Eloquent */

    public function templates()
    {
        return $this->hasMany(MailTemplate::class, 'id_smtp');
    }
}
