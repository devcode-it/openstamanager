<?php

namespace Modules\Emails;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\StoreTrait;

class Account extends Model
{
    use StoreTrait;
    use SoftDeletes;

    protected $table = 'em_accounts';

    /* Relazioni Eloquent */

    public function templates()
    {
        return $this->hasMany(Template::class, 'id_account');
    }
}
