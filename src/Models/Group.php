<?php

namespace Models;

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
        return $this->morphedByMany(Module::class, 'permission', 'zz_permissions', 'group_id', 'external_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }

    public function plugins()
    {
        return $this->morphedByMany(Plugin::class, 'permission', 'zz_permissions', 'group_id', 'external_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }

    public function widgets()
    {
        return $this->morphedByMany(Widget::class, 'permission', 'zz_permissions', 'group_id', 'external_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }

    public function segments()
    {
        return $this->morphedByMany(Segment::class, 'permission', 'zz_permissions', 'group_id', 'external_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }

    public function prints()
    {
        return $this->morphedByMany(PrintTemplate::class, 'permission', 'zz_permissions', 'group_id', 'external_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }

    public function views()
    {
        return $this->belongsToMany(View::class, 'zz_group_view', 'id_gruppo', 'id_vista');
    }
}
