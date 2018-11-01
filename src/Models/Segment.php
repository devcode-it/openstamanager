<?php

namespace Models;

use App;
use Traits\RecordTrait;
use Traits\UploadTrait;
use Traits\StoreTrait;
use Traits\PermissionTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Segment extends Model
{
    use PermissionTrait;

    protected $table = 'zz_segments';

    protected $appends = [
        'permission',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('permission', function (Builder $builder) {
            $builder->with('groups');
        });
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function groups()
    {
        return $this->morphToMany(Group::class, 'permission', 'zz_permissions', 'external_id', 'group_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }
}
