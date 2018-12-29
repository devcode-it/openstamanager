<?php

namespace Models;

use Common\Model;
use Traits\PathTrait;
use Traits\StoreTrait;

class PrintTemplate extends Model
{
    use PathTrait, StoreTrait;

    protected $table = 'zz_prints';
    protected $main_folder = 'templates';

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });
    }
}
