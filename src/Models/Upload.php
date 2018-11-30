<?php

namespace Models;

use Common\Model;

class Upload extends Model
{
    protected $table = 'zz_files';

    public function getCategoryAttribute()
    {
        return $this->attributes['category'] ?: 'Generale';
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }
}
