<?php

namespace Models;

use Traits\PathTrait;
use Traits\StoreTrait;
use Illuminate\Database\Eloquent\Builder;
use Common\Model;

class PrintTemplate extends Model
{
    use PathTrait, StoreTrait;

    protected $table = 'zz_prints';
    protected $main_folder = 'templates';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });

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
