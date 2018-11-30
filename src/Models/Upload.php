<?php

namespace Models;

use Traits\StoreTrait;
use Common\Model;

class Upload extends Model
{
    protected $table = 'zz_files';

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }
}
