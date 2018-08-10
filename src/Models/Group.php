<?php

namespace Models;

use Auth;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'zz_groups';

    /* Relazioni Eloquent */

    public function users()
    {
        return $this->hasMany(User::class, 'idgruppo');
    }

    public function modules()
    {
        if ($this->nome == 'Amministratori') {
            return Module::all();
        } else {
            return $this->belongsToMany(Module::class, 'zz_permissions', 'idgruppo', 'idmodule')->withPivot('permessi')->get();
        }
    }

    public function views()
    {
        return $this->belongsToMany(View::class, 'zz_group_view', 'id_gruppo', 'id_vista');
    }
}
