<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use App;

class View extends Model
{
    protected $table = 'zz_views';

    public function getQueryAttribute($value)
    {
        return App::replacePlaceholder($value);
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
