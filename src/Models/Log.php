<?php

namespace Models;

use Common\Model;

class Log extends Model
{
    protected $table = 'zz_logs';

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }
}
