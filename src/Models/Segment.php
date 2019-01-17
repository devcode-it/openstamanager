<?php

namespace Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Traits\PermissionTrait;

class Segment extends Model
{
    use PermissionTrait;

    protected $table = 'zz_segments';

    protected $appends = [
        'permission',
    ];

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function groups()
    {
        return $this->morphToMany(Group::class, 'permission', 'zz_permissions', 'external_id', 'group_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('permission', function (Builder $builder) {
            $builder->with('groups');
        });
    }
}
