<?php

namespace Models;

use Traits\PathTrait;
use Traits\StoreTrait;
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
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }
}
