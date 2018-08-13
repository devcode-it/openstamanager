<?php

namespace Models;

use Traits\PathTrait;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    use PathTrait;

    protected $table = 'zz_prints';
    protected $main_folder = 'templates';

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module')->first();
    }
}
