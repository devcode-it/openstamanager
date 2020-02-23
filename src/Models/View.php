<?php

namespace Models;

use Common\Model;

class View extends Model
{
    protected $table = 'zz_views';

    public function getQueryAttribute($value)
    {
        return Util\Query::replacePlaceholder($value);
    }

    /* Relazioni Eloquent */

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'zz_group_view', 'id_vista', 'id_gruppo');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }
}
