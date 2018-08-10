<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    protected $table = 'zz_prints';

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module')->first();
    }
}
